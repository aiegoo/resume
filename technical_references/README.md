# Tony Lee - Technical Architecture References & Project Dossier
### 실무 아키텍처 및 엔지니어링 기술 레퍼런스 모음

이 디렉토리는 이력서([RESUME_KO.md](../RESUME_KO.md) / [RESUME_EN.md](../RESUME_EN.md))에 기재된 주요 프로젝트들의 실제 설계 문서, 아키텍처 다이어그램, 배포 명세서 및 기술 구현 증빙 자료들을 체계적으로 분류한 기술 레퍼런스 모음입니다.

---

## 📂 카테고리별 기술 문서 색인

### 1. 🛸 드론 & 지상관제시스템 (Drone GCS & Autonomous Fleet)
> **이력서 연계 경력**: 유콘크리에이티브(주) 3D-IR 드론 파이프라인 / (주)제이디랩 드론 GCS 수석 / 초경량비행장치 1종 조종자

| 문서명 | 주요 내용 및 기술 요소 |
| :--- | :--- |
| [10-GCS-Ecosystem-Architecture-and-BVLOS-Cloud-Integration.md](drone_gcs/10-GCS-Ecosystem-Architecture-and-BVLOS-Cloud-Integration.md) | • **BVLOS(비가시권 비행)** 클라우드 연동 아키텍처<br>• MAVLink 통신 프로토콜, WebRTC 저지연 영상 스트리밍, 페일세이프 제어 |
| [09-Heterogeneous-Drone-SDK-Adapters-and-Fleet-GCS.md](drone_gcs/09-Heterogeneous-Drone-SDK-Adapters-and-Fleet-GCS.md) | • 이기종 멀티 드론 SDK 어댑터 설계<br>• PX4, ArduPilot, DJI 통합 지상관제(GCS) 소프트웨어 스택 |
| [18-Heterogeneous-GCS-Product-and-Evidence-Briefing.md](drone_gcs/18-Heterogeneous-GCS-Product-and-Evidence-Briefing.md) | • GCS 상용 제품 개발 명세 및 기술 실증(PoC) 브리핑 자료 |
| [13-Gazebo-Sim-Harmonic-Fleet-Actuation-and-Cockpit-Integration.md](drone_gcs/13-Gazebo-Sim-Harmonic-Fleet-Actuation-and-Cockpit-Integration.md) | • Gazebo Sim 기반 자율비행 시뮬레이션 및 3D 조종석(Cockpit) 인터랙션 |
| [hardware_specs_rpi5_hailo8.md](drone_gcs/hardware_specs_rpi5_hailo8.md) | • Raspberry Pi 5 + **Hailo-8 NPU** 엣지 AI 가속기 하드웨어 명세 및 온디바이스 컴퓨터 비전 |

---

### 2. ⚡ 검색 & 로컬 LLM / RAG 파이프라인 (Typesense & Conversational AI)
> **이력서 연계 경력**: 자체 호스팅 로컬 LLM(Ollama, llama.cpp), Typesense 시맨틱 벡터 검색 및 대화형 챗봇

| 문서명 | 주요 내용 및 기술 요소 |
| :--- | :--- |
| [YOGA_PROJECT_STRUCTURE_AND_WORKFLOW.md](typesense_rag/YOGA_PROJECT_STRUCTURE_AND_WORKFLOW.md) | • 전체 RAG 검색 및 챗봇 서비스 아키텍처 워크플로우<br>• 크롤링 데이터 전처리, 벡터 임베딩, Typesense 인덱싱 |
| [TYPESENSE_SETUP.md](typesense_rag/TYPESENSE_SETUP.md) | • Typesense 클러스터 구축, 하이브리드 검색(BM25 + Vector) 스키마 정의 |
| [ARCHITECTURE_VISUAL_DIAGRAM.md](typesense_rag/ARCHITECTURE_VISUAL_DIAGRAM.md) | • 시스템 전체 계층형 아키텍처 시각화 다이어그램 (클라이언트, API 게이트웨이, 임베딩, 스토리지) |
| [INFRASTRUCTURE_SETUP.md](typesense_rag/INFRASTRUCTURE_SETUP.md) | • 온프레미스 GPU 가속 환경 및 WSL2 + CUDA 기반 로컬 LLM 서빙 인프라 명세 |

---

### 3. ☁️ AWS 클라우드 & 설명가능한 AI (Cloud AI & Vector DB)
> **이력서 연계 경력**: **제주오르미캠프 우수상 (팀프로젝트 1위, 2025.12)** / AWS Certified Cloud Practitioner (CLF-C02)

| 문서명 | 주요 내용 및 기술 요소 |
| :--- | :--- |
| [ARCHITECTURE_FLOW.md](cloud_ai_aws/ARCHITECTURE_FLOW.md) | • **오르미 1위 팀프로젝트** 전체 클라우드 아키텍처 플로우<br>• AWS ECS Fargate, OpenSearch, API Gateway, Terraform IaC |
| [MODEL_EXPLAINABILITY.md](cloud_ai_aws/MODEL_EXPLAINABILITY.md) | • 생성형 AI 및 머신러닝 모델의 **설명가능성(Explainability)** 및 추론 투명성 아키텍처 |
| [AWS_ENDPOINTS_AND_CHROMADB_MIGRATION.md](cloud_ai_aws/AWS_ENDPOINTS_AND_CHROMADB_MIGRATION.md) | • ChromaDB에서 AWS 엔드포인트로의 벡터 데이터 마이그레이션 전략 및 벤치마크 |
| [AWS_BEDROCK_FALLBACK_GUIDE.md](cloud_ai_aws/AWS_BEDROCK_FALLBACK_GUIDE.md) | • 온프레미스/로컬 LLM 장애 시 **AWS Bedrock**으로의 고가용성 자동 폴백(Failover) 가이드 |

---

### 4. 🎮 자연어 처리(NLP) & Unity 3D 시뮬레이션 (KOR2UNITY)
> **이력서 연계 경력**: 한국어 음성/텍스트 기반 3D Unity 모빌리티 인터랙션 및 시뮬레이션

| 문서명 | 주요 내용 및 기술 요소 |
| :--- | :--- |
| [KOR2UNITY_TECHNICAL_ARCHITECTURE.md](korean_nlp_unity/KOR2UNITY_TECHNICAL_ARCHITECTURE.md) | • 한국어 자연어 지시어를 Unity 3D 엔진의 물리 액션으로 변환하는 실시간 파이프라인 |
| [KOR2UNITY_QUICK_START.md](korean_nlp_unity/KOR2UNITY_QUICK_START.md) | • 프로젝트 셋업 가이드, 소켓 통신 및 Unity 패키지 연동 절차 |

---

### 💡 기술 레퍼런스 활용 방법
각 기술 문서는 Markdown 형식으로 작성되어 있으며, 상세한 설정 스크립트, 아키텍처 다이어그램, 벤치마크 수치 및 실제 운영 노하우를 포함하고 있습니다. 각 문서를 클릭하여 즉시 전문을 열람할 수 있습니다.
