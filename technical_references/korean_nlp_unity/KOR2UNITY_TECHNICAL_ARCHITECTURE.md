# Kor2Unity Technical Architecture & Component Mapping

## 🏗️ SYSTEM ARCHITECTURE OVERVIEW

### Multi-Environment Hybrid Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    KOR2UNITY PLATFORM                      │
│                 Hybrid Architecture                        │
└─────────────────────────────────────────────────────────────┘
                               │
                ┌──────────────┼──────────────┐
                │              │              │
        ┌───────▼───────┐ ┌───▼────┐ ┌───────▼────────┐
        │   WSL ENV     │ │ DOCKER │ │  WINDOWS HOST  │
        │ (AI/TUI/Dev)  │ │(Services)│ │ (Desktop/Tools)│
        └───────────────┘ └────────┘ └────────────────┘
```

---

## 🔄 SERVICE TOPOLOGY

### Current State Analysis (Based on Issue #31)

#### **WSL Environment** (May Still Be Operational)
```
/home/hsyyu/repo/kor2unity/
│
├── 🤖 AI MODELS
│   ├── Llama 2 7B-HF (13.5GB) → localhost:8204
│   ├── MiniGPT-4 integration
│   └── minigpt4 conda environment
│
├── 🖥️ TUI INTERFACE
│   ├── kor2unity_tui.py → /korean command
│   ├── tmux sessions management
│   └── Real-time system monitoring
│
├── 🔧 DEVELOPMENT TOOLS
│   ├── kor2unity_status.py → Health checks
│   ├── test_kor2unity_api.py → API testing
│   └── Debugging environment
│
└── 📊 MONITORING
    ├── System status dashboard
    ├── Service health tracking
    └── Performance metrics
```

#### **Docker Environment** (Partially Restored)
```
D:\Docker\data\
│
├── 🏃 RUNNING (5/18)
│   ├── MongoDB → localhost:8202
│   ├── Redis → localhost:8210
│   └── 3 additional services
│
├── 📋 READY TO DEPLOY (13/18)
│   ├── korean-learning-api
│   ├── audio-api
│   ├── llm-api
│   ├── unity-integration-service
│   ├── voice-processing-service
│   └── 8 additional microservices
│
└── 🌐 NETWORK
    ├── korean-network bridge
    ├── IP Range: 172.20.0.0/16
    └── Port Range: 8200-8212
```

---

## 🔗 SERVICE INTEGRATION MAP

### **Port Allocation & Service Mapping**

| Port | Service | Environment | Status | Purpose |
|------|---------|-------------|--------|---------|
| 8201 | Legacy API | WSL | ❓ Unknown | Original API service |
| 8202 | MongoDB | Docker | ✅ Running | Database service |
| 8203 | - | - | 🔄 Reserved | Future allocation |
| 8204 | Llama 2 7B-HF | WSL | ❓ Unknown | Self-hosted AI model |
| 8205 | Audio API | Docker | 📋 Ready | Voice processing |
| 8206 | Unity Service | Docker | 📋 Ready | Unity integration |
| 8207 | Korean API | Docker | 📋 Ready | Language processing |
| 8208 | Voice TTS | Docker | 📋 Ready | Coqui TTS service |
| 8209 | NLP Service | Docker | 📋 Ready | Natural language |
| 8210 | Redis | Docker | ✅ Running | Cache service |
| 8211 | Web UI | Docker | 📋 Ready | Web interface |
| 8212 | Monitoring | Docker | 📋 Ready | System metrics |

### **Data Flow Architecture**

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   CLIENT    │    │   GATEWAY   │    │  SERVICES   │
│             │    │             │    │             │
│ ┌─────────┐ │    │ ┌─────────┐ │    │ ┌─────────┐ │
│ │Unity App│ │◄──►│ │FastAPI  │ │◄──►│ │Korean   │ │
│ └─────────┘ │    │ │Gateway  │ │    │ │Learning │ │
│             │    │ └─────────┘ │    │ │API      │ │
│ ┌─────────┐ │    │             │    │ └─────────┘ │
│ │Web UI   │ │◄──►│ ┌─────────┐ │    │             │
│ └─────────┘ │    │ │WebSocket│ │◄──►│ ┌─────────┐ │
│             │    │ │Handler  │ │    │ │Voice    │ │
│ ┌─────────┐ │    │ └─────────┘ │    │ │Processing│ │
│ │TUI      │ │◄──►│             │    │ └─────────┘ │
│ │/korean  │ │    │ ┌─────────┐ │    │             │
│ └─────────┘ │    │ │Auth     │ │    │ ┌─────────┐ │
└─────────────┘    │ │Service  │ │    │ │AI Models│ │
                   │ └─────────┘ │    │ │LLM/TTS  │ │
                   └─────────────┘    │ └─────────┘ │
                                      └─────────────┘
```

