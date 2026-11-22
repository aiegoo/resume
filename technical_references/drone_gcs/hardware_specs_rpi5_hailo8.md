# Raspberry Pi 5 + Hailo-8 NPU Hardware Testbed Specification

This document records the exact physical hardware, operating system, firmware, and compute specifications of the **Airborne Companion Computer (APCC)** edge testbed (`rentool`), verified over SSH on September 9, 2026.

---

## 1. System Overview

| Parameter | Specification | Verification Method |
| :--- | :--- | :--- |
| **Device Model** | Raspberry Pi 5 Model B (BCM2712) | Direct Kernel / Device Tree |
| **Hostname** | `rentool` | `hostname` |
| **Management IP (Ethernet)** | `172.30.1.75/24` (`eth0`) | `ip -br a` |
| **Management IP (Wi-Fi)** | `172.30.1.76/24` (`wlan0`) | `ip -br a` |
| **Target Role in Phase 3** | Airborne Companion Computer (APCC) Edge AI Testbed | Phase 3 Architecture |
| **Isolation Boundary** | `/home/hi/drone-testbed/` with dedicated `venv` | User-space Sandbox |

---

## 2. Compute Architecture & Processor

```text
Architecture:                         aarch64
CPU op-mode(s):                       32-bit, 64-bit
Byte Order:                           Little Endian
CPU(s):                               4
Model name:                           Cortex-A76
Thread(s) per core:                   1
Core(s) per socket:                   4
Socket(s):                            1
CPU max MHz:                          2400.0000 MHz (2.4 GHz)
CPU min MHz:                          1500.0000 MHz (1.5 GHz)
Instruction Set:                      ARMv8.2-A (FP, ASIMD, AES, PMULL, SHA1, SHA2, CRC32)
```

---

## 3. Memory & Storage Subsystem

### RAM
* **Total Capacity**: 8 GB LPDDR4X (7.9 GiB reported by kernel)
* **Available at Idle**: 7.1 GiB available (6.3 GiB completely free)
* **Swap Allocation**: 511 MiB (0B utilized)

### Primary Storage (Internal MicroSD)
* **Block Device**: `/dev/mmcblk0`
* **Card Capacity**: 1 TB (955 GB raw, 1,001,390,080 blocks)
* **Partition Layout**:
  * `/dev/mmcblk0p1`: `512 MB` FAT32 mounted at `/boot/firmware` (442 MB free)
  * `/dev/mmcblk0p2`: `940 GB` Ext4 mounted at `/` (855 GB free, 5% utilization)

---

## 4. Edge AI Accelerator (PCIe Gen 2/3)

```text
lspci:
0001:00:00.0 PCI bridge: Broadcom Inc. and subsidiaries BCM2712 PCIe Bridge (rev 21)
0001:01:00.0 Co-processor: Hailo Technologies Ltd. Hailo-8 AI Processor (rev 01)
0002:00:00.0 PCI bridge: Broadcom Inc. and subsidiaries BCM2712 PCIe Bridge (rev 21)
0002:01:00.0 Ethernet controller: Raspberry Pi Ltd RP1 PCIe 2.0 South Bridge
```

* **Chipset**: Hailo-8 AI Processor (rev 01)
* **PCIe Bus ID**: `0001:01:00.0`
* **Rated Performance**: 26 Tera-Operations Per Second (TOPS)
* **Efficiency**: ~3 TOPS/Watt (sub-5W operational envelope, ideal for drone battery endurance)
* **Neural Architecture Target**: YOLOv8s / YOLOv10s 640×640 tactical object detection @ 30+ FPS

---

## 5. Operating System & Kernel Runtime

* **Distribution**: Debian GNU/Linux 12 (`bookworm`) 64-bit (`aarch64`)
* **Kernel Version**: `Linux rentool 6.12.25+rpt-rpi-2712 #1 SMP PREEMPT Debian 1:6.12.25-1+rpt1 (2025-04-30) aarch64`
* **Python Runtime**: Python 3.11.2 (system) + Python 3.11.2 (`/home/hi/drone-testbed/venv/`)
* **Core C Runtime**: `glibc 2.36`

---

## 6. Thermal & Power Characteristics

* **Idle Core Temperature**: `49.9°C` (measured via `vcgencmd measure_temp`)
* **Throttling State**: `0x0` (No undervoltage, no frequency capping, power delivery healthy)
* **Cooling Solution**: Passive heatsink with active thermal headroom up to 85°C.

---

## 7. Project Isolation Safeguards

To ensure complete safety for existing user projects (`hailo-task`, `owlvit-env`, WayVNC, OCR), the following operational rules are permanently enforced:
1. **Zero System Disruption**: No destructive formatting, no partition modifications, and no system-wide `dist-upgrade` commands.
2. **Dedicated Workspace**: All Phase 3 testbed code, models, and scripts reside exclusively in:
   ```text
   /home/hi/drone-testbed/
   ├── venv/             (Isolated Python 3.11 environment)
   ├── app/              (Phase 3 APCC runtime & DDIL queue)
   └── logs/             (Flight & AI telemetry logs)
   ```
3. **Dedicated Ports**:
   * Port `14550`: MAVLink GCS Primary
   * Port `14555`: SITL / Autopilot Bridge
   * Port `8088`: Tactical Glass Cockpit & Verification Telemetry
