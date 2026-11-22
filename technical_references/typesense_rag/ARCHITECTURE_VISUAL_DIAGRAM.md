# Single Source of Truth - Visual Architecture

**Date**: February 13, 2026  
**Purpose**: Visual reference for canonical pose library architecture

---

## 🏗️ System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                   🏆 SINGLE SOURCE OF TRUTH                          │
│                   hatha/materials/poses/*.md                         │
│                   (64+ canonical pose files)                         │
│                                                                       │
│  ┌──────────────────┐              ┌──────────────────────┐         │
│  │  Simple Format   │              │ Comprehensive Format │         │
│  │  (80-150 lines)  │              │  (800-1,500 lines)   │         │
│  │                  │              │                      │         │
│  │  sukhasana.md    │              │  setu_bandha_...     │         │
│  │  tadasana.md     │              │  uttanasana_...      │         │
│  │  dandasana.md    │              │  utkatasana_...      │         │
│  └──────────────────┘              └──────────────────────┘         │
└─────────────────────────────────────────────────────────────────────┘
                              ▲
                              │
                              │ ALL FILES REFERENCE/IMPORT
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────┐   ┌──────────────────┐   ┌──────────────────┐
│ Weekly Notes  │   │ Cueing Scripts   │   │   Sequences      │
│               │   │                  │   │                  │
│ week06.md     │   │ session06_...md  │   │ hatha-seq-1.md   │
│               │   │                  │   │                  │
│ LINK pattern  │   │ INCLUDE pattern  │   │ REFERENCE +      │
│ ─────────     │   │ ───────────      │   │ CONTEXT          │
│               │   │                  │   │                  │
│ [Sukhasana]   │   │ {% raw %}{% include_... %}{% endraw %}│   │ [View Full...]   │
│ (...poses/)   │   │ + Session        │   │ + Flow context   │
│               │   │   Overlay 🎤     │   │                  │
└───────────────┘   └──────────────────┘   └──────────────────┘
        │                     │                     │
        │                     │                     │
        └─────────────────────┴─────────────────────┘
                              │
                              ▼
                    ┌─────────────────────┐
                    │  User Experience    │
                    │  ───────────────    │
                    │  • Clicks link      │
                    │  • Sees full pose   │
                    │  • One voice        │
                    │  • Always current   │
                    └─────────────────────┘
```

---

## 🔄 Data Flow Diagram

### Pattern 1: Weekly Notes → Canonical (LINK)

```
┌─────────────────────────────────────────────────────────────┐
│  certification/weekly-notes/week06_feb14.md                 │
│                                                              │
│  ## Class Notes                                             │
│                                                              │
│  1. [Sukhasana](../../hatha/materials/poses/sukhasana.md)   │
│     └──────────────────────┬────────────────────────┘       │
│                            │                                │
│                            │ User clicks link               │
│                            ▼                                │
│              ┌─────────────────────────────┐                │
│              │  hatha/materials/poses/     │                │
│              │  sukhasana.md               │                │
│              │                             │                │
│              │  # Sukhasana / Easy Seat    │                │
│              │                             │                │
│              │  ## Quick Reference         │                │
│              │  정렬 포인트:                 │                │
│              │  - 무릎이 엉덩이보다 낮게      │                │
│              │  - 척추 길게                 │                │
│              │  ...                        │                │
│              │                             │                │
│              │  ## 한국어 상세 설명          │                │
│              │  [Full details...]          │                │
│              │                             │                │
│              │  ## English Description     │                │
│              │  [Full details...]          │                │
│              └─────────────────────────────┘                │
│                                                              │
│  ✅ Benefits:                                                │
│  - Fast note-taking (just paste link)                       │
│  - No content duplication                                   │
│  - Click for full details when studying                     │
└─────────────────────────────────────────────────────────────┘
```

---

### Pattern 2: Cueing Scripts → Canonical (INCLUDE + OVERLAY)

```
┌──────────────────────────────────────────────────────────────────┐
│  certification/exam-prep/practical-cueing/                       │
│  session06_feb14_cueing_scripts.md                               │
│                                                                   │
│  ### 1. Sukhasana (Easy Seat)                                    │
│                                                                   │
│  > 📖 Canonical Source: _poses/sukhasana.md                      │
│                                                                   │
│  {% raw %}{% include pose.html id="sukhasana" %}{% endraw %}    │
│  └────────────────────┬────────────────────────────────┘         │
│                       │                                          │
│                       │ Jekyll renders full content inline       │
│                       ▼                                          │
│       ╔═══════════════════════════════════════╗                  │
│       ║  [Full canonical content embedded]   ║                  │
│       ║                                       ║                  │
│       ║  # Sukhasana / Easy Seat              ║                  │
│       ║  ## Quick Reference                   ║                  │
│       ║  정렬 포인트: ...                       ║                  │
│       ║  [All sections from canonical file]   ║                  │
│       ╚═══════════════════════════════════════╝                  │
│                                                                   │
│  ---                                                              │
│                                                                   │
│  #### 🎤 Session 6 Teaching Overlay                              │
│                                                                   │
│  **Context for This Session**:                                   │
│  - Introduces Spinal Angles observation                          │
│  - Foundation for neutral spine                                  │
│                                                                   │
│  **Korean Oral Cues**:                                           │
│  ```korean                                                       │
│  "편안하게 바닥에 앉아주세요"                                       │
│  "척추를 위로 쭉 세워주세요"                                       │
│  ```                                                             │
│                                                                   │
│  **Teaching Notes**:                                             │
│  - Timing: 5 minutes                                             │
│  - Watch for: Tight hips → offer blocks                          │
│                                                                   │
│  ✅ Benefits:                                                     │
│  - Reads as single seamless document                             │
│  - Canonical content always current                              │
│  - Session-specific notes clearly separated                      │
└──────────────────────────────────────────────────────────────────┘
```

---

### Pattern 3: Sequences → Canonical (REFERENCE + CONTEXT)

```
┌─────────────────────────────────────────────────────────────────┐
│  hatha/materials/sequences/session06-hatha-sequence-1.md        │
│                                                                  │
│  # Hatha Sequence 1 - Complete Practice                         │
│                                                                  │
│  ## Opening (10 minutes)                                        │
│                                                                  │
│  ### 1. Sukhasana (Easy Seat) - 5 min                           │
│  **[View Full Pose Details](../poses/sukhasana.md)**            │
│            └────────────┬─────────────┘                         │
│                         │                                       │
│                         │ Link to canonical                     │
│                         ▼                                       │
│         ┌───────────────────────────────┐                       │
│         │  hatha/materials/poses/       │                       │
│         │  sukhasana.md                 │                       │
│         │  [Full details available]     │                       │
│         └───────────────────────────────┘                       │
│                                                                  │
│  **Session 6 Context**:                                         │
│  - Introduce "Spinal Angles" concept here                       │
│  - Observe natural spinal curves                                │
│  - Foundation for understanding all poses                       │
│                                                                  │
│  **Cueing Notes**:                                              │
│  ```korean                                                      │
│  "척추의 자연스러운 곡선을 관찰해주세요..."                        │
│  ```                                                            │
│                                                                  │
│  **Transitions**: Sukhasana → hands/knees for Cat-Cow          │
│                                                                  │
│  ─────────────────────────────────────────────────────          │
│                                                                  │
│  ### 2. Marjaryasana (Cat-Cow) - 10 rounds                      │
│  **[View Full Pose Details](../poses/marjaryasana.md)**        │
│                                                                  │
│  **Session 6 Context**:                                         │
│  - Apply Spinal Angles 1 (flexion/extension)                   │
│  - Count aloud: "하나, 둘, 셋..."                                │
│                                                                  │
│  **Flow**: Inhale (Cow) → Exhale (Cat) → 10 rounds             │
│                                                                  │
│  [Continue with all 23 poses...]                                │
│                                                                  │
│  ✅ Benefits:                                                    │
│  - Shows sequence FLOW and ORDER                                │
│  - Link to canonical for detailed reference                     │
│  - Session context explains WHY this pose here                  │
│  - Reusable across multiple sessions                            │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🤖 RAG & AI Agent Integration

### Semantic Search Flow

```
┌──────────────────────────────────────────────────────────────────┐
│  User Query: "Show me alignment cues for bridge pose"           │
└──────────────────────┬───────────────────────────────────────────┘
                       │
                       ▼
          ┌────────────────────────────┐
          │  RAG Pipeline              │
          │  ───────────────           │
          │  1. Embed query            │
          │  2. Search vector DB       │
          │  3. Filter by priority     │
          └────────────┬───────────────┘
                       │
                       │ Priority 1: hatha/materials/poses/
                       │ (Canonical sources only)
                       ▼
          ┌────────────────────────────────────────────┐
          │  Vector Database                           │
          │  ─────────────────                         │
          │                                            │
          │  ❌ Skip: weekly notes (priority 2)        │
          │  ❌ Skip: cueing scripts (priority 2)      │
          │  ✅ Match: hatha/materials/poses/          │
          │           setu_bandha_sarvangasana_...md   │
          │           (priority 1)                     │
          └────────────┬───────────────────────────────┘
                       │
                       ▼
          ┌────────────────────────────────────────────┐
          │  Return Result                             │
          │  ─────────────                             │
          │                                            │
          │  Source: hatha/materials/poses/            │
          │          setu_bandha_sarvangasana_         │
          │          comprehensive.md                  │
          │                                            │
          │  Content:                                  │
          │  ───────                                   │
          │  ## Key Alignment Cues (Top 5)            │
          │  1. Feet hip-width, parallel              │
          │  2. Knees track over ankles               │
          │  3. Pelvis tilts posterior                │
          │  4. Shoulders ground, chest lifts         │
          │  5. Neck neutral (slight tuck)            │
          │                                            │
          │  Context:                                  │
          │  - Book 2, Pages 187-189                  │
          │  - First taught: Session 1                │
          │  - Reviewed: Session 6                    │
          │                                            │
          │  Related:                                  │
          │  - Prep poses: Cat-Cow, Cobra             │
          │  - Counter: Knees to Chest                │
          └────────────────────────────────────────────┘
                       │
                       ▼
          ┌────────────────────────────┐
          │  User Sees:                │
          │  ─────────                 │
          │  ✅ ONE authoritative      │
          │     answer                 │
          │  ✅ Consistent with all    │
          │     study materials        │
          │  ✅ Complete reference     │
          │     (book pages, sessions) │
          └────────────────────────────┘

✅ No duplicate results
✅ No conflicting information
✅ Fastest query time (direct to source)
```

---

### Story-Telling Agent Workflow

```
┌──────────────────────────────────────────────────────────────────┐
│  User Request: "Create a 60-min beginner backbend class"        │
└──────────────────────┬───────────────────────────────────────────┘
                       │
                       ▼
          ┌────────────────────────────┐
          │  Agent: Query Canonical    │
          │  ──────────────────────    │
          │                            │
          │  Search for:               │
          │  - category: backbend      │
          │  - difficulty: beginner    │
          │  - in: hatha/materials/    │
          │        poses/              │
          └────────────┬───────────────┘
                       │
                       ▼
          ┌────────────────────────────────────────┐
          │  Found Poses:                          │
          │  ────────────                          │
          │                                        │
          │  1. bhujangasana.md (Cobra)            │
          │     - difficulty: beginner             │
          │     - prep: Cat-Cow, Down Dog          │
          │     - counter: Child's Pose            │
          │                                        │
          │  2. setu_bandha_sarvangasana_...md     │
          │     - difficulty: beginner-inter       │
          │     - prep: Cobra, Locust              │
          │     - counter: Knees to Chest          │
          │                                        │
          │  3. salabhasana.md (Locust)            │
          │     - difficulty: intermediate         │
          │     - prep: Cobra variations           │
          │     - counter: Child's Pose            │
          └────────────┬───────────────────────────┘
                       │
                       ▼
          ┌────────────────────────────────────────┐
          │  Agent: Build Sequence                 │
          │  ─────────────────────                 │
          │                                        │
          │  Opening (10 min):                     │
          │  - Sukhasana (centering)               │
          │  - Cat-Cow (spinal prep)               │
          │  - Child's Pose (rest)                 │
          │                                        │
          │  Warm-up (15 min):                     │
          │  - Down Dog                            │
          │  - Cobra prep variations               │
          │  - Locust prep                         │
          │                                        │
          │  Main Practice (25 min):               │
          │  - Cobra (3 rounds)                    │
          │  - Bridge (2 rounds, hold)             │
          │  - Locust (2 rounds)                   │
          │  - Bridge (final hold)                 │
          │                                        │
          │  Cool-down (10 min):                   │
          │  - Child's Pose                        │
          │  - Supine Twist                        │
          │  - Savasana                            │
          └────────────┬───────────────────────────┘
                       │
                       ▼
          ┌────────────────────────────────────────┐
          │  Agent: Generate Teaching Cues         │
          │  ─────────────────────────────         │
          │                                        │
          │  Pull from canonical sources:          │
          │                                        │
          │  For Cobra:                            │
          │  - Korean: 티칭 큐 section              │
          │  - English: Teaching Cues section      │
          │                                        │
          │  For Bridge:                           │
          │  - Korean: 티칭 큐 section              │
          │  - English: Teaching Cues section      │
          │                                        │
          │  ✅ All cues from ONE source           │
          │  ✅ Consistent voice throughout        │
          └────────────┬───────────────────────────┘
                       │
                       ▼
          ┌────────────────────────────────────────┐
          │  Output: New Sequence File             │
          │  ─────────────────────────             │
          │                                        │
          │  File: sequences/                      │
          │        beginner-backbend-class.md      │
          │                                        │
          │  Content:                              │
          │  - References canonical poses          │
          │  - Adds narrative flow                 │
          │  - Includes timing/transitions         │
          │  - NO duplicate pose content           │
          │                                        │
          │  User can:                             │
          │  - Click pose links for details        │
          │  - Follow flow as written              │
          │  - Trust consistency (one source)      │
          └────────────────────────────────────────┘

✅ Agent reads from single canonical source
✅ Generated content references (not duplicates)
✅ Teaching voice consistent with all materials
```

---

## 📊 Before vs. After Comparison

### ❌ BEFORE (Multiple Sources)

```
certification/cueing-repository/poses/standing/tadasana.md
├── Alignment: "Feet together"
├── Benefits: "Improves posture"
└── Modifications: "Use wall for balance"

hatha/materials/poses/tadasana.md
├── Alignment: "Feet hip-width OR together"
├── Benefits: "Improves posture, strengthens legs"
└── Modifications: "Use wall OR blocks under feet"

session03_cueing_scripts.md (inline content)
├── Alignment: "Feet parallel, hip-distance"
├── Benefits: "Grounds and centers"
└── Modifications: "Wall support"

┌─────────────────────────────────────┐
│  ❌ Problem: Which is correct?      │
│  ❌ RAG: Returns 3 different answers│
│  ❌ Student: Confused by conflicts  │
│  ❌ Maintenance: Update 3 places    │
└─────────────────────────────────────┘
```

---

### ✅ AFTER (Single Source)

```
hatha/materials/poses/tadasana.md  ← 🏆 ONLY SOURCE
├── Alignment: "Feet hip-width OR together (specify)"
├── Benefits: "Improves posture, strengthens legs, grounds"
└── Modifications: "Wall support OR blocks under feet"
    │
    │ ALL FILES REFERENCE THIS
    │
    ├─→ certification/weekly-notes/week06.md
    │   [Tadasana](../../hatha/materials/poses/tadasana.md)
    │
   ├─→ certification/exam-prep/.../session06_cueing_scripts.md
   │   {% raw %}{% include_relative .../tadasana.md %}{% endraw %}
    │   + Session 6 overlay: "Today we use hip-width"
    │
    └─→ hatha/materials/sequences/hatha-seq-1.md
        [View Full Pose](../poses/tadasana.md)
        Session context: "Establishes neutral standing"

┌─────────────────────────────────────┐
│  ✅ Solution: ONE authoritative     │
│  ✅ RAG: Returns ONE answer         │
│  ✅ Student: Clear, consistent info │
│  ✅ Maintenance: Update ONCE        │
└─────────────────────────────────────┘
```

---

## 🎯 Key Principles Visualized

### Principle 1: Single Source of Truth

```
              🏆 CANONICAL
         hatha/materials/poses/
                  │
       ┌──────────┼──────────┐
       │          │          │
       ▼          ▼          ▼
   Weekly     Cueing    Sequences
   Notes     Scripts
   
   LINK      INCLUDE   REFERENCE
```

### Principle 2: Reference, Don't Duplicate

```
❌ WRONG:
   session06.md contains full pose description (1,000 lines)
   + sukhasana.md contains same description (1,000 lines)
   = 2,000 lines, sync burden, conflicts

✅ RIGHT:
   session06.md: {% raw %}{% include sukhasana.md %}{% endraw %} (1 line)
   + sukhasana.md: full description (1,000 lines)
   = 1,001 lines, no sync, no conflicts
```

### Principle 3: Canonical + Overlay

```
┌──────────────────────────────────┐
│  Canonical Content               │  ← Universal, stable
│  ─────────────────               │
│  - Pose name (all languages)     │
│  - Alignment cues                │
│  - Benefits                      │
│  - Modifications                 │
│  - Contraindications             │
│  - Book references               │
└──────────────────────────────────┘
              +
┌──────────────────────────────────┐
│  Session Overlay                 │  ← Context-specific
│  ───────────────                 │
│  - Why this pose today           │
│  - Oral cues for this class      │
│  - Student observations          │
│  - Timing for this sequence      │
│  - Props used this session       │
└──────────────────────────────────┘
              =
┌──────────────────────────────────┐
│  Complete Teaching Script        │  ← Best of both
│  ────────────────────────        │
│  - Authoritative content         │
│  - Session-specific guidance     │
│  - Always current                │
│  - Exam-ready                    │
└──────────────────────────────────┘
```

---

## 🚀 Migration Path

### Phase 1: Identify (This Week)

```
Current State:
├── hatha/materials/poses/         ← 64 files (canonical)
├── certification/cueing-repo/     ← ??? files (duplicates?)
└── session*/cueing_scripts.md     ← inline content (duplicates?)

Action:
1. find . -name "*asana*.md" > all_pose_files.txt
2. Compare content
3. Mark duplicates
```

### Phase 2: Consolidate (Feb 15-20)

```
For each duplicate:
1. Compare content quality
2. Merge best content → canonical
3. Update references → link to canonical
4. Delete duplicate OR mark deprecated
```

### Phase 3: Validate (Before Session 10)

```
Tests:
├── All links resolve ✅
├── RAG returns canonical only ✅
├── Semantic search no duplicates ✅
└── Agent queries consistent ✅
```

---

**Status**: 🎯 Architecture Documented  
**Next**: Use in Session 6 tomorrow (Feb 14)  
**Review**: Feb 15 - validate real-world usage

**See Also**:
- Full details: `/SINGLE_SOURCE_TRUTH_ARCHITECTURE.md`
- Implementation: `/SINGLE_SOURCE_IMPLEMENTATION_SUMMARY.md`
- Quick start: `/SESSION06_QUICK_REFERENCE.md`