---

## 🎯 COMPONENT RESPONSIBILITY MATRIX

### **Core Components**

| Component | Environment | Responsibility | Dependencies | Status |
|-----------|-------------|----------------|--------------|--------|
| **Llama 2 7B-HF** | WSL | AI inference, dialogue generation | CUDA, minigpt4 env | ❓ |
| **Korean TUI** | WSL | Terminal interface, /korean command | Python, tmux | ❓ |
| **MongoDB** | Docker | Data persistence, user progress | None | ✅ |
| **Redis** | Docker | Caching, session management | None | ✅ |
| **FastAPI Gateway** | Docker | API routing, authentication | MongoDB, Redis | 📋 |
| **Audio API** | Docker | Voice processing, TTS/STT | Coqui TTS models | 📋 |
| **Unity Service** | Docker | WebGL integration, character AI | Audio API, Korean API | 📋 |
| **Web UI** | Docker | Browser interface, accessibility | FastAPI Gateway | 📋 |

### **Voice Platform Stack**

```
┌─────────────────────────────────────────────────────────────┐
│                 VOICE PLATFORM STACK                       │
├─────────────────────────────────────────────────────────────┤
│ 🎤 AUDIO INPUT                                             │
│ ├─ WebRTC → Browser capture                               │
│ ├─ WebSocket → Real-time streaming                        │
│ └─ Mobile → Device microphone                             │
├─────────────────────────────────────────────────────────────┤
│ 🔊 SPEECH PROCESSING                                       │
│ ├─ STT → Korean speech recognition                        │
│ ├─ Pronunciation → Assessment & scoring                   │
│ └─ Audio Format → Conversion pipeline                     │
├─────────────────────────────────────────────────────────────┤
│ 🎭 TTS GENERATION                                          │
│ ├─ Coqui XTTS → Zero-shot voice cloning                  │
│ ├─ Korean Voices → HuggingFace datasets                  │
│ └─ Character Voices → Unity integration                   │
├─────────────────────────────────────────────────────────────┤
│ 🧠 AI CONVERSATION                                         │
│ ├─ Context Awareness → Learning progress                  │
│ ├─ Dialogue Generation → Natural responses                │
│ └─ Assessment → Real-time feedback                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 DEPLOYMENT & RECOVERY STRATEGY

### **Recovery Priority Matrix**

| Priority | Component | Action | Verification | Risk Level |
|----------|-----------|--------|--------------|------------|
| **P0** | WSL Environment | Audit existing services | `kor2unity_status.py` | 🟢 Low |
| **P1** | TUI Interface | Check tmux sessions | `/korean` command | 🟡 Medium |
| **P2** | AI Models | Verify Llama accessibility | `localhost:8204` | 🟠 High |
| **P3** | Docker Services | Start remaining 13 containers | Health checks | 🟡 Medium |
| **P4** | Service Integration | Bridge WSL ↔ Docker | End-to-end testing | 🔴 Critical |

### **Service Startup Sequence**

```
1. 🔍 AUDIT PHASE
   ├─ Check WSL status: wsl --list --verbose
   ├─ Verify conda env: conda activate minigpt4
   └─ Test TUI access: cd /home/hsyyu/repo/kor2unity

2. 🚀 CORE SERVICES
   ├─ Start MongoDB (✅ Already running)
   ├─ Start Redis (✅ Already running)
   └─ Verify networking: ping localhost:8202

3. 🤖 AI SERVICES
   ├─ Check Llama model: curl localhost:8204/health
   ├─ Start LLM API service
   └─ Test AI inference pipeline

4. 🎵 VOICE SERVICES
   ├─ Start Audio API: docker-compose up audio-api
   ├─ Load Coqui TTS models
   └─ Test voice generation

