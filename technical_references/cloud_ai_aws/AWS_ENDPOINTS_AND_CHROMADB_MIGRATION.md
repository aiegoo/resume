# 🌐 AWS Endpoints Overview & ChromaDB Migration Guide

**Created:** December 26, 2025  
**Purpose:** Complete mapping of local → AWS infrastructure with ChromaDB migration details

---

## 📊 Executive Summary

### Architecture Transformation

| Component | Local Setup | AWS Setup | Access Method |
|-----------|-------------|-----------|---------------|
| **Backend API** | localhost:8000 | ALB DNS:80 → /api/* | Application Load Balancer |
| **ChromaDB** | localhost:8001/8002 | EFS mount | Internal ECS mount at /chromadb |
| **PostgreSQL** | localhost:5432 | RDS DNS:5432 | Private subnet, SG restricted |
| **Redis** | localhost:6379 | ElastiCache DNS:6379 | Private subnet, SG restricted |
| **Grafana** | localhost:3001 | ALB DNS:80 → /grafana/* | ALB path-based routing |
| **Langfuse** | localhost:3000 | ALB DNS:80 → /langfuse/* | ALB path-based routing |

---

## 🔌 AWS Service Endpoints

### 1. Application Load Balancer (Public Entry Point)

**Resource:** `aws_lb.main` in `terraform/load-balancer.tf`

```bash
# Primary Access URL (after deployment)
http://jeju-stories-prod-alb-XXXXXXXXX.ap-northeast-2.elb.amazonaws.com

# Get actual URL with:
terraform output alb_dns_name
```

#### Path-Based Routing

| Path | Target Service | Port | Health Check |
|------|---------------|------|--------------|
| `/api/*` | Backend API | 8000 | `GET /health` |
| `/docs` | Backend API | 8000 | FastAPI Swagger |
| `/redoc` | Backend API | 8000 | ReDoc |
| `/grafana/*` | Grafana | 3000 | `GET /api/health` |
| `/langfuse/*` | Langfuse | 3000 | `GET /api/public/health` |

#### Security Configuration

```terraform
# Security Group: terraform/networking.tf
resource "aws_security_group" "alb" {
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]  # Public HTTP access
  }
  
  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]  # Public HTTPS access (if SSL enabled)
  }
}
```

---

### 2. Backend API Service

**ECS Service:** `aws_ecs_service.backend` in `terraform/ecs.tf`

#### Container Configuration

```terraform
# Task Definition
resource "aws_ecs_task_definition" "backend" {
  family                   = "jeju-backend"
  requires_compatibilities = ["FARGATE"]
  cpu                      = "1024"   # 1 vCPU
  memory                   = "2048"   # 2 GB
  
  container_definitions = jsonencode([{
    name  = "backend"
    image = "${var.backend_image}:latest"
    
    # Port mapping
    portMappings = [{
      containerPort = 8000
      protocol      = "tcp"
    }]
    
    # EFS mount for ChromaDB
    mountPoints = [{
      sourceVolume  = "chromadb"
      containerPath = "/chromadb"
      readOnly      = false
    }]
    
    # Environment variables
    environment = [
      # AWS Bedrock (AI)
      { name = "AWS_REGION", value = "ap-northeast-2" },
      { name = "AWS_BEDROCK_MODEL", value = "anthropic.claude-3-5-sonnet-20240620-v1:0" },
      
      # Database (RDS)
      { name = "POSTGRES_HOST", value = aws_db_instance.main.address },
      { name = "POSTGRES_PORT", value = "5432" },
      { name = "POSTGRES_DB", value = "jeju_stories" },
      
      # Redis (ElastiCache)
      { name = "REDIS_HOST", value = aws_elasticache_cluster.redis.cache_nodes[0].address },
      { name = "REDIS_PORT", value = "6379" },
      
      # ChromaDB (EFS-backed)
      { name = "CHROMADB_MODE", value = "persistent" },
      { name = "CHROMADB_PATH", value = "/chromadb" },
      
      # Monitoring
      { name = "LANGFUSE_HOST", value = "http://langfuse.${var.ecs_private_dns_namespace}" },
      { name = "GRAFANA_URL", value = "http://grafana.${var.ecs_private_dns_namespace}:3000" }
    ]
    
    # Secrets from Secrets Manager
    secrets = [
      {
        name      = "POSTGRES_USER"
        valueFrom = "${aws_secretsmanager_secret.db_credentials.arn}:username::"
      },
      {
        name      = "POSTGRES_PASSWORD"
        valueFrom = "${aws_secretsmanager_secret.db_credentials.arn}:password::"
      }
    ]
  }])
}
```

#### Local vs AWS Configuration

| Setting | Local Docker | AWS ECS |
|---------|-------------|---------|
| **ChromaDB Mode** | `CHROMADB_HOST=chromadb`<br>`CHROMADB_PORT=8001` | `CHROMADB_MODE=persistent`<br>`CHROMADB_PATH=/chromadb` |
| **Database Host** | `POSTGRES_HOST=postgres` (Docker DNS) | `POSTGRES_HOST=jeju-stories-prod-rds.xxxx.ap-northeast-2.rds.amazonaws.com` |
| **Redis Host** | `REDIS_HOST=redis` (Docker DNS) | `REDIS_HOST=jeju-redis-prod.xxxx.cache.amazonaws.com` |
| **Bedrock Access** | Local AWS credentials | ECS Task Role with Bedrock policy |

---

### 3. ChromaDB Vector Database

**Critical Architecture Change:** HTTP service → File-based persistence on EFS

#### Local Docker Setup

```yaml
# docker-compose.all-services.yml (lines 140-155)
services:
  chromadb:
    image: chromadb/chroma:latest
    container_name: jeju-chromadb
    ports:
      - "8001:8000"  # External access
    volumes:
      - ./team-data/vector_db/chroma:/chroma/chroma
    environment:
      - IS_PERSISTENT=TRUE
      - PERSIST_DIRECTORY=/chroma/chroma
```

**Local Access:**
- HTTP API: `http://localhost:8001`
- Python Client: `chromadb.HttpClient(host='localhost', port=8001)`
- Data Path: `./team-data/vector_db/chroma/` (40.95 MB, 1,305 stories)

#### AWS EFS Setup

```terraform
# terraform/storage.tf
resource "aws_efs_file_system" "main" {
  creation_token = "${var.project_name}-${var.environment}-efs"
  encrypted      = true
  
  lifecycle_policy {
    transition_to_ia = "AFTER_30_DAYS"  # Transition to cheaper tier
  }
  
  tags = {
    Name = "${var.project_name}-${var.environment}-chromadb"
  }
}

# EFS Access Point for ChromaDB
resource "aws_efs_access_point" "chromadb" {
  file_system_id = aws_efs_file_system.main.id
  
  root_directory {
    path = "/chromadb"
    creation_info {
      owner_gid   = 1000  # ChromaDB container UID
      owner_uid   = 1000  # ChromaDB container GID
      permissions = "755"
    }
  }
  
  posix_user {
    gid = 1000
    uid = 1000
  }
}
```

**AWS Access:**
- Mount Point: `/chromadb` (inside ECS container)
- Python Client: `chromadb.PersistentClient(path='/chromadb')`
- Storage: Elastic File System (grows with data, no size limits)
- Networking: Private mount targets in each AZ

#### Migration Process

The `scripts/upload-chromadb-to-efs.sh` script automates this:

```bash
#!/bin/bash
# Upload ChromaDB data from local to AWS EFS

EFS_ID=$1  # Get from: terraform output efs_id
LOCAL_CHROMA_PATH="team-data/vector_db/chroma"

# Step 1: Create temporary EC2 instance with EFS mount
aws ec2 run-instances \
  --image-id ami-0c9c942bd7bf113a2 \
  --instance-type t3.micro \
  --key-name jeju-stories-key \
  --security-group-ids sg-xxxx \
  --subnet-id subnet-xxxx

# Step 2: Mount EFS to EC2
sudo mount -t efs -o tls ${EFS_ID}:/ /mnt/efs

# Step 3: Rsync local data to EFS
rsync -avz --progress \
  ${LOCAL_CHROMA_PATH}/* \
  ec2-user@EC2_IP:/mnt/efs/chromadb/

# Step 4: Set correct permissions
sudo chown -R 1000:1000 /mnt/efs/chromadb
sudo chmod -R 755 /mnt/efs/chromadb

# Step 5: Terminate EC2 instance
aws ec2 terminate-instances --instance-ids i-xxxx
```

**What Gets Migrated:**

```bash
team-data/vector_db/chroma/
├── chroma.sqlite3          # Collection metadata (1.3 MB)
├── ef80f48b-xxx/           # Collection UUID directory
│   ├── data_level0.bin     # Vector data (38 MB)
│   ├── header.bin          # Index header
│   ├── index_metadata.bin  # Index config
│   └── length.bin          # Document lengths
```

---

### 4. RDS PostgreSQL Database

**Resource:** `aws_db_instance.main` in `terraform/database.tf`

#### Configuration

```terraform
resource "aws_db_instance" "main" {
  identifier     = "${var.project_name}-${var.environment}-rds"
  engine         = "postgres"
  engine_version = "15.4"
  
  # Compute
  instance_class = "db.t3.small"  # 2 vCPU, 2 GB RAM
  
  # Storage
  allocated_storage     = 20
  max_allocated_storage = 100  # Auto-scaling enabled
  storage_type          = "gp3"
  storage_encrypted     = true
  
  # Database
  db_name  = "jeju_stories"
  username = "postgres"
  password = random_password.db_password.result  # 32-char random
  
  # Networking
  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.rds.id]
  publicly_accessible    = false  # Private only
  
  # Backup
  backup_retention_period = 7
  backup_window          = "03:00-04:00"  # UTC
  maintenance_window     = "Mon:04:00-Mon:05:00"
}
```

#### Endpoint Access

```bash
# DNS Endpoint (from terraform output)
jeju-stories-prod-rds.abcdefg.ap-northeast-2.rds.amazonaws.com:5432

# Connection from ECS Task
psql -h ${POSTGRES_HOST} -U postgres -d jeju_stories

# Security: Only accessible from ECS security group
aws_security_group_rule.rds_from_ecs {
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.ecs.id
  security_group_id        = aws_security_group.rds.id
}
```

#### Credentials Management

```terraform
# Secrets Manager stores DB credentials
resource "aws_secretsmanager_secret" "db_credentials" {
  name = "${var.project_name}-${var.environment}-db-credentials"
}

resource "aws_secretsmanager_secret_version" "db_credentials" {
  secret_id = aws_secretsmanager_secret.db_credentials.id
  secret_string = jsonencode({
    username = "postgres"
    password = random_password.db_password.result
    host     = aws_db_instance.main.address
    port     = 5432
    dbname   = "jeju_stories"
  })
}

# ECS tasks retrieve credentials at runtime
secrets = [
  {
    name      = "POSTGRES_PASSWORD"
    valueFrom = "${aws_secretsmanager_secret.db_credentials.arn}:password::"
  }
]
```

---

### 5. ElastiCache Redis

**Resource:** `aws_elasticache_cluster.redis` in `terraform/database.tf`

#### Configuration

```terraform
resource "aws_elasticache_cluster" "redis" {
  cluster_id           = "${var.project_name}-${var.environment}-redis"
  engine               = "redis"
  engine_version       = "7.0"
  node_type            = "cache.t3.micro"  # 0.5 GB RAM
  num_cache_nodes      = 1
  parameter_group_name = "default.redis7"
  
  # Networking
  subnet_group_name    = aws_elasticache_subnet_group.redis.name
  security_group_ids   = [aws_security_group.redis.id]
  
  # Persistence
  snapshot_retention_limit = 5
  snapshot_window          = "03:00-05:00"
}
```

#### Endpoint Access

```bash
# DNS Endpoint
jeju-redis-prod.abcdef.0001.apne2.cache.amazonaws.com:6379

# Connection from ECS Task
redis-cli -h ${REDIS_HOST} -p 6379

# Security: Only accessible from ECS security group
aws_security_group_rule.redis_from_ecs {
  type                     = "ingress"
  from_port                = 6379
  to_port                  = 6379
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.ecs.id
  security_group_id        = aws_security_group.redis.id
}
```

---

### 6. AWS Bedrock (AI Service)

**No endpoint configuration needed** - Accessed via AWS SDK

#### IAM Role Configuration

```terraform
# terraform/ecs.tf
resource "aws_iam_role_policy" "ecs_task_bedrock" {
  name = "${var.project_name}-${var.environment}-bedrock-access"
  role = aws_iam_role.ecs_task.id
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "bedrock:InvokeModel",
          "bedrock:InvokeModelWithResponseStream"
        ]
        Resource = [
          "arn:aws:bedrock:ap-northeast-2::foundation-model/anthropic.claude-3-5-sonnet-20240620-v1:0",
          "arn:aws:bedrock:ap-northeast-2::foundation-model/anthropic.claude-3-haiku-20240307-v1:0"
        ]
      }
    ]
  })
}
```

#### Backend Code

```python
# backend/main.py (AWS SDK automatically uses ECS Task Role)
import boto3

bedrock_runtime = boto3.client(
    service_name='bedrock-runtime',
    region_name='ap-northeast-2'
)

response = bedrock_runtime.invoke_model(
    modelId='anthropic.claude-3-5-sonnet-20240620-v1:0',
    body=json.dumps({
        "anthropic_version": "bedrock-2023-05-31",
        "messages": messages,
        "max_tokens": 4096
    })
)
```

---

## 🔄 Environment Variable Mapping

### Local Docker (.env)

```bash
# backend/.env (local)
AWS_REGION=ap-northeast-2
AWS_ACCESS_KEY_ID=AKIAXXXXXXXXXX
AWS_SECRET_ACCESS_KEY=xxxxxxxxxxxx

POSTGRES_HOST=postgres
POSTGRES_PORT=5432
POSTGRES_DB=jeju_stories
POSTGRES_USER=postgres
POSTGRES_PASSWORD=localpassword123

REDIS_HOST=redis
REDIS_PORT=6379

CHROMADB_HOST=chromadb
CHROMADB_PORT=8001
```

### AWS ECS (Environment Variables)

```terraform
# terraform/ecs.tf - Backend task definition
environment = [
  # AWS services (automatic via IAM role)
  { name = "AWS_REGION", value = "ap-northeast-2" },
  
  # RDS (managed endpoint)
  { name = "POSTGRES_HOST", value = aws_db_instance.main.address },
  { name = "POSTGRES_PORT", value = "5432" },
  { name = "POSTGRES_DB", value = "jeju_stories" },
  
  # ElastiCache (managed endpoint)
  { name = "REDIS_HOST", value = aws_elasticache_cluster.redis.cache_nodes[0].address },
  { name = "REDIS_PORT", value = "6379" },
  
  # ChromaDB (EFS mount)
  { name = "CHROMADB_MODE", value = "persistent" },
  { name = "CHROMADB_PATH", value = "/chromadb" }
]

secrets = [
  # Secrets Manager (automatic retrieval)
  {
    name      = "POSTGRES_USER"
    valueFrom = "${aws_secretsmanager_secret.db_credentials.arn}:username::"
  },
  {
    name      = "POSTGRES_PASSWORD"
    valueFrom = "${aws_secretsmanager_secret.db_credentials.arn}:password::"
  }
]
```

---

## 📈 Cost Breakdown

### Monthly AWS Costs

| Service | Instance Type | Usage | Monthly Cost |
|---------|--------------|-------|--------------|
| **ECS Fargate** | Backend (1 vCPU, 2 GB) × 2 | 730 hrs | $71.54 |
| | Grafana (0.5 vCPU, 1 GB) × 1 | 730 hrs | $17.88 |
| | Langfuse (0.5 vCPU, 1 GB) × 1 | 730 hrs | $17.88 |
| **RDS PostgreSQL** | db.t3.small (2 vCPU, 2 GB) | 730 hrs | $24.82 |
| **ElastiCache Redis** | cache.t3.micro (0.5 GB) | 730 hrs | $12.41 |
| **EFS** | 50 GB storage | Standard class | $15.00 |
| **ALB** | 1 load balancer | 730 hrs + LCUs | $16.20 |
| **NAT Gateway** (optional) | 1 gateway | 730 hrs + data | $32.85 |
| **S3** | 100 GB assets | Standard tier | $2.30 |
| **Secrets Manager** | 2 secrets | Stored secrets | $0.80 |
| **CloudWatch Logs** | 10 GB logs | Standard retention | $5.00 |
| **AWS Bedrock** | Claude 3.5 Sonnet | Pay per token | Variable |
| | | **Total:** | **$161-$206/mo** |

**Cost Optimization:**
- NAT Gateway can be removed (use VPC endpoints) → Save $33/mo
- EFS transitions to IA after 30 days → Save ~$7/mo
- RDS can scale down to db.t3.micro → Save $12/mo

---

## 🚀 Deployment Steps

### Step 1: Configure AWS Credentials

```bash
aws configure
# AWS Access Key ID: YOUR_KEY
# AWS Secret Access Key: YOUR_SECRET
# Default region: ap-northeast-2
# Output format: json
```

### Step 2: Deploy Infrastructure

```bash
# Automated deployment
./deploy-aws-complete.sh

# OR Manual Terraform
cd terraform
terraform init
terraform plan
terraform apply
```

### Step 3: Upload ChromaDB Data

```bash
# Get EFS ID from Terraform output
EFS_ID=$(terraform output -raw efs_id)

# Upload ChromaDB vectors
./scripts/upload-chromadb-to-efs.sh ${EFS_ID}
```

### Step 4: Build and Push Docker Images

```bash
# Get AWS account ID
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)

# Build and push to ECR
./scripts/build-and-push-images.sh
```

### Step 5: Verify Deployment

```bash
# Get ALB DNS
ALB_DNS=$(terraform output -raw alb_dns_name)

# Test endpoints
curl http://${ALB_DNS}/health
curl http://${ALB_DNS}/api/
curl http://${ALB_DNS}/docs

# Check ChromaDB stories
curl http://${ALB_DNS}/api/ | jq '.total_stories'
# Expected: 1305
```

---

## 🔍 Troubleshooting

### ChromaDB Issues

**Problem:** Backend can't find ChromaDB data

```bash
# Check EFS mount
aws ecs execute-command \
  --cluster jeju-stories-prod \
  --task TASK_ID \
  --container backend \
  --interactive \
  --command "/bin/sh"

# Inside container
ls -la /chromadb
# Should see: chroma.sqlite3, ef80f48b-xxx/

# Check file permissions
ls -la /chromadb/
# Should be: drwxr-xr-x 1000 1000
```

**Solution:** Re-upload with correct permissions

```bash
./scripts/upload-chromadb-to-efs.sh $(terraform output -raw efs_id)
```

### Database Connection Issues

**Problem:** Backend can't connect to RDS

```bash
# Check security groups
aws ec2 describe-security-groups \
  --group-ids sg-xxxx \
  --query 'SecurityGroups[0].IpPermissions'

# Verify RDS endpoint
aws rds describe-db-instances \
  --db-instance-identifier jeju-stories-prod-rds \
  --query 'DBInstances[0].Endpoint'
```

**Solution:** Ensure ECS security group is allowed in RDS security group

### Load Balancer 502 Errors

**Problem:** ALB returns 502 Bad Gateway

```bash
# Check target health
aws elbv2 describe-target-health \
  --target-group-arn $(terraform output -raw backend_target_group_arn)

# Check ECS task logs
aws logs tail /ecs/jeju-backend --follow
```

---

## 📚 Quick Reference

### Getting Endpoint URLs

```bash
# All endpoints at once
terraform output

# Specific endpoints
terraform output alb_dns_name
terraform output rds_endpoint
terraform output redis_endpoint
terraform output efs_id
```

### Updating Backend Code

```bash
# 1. Build new image
cd backend
docker build -t ${AWS_ACCOUNT_ID}.dkr.ecr.ap-northeast-2.amazonaws.com/jeju-backend:latest .

# 2. Push to ECR
aws ecr get-login-password --region ap-northeast-2 | \
  docker login --username AWS --password-stdin ${AWS_ACCOUNT_ID}.dkr.ecr.ap-northeast-2.amazonaws.com
docker push ${AWS_ACCOUNT_ID}.dkr.ecr.ap-northeast-2.amazonaws.com/jeju-backend:latest

# 3. Force ECS to pull new image
aws ecs update-service \
  --cluster jeju-stories-prod \
  --service jeju-backend \
  --force-new-deployment
```

### Monitoring

```bash
# View logs
aws logs tail /ecs/jeju-backend --follow

# Check ECS task status
aws ecs describe-services \
  --cluster jeju-stories-prod \
  --services jeju-backend

# View Grafana dashboard
echo "http://$(terraform output -raw alb_dns_name)/grafana"

# View Langfuse traces
echo "http://$(terraform output -raw alb_dns_name)/langfuse"
```

---

## 🎯 Key Differences: Local vs AWS

| Aspect | Local Docker | AWS ECS |
|--------|-------------|---------|
| **Service Discovery** | Docker DNS (`chromadb`, `postgres`, `redis`) | AWS managed DNS + Security Groups |
| **ChromaDB** | HTTP service on port 8001 | File-based on EFS mount |
| **Credentials** | Plain text in `.env` | Secrets Manager + IAM roles |
| **Networking** | Bridge network | VPC with public/private subnets |
| **SSL/TLS** | None | ALB with ACM certificate (optional) |
| **Scaling** | Manual `docker-compose scale` | ECS auto-scaling based on CPU/memory |
| **Monitoring** | Local Grafana on :3001 | CloudWatch + Grafana behind ALB |
| **Cost** | $0 (hardware only) | ~$161-206/month |
| **Availability** | Single machine | Multi-AZ (99.99% SLA) |

---

**Created by:** GitHub Copilot  
**Terraform Files:** `terraform/*.tf`  
**Deployment Guide:** [AWS_DEPLOYMENT_COMPLETE.md](AWS_DEPLOYMENT_COMPLETE.md)  
**Quick Reference:** [AWS_QUICK_REFERENCE.md](AWS_QUICK_REFERENCE.md)
