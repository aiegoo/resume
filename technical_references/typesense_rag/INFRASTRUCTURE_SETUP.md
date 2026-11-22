# Yoga Site Infrastructure Setup

**Date:** 2026-06-02  
**Status:** Active with routing drift (see Runtime Reality Check)

## Overview

This document describes the complete infrastructure setup for the Yoga teaching platform, including search, chat APIs, and public access via Cloudflare tunnels.

## Runtime Reality Check (2026-06-02)

This section is the operational source of truth for the current environment and should be used before applying any tunnel or service changes.

### Key Findings

- Public IP is dynamic and can change by location (home, mobile hotspot, cafe). This is expected.
- Cloudflare Tunnel remains valid across IP changes as long as the connector is active.
- Current failures are caused by hostname to tunnel to origin mapping drift, not by IP rotation alone.

### Canonical Mapping (Current)

| Hostname | Expected Purpose | Observed Public Response | Active Tunnel Serving It | Connector Runtime | Effective Origin | Origin Health |
|----------|------------------|--------------------------|--------------------------|-------------------|------------------|---------------|
| `match.yogaman.club` | Match UI / app | `200` | `2a543e3b-02cd-4513-9bbf-ec4a60c24284` (`yogaman-match`) | `linux_amd64` on this machine (`220.72.232.248`) | `localhost:8088` | `200` |
| `search.yogaman.club` | Search API | root `404`, `/api/search/health` `200` | Not served by local `yogaman-match` config | Served by another tunnel/config | Unknown from this host | Public API health `200` |
| `fyt200.yogaman.club` | Chat/API frontend route (historical) | root `404`, `/api/search/health` `404` | Not served by local `yogaman-match` config | Served by another tunnel/config | Unknown from this host | Route contract mismatch (`404`) |
| `mac-deploy.yogaman.club` | Jekyll dev route (historical) | `502` | Historically under `cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8` | `darwin_arm64` connector (`119.193.32.85`) | historically `localhost:4001` | Local `4001` not listening on this Linux host |

### Hostname Ownership Freeze (Step 1 Completed)

Use this table as the owner source of truth until intentionally changed.

| Hostname | Owner Tunnel ID | Current Owner Runtime | Evidence | Current Status |
|----------|------------------|-----------------------|----------|----------------|
| `match.yogaman.club` | `2a543e3b-02cd-4513-9bbf-ec4a60c24284` | This Linux/WSL machine (`linux_amd64`, IP `220.72.232.248`) | `cloudflared tunnel info 2a543e3b...` + local ingress config | `200` |
| `search.yogaman.club` | `cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8` (provisional) | Remote macOS connector (`darwin_arm64`, IP `119.193.32.85`) | `cloudflared tunnel info cd5a4c8e...` + public `/api/search/health=200` | API health `200` |
| `fyt200.yogaman.club` | `cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8` (provisional) | Remote macOS connector (`darwin_arm64`, IP `119.193.32.85`) | Historical config references + same tunnel ownership pattern | `404` |
| `mac-deploy.yogaman.club` | `cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8` (provisional) | Remote macOS connector (`darwin_arm64`, IP `119.193.32.85`) | `cloudflared tunnel info cd5a4c8e...` + public `502` | `502` |

Notes:
- `fyt200-mac` tunnel (`1f5acfbc-266f-483a-903c-5c64bae9b4a5`) has no active connector and is not currently serving traffic.
- `search/fyt200/mac-deploy` mapping to `cd5a4c8e...` is marked provisional because DNS route ownership cannot be listed from this host alone.

### Tunnel Ownership Snapshot

- Local active tunnel config (`~/.cloudflared/config.yml`):
  - `2a543e3b-02cd-4513-9bbf-ec4a60c24284`
  - ingress only for `match.yogaman.club -> http://localhost:8088`
- Historical/documented tunnel:
  - `cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8` (`yoga-mac-deploy`)
  - Active connectors are currently `darwin_arm64` from `119.193.32.85`
- `1f5acfbc-266f-483a-903c-5c64bae9b4a5` (`fyt200-mac`) currently has no active connection.

### Local Service Reality (This Linux/WSL Host)

- Listening and healthy:
  - `localhost:8088` (via `aeogeo-caddy`) -> `200`
  - `localhost:5001` (`yoga-search-api`) -> `/health` `200`
  - `localhost:5002` (`yoga-chat-api`) -> `/health` `200`
- Not listening:
  - `localhost:4001`
  - `localhost:8081`
- Important contract note:
  - `localhost:5001/api/search/health` is `404` on this host.
  - That means historical assumptions about port `5001` route schema are no longer universally valid.

### Minimal Recovery Runbook (Reversible)

Apply changes in order. Stop after each step if validation fails.

1. Freeze canonical ownership table
  - Decide one owner tunnel per hostname (`search`, `fyt200`, `mac-deploy`, `match`).
  - Record owner machine and local origin port for each.

2. Validate origin locally on owner machine
  - `curl -sS -o /dev/null -w "HTTP %{http_code}\n" http://localhost:<port>/<health-path>`
  - For UI-only apps, validate root path instead of API health.

3. Validate tunnel connector state
  - `cloudflared tunnel info <tunnel-id>`
  - Ensure at least one active connector on intended owner machine.

4. Update ingress only when origin is confirmed healthy
  - Edit owner machine `~/.cloudflared/config.yml`
  - Keep a catch-all `http_status:404` rule last.
  - Restart cloudflared on owner machine.

5. Re-validate public routes
  - `match.yogaman.club` should return `200`
  - `search.yogaman.club/api/search/health` should return `200`
  - `fyt200.yogaman.club` should return expected app/API route response
  - `mac-deploy.yogaman.club` should return `200` only if its local dev origin is intentionally online

6. Rollback plan
  - Revert only the last ingress change.
  - Restart cloudflared.
  - Re-test affected hostname immediately.

### Execution Scope From This Machine

- Can fix immediately from this Linux/WSL host:
  - `match.yogaman.club` only (owner tunnel `2a543e3b...`, origin `localhost:8088`).
- Cannot directly fix from this host without remote owner access:
  - `search.yogaman.club`
  - `fyt200.yogaman.club`
  - `mac-deploy.yogaman.club`
- Required to fix the three hostnames above:
  - access to the remote macOS owner machine currently running tunnel `cd5a4c8e...`, or
  - DNS/tunnel route reassignment to a tunnel controlled from this host.

### Remote Owner Command Pack (macOS)

Run this on the macOS machine that owns tunnel `cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8`.

1. Pre-check and snapshot

    date
    curl -sS https://ifconfig.me
    pgrep -a cloudflared
    cloudflared tunnel info cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8
    cp ~/.cloudflared/config.yml ~/.cloudflared/config.yml.bak.$(date +%Y%m%d_%H%M%S)
    cat ~/.cloudflared/config.yml

2. Validate local origins before touching tunnel config

    for u in http://localhost:5001/api/search/health http://localhost:5002/health http://localhost:4001; do
      echo "===== $u"
      curl -sS -o /tmp/origin_check.out -w "HTTP %{http_code}\n" "$u"
      head -n 6 /tmp/origin_check.out
      echo
    done