5. 🎮 FRONTEND SERVICES
   ├─ Start Unity integration service
   ├─ Start Web UI service
   └─ Test end-to-end workflow

6. 📊 MONITORING
   ├─ Start system monitoring
   ├─ Verify all health checks
   └─ Document operational state
```

---

## 🧪 TESTING & VALIDATION FRAMEWORK

### **Service Health Checks**

```python
# Expected in scripts/kor2unity_status.py
HEALTH_ENDPOINTS = {
    'legacy_api': 'http://localhost:8201/health',
    'mongodb': 'http://localhost:8202/status',
    'llama_model': 'http://localhost:8204/models',
    'audio_api': 'http://localhost:8205/health',
    'unity_service': 'http://localhost:8206/health',
    'korean_api': 'http://localhost:8207/health',
    'voice_tts': 'http://localhost:8208/health',
    'redis': 'http://localhost:8210/ping',
    'web_ui': 'http://localhost:8211/health',
    'monitoring': 'http://localhost:8212/metrics'
}
```

### **Integration Test Scenarios**

| Test Scenario | Components | Expected Outcome | Validation |
|---------------|------------|------------------|------------|
| **TUI Startup** | WSL + TUI | `/korean` command active | Interactive prompt |
| **AI Inference** | Llama + API | Korean response generation | Text output |
| **Voice Pipeline** | Audio + TTS | Korean speech synthesis | Audio file |
| **Unity Integration** | Unity + Voice + AI | Character interaction | WebGL render |
| **End-to-End** | All components | Complete learning session | User progress |

---

## 📚 DEVELOPMENT ENVIRONMENT SETUP

### **Conda Environment Structure**

```yaml
# minigpt4 environment (WSL)
name: minigpt4
dependencies:
  - python=3.9
  - pytorch=1.13.1
  - torchvision=0.14.1
  - torchaudio=0.13.1
  - cudatoolkit=11.7
  - transformers=4.21.0
  - pip:
    - accelerate
    - bitsandbytes
    - sentencepiece
    - gradio
    - omegaconf
    - iopath
    - decord
```

### **Docker Compose Services**

```yaml
# Expected docker-compose.yml structure
version: '3.8'
services:
  mongodb:
    image: mongo:latest
    ports: ["8202:27017"]
    networks: [korean-network]
    
  redis:
    image: redis:alpine
    ports: ["8210:6379"]
    networks: [korean-network]
    
  korean-learning-api:
    build: ./api/korean-learning
    ports: ["8207:8000"]
    depends_on: [mongodb, redis]
    networks: [korean-network]
    
  audio-api:
    build: ./api/audio
    ports: ["8205:8000"]
    volumes: ["./voice:/app/voice"]
    networks: [korean-network]
    
  unity-integration:
    build: ./api/unity
    ports: ["8206:8000"]
    depends_on: [korean-learning-api]
    networks: [korean-network]

networks:
  korean-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16
```

---

## 🔄 CONTINUOUS INTEGRATION & MONITORING

### **Monitoring Stack**

| Metric Type | Tool/Service | Port | Purpose |
|-------------|--------------|------|---------|
| **System Metrics** | Prometheus | 8212 | Resource monitoring |
| **Application Logs** | Custom Dashboard | 8212 | Service health |
| **AI Model Performance** | Custom API | 8204 | Inference metrics |
| **Voice Quality** | Audio Analysis | 8208 | TTS/STT quality |
| **User Analytics** | MongoDB Queries | 8202 | Learning progress |

### **Alert Thresholds**

```yaml
alerts:
  high_priority:
    - ai_model_unavailable: localhost:8204 unreachable > 30s
    - database_connection_lost: MongoDB connection failed
    - voice_processing_timeout: TTS generation > 10s
    
  medium_priority:
    - high_memory_usage: >80% for 5 minutes
    - slow_api_response: >2s average response time
    - unity_rendering_issues: WebGL load failures
    
  low_priority:
    - redis_cache_miss_rate: >50%
    - tui_session_timeout: Inactive > 30 minutes
    - log_volume_high: >1000 entries/minute
```

---

**Last Updated**: November 5, 2025  
**Architecture Version**: v2.1 (Post-Recovery Analysis)  
**Next Review**: After successful WSL environment audit

---

*This technical architecture document provides the foundational understanding needed for system recovery and continued development of the Kor2Unity Korean Learning Platform.*