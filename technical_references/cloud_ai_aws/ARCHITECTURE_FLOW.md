# 🏗️ Jeju Stories - System Architecture & Data Flow

## Overview

This document explains the complete system architecture, data flow, and how all components work together using your API assets.

---

## 🎯 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        USER INTERACTIONS                            │
├─────────────────┬───────────────────────────┬──────────────────────┤
│                 │                           │                       │
│  📱 Android App │   🌐 Web Browser          │   📊 Monitoring      │
│  (GPS Tracker)  │   (Presentation/UI)       │   (Dashboards)       │
│                 │                           │                       │
│  - GPS Tracking │   - Test UI               │   - Grafana :3000    │
│  - Geofencing   │   - Branching UI          │   - Langfuse :3001   │
│  - AI Chat      │   - API Docs              │   - Prometheus :9090 │
│  - Story Alerts │   - Trail Maps            │                      │
│                 │                           │                       │
└────────┬────────┴─────────────┬─────────────┴───────────┬──────────┘
         │                      │                         │
         ▼                      ▼                         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    BACKEND API LAYER (FastAPI :8000)                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  🔌 API Endpoints:                                                  │
│  ├─ /health                   - Service health check               │
│  ├─ /metrics                  - Prometheus metrics                 │
│  ├─ /generate-story           - RAG-based story generation         │
│  ├─ /api/story/start          - Branching story initiation         │
│  ├─ /api/story/choose         - User choice handling               │
│  ├─ /test-ui                  - Quick testing interface            │
│  ├─ /branching-ui             - Interactive story UI               │
│  └─ /chat-ui                  - Chatbot interface                  │
│                                                                     │
│  🧠 Core Logic:                                                     │
│  ├─ RAG Pipeline              - Query → Retrieve → Generate        │
│  ├─ Many-shot Training        - Few-shot examples (7 scenarios)    │
│  ├─ Branching Story Manager   - Session-based state management     │
│  └─ Semantic DNA Extraction   - Emotion, conflict, themes          │
│                                                                     │
└────────┬──────────┬─────────────┬──────────────┬────────────┬──────┘
         │          │             │              │            │
         ▼          ▼             ▼              ▼            ▼