3. Minimal fix policy

- Keep only hostnames that have a confirmed healthy local origin.
- If localhost:4001 is down, remove or disable mac-deploy.yogaman.club ingress until origin is intentionally up.
- If localhost:5002 does not serve expected fyt200 routes, point fyt200 to the correct local service or disable temporarily.

4. Restart tunnel and re-check public endpoints

    pkill cloudflared
    cloudflared tunnel --config ~/.cloudflared/config.yml --loglevel warn run > /tmp/cloudflared.log 2>&1 &
    sleep 3
    cloudflared tunnel info cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8
    for u in https://search.yogaman.club/api/search/health https://fyt200.yogaman.club https://mac-deploy.yogaman.club; do
      echo "===== $u"
      curl -sS -o /tmp/public_check.out -w "HTTP %{http_code}\n" "$u"
      head -n 6 /tmp/public_check.out
      echo
    done

5. Rollback if regression appears

    cp ~/.cloudflared/config.yml.bak.<timestamp> ~/.cloudflared/config.yml
    pkill cloudflared
    cloudflared tunnel --config ~/.cloudflared/config.yml --loglevel warn run > /tmp/cloudflared.log 2>&1 &
    cloudflared tunnel info cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8

6. Post-fix evidence to save

- Final ~/.cloudflared/config.yml
- Output of cloudflared tunnel info cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8
- Public checks for:
  - https://search.yogaman.club/api/search/health
  - https://fyt200.yogaman.club
  - https://mac-deploy.yogaman.club

### VS Code Remote Trial Guide (PowerMac)

Use this guide when you want to connect from your current VS Code session to the PowerMac that owns tunnel `cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8`, then run the recovery checks safely.

1. Confirm network path first

- If both machines are on the same LAN, use the PowerMac local IP.
- If you are outside home network (for example cafe), use VPN overlay IP (Tailscale or equivalent) or another secure reachable address.
- If you do not use VPN and this only works on home network, configure router port forwarding for SSH to the PowerMac.
- If no reachable IP exists, VS Code Remote SSH cannot connect yet.

Recommended priority:
1. VPN route (Tailscale/WireGuard) first.
2. Port forwarding only when VPN is not available.

If using port forwarding from outside home:

1. Reserve static LAN IP for PowerMac in router DHCP.
2. Forward an external high port (example `55222`) to PowerMac `22/tcp`.
3. Restrict source IP range in router firewall if possible.
4. Use SSH keys only and disable password login on PowerMac SSH.
5. Optional but recommended: disable direct root login.
6. If ISP public IP changes often, use DDNS hostname.

Example SSH host block for forwarded port:

    Host powermac
      HostName <your-ddns-or-public-ip>
      Port 55222
      User <mac_user>
      IdentityFile ~/.ssh/id_ed25519
      ServerAliveInterval 30
      ServerAliveCountMax 120

2. Prepare SSH on PowerMac

- On PowerMac, enable Remote Login (SSH).
- Verify SSH is listening:
      sudo systemsetup -getremotelogin
      sudo lsof -iTCP:22 -sTCP:LISTEN

3. Prepare SSH key from your current machine

- Create key if needed:
      ssh-keygen -t ed25519 -C "vscode-powermac"
- Copy public key to PowerMac:
      ssh-copy-id <mac_user>@<powermac_ip_or_dns>

4. Add SSH host entry

- Edit local SSH config:
      nano ~/.ssh/config

- Add this block:
      Host powermac
        HostName <powermac_ip_or_dns>
        User <mac_user>
        IdentityFile ~/.ssh/id_ed25519
        ServerAliveInterval 30
        ServerAliveCountMax 120

5. Validate plain SSH before VS Code

      ssh powermac

- If this fails, fix SSH networking first. Do not proceed to VS Code until this works.

6. Connect from VS Code

- Install extension: Remote - SSH.
- Open Command Palette.
- Run: Remote-SSH: Connect to Host...
- Choose host: powermac.
- Open folder on remote host: /Users/<mac_user>/repos/aiegoo/yoga

7. Run the tunnel ownership and health checks in remote terminal

      cloudflared tunnel info cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8
      cat ~/.cloudflared/config.yml
      for u in http://localhost:5001/api/search/health http://localhost:5002/health http://localhost:4001; do
        echo "===== $u"
        curl -sS -o /tmp/origin_check.out -w "HTTP %{http_code}\n" "$u"
        head -n 6 /tmp/origin_check.out
        echo
      done

8. Apply minimal change and restart cloudflared only if origins are healthy

- Follow the Remote Owner Command Pack section above.
- Keep changes minimal and reversible.

9. Verify from public internet

      for u in https://search.yogaman.club/api/search/health https://fyt200.yogaman.club https://mac-deploy.yogaman.club; do
        echo "===== $u"
        curl -sS -o /tmp/public_check.out -w "HTTP %{http_code}\n" "$u"
        head -n 6 /tmp/public_check.out
        echo
      done

10. Roll back quickly if any regression appears

- Restore the snapshot backup config.
- Restart cloudflared.
- Re-run public checks.

### VS Code Remote Troubleshooting (Fast)

- Timeout when outside home network:
  - likely no reachable route to PowerMac. Use Tailscale/home VPN or verify router port forwarding + DDNS first.
- Permission denied (publickey):
  - wrong user, wrong key path, or missing authorized_keys entry.
- Connection refused on forwarded port:
  - router forward target IP changed; re-check static DHCP lease and forwarding rule.
- Connection hangs on forwarded port:
  - ISP/CGNAT may block inbound connection; use VPN overlay instead.
- Connected but tunnel command not found:
  - cloudflared not installed in PATH on PowerMac shell profile.
- Tunnel up but endpoint still wrong:
  - verify expected route path (root route vs API route) before concluding failure.

### What To Avoid

- Do not merge hostnames into a single tunnel config until ownership is confirmed.
- Do not treat root `404` as tunnel failure without checking expected path.
- Do not assume static public IP in operating docs.

### app.yogaman.club Read-Only Verification (No Changes Applied)

This section captures safe diagnostics only. No DNS, tunnel, or container routing change was made.

Observed facts:

- `https://app.yogaman.club` currently returns `HTTP 522` (Cloudflare edge reached, origin unreachable).
- TLS certificate for `app.yogaman.club` matches the same `yogaman.club` certificate used by other subdomains.
- DNS for `app.yogaman.club`, `match.yogaman.club`, `search.yogaman.club` resolves to Cloudflare anycast IPs; this alone does not identify origin ownership.
- This Linux/WSL host active cloudflared ingress (in `~/.cloudflared/config.yml`) includes only:
  - `match.yogaman.club -> http://localhost:8088`
- No local cloudflared config on this host currently maps `app.yogaman.club`.

Interpretation:

- `app.yogaman.club` appears to be managed by a different tunnel or infrastructure path than this host's local ingress.
- Because the expected production path is AWS-backed, this host should not be used to re-point `app.yogaman.club` without explicit change control.

