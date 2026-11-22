# Kor2Unity Recovery - Quick Start Guide for New Copilot Agent

## 🚀 **IMMEDIATE ACTION CHECKLIST**

### **First 30 Minutes - Situation Assessment**

#### 1. **Environment Status Check**
```bash
# Check WSL availability
wsl --list --verbose

# If WSL is available, check for existing services
wsl
cd /home/hsyyu/repo/kor2unity 2>/dev/null && echo "✅ Repo exists" || echo "❌ Repo missing"
ls scripts/ 2>/dev/null && echo "✅ Scripts found" || echo "❌ Scripts missing"
```

#### 2. **Service Connectivity Test**
```bash
# Test potentially running services from Issue #31
curl -s localhost:8201/health && echo "✅ Legacy API running" || echo "❌ Legacy API down"
curl -s localhost:8202 && echo "✅ MongoDB responding" || echo "❌ MongoDB down" 
curl -s localhost:8204/models && echo "✅ Llama model accessible" || echo "❌ AI model down"
curl -s localhost:8210/ping && echo "✅ Redis responding" || echo "❌ Redis down"
```

#### 3. **Docker Environment Check**
```bash
# Check Docker Desktop status
docker --version
docker-compose --version
docker ps -a | grep kor2unity

# Check for existing containers
docker network ls | grep korean
```

### **First Hour - Recovery Assessment**

#### 4. **Create Status Report**
Document findings in a new GitHub issue with:
- WSL environment status
- Service availability results
- Docker container status
- Available file systems and data

#### 5. **Identify Recovery Path**
Based on assessment, choose:
- **Path A**: WSL services still operational → Integration focus
- **Path B**: WSL services lost → Full recovery required
- **Path C**: Hybrid state → Selective recovery

---

## 📋 **CRITICAL FILES TO LOCATE**

### **Priority 1: Core Scripts** (WSL Environment)
```
/home/hsyyu/repo/kor2unity/scripts/
├── kor2unity_status.py      # 🎯 PRIMARY - System health checker
├── kor2unity_tui.py         # 🎯 PRIMARY - Main TUI interface
└── test_kor2unity_api.py    # 🔧 SECONDARY - API testing
```

### **Priority 2: Configuration Files**
```
Expected locations:
├── docker-compose.yml       # Container orchestration
├── requirements.txt         # Python dependencies
├── .env files              # Environment configuration
└── conda environment.yml   # AI model environment
```

### **Priority 3: Model Files** (13.5GB)
```
/home/hsyyu/llama2-7b-hf/   # Self-hosted AI model
```

### **Priority 4: Documentation**
```
Existing documentation (if available):
├── KOR2UNITY_DEVELOPMENT_ECOSYSTEM_INSIGHTS.md
├── README.md
└── Architecture documentation
```

---

## 🔍 **INVESTIGATION COMMANDS**

### **WSL Deep Dive**
```bash
# Check if WSL is running and accessible
wsl --status
wsl --list --verbose

# If accessible, investigate the environment
wsl
whoami
pwd
ls -la /home/
ls -la /home/hsyyu/ 2>/dev/null || echo "User directory not found"

# Check for conda environments
conda info --envs 2>/dev/null || echo "Conda not available"
which python
python --version

# Check for tmux sessions (TUI might be running)
tmux list-sessions 2>/dev/null || echo "No tmux sessions"
```

### **Process and Port Investigation**
```bash
# Check what's running on expected ports
netstat -tuln | grep 820   # Check 8201-8212 range
ss -tuln | grep 820        # Alternative port check

# Check for Python processes
ps aux | grep python
ps aux | grep kor2unity
```

### **File System Exploration**
```bash
# Look for kor2unity related files
find /home -name "*kor2unity*" 2>/dev/null
find /home -name "*.py" -path "*/kor2unity/*" 2>/dev/null
find /home -name "llama*" 2>/dev/null

# Check for Docker-related files
find . -name "docker-compose.yml" 2>/dev/null
find . -name "Dockerfile" 2>/dev/null
```

---

## 🎯 **DECISION TREE**