┌────────────┐ ┌──────────┐ ┌──────────┐ ┌────────────┐ ┌──────────┐
│ ChromaDB   │ │AWS Bedrock│ │PostgreSQL│ │   Redis    │ │Langfuse  │
│ :chroma    │ │ Claude 3.5│ │  :5432   │ │   :6379    │ │  :3001   │
├────────────┤ ├──────────┤ ├──────────┤ ├────────────┤ ├──────────┤
│            │ │          │ │          │ │            │ │          │
│ 1305       │ │ Story    │ │ Agent    │ │ Session    │ │ LLM      │
│ Embeddings │ │Generation│ │ Traces   │ │ Cache      │ │ Tracing  │
│            │ │          │ │          │ │            │ │          │
│ Jeju       │ │ Your     │ │ Monitor  │ │ Fast       │ │ Cost     │
│ Stories    │ │ AWS Key  │ │ Data     │ │ Access     │ │ Tracking │
│            │ │          │ │          │ │            │ │          │
└────────────┘ └──────────┘ └──────────┘ └────────────┘ └──────────┘
```

---

## 🔄 Data Flow Diagrams

### 1️⃣ Story Generation Flow (RAG Pipeline)

```
User Request
    │
    ├─ "Generate a healing story about a detective"
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: SEMANTIC SEARCH (ChromaDB)                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Query: "detective healing truth"                          │
│     │                                                       │
│     ├─→ Convert to embedding (384 dimensions)              │
│     │                                                       │
│     ├─→ ChromaDB similarity search                         │
│     │   └─ Search 1305 Jeju folklore stories               │
│     │                                                       │
│     └─→ Retrieve top 5 most relevant stories:              │
│         1. 천지연 폭포 전설 (0.82 similarity)                  │
│         2. 사려니숲 도깨비 이야기 (0.78)                        │
│         3. 설문대할망 창조신화 (0.75)                          │
│         4. ...                                              │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: PROMPT CONSTRUCTION                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  System Prompt:                                             │
│  ├─ Role: "Jeju folklore storyteller..."                   │
│  ├─ Constraints: 200-300 words, Korean, first-person       │
│  └─ Few-shot examples (7 training scenarios)               │
│                                                             │
│  Context Injection:                                         │
│  ├─ Retrieved stories (full text)                          │
│  ├─ Semantic DNA (emotion, conflict, themes)               │
│  └─ User parameters (archetype, query, hook)               │
│                                                             │
│  User Prompt:                                               │
│  "Create a healing story where a detective searches for     │
│   truth, using sensory hook strategy..."                   │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 3: LLM GENERATION (AWS Bedrock)                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Model: anthropic.claude-3-5-sonnet-20241022-v2:0          │
│  Region: ap-northeast-2 (Seoul)                             │
│                                                             │
│  Your AWS Credentials:                                      │
│  ├─ AWS_ACCESS_KEY_ID     ─→  IAM User/Role                │
│  ├─ AWS_SECRET_ACCESS_KEY ─→  Secure credentials           │
│  └─ AWS_REGION            ─→  ap-northeast-2               │
│                                                             │
│  API Call:                                                  │
│  boto3.client('bedrock-runtime')                            │
│    .invoke_model(                                           │
│       modelId="anthropic.claude-3-5-sonnet...",             │
│       body={                                                │
│         "messages": [...],                                  │
│         "max_tokens": 2000,                                 │
│         "temperature": 0.7                                  │
│       }                                                     │
│    )                                                        │
│                                                             │
│  Response:                                                  │
│  ├─ Generated story (Korean, 200-300 words)                │
│  ├─ Prompt tokens: ~1500                                   │
│  ├─ Completion tokens: ~400                                │
│  └─ Cost: ~$0.03                                            │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: POST-PROCESSING                                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Extract Semantic DNA:                                      │
│  ├─ Emotion: "Peaceful"                                     │
│  ├─ Conflict: "Internal truth vs. perception"              │
│  ├─ Themes: ["self-discovery", "nature", "wisdom"]         │
│  ├─ Symbols: ["forest", "mist", "ancient tree"]            │
│  └─ Category: "Legend"                                      │
│                                                             │
│  Format Response:                                           │
│  {                                                          │
│    "story": "제주의 깊은 숲속에서...",                        │
│    "semantic_dna": {...},                                   │
│    "retrieved_stories": 5,                                  │
│    "model": "claude-3.5-sonnet",                            │
│    "prompt_tokens": 1523,                                   │
│    "completion_tokens": 387                                 │
│  }                                                          │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
                User receives generated story
```

### 2️⃣ Android GPS → Geofence → Story Trigger Flow

```
User walking in Jeju
    │
    ├─ GPS: 33.4489°N, 126.9389°E
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ ANDROID APP: LocationTrackingService                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FusedLocationProviderClient                                │
│  ├─ Update interval: 5 seconds                             │
│  ├─ Priority: HIGH_ACCURACY                                │
│  └─ Runs in foreground service (notification)              │
│                                                             │
│  Save to JSON:                                              │
│  {                                                          │
│    "latitude": 33.4489,                                     │
│    "longitude": 126.9389,                                   │
│    "altitude": 522.0,                                       │
│    "speed": 1.2,                                            │
│    "accuracy": 8.5,                                         │
│    "timestamp": "2025-12-17T10:30:00"                       │
│  }                                                          │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ GEOFENCE MANAGER: Check proximity                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Registered Geofences (8 hotspots):                         │
│  ├─ 사려니숲길 입구 (33.4489, 126.9389) - radius 50m         │
│  ├─ 천년 삼나무 (33.4501, 126.9402) - radius 50m             │
│  └─ ...                                                     │
│                                                             │
│  Trigger Type:                                              │
│  ├─ ENTER: User entered geofence                           │
│  ├─ DWELL: User stayed 2+ minutes                          │
│  └─ EXIT: User left geofence                               │
│                                                             │
│  Match: User ENTERED "사려니숲길 입구"                        │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ BROADCAST RECEIVER: GeofenceBroadcastReceiver              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Intent received:                                           │
│  ├─ Geofence ID: "saryeoni_entrance"                       │
│  ├─ Transition: GEOFENCE_TRANSITION_ENTER                  │
│  └─ Location: 33.4489, 126.9389                            │
│                                                             │
│  Lookup story in local database:                           │
│  {                                                          │
│    "title": "사려니숲 도깨비 전설",                           │
│    "category": "folktale",                                  │
│    "preview": "옛날 이 숲에는 도깨비가 살았다는데..."          │
│  }                                                          │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ NOTIFICATION: Story alert                                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌────────────────────────────────────────┐                │
│  │  📖 새로운 이야기를 발견했어요!          │                │
│  │  ────────────────────────────────      │                │
│  │  사려니숲 도깨비 전설                    │                │
│  │                                        │                │
│  │  옛날 이 숲에는 도깨비가 살았다는데...   │                │
│  │                                        │                │
│  │  [읽기]  [나중에]                       │                │
│  └────────────────────────────────────────┘                │
│                                                             │
│  User taps [읽기]                                           │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ STORY DETAIL ACTIVITY: Full story display                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Display:                                                   │
│  ├─ Story title                                             │
│  ├─ Category badge (민담/전설/신화)                          │
│  ├─ Full story text                                        │
│  ├─ Map with location marker                               │
│  └─ Related stories (from same area)                       │
│                                                             │
│  Optional: Voice narration (TTS)                            │
│  Optional: Share with friends                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 3️⃣ AI Chat Flow (Android App)