Safe follow-up checklist (still read-only):

1. In Cloudflare DNS, verify the exact record target for `app.yogaman.club`.
2. In Cloudflare Tunnel routes, verify which tunnel UUID is bound to `app.yogaman.club`.
3. On that owner environment only, verify origin health for the mapped upstream.
4. Keep this host limited to `match.yogaman.club` operations unless ownership is intentionally reassigned.

### AWS Host Access Check (Read-Only, 2026-06-02)

Purpose:
- verify whether `yogaq-crawler` and `yogaq-app` can be used as current origin verification points.

Observed from AWS API and non-invasive probes:

- AWS identity is valid in account `495264909330` (API access works).
- `team11_yogaq-app` (`i-05b1c2b1d29a78c1b`) is currently `stopped`.
  - configured public IP: `43.201.156.173` (Elastic IP attached to stopped instance).
  - implication: SSH and origin checks to this instance will fail until instance is running.
- `team11_test` (`i-07c786ab654e692aa`) is `running` with public IP `3.36.222.113`.
  - this matches current `yogaq-crawler` SSH alias target.
  - direct SSH probe from this environment times out on port `22`.
- SSM is online for `i-07c786ab654e692aa`, and read-only command execution succeeded.
  - hostname: `ip-172-31-32-23`
  - user context: `root`
  - `docker` command not found
  - no `~/.cloudflared/config.yml` present

Operational meaning:

- At check time, these two AWS hosts are not confirmed as the active `app.yogaman.club` serving origin from this environment.
- `yogaq-app` must be started before any SSH/origin verification is possible.
- `yogaq-crawler` is reachable via SSM but does not currently show local docker/cloudflared runtime typical of tunnel origin hosting.

Safe next checks (no routing changes):

1. Start `i-05b1c2b1d29a78c1b` (if intended to serve app), then run read-only health checks.
2. Verify security group/NACL/host firewall for SSH on crawler if direct SSH is required.
3. In Cloudflare, identify exact tunnel/DNS route owner for `app.yogaman.club` and compare against AWS instance role.

Update after manual restart (2026-06-02 03:34 UTC):

- `team11_yogaq-app` (`i-05b1c2b1d29a78c1b`) is now `running`.
- SSH via `yogaq-app` alias succeeds (host `ip-172-31-46-249`, user `ubuntu`).
- Docker runtime on app host is active and includes:
  - `aeogeo-caddy`
  - `aeogeo-api`
  - `aeogeo-frontend`
  - `aeogeo-st-match`
  - `aeogeo-st-safety`
  - `aeogeo-jupyter`
- Public endpoint `https://app.yogaman.club` now returns `HTTP 200`.

Interpretation of this update:

- Prior `522` was consistent with origin unavailability while app host was stopped.
- Current behavior confirms app domain serving path recovered after app host restart.

---

## Architecture

### Services Stack

```
┌─────────────────────────────────────────────────────────────┐
│                   Public Access Layer                        │
├─────────────────────────────────────────────────────────────┤
│  Cloudflare Tunnels (4 hostnames)                           │
│  ├─ search.yogaman.club    → localhost:5001 (Search API)    │
│  ├─ fyt200.yogaman.club    → localhost:5002 (Chat API)      │
│  ├─ mac-deploy.yogaman.club → localhost:4001 (Jekyll Dev)   │
│  └─ api.yogaman.club       → localhost:8081                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                          │
├─────────────────────────────────────────────────────────────┤
│  Port 4001: Jekyll Static Site (Development)                │
│  Port 5001: Search API (Docker - yoga-search-api)           │
│  Port 5002: Chat API (Docker - yoga-chat-api)               │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   Data & AI Layer                            │
├─────────────────────────────────────────────────────────────┤
│  Port 8108: Typesense (Docker - yoga-typesense)             │
│  │  └─ 10 collections, 4,438 documents                      │
│  │  └─ Volume: typesense_typesense-data (external)          │
│  │                                                           │
│  Port 11434: Ollama (Docker - yoga-ollama)                  │
│     └─ 4 models: nomic-embed-text, phi3:mini,               │
│        tinyllama, llama3                                     │
└─────────────────────────────────────────────────────────────┘
```

---

## Docker Containers

### Running Services

| Container | Port | Status | Purpose |
|-----------|------|--------|---------|
| `yoga-typesense` | 8108 | ✅ Healthy | Vector database with 10 collections |
| `yoga-ollama` | 11434 | ✅ Running | LLM inference (4 models) |
| `yoga-search-api` | 5001 | ✅ Healthy | Document search API |
| `yoga-chat-api` | 5002 | ✅ Healthy | Conversational Q&A API |

### Data Volumes

- **`typesense_typesense-data`** (external) - Persistent Typesense data with all collections
- **`rag_pipeline_ollama-data`** - Ollama model storage
- **`rag_pipeline_embedding-cache`** - Embedding cache for faster searches

---

## Typesense Collections

| Collection Name | Documents | Description |
|----------------|-----------|-------------|
| asanas_2700 | 1,421 | Comprehensive asana database |
| glossary_lexicon | 761 | Sanskrit terms and definitions |
| hatha_yoga_pages | 549 | Hatha yoga practice content |
| uploaded_documents | 469 | User-uploaded content |
| asana_pranayama_mudra_bandha | 453 | APMB book content |
| hatha_anatomy_enriched | 235 | Anatomical references |
| hatha_anatomy_pages | 235 | Alternative anatomy view |
| hatha_anatomy_keys | 235 | Anatomy key concepts |
| cueing_scripts | 80 | Teaching cues and sequences |
| yoga_documents | 0 | General yoga documents (empty) |

**Total:** 4,438 documents  
**Accessible via Search API:** 3,499 documents

---

## Cloudflare Tunnel Configuration

**Location:** `~/.cloudflared/config.yml`

```yaml
tunnel: cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8
credentials-file: /Users/a2485/.cloudflared/cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8.json
ingress:
  - hostname: api.yogaman.club
    service: http://127.0.0.1:8081
  - hostname: search.yogaman.club      # ← NEW: Search API
    service: http://127.0.0.1:5001
  - hostname: fyt200.yogaman.club
    service: http://127.0.0.1:5002
  - hostname: mac-deploy.yogaman.club
    service: http://127.0.0.1:4001
  - service: http_status:404
```

### Managing Cloudflared Tunnel

**Start with reduced logging (recommended):**
```bash
cloudflared tunnel --config ~/.cloudflared/config.yml --loglevel warn run > /tmp/cloudflared.log 2>&1 &
```

**Log Levels:**
- `debug` - Very verbose, shows all connection attempts (noisy)
- `info` - Default, shows connection warnings (can be noisy)
- `warn` - **Recommended**, only shows important warnings
- `error` - Only shows errors
- `fatal` - Only critical failures