### **Scenario A: WSL + Services Operational**
```
✅ WSL accessible
✅ /home/hsyyu/repo/kor2unity exists
✅ Some services responding on 820x ports

→ ACTION: Integration and documentation focus
→ PRIORITY: Bridge with Docker services (#82)
→ TIMELINE: 1-2 days to full operation
```

### **Scenario B: WSL Lost, Docker Partial**
```
❌ WSL inaccessible or repo missing
✅ Docker containers (MongoDB, Redis) running
❓ AI models and TUI interface lost

→ ACTION: Rebuild WSL environment from Docker
→ PRIORITY: Restore AI models and TUI (#85, #86)
→ TIMELINE: 1-2 weeks for full recovery
```

### **Scenario C: Complete Loss**
```
❌ WSL inaccessible
❌ Docker services not running
❌ No accessible services

→ ACTION: Full platform rebuild using Issue knowledge
→ PRIORITY: Infrastructure restoration (#81, #84)
→ TIMELINE: 3-4 weeks for complete restoration
```

---

## 📊 **SUCCESS METRICS & MILESTONES**

### **Day 1 Goals**
- [ ] Complete environment assessment
- [ ] Document current state in GitHub issue
- [ ] Identify viable recovery path
- [ ] Begin critical component restoration

### **Week 1 Goals**
- [ ] Restore core TUI interface (if lost)
- [ ] Verify AI model accessibility
- [ ] Complete Docker service integration
- [ ] Establish basic Korean learning workflow

### **Week 2 Goals**
- [ ] Full voice platform integration
- [ ] Unity WebGL foundation
- [ ] End-to-end testing complete
- [ ] Documentation updated

---

## 🔗 **CRITICAL ISSUE REFERENCES**

### **Must Read First**
- **Issue #84**: Korean Platform Restoration - Master Roadmap
- **Issue #31**: EPIC: kor2Unity Self-hosted AI Integration Sprint (COMPLETED)
- **Issue #81**: Docker Desktop & WSL Restoration Milestone (COMPLETED)

### **Current Active Work**
- **Issue #85**: Track WSL terminal setup docs and Powerline fixes
- **Issue #82**: Phase 2: Korean API Services Restoration  
- **Issue #83**: Phase 3: Frontend & Unity Integration

### **Reference Architecture**
- **Issue #67**: Enhanced Korean Voice Platform - Integration Complete!
- **Issue #59**: MILESTONE: Korean Live Dialogue System - Complete Implementation

---

## 🛠️ **TOOLS & RESOURCES**

### **Development Tools**
```bash
# Essential tools for recovery
gh --version          # GitHub CLI for issue management
docker --version      # Container management
wsl --version         # WSL management
conda --version       # AI environment management
```

### **Monitoring Commands**
```bash
# Service monitoring during recovery
watch -n 5 'curl -s localhost:8201/health'    # Legacy API
watch -n 5 'docker ps | grep kor2unity'       # Container status
watch -n 10 'wsl -d Ubuntu'                   # WSL connectivity
```

### **Emergency Contacts**
- **GitHub Issues**: Primary communication channel
- **Master Roadmap**: Issue #84 for coordination
- **Knowledge Base**: Created documentation files in /home/aiegoo/repos/

---

## ⚡ **QUICK COMMAND REFERENCE**

### **Essential Recovery Commands**
```bash
# WSL diagnostics
wsl --list --verbose
wsl --status
wsl --shutdown && wsl

# Docker recovery
docker-compose down
docker-compose up -d mongodb redis
docker logs <container_name>

# Service testing
curl localhost:8201/health
curl localhost:8202
curl localhost:8204/models

# Process management
tmux list-sessions
tmux attach-session -t kor2unity-tui
conda activate minigpt4
```

---

## 📞 **IMMEDIATE NEXT STEPS**

1. **Execute Environment Assessment** (30 minutes)
2. **Create GitHub Issue** with findings (15 minutes)
3. **Choose Recovery Path** based on assessment (15 minutes)
4. **Begin Critical Component Recovery** (remainder of session)

---

**Last Updated**: November 5, 2025  
**Target Audience**: New Copilot Agent taking over recovery  
**Context**: WSL environment lost, Docker partially restored, 78+ issues of project history available

---

*This quick start guide provides the essential framework for immediate recovery action while preserving the sophisticated Korean learning platform that was previously operational.*