```
User opens chat in app
    │
    ├─ Tap microphone or type message
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ CHAT ACTIVITY: User input                                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Input Methods:                                             │
│  ├─ Text: "설문대할망에 대해 알려줘"                         │
│  └─ Voice: Speech-to-Text → Korean transcription           │
│                                                             │
│  Context Collection:                                        │
│  ├─ Current GPS: 33.4489, 126.9389                         │
│  ├─ Nearby stories: ["사려니숲 도깨비", "천년 삼나무"]       │
│  └─ User profile: interests, age, persona                  │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ LLM SERVICE: Choose provider                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Option 1: OpenAI (Your Key)                               │
│  ├─ Model: GPT-4o-mini                                     │
│  ├─ API: https://api.openai.com/v1/chat/completions        │
│  └─ Key: OPENAI_API_KEY from local.properties              │
│                                                             │
│  Option 2: Allen API (Team Credentials)                    │
│  ├─ Client ID: 9e7b6520-5d48-4702-9bc9-4f1df8f92f92        │
│  ├─ API: https://kdt-api-function.azurewebsites.net        │
│  └─ Free 100 requests/day                                  │
│                                                             │
│  Option 3: Anthropic Claude (Your Key)                     │
│  ├─ Model: Claude 3.5 Sonnet                               │
│  ├─ API: https://api.anthropic.com/v1/messages             │
│  └─ Key: ANTHROPIC_API_KEY                                 │
│                                                             │
│  Option 4: Google Gemini (Your Key)                        │
│  ├─ Model: Gemini Pro                                      │
│  └─ Key: GEMINI_API_KEY                                    │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ API CALL: Send request                                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  POST to selected LLM provider                              │
│  {                                                          │
│    "messages": [                                            │
│      {                                                      │
│        "role": "system",                                    │
│        "content": "You are a Jeju folklore expert..."      │
│      },                                                     │
│      {                                                      │
│        "role": "user",                                      │
│        "content": "설문대할망에 대해 알려줘"                 │
│      }                                                      │
│    ],                                                       │
│    "temperature": 0.7,                                      │
│    "max_tokens": 500                                        │
│  }                                                          │
│                                                             │
│  Response:                                                  │
│  "설문대할망은 제주도를 창조한 여신으로..."                   │
│                                                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ DISPLAY: Show response                                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  RecyclerView with chat bubbles:                            │
│                                                             │
│  ┌────────────────────────────────┐                        │
│  │ 👤 You (10:30 AM)              │                        │
│  │ 설문대할망에 대해 알려줘         │                        │
│  └────────────────────────────────┘                        │
│                                                             │
│  ┌────────────────────────────────┐                        │
│  │ 🤖 AI Assistant (10:30 AM)     │                        │
│  │ 설문대할망은 제주도를 창조한...  │                        │
│  └────────────────────────────────┘                        │
│                                                             │
│  [🎤 Voice] [Type message...] [Send]                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔑 API Asset Integration Points

### Where Your API Keys Are Used

```
┌─────────────────────────────────────────────────────────────┐
│ AWS BEDROCK (REQUIRED)                                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Used by: Backend API (main.py)                             │
│  When: Every /generate-story call                           │
│  Purpose: Generate AI stories with Claude 3.5 Sonnet        │
│                                                             │
│  Configuration:                                             │
│  ├─ File: backend/.env                                     │
│  ├─ Variables:                                              │
│  │   AWS_REGION=ap-northeast-2                             │
│  │   AWS_ACCESS_KEY_ID=AKIA...                             │
│  │   AWS_SECRET_ACCESS_KEY=...                             │
│  └─ Code: line 58-64 in main.py                            │
│                                                             │
│  Cost per story: ~$0.02-0.05                                │
│  Request flow:                                              │
│  1. User → /generate-story                                  │
│  2. ChromaDB → retrieve stories                             │
│  3. Build prompt with context                               │
│  4. boto3 → bedrock.invoke_model()                          │
│  5. Parse response → return to user                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ GOOGLE MAPS (ALREADY CONFIGURED)                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Used by: Android App (GPS tracking, map display)           │
│  When: App opens MapActivity                                │
│  Purpose: Show trail maps, geofences, location markers      │
│                                                             │
│  Configuration:                                             │
│  ├─ File: android-gps-tracker/local.properties             │
│  ├─ Variable: GOOGLE_MAPS_API_KEY                          │
│  ├─ Current: AIzaSyDqdKAVzUj8AlhiNgKTk0fAgzxKDfJxyec       │
│  └─ Injected at build time → AndroidManifest.xml           │
│                                                             │
│  Features:                                                  │
│  ├─ Real-time GPS tracking (5s updates)                    │
│  ├─ Geofence visualization (8 hotspots)                    │
│  ├─ Trail path overlay (Saryeoni, Yeongcheonak, etc.)      │
│  └─ Location markers with story info                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ OPENAI (OPTIONAL - Android Chat)                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Used by: Android App (ChatActivity.kt)                     │
│  When: User sends chat message                              │
│  Purpose: AI chatbot for folklore questions                 │
│                                                             │
│  Configuration:                                             │
│  ├─ File: android-gps-tracker/local.properties             │
│  ├─ Variable: OPENAI_API_KEY=sk-proj-...                   │
│  └─ Code: LLMService.kt line 45-78                         │
│                                                             │
│  Alternative: Allen API (free 100/day)                      │
│  Cost with OpenAI: ~$0.001 per message (GPT-4o-mini)        │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ LANGFUSE (OPTIONAL - Monitoring)                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Used by: Backend API (if configured)                       │
│  When: Every LLM call                                        │
│  Purpose: Track costs, latency, prompts, responses          │
│                                                             │
│  Configuration:                                             │
│  ├─ Sign up: https://langfuse.com                          │
│  ├─ File: backend/.env                                     │
│  ├─ Variables:                                              │
│  │   LANGFUSE_PUBLIC_KEY=pk-lf-...                         │
│  │   LANGFUSE_SECRET_KEY=sk-lf-...                         │
│  │   LANGFUSE_HOST=https://cloud.langfuse.com              │
│  └─ Access: http://localhost:3001 (in Docker)              │
│                                                             │
│  Benefits:                                                  │
│  ├─ Track spending per user/session                        │
│  ├─ Debug failed prompts                                   │
│  ├─ Optimize token usage                                   │
│  └─ FREE tier: 50k observations/month                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Service Orchestration (Docker)