**Common Commands:**
```bash
# Check status
ps aux | grep cloudflared

# View logs
tail -f /tmp/cloudflared.log

# Stop tunnel
pkill cloudflared

# Restart with less noise
pkill cloudflared && cloudflared tunnel --config ~/.cloudflared/config.yml --loglevel warn run > /tmp/cloudflared.log 2>&1 &
```

**Understanding Connection Warnings:**

You may see warnings like:
```
WRN Connection terminated error="failed to dial to edge with quic: timeout: no recent network activity"
WRN Failed to dial a quic connection error="..."
INF Retrying connection in up to 1m4s
```

**These are normal** and indicate:
- Cloudflare is maintaining 4 redundant connections to their edge
- Network hiccups or ISP routing changes
- Cloudflare edge server maintenance
- **The tunnel automatically reconnects** - no action needed

**When to worry:**
- ❌ All 4 connections fail repeatedly for >5 minutes
- ❌ Public endpoints (search.yogaman.club) stop responding
- ❌ `ERR` messages about authentication or configuration

**Troubleshooting:**
```bash
# Test if tunnel is working
curl https://search.yogaman.club/api/search/health

# Check tunnel status
cloudflared tunnel info cd5a4c8e-a9fe-41c3-9273-5be1cbb0ade8

# Re-authenticate if needed
cloudflared tunnel login
```

---
  - hostname: fyt200.yogaman.club
    service: http://127.0.0.1:5002
  - hostname: mac-deploy.yogaman.club
    service: http://127.0.0.1:4001
  - service: http_status:404
```

### Starting Cloudflared

```bash
cloudflared tunnel --config ~/.cloudflared/config.yml run &
```

---

## Changes Made (2026-02-19)

### 1. Resolved Duplicate Typesense Containers

**Problem:** Two Typesense containers existed:
- `yoga-typesense` (running, empty) - using `rag_pipeline_typesense-data`
- `yoga-site-typesense` (stopped) - using `typesense_typesense-data` with all data

**Solution:**
- Removed defunct containers
- Updated `docker-compose.yml` to use external `typesense_typesense-data` volume
- Verified all 10 collections with 4,438 documents are accessible

### 2. Added Search API Public Endpoint

**Problem:** Search page at `/search/` was hardcoded to `fyt200.yogaman.club` (Chat API)

**Solution:**
- Added `search.yogaman.club` hostname to Cloudflare tunnel
- Updated `assets/js/search.js` to use new endpoint
- Configured proper routing for port 5001

**Files Modified:**
- `~/.cloudflared/config.yml` - Added search.yogaman.club ingress
- `assets/js/search.js` - Changed `API_BASE_URL` from fyt200 to search
- `rag_pipeline/docker-compose.yml` - Updated Typesense volume reference

### 3. Dockerized All Services

**Problem:** Search API was running as host process (PID-based, unreliable)

**Solution:**
- Built Docker image: `rag_pipeline-chat-api`
- Started both APIs as Docker containers with proper networking
- Connected to shared `rag_pipeline_yoga-network`
- Mounted code volumes for development

---

## Usage Instructions

### Starting Services

**⚠️ CRITICAL: Always use docker-compose to manage services**

#### Why docker-compose?
- ✅ **Persistent**: Services auto-restart on system reboot (`restart: unless-stopped`)
- ✅ **Managed**: Standardized commands (up, down, restart, logs)
- ✅ **Consistent**: Configuration from docker-compose.yml (no drift)
- ✅ **Reliable**: Health checks and dependency management
- ❌ **Manual `docker run`**: Creates orphan containers that disappear on restart

#### 1. Start All Services
```bash
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline
docker-compose up -d
```

#### 2. Start Specific Service
```bash
docker-compose up -d chat-api     # Start chat API only
docker-compose up -d search-api   # Start search API only
docker-compose up -d typesense    # Start Typesense only
```

#### 3. Restart Service (Pick up code changes)
```bash
docker-compose restart chat-api   # Restart chat API
docker-compose restart search-api # Restart search API
```

**Note:** Environment variable changes require recreation (see Troubleshooting section)

#### 4. Stop Services
```bash
docker-compose stop              # Stop all
docker-compose stop chat-api     # Stop specific service
docker-compose down              # Stop and remove containers
```

#### 5. Verify Services Running
```bash
# Check docker-compose managed services
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline
docker-compose ps

# All should show "Up X minutes (healthy)" or "(unhealthy)" but responsive
# Note: Typesense may show unhealthy but still work - test the endpoint

# Test local endpoints
curl http://localhost:5001/api/search/health  # Search API
curl http://localhost:5002/api/chat/health    # Chat API
curl http://localhost:8108/health             # Typesense

# Test public endpoints via Cloudflare
curl https://search.yogaman.club/api/search/health
curl https://fyt200.yogaman.club/api/chat/health

# Test chatbot in browser
open https://aiegoo.github.io/yoga/chat/
```

**Expected Results:**
- ✅ All services return `{"status": "healthy"}` or `{"ok": true}`
- ✅ Chatbot loads and can send messages
- ✅ No 502 Bad Gateway or CORS errors

#### 6. ~~Start Chat API (Manual)~~ ❌ DEPRECATED - Use docker-compose
```bash
# DO NOT USE - This creates unmanaged containers
# docker run -d --name yoga-chat-api \
  -e TYPESENSE_PORT=8108 \
  -e TYPESENSE_API_KEY=yoga-site-typesense-admin-key \
  -e OLLAMA_HOST=ollama \
  -e OLLAMA_PORT=11434 \
  -e OLLAMA_BASE_URL=http://ollama:11434 \
  -e FLASK_ENV=production \
  -e PYTHONPATH=/app \
  rag_pipeline-chat-api python api/search_api.py
```

#### 7. Start Cloudflared Tunnel
```bash
cloudflared tunnel --config ~/.cloudflared/config.yml run &
```

#### 5. Start Jekyll (Development)
```bash
cd /Users/a2485/repos/aiegoo/yoga
./start_jekyll.sh
```

---

## Health Checks

### Local Endpoints

```bash
# Typesense
curl http://localhost:8108/health
# Should return: {"ok":true}

# Ollama
curl http://localhost:11434/api/tags
# Should return list of 4 models

# Search API
curl http://localhost:5001/api/search/health
# Should show: status=healthy, totalDocuments=3,499

# Chat API
curl http://localhost:5002/api/chat/health
# Should show: status=healthy, collections=4
```

### Public Endpoints

```bash
# Search API
curl https://search.yogaman.club/api/search/health

# Chat API
curl https://fyt200.yogaman.club/api/chat/health

# Jekyll Site
curl https://mac-deploy.yogaman.club
```

### Comprehensive Status Check

```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep yoga
```

---

## Accessing the Applications

### 1. Search Interface
- **Local:** http://localhost:4001/search/
- **Public:** https://search.yogaman.club (via Jekyll redirects)
- **Features:**
  - Keyword search across 10 collections
  - Category filtering (2700 Asanas, Anatomy, Cueing, etc.)
  - Semantic search with embeddings
  - 3,499 documents searchable

### 2. Chat Interface
- **Local:** http://localhost:4001/chat/
- **Public:** https://fyt200.yogaman.club (direct API access)
- **Features:**
  - Conversational Q&A with RAG
  - Multi-collection semantic search
  - LLM-powered responses (phi3:mini)
  - Voice input/output (STT/TTS)
  - 5 knowledge base collections

### 3. Jekyll Site
- **Local:** http://localhost:4001/
- **Public:** https://mac-deploy.yogaman.club
- **Content:**
  - FYT100/FYT200 course materials
  - Session notes and cueing scripts
  - Study materials and references

---

## Troubleshooting

### Container Health Issues

Some containers may show "unhealthy" status but still work correctly. This is due to health check configuration in docker-compose.yml.

**Verify actual functionality:**
```bash
# Test each service directly
curl http://localhost:8108/health        # Typesense
curl http://localhost:11434/api/tags     # Ollama
curl http://localhost:5001/api/search/health  # Search
curl http://localhost:5002/api/chat/health    # Chat
```

### Port Conflicts

If ports are already in use:
```bash
# Check what's using a port
lsof -i :5001
lsof -i :5002

# Kill process if needed
kill -9 <PID>
```

### Typesense Data Missing

If collections are empty:
```bash
# Check volume
docker volume inspect typesense_typesense-data

# Verify collections
curl http://localhost:8108/collections \
  -H "X-TYPESENSE-API-KEY: yoga-site-typesense-admin-key"
```

### Cloudflared Tunnel Down

```bash
# Check if running
ps aux | grep cloudflared

# Restart
pkill cloudflared
cloudflared tunnel --config ~/.cloudflared/config.yml run &
```

### Rebuilding Docker Images

```bash
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline

# Rebuild image
docker build -t rag_pipeline-chat-api .

# Stop and remove old containers
docker stop yoga-search-api yoga-chat-api
docker rm yoga-search-api yoga-chat-api

# Restart with fresh image (use commands from "Starting Services" section)
```

---

## Maintenance

### Stopping All Services

```bash
# Stop Docker containers
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline
docker-compose down
docker stop yoga-search-api yoga-chat-api
docker rm yoga-search-api yoga-chat-api

# Stop Cloudflared
pkill cloudflared

# Stop Jekyll
pkill -f "jekyll serve"
```

### Backing Up Data

```bash
# Backup Typesense volume
docker run --rm \
  -v typesense_typesense-data:/data \
  -v $(pwd)/backups:/backup \
  ubuntu tar czf /backup/typesense-backup-$(date +%Y%m%d).tar.gz /data

# Backup Ollama models
docker run --rm \
  -v rag_pipeline_ollama-data:/data \
  -v $(pwd)/backups:/backup \
  ubuntu tar czf /backup/ollama-backup-$(date +%Y%m%d).tar.gz /data
```

### Updating Collections

To re-index or add new documents:
```bash
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline

# Run indexing script (if available)
python3 index_content.py

# Or use upload endpoint
curl -X POST http://localhost:5002/api/upload \
  -F "file=@/path/to/document.pdf"
```

---

## Environment Variables

### Required for APIs

| Variable | Default | Description |
|----------|---------|-------------|
| `TYPESENSE_HOST` | typesense | Typesense container hostname |
| `TYPESENSE_PORT` | 8108 | Typesense port |
| `TYPESENSE_API_KEY` | yoga-site-typesense-admin-key | API key |
| `OLLAMA_BASE_URL` | http://ollama:11434 | Ollama endpoint |
| `OLLAMA_CHAT_MODEL` | phi3:mini | Default LLM model |
| `FLASK_ENV` | production | Flask environment |
| `PYTHONPATH` | /app | Python module path |

---

## Network Architecture

All Docker containers run on `rag_pipeline_yoga-network` bridge network:

```
rag_pipeline_yoga-network (bridge)
├─ yoga-typesense (8108)
├─ yoga-ollama (11434)
├─ yoga-search-api (5001)
└─ yoga-chat-api (5002)
```

**Communication:**
- Containers communicate via service names (e.g., `http://typesense:8108`)
- Host access via `localhost:PORT`
- Public access via Cloudflare tunnel → `127.0.0.1:PORT`

---

## Performance Notes

### Search API
- **Average response time:** 200-500ms
- **Concurrent requests:** Supports up to 50 concurrent
- **Rate limiting:** 200/day, 50/hour per IP (if enabled)

### Chat API
- **Average response time:** 3-8 seconds (streaming)
- **LLM inference:** ~2-4 seconds with phi3:mini
- **Embedding generation:** 8-14 seconds per query

### Typesense
- **Search latency:** <50ms for most queries
- **Index size:** ~120MB for 4,438 documents
- **Vector dimensions:** 768 (nomic-embed-text)

---

## Security Considerations

1. **API Keys:** Currently using default keys - should be changed in production
2. **CORS:** Configured for specific origins in both APIs
3. **Tunnel Access:** Public endpoints are exposed via Cloudflare
4. **Volume Permissions:** Docker volumes owned by Docker user

### Recommended Production Changes

```yaml
# Update API keys in docker-compose.yml
environment:
  TYPESENSE_API_KEY: ${TYPESENSE_API_KEY}  # Use env file

# Add authentication to APIs
# Implement rate limiting
# Use HTTPS for all internal communication
```

---

## Persistence & Auto-Start Configuration

### Overview

All services are configured to automatically restart after system reboot, hibernation, or container crashes. This ensures the infrastructure is always available without manual intervention.

### Docker Restart Policies

All containers have `restart: unless-stopped` policy configured:

| Container | Restart Policy | Behavior |
|-----------|---------------|----------|
| `yoga-typesense` | unless-stopped | Auto-restarts on crash, survives reboot |
| `yoga-search-api` | unless-stopped | Auto-restarts on crash, survives reboot |
| `yoga-chat-api` | unless-stopped | Auto-restarts on crash, survives reboot |
| `yoga-ollama` | ❌ Disabled | Uses host Ollama for better performance |

**Note:** The Docker Ollama container is intentionally disabled. The infrastructure uses the **native macOS Ollama** installation for 20x faster LLM inference.

### Automatic Startup Script

**Location:** `/Users/a2485/repos/aiegoo/yoga/start_infrastructure.sh`

This script handles complete infrastructure initialization:

```bash
#!/bin/bash
# Startup script for Yoga Infrastructure

# 1. Check Docker is running
# 2. Start all Docker containers via docker-compose
# 3. Wait for services to be healthy
# 4. Start Cloudflare tunnel
# 5. (Optional) Start Jekyll dev server
```

**Usage:**
```bash
# Manual start
cd /Users/a2485/repos/aiegoo/yoga
./start_infrastructure.sh

# Check output
tail -f /tmp/yoga-infrastructure.log
```

### macOS LaunchAgent (Auto-Start at Login)

**Location:** `~/Library/LaunchAgents/com.yogaman.infrastructure.plist`

The LaunchAgent automatically runs `start_infrastructure.sh` when you log in to macOS.