```
docker-compose.all-services.yml
        │
        ├─── Networks
        │    └─ jeju-network (172.22.0.0/16)
        │       All services communicate internally
        │
        ├─── Volumes (Data Persistence)
        │    ├─ backend_postgres_data (Grafana DB)
        │    ├─ backend_grafana_data (Dashboards)
        │    ├─ backend_prometheus_data (Metrics)
        │    └─ backend_redis_data (Cache)
        │
        └─── Services (11 total)
             │
             ├─ postgres:5432 (PostgreSQL)
             │  └─ Stores: agent traces, Grafana dashboards
             │
             ├─ redis:6379 (Redis)
             │  └─ Stores: session cache, fast lookups
             │
             ├─ langfuse:3001 (LLM Tracing)
             │  └─ Depends on: postgres
             │
             ├─ prometheus:9090 (Metrics)
             │  └─ Scrapes: backend /metrics every 15s
             │
             ├─ grafana:3000 (Dashboards)
             │  └─ Connects to: prometheus, postgres
             │
             ├─ backend:8000 (FastAPI)
             │  ├─ Depends on: postgres, redis
             │  ├─ Mounts: ChromaDB volume
             │  ├─ Environment: AWS keys, DB URLs
             │  └─ Health check: /metrics endpoint
             │
             ├─ trail-saryeoni:8765
             │  └─ Serves: team-data/shared_data/saryeoni/
             │
             ├─ trail-yeongcheonak:8766
             │  └─ Serves: team-data/shared_data/yeongcheonak/
             │
             ├─ trail-halla:8767
             │  └─ Serves: team-data/shared_data/halla/
             │
             ├─ geofence-map:8768
             │  └─ Serves: android-gps-tracker/geofence map
             │
             └─ presentation:8080
                └─ Serves: Reveal.js slides
```