**Status Check:**
```bash
# Check if LaunchAgent is loaded
launchctl list | grep yogaman

# View logs
tail -f /tmp/yoga-infrastructure.log
tail -f /tmp/yoga-infrastructure-error.log
```

**Manual Control:**
```bash
# Unload (disable auto-start)
launchctl unload ~/Library/LaunchAgents/com.yogaman.infrastructure.plist

# Reload (enable auto-start)
launchctl load ~/Library/LaunchAgents/com.yogaman.infrastructure.plist

# Run immediately (test)
launchctl start com.yogaman.infrastructure
```

### Testing Persistence

**Test 1: Container Crash Recovery**
```bash
# Kill a container
docker kill yoga-chat-api

# Wait 5-10 seconds
sleep 10

# Verify it restarted
docker ps --filter "name=yoga-chat-api"
# Should show: Up X seconds
```

**Test 2: System Reboot**
```bash
# Reboot your Mac
sudo reboot

# After login, check status
docker ps --filter "name=yoga"

# All 3 containers should be running
```

**Test 3: Docker Desktop Restart**
```bash
# Quit Docker Desktop completely
# Restart Docker Desktop
# Wait 30 seconds for Docker to initialize

# Check containers
docker ps --filter "name=yoga"
# All containers should auto-start
```

### What Persists After Reboot

✅ **Automatically Starts:**
- Docker containers (typesense, search-api, chat-api)
- Cloudflare tunnel (via LaunchAgent)
- All data in Docker volumes

❌ **Requires Manual Start:**
- Jekyll development server (port 4001)
- Host Ollama service (should auto-start with macOS)

**Note:** To enable Jekyll auto-start, uncomment the Jekyll section in `start_infrastructure.sh` (lines 27-33).

### Startup Order

1. **macOS boots** → User logs in
2. **LaunchAgent triggers** → Runs `start_infrastructure.sh`
3. **Docker check** → Waits for Docker Desktop to be ready
4. **Containers start** → docker-compose up -d
5. **Health checks** → Waits for services to be healthy
6. **Cloudflare tunnel** → Starts public access
7. **Ready** → All services available

### Troubleshooting Auto-Start

**Problem: Containers don't start after reboot**
```bash
# Check Docker Desktop is running
docker info

# Check LaunchAgent status
launchctl list | grep yogaman

# View LaunchAgent logs
cat /tmp/yoga-infrastructure.log
cat /tmp/yoga-infrastructure-error.log

# Manually trigger
./start_infrastructure.sh
```

**Problem: LaunchAgent not running**
```bash
# Reload the agent
launchctl unload ~/Library/LaunchAgents/com.yogaman.infrastructure.plist
launchctl load ~/Library/LaunchAgents/com.yogaman.infrastructure.plist
```

**Problem: Docker starts but containers are stopped**
```bash
# Check restart policy
docker inspect yoga-chat-api | grep -A 5 RestartPolicy

# Should show: "Name": "unless-stopped"

# If not, update with:
docker update --restart unless-stopped yoga-chat-api
```

### Manual Container Management

```bash
# Stop all yoga containers
docker stop yoga-chat-api yoga-search-api yoga-typesense

# Start all containers
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline
docker-compose up -d

# Or use startup script
./start_infrastructure.sh

# Check health
curl http://localhost:8108/health
curl http://localhost:5001/api/search/health
curl http://localhost:5002/api/chat/health
```

### Performance Note: Host Ollama vs Docker Ollama

**Before (Docker Ollama):** 
- Response time: 9-17 minutes 😱
- Reason: CPU/GPU virtualization overhead

**After (Host Ollama):**
- Response time: 15-25 seconds ✅
- Reason: Direct hardware access

**Configuration:**
- Chat API connects to `host.docker.internal:11434`
- Uses native macOS Ollama installation
- Model: `llama3:latest` (8B parameters)

To ensure host Ollama starts automatically:
```bash
# Check if Ollama is running
ps aux | grep ollama | grep -v grep

# If not, Ollama should auto-start with macOS
# Or start manually:
ollama serve &
```

---

## Contact & Support

- **Repository:** https://github.com/aiegoo/yoga
- **Current Branch:** feature/frontmatter-navigation
- **Deploy Branch:** fyt200

For issues or questions, refer to project documentation or create a GitHub issue.

---

## Changelog

### 2026-02-19

**Infrastructure Fixes:**
- ✅ Resolved duplicate Typesense container issue
- ✅ Added search.yogaman.club public endpoint
- ✅ Dockerized all API services
- ✅ Updated Cloudflare tunnel configuration
- ✅ Verified all 4,438 documents accessible
- ✅ Unified data volumes for consistency

**Performance Improvements:**
- ✅ Switched from Docker Ollama to host Ollama (20x faster: 20s vs 9+ min)
- ✅ Chat API now uses `host.docker.internal:11434` for native Ollama access
- ✅ Increased LLM timeout from 120s to 600s to handle slower queries

**Persistence & Auto-Start:**
- ✅ Added `restart: unless-stopped` policy to all containers
- ✅ Created `start_infrastructure.sh` startup script
- ✅ Installed macOS LaunchAgent for auto-start at login
- ✅ Containers now survive system reboot and hibernation

**Bug Fixes:**
- ✅ Fixed CORS issue: Added `localhost:4001` to allowed origins
- ✅ Chat API now accessible from Jekyll dev server
- ✅ Fixed semantic search variable initialization bug
- ✅ Fixed Typesense client to use environment variables for Docker networking
- ✅ **Fixed Typesense semantic search collection/field mismatches (2026-02-19 evening)**
  - Fixed `hatha_anatomy_enriched` collection field names in queries
  - Configured Docker to use host-based Ollama for embeddings
  - Added missing collection mapping for semantic search

---

## Typesense Semantic Search Configuration (2026-02-19)

### Issue: Semantic Search Returning Zero Results

**Problem Identified:**
The dockerized Typesense semantic search was failing to return results for anatomy queries despite having 235 documents in the `hatha_anatomy_enriched` collection. Investigation revealed multiple issues:

1. **Field name mismatches** between search queries and actual Typesense schema
2. **Docker networking issues** preventing embedding generation
3. **Missing collection mappings** in semantic search configuration

### Root Causes

#### 1. Query Field Mismatches

**File:** `rag_pipeline/rag/search_service.py`

**Issue:** Query was using incorrect field names that didn't exist in the collections:

| Collection | Incorrect Field | Correct Field |
|------------|----------------|---------------|
| `yoga_documents` | `description` | (Field doesn't exist) |
| `hatha_anatomy_enriched` | `yoga_poses` | `yoga_poses_mentioned` |
| `hatha_anatomy_enriched` | `primary_structures` | `primary_anatomical_structures` |
| (semantic search) | N/A for `hatha_anatomy_enriched` | `content` (missing mapping) |

**Impact:** Typesense threw "field not found" errors, causing searches to fail or return empty results.

#### 2. Ollama Connection from Docker

**File:** `rag_pipeline/docker-compose.yml`

**Issue:** Search API container was configured to connect to `OLLAMA_HOST: ollama`, expecting a containerized Ollama service. However, we disabled Docker Ollama for performance and use host-based Ollama instead.

**Original Configuration:**
```yaml
environment:
  OLLAMA_HOST: ollama
  OLLAMA_PORT: 11434
  OLLAMA_BASE_URL: http://ollama:11434
depends_on:
  ollama:
    condition: service_healthy  # This always failed
```

**Impact:** 
- Semantic search failed during embedding generation
- Error: `Failed to resolve 'ollama' ([Errno -2] Name or service not known)`
- Searches fell back to keyword search (which worked for some collections but not others)

#### 3. Missing Collection Query Field Mapping

**File:** `rag_pipeline/rag/search_service.py` (line ~1048)

**Issue:** The `COLLECTION_QUERY_FIELDS` dictionary was missing an entry for `hatha_anatomy_enriched`:

```python
COLLECTION_QUERY_FIELDS = {
    'cueing_scripts': 'title',
    'glossary_lexicon': 'term_en',
    'asanas_2700': 'name',
    'hatha_anatomy_pages': 'content',
    # 'hatha_anatomy_enriched': 'content',  # ← MISSING!
    'asana_pranayama_mudra_bandha': 'text',
    'hatha_yoga_pages': 'content',
    'yoga_documents': 'title',
}
```

**Impact:** When semantic search tried to query `hatha_anatomy_enriched`, it fell back to default 'title' field, which caused mismatches.

### Solutions Implemented

#### Fix 1: Corrected Query Field Names

**File:** `rag_pipeline/rag/search_service.py`

**Changes:**

1. **Main collection query fields** (line ~120):
```python
# Before:
QUERY_BY = 'title,description,content'
QUERY_BY_WEIGHTS = '5,3,2'

# After:
QUERY_BY = 'title,content'  # Removed non-existent 'description'
QUERY_BY_WEIGHTS = '5,2'
```

2. **Anatomy collection query fields** (line ~338):
```python
# Before:
anatomy_search_params['query_by'] = 'title,content,full_text,keywords,yoga_poses,primary_structures'

# After:
anatomy_search_params['query_by'] = 'title,content,full_text,keywords,yoga_poses_mentioned,primary_anatomical_structures'
```

3. **Added semantic search mapping** (line ~1048):
```python
COLLECTION_QUERY_FIELDS = {
    'cueing_scripts': 'title',
    'glossary_lexicon': 'term_en',
    'asanas_2700': 'name',
    'hatha_anatomy_pages': 'content',
    'hatha_anatomy_enriched': 'content',  # ← ADDED
    'asana_pranayama_mudra_bandha': 'text',
    'hatha_yoga_pages': 'content',
    'yoga_documents': 'title',
}
```

#### Fix 2: Docker Ollama Connection

**File:** `rag_pipeline/docker-compose.yml`

**Changes:**

```yaml
# Before:
environment:
  OLLAMA_HOST: ollama
  OLLAMA_PORT: 11434
  OLLAMA_BASE_URL: http://ollama:11434
depends_on:
  typesense:
    condition: service_healthy
  ollama:
    condition: service_healthy

# After:
environment:
  OLLAMA_HOST: host.docker.internal  # ← Connect to host Ollama
  OLLAMA_PORT: 11434
  OLLAMA_BASE_URL: http://host.docker.internal:11434
depends_on:
  typesense:
    condition: service_healthy
  # Removed ollama dependency
```

**Why `host.docker.internal`?**
- Docker special DNS name that resolves to host machine's IP
- Allows containers to access services running on the macOS host
- Works on Docker Desktop for Mac (and Windows)
- No need for complex network bridge configurations

#### Fix 3: Container Recreation

**Critical:** Simply restarting containers with `docker-compose restart` does NOT pick up new environment variables. Containers must be recreated.

**Commands used:**
```bash
# Remove old container
docker rm -f yoga-search-api

# Recreate with new environment from docker-compose.yml
docker run -d --name yoga-search-api \
  --network rag_pipeline_yoga-network \
  -p 5001:5001 \
  -v /Users/a2485/repos/aiegoo/yoga/rag_pipeline:/app \
  -v rag_pipeline_embedding-cache:/app/.embedding_cache \
  -e TYPESENSE_HOST=typesense \
  -e TYPESENSE_PORT=8108 \
  -e TYPESENSE_PROTOCOL=http \
  -e TYPESENSE_API_KEY=yoga-site-typesense-admin-key \
  -e OLLAMA_HOST=host.docker.internal \
  -e OLLAMA_PORT=11434 \
  -e OLLAMA_BASE_URL=http://host.docker.internal:11434 \
  -e FLASK_ENV=production \
  -e FLASK_DEBUG=0 \
  -e PYTHONPATH=/app \
  -e PYTHONUNBUFFERED=1 \
  rag_pipeline-search-api
```

### Verification & Testing

#### 1. Test Ollama Connection from Container

```bash
# Test if container can reach host Ollama
docker exec yoga-search-api curl -s http://host.docker.internal:11434/api/tags

# Expected output: JSON with models list including "nomic-embed-text"
```

#### 2. Test Embedding Generation

```bash
# Generate test embedding
docker exec yoga-search-api python3 -c "
import os
os.environ['OLLAMA_BASE_URL'] = 'http://host.docker.internal:11434'
from embedding_service import EmbeddingService
svc = EmbeddingService()
result = svc.generate_embedding('test shoulder muscles')
print(f'Result type: {type(result)}')
print(f'Dimensions: {len(result[\"embedding\"])}')
print(f'Model: {result[\"model\"]}')
"

# Expected output:
# Result type: <class 'dict'>
# Dimensions: 768
# Model: nomic-embed-text
```

#### 3. Test Semantic Search API

```bash
# Test anatomy search with semantic matching
curl -X POST "http://localhost:5001/api/search" \
  -H "Content-Type: application/json" \
  -d '{
    "q": "shoulder muscles",
    "categories": ["hatha-anatomy"],
    "page": 1,
    "perPage": 3
  }' | python3 -m json.tool

# Expected output:
# {
#   "found": 3,
#   "searchMode": "semantic",
#   "results": [
#     {
#       "title": "Anatomy - shoulder page",
#       "category": "anatomy",
#       "score": 0.8175...,
#       ...
#     }
#   ]
# }
```

### Collection Schema Reference

For debugging, here are the actual field names in each collection:

#### `yoga_documents` (main collection)
```json
{
  "fields": ["title", "content", "source", "file_path", "category", "tags", "timestamp", "embedding"]
}
```
**Note:** No `description` field exists!

#### `hatha_anatomy_enriched`
```json
{
  "fields": [
    "title", "content", "page_number", "book", "category",
    "primary_anatomical_structures",  // NOT "primary_structures"
    "secondary_anatomical_structures",
    "body_regions", "movement_types", "muscle_actions",
    "yoga_poses_mentioned",  // NOT "yoga_poses"
    "concepts", "page_type", "difficulty_level",
    "searchable_summary", "image_path", "image_url",
    "embedding"
  ]
}
```

#### Check Collection Schema

```bash
# List all collections
curl -s http://localhost:8108/collections \
  -H "X-TYPESENSE-API-KEY: yoga-site-typesense-admin-key" \
  | python3 -m json.tool

# Get specific collection schema
curl -s http://localhost:8108/collections/hatha_anatomy_enriched \
  -H "X-TYPESENSE-API-KEY: yoga-site-typesense-admin-key" \
  | python3 -m json.tool
```

### Performance After Fix

**Before Fix:**
- Semantic search: 0 results for anatomy queries
- Keyword search: 171 results found but semantic returned 0
- Embedding generation: Failed with DNS resolution errors

**After Fix:**
- Semantic search: ✅ 3 results for "shoulder muscles"
- Embedding generation: ✅ 768-dim vectors from nomic-embed-text
- Search time: ~26ms (semantic) vs ~80ms (keyword)
- Relevance scores: 0.77-0.82 (good semantic matching)

### Troubleshooting Guide

#### Problem: Semantic search returns 0 results

**Check 1: Verify Ollama connectivity**
```bash
docker exec yoga-search-api curl http://host.docker.internal:11434/api/tags
```
Expected: JSON with models list. If error, check if Ollama is running on host.

**Check 2: Verify embedding generation**
```bash
docker logs yoga-search-api --tail=20 | grep -i "embedding\|ollama"
```
Look for "Generated query embedding: 768 dims" in logs.

**Check 3: Test direct Typesense query**
```bash
curl -s "http://localhost:8108/collections/hatha_anatomy_enriched/documents/search?q=shoulder&query_by=title,content&per_page=3" \
  -H "X-TYPESENSE-API-KEY: yoga-site-typesense-admin-key" \
  | python3 -m json.tool
```
If this works but semantic doesn't, check embedding service logs.

#### Problem: Container environment variables not updating

```bash
# Verify current environment
docker exec yoga-search-api env | grep OLLAMA

# If showing old values (ollama instead of host.docker.internal):
docker rm -f yoga-search-api
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline
docker-compose up -d search-api

# OR recreate manually with correct env vars (see Fix 3 above)
```

#### Problem: Field not found errors in logs

```bash
# Check the exact error
docker logs yoga-search-api --tail=50 | grep -i "error\|field"

# Compare with actual schema
curl -s http://localhost:8108/collections/COLLECTION_NAME \
  -H "X-TYPESENSE-API-KEY: yoga-site-typesense-admin-key" \
  | python3 -m json.tool | grep -A 2 '"name"'

# Update search_service.py query fields to match schema
```

### Future Improvements

1. **Schema Validation:** Add startup check to validate query fields match collection schemas
2. **Health Check Enhancement:** Include embedding service status in `/api/search/health`
3. **Error Handling:** Better error messages when field mismatches occur
4. **Configuration:** Move query field mappings to config file instead of hardcoding
5. **Monitoring:** Add Prometheus metrics for semantic search success/failure rates

---

## Common Pitfalls & Solutions

### Issue: Services Show "Unhealthy" but Actually Work

**Symptoms:**
- `docker ps` shows container as "(unhealthy)"
- Service responds correctly to curl requests
- Chatbot works fine from browser

**Root Cause:**
Container was created manually with `docker run` instead of via docker-compose, resulting in:
- Incorrect health check configuration
- No auto-restart on system reboot
- Configuration drift from docker-compose.yml
- Service not managed by docker-compose commands

**Solution:**
```bash
# 1. Remove manually created container
docker rm -f yoga-chat-api

# 2. Start via docker-compose (proper way)
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline
docker-compose up -d chat-api

# 3. Verify health status
docker ps --filter "name=yoga"
# Should show "Up X seconds (healthy)"
```

**Prevention:**
- ⚠️ **NEVER use `docker run` for yoga services**
- ✅ Always use `docker-compose up -d <service-name>`
- ✅ All services defined in docker-compose.yml with proper health checks
- ✅ Configuration managed centrally

### Issue: Service Stops After System Restart

**Symptoms:**
- Services work fine, then disappear after Mac restart
- `docker ps` shows no yoga containers
- Chatbot gets 502 Bad Gateway errors

**Root Cause:**
Containers created with manual `docker run` don't persist across restarts.

**Solution:**
```bash
# Start all services via docker-compose
cd /Users/a2485/repos/aiegoo/yoga/rag_pipeline
docker-compose up -d

# Verify all services running
docker-compose ps
```

**Why docker-compose Persists:**
- `restart: unless-stopped` in docker-compose.yml
- Docker daemon auto-restarts containers on system boot
- Services maintain state and configuration

### Related Files Modified

1. `rag_pipeline/rag/search_service.py` - Query field corrections
2. `rag_pipeline/docker-compose.yml` - Ollama connection configuration
3. This documentation (`INFRASTRUCTURE_SETUP.md`) - Added troubleshooting guide

---

## Anatomy Collection Search Fixes (2026-02-20)

### Issue: Search and Chat APIs Not Returning Anatomy Results

**Problem:** Search API returned 0 results for "rotator" query while Chat API showed context from anatomy sources, indicating anatomy collections weren't properly integrated.

### Root Causes & Fixes

#### 1. Missing Required Fields in Anatomy Result Construction

**File:** `rag_pipeline/rag/search_service.py` (lines 580-607)

**Fix Applied:** Added all required SearchResult fields:
- `content_snippet`, `popularity`, `highlight`, `full_text`
- Changed `relevance_score` → `score` (correct parameter)
- Enhanced tags with `yoga_poses_mentioned`
- Added `github_url` for source links

#### 2. Incorrect Field Names in Anatomy Query

**File:** `rag_pipeline/rag/search_service.py` (line 338)

**Fix Applied:** Corrected field names to match schema:
- `yoga_poses` → `yoga_poses_mentioned`
- `primary_structures` → `primary_anatomical_structures`

#### 3. Invalid "description" Field in Main Query

**File:** `rag_pipeline/rag/search_service.py` (lines 118-119)

**Fix Applied:** Removed non-existent field:
- Changed `'title,description,content'` → `'title,content'`

### Verification Results

**Anatomy Search:**
- ✅ Found: 5 results from hatha_anatomy_enriched
- ✅ Image URLs: `https://fyt200.yogaman.club/api/anatomy-images/page_XXXX.png`
- ✅ Search time: ~86ms (semantic)

**Cross-Collection Search:**
- Total found: 25 results for "rotator cuff"
- Categories: anatomy, hatha-yoga, reference-books, lexicon, cueing

### Services Restarted

Both APIs restarted to apply fixes:
```bash
docker-compose restart search-api chat-api
```

**Status:**
- yoga-search-api: ✅ Healthy
- yoga-chat-api: ✅ Healthy  
- yoga-typesense: ✅ Healthy (10 collections, 4,438 documents)

---

**Last Updated:** 2026-02-20  
**Document Version:** 1.3  
**Infrastructure Status:** ✅ Production Ready