---

## 🚀 Deployment Sequence

```
start-all-services.sh
        │
        ├─ STEP 1: Verify docker-compose installed
        ├─ STEP 2: Check backend/.env exists
        │          (create from template if missing)
        │
        ├─ STEP 3: Start databases
        │  ├─ docker-compose up -d postgres redis
        │  └─ Wait 10s for health checks
        │
        ├─ STEP 4: Start monitoring
        │  └─ docker-compose up -d prometheus grafana langfuse
        │
        ├─ STEP 5: Start backend
        │  ├─ docker-compose up -d backend
        │  └─ Wait 15s for health check
        │
        ├─ STEP 6: Start trail maps
        │  └─ docker-compose up -d trail-* geofence-map
        │
        ├─ STEP 7: Start presentation
        │  └─ docker-compose up -d presentation
        │
        └─ STEP 8: Display access URLs
           ├─ Grafana: http://localhost:3000
           ├─ Langfuse: http://localhost:3001
           ├─ Backend: http://localhost:8000/docs
           └─ ...
```

---

## 🔍 Monitoring & Observability

```
Grafana (http://localhost:3000)
    │
    ├─ Data Sources
    │  ├─ Prometheus (metrics)
    │  └─ PostgreSQL (traces)
    │
    ├─ Dashboards
    │  ├─ Backend API Performance
    │  │  ├─ Request rate
    │  │  ├─ Response times
    │  │  ├─ Error rates
    │  │  └─ Health status
    │  │
    │  ├─ ChromaDB Performance
    │  │  ├─ Query latency
    │  │  ├─ Embedding operations
    │  │  └─ Collection size
    │  │
    │  └─ LLM Costs & Usage
    │     ├─ Token consumption
    │     ├─ Cost per story
    │     └─ API call distribution
    │
    └─ Alerts
       ├─ Backend unhealthy > 2 min
       ├─ Error rate > 5%
       └─ Response time > 5s

Langfuse (http://localhost:3001)
    │
    ├─ Traces
    │  ├─ View each LLM call
    │  ├─ Prompt + response pairs
    │  └─ Execution timeline
    │
    ├─ Analytics
    │  ├─ Cost breakdown by user/session
    │  ├─ Token usage trends
    │  └─ Model performance comparison
    │
    └─ Datasets
       └─ Export successful prompts for fine-tuning

Prometheus (http://localhost:9090)
    │
    ├─ Metrics
    │  ├─ http_requests_total
    │  ├─ http_request_duration_seconds
    │  ├─ chromadb_query_duration
    │  └─ bedrock_api_calls
    │
    └─ Queries
       ├─ rate(http_requests_total[5m])
       └─ histogram_quantile(0.95, http_request_duration_seconds)
```

---

## 💡 Tips for Using Your Assets

### AWS Bedrock Optimization
```python
# Monitor token usage
response = bedrock.invoke_model(...)
prompt_tokens = response['usage']['input_tokens']
completion_tokens = response['usage']['output_tokens']
cost = (prompt_tokens * 0.003 + completion_tokens * 0.015) / 1000

# Reduce costs:
# 1. Limit max_tokens (default 2000 → 1000)
# 2. Increase temperature for creativity (0.7 → 0.9)
# 3. Cache system prompts (not sent every time)
# 4. Use Claude Haiku for simple queries ($0.25 vs $3 per 1M tokens)
```

### Google Maps Optimization
```kotlin
// Batch geofence updates
val geofences = buildList {
    add(buildGeofence("spot1", lat1, lon1, 50f))
    add(buildGeofence("spot2", lat2, lon2, 50f))
}
geofencingClient.addGeofences(geofenceRequest, pendingIntent)

// Reduce API calls:
// 1. Use caching for static maps
// 2. Request location updates only when needed
// 3. Use LOW_POWER mode when not actively tracking
```

---

**For complete setup instructions, see**:
- [API Assets Checklist](API_ASSETS_CHECKLIST.md)
- [Setup Local Environment](SETUP_LOCAL_ENVIRONMENT.md)
- Run: `bash configure-environment.sh`
