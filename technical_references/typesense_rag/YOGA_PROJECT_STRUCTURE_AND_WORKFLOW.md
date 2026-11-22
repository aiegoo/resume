# Yoga Repository Structure & Workflow Documentation

**Date**: January 11, 2026  
**Repository**: aiegoo/yoga  
**Purpose**: Comprehensive bilingual (EN/KR) yoga pose documentation with source citations

---

## 📋 Executive Summary

### Project Overview
Creating a comprehensive library of yoga pose guides with:
- **Bilingual content**: English + Korean (한국어)
- **Source-verified**: Citations from HathaYoga.pdf with page numbers
- **Multi-level**: Beginner, Intermediate, Advanced variations
- **Teaching-focused**: Alignment cues, safety, modifications
- **AI-enhanced**: Chatbot prompts + image generation prompts included

### Current Progress
- **4 poses completed** on master branch (out of 120+):
  1. ✅ Upavistha Konasana (Wide-Angle Seated Forward Bend)
  2. ✅ Paschimottanasana (Seated Forward Bend)
  3. ✅ Trikonasana (Triangle Pose)
  4. ✅ Bhujangasana (Cobra Pose) - 1,402 lines, 7 citations

- **Citation Coverage**: 54% (53/98 curriculum poses linked to source)

### Priority Next Targets (by citation count)
1. **Pawanmuktasana** (Wind-Relieving Pose) - 6 citations
2. **Vajrasana** (Thunderbolt Pose) - 5 citations
3. **Baddha Konasana** (Butterfly) - 4 citations
4. **Salabhasana** (Locust Pose) - sequencing complement to Bhujangasana

---

## 🌿 Repository Structure

### Branch Strategy

#### **`master` Branch** - Content Creation
```
aiegoo/yoga/
├── README.md
├── hatha/
│   ├── materials/
│   │   ├── hatha_pose_curriculum.md          # Master checklist (120+ poses)
│   │   └── poses/
│   │       ├── bhujangasana.md              # ✅ 1,402 lines, 7 citations
│   │       ├── paschimottanasana.md         # ✅ 860+ lines, 17 citations
│   │       ├── trikonasana.md               # ✅ 1,083 lines, 10 citations
│   │       ├── upavistha_konasana.md        # ✅ 712 lines
│   │       └── images/                       # Pose illustration images
│   ├── levels/
│   ├── sessions/
│   └── music/
├── ashtanga/
├── iyengar/
├── kundalini/
├── vinyasa/
├── yin/
├── restorative/
├── anatomy-focused/
├── props-yoga/
└── references/
    ├── HathaYoga.pdf                        # Source material
    └── (other reference materials)
```

#### **`books` Branch** - Research & OCR Data
```
aiegoo/yoga/ (books branch)
├── references/
│   └── hatha/
│       ├── HathaYoga.pdf                    # 📖 Source PDF
│       ├── ocr-output/
│       │   └── HathaYoga_data.json         # 🔍 786MB OCR data (gitignored)
│       ├── citations/
│       │   ├── pose_sourcebook.json         # 📊 Citation database
│       │   └── pose_sourcebook.md           # Human-readable citations
│       └── vectordb/
│           └── vectordb_chunks.jsonl        # 🔎 Vector embeddings for search
└── tools/
    ├── README.md                            # OCR processing guide
    └── format_conversion_guide.md           # Data transformation docs
```

---

## 📝 Documentation Template Structure

### File Format: `{pose_name}.md`
**Location**: `hatha/materials/poses/{pose_name}.md`  
**Length**: 800-1,100 lines  
**Language**: Bilingual (English ~50%, Korean ~45%, Citations ~5%)

### Template Sections (15 Main + 2 AI Sections)

#### 📊 **1. Page References Summary Table** (NEW)
```markdown
| Page | Content Type | Description |
|------|-------------|-------------|
| 96   | Main Instruction | Complete pose instructions, benefits |
| 240  | Variation | Extended variation details |
```

#### 🇬🇧 **English Version Sections (2-15)**
1. **Sanskrit Name** - Pronunciation, etymology, meaning
2. **Classification** - Type, level, duration, focus
3. **Physical Benefits** - Muscular/skeletal, internal systems, therapeutic
4. **Contraindications & Cautions** - Safety warnings, modifications
5. **Step-by-Step Instructions** - Foundation, breath, movement
6. **Variations by Level** - Beginner, Intermediate, Advanced options
7. **Alignment Principles** - ✓ Dos and ✗ Don'ts with visual cues
8. **Breath Integration** - Ujjayi, breath ratio, mental focus
9. **Props & Modifications** - Blocks, straps, walls, chairs
10. **Common Misalignments & Corrections** - Issue → Cue → Correction
11. **Sequencing Suggestions** - Prep poses, counter poses, complements
12. **Teaching Tips & Verbal Cues** - Effective instructor language
13. **Energy & Mindfulness** - Chakra, mental/emotional benefits
14. **Practice Variations** - Dynamic, restorative, vinyasa, pranayama
15. **Source References** - HathaYoga.pdf citations with page numbers and quotes

#### 🇰🇷 **Korean Version (한국어 버전)**
- Mirrors all English sections
- Maintains technical accuracy
- Cultural appropriateness in translation
- Parallel section organization

#### 🤖 **AI Assistant Prompts** (NEW)
1. **Chatbot Conversation Prompt**
   - Ready-to-use Q&A assistant prompt
   - Includes pose context, safety, modifications
   - Teaching guidance with compassionate tone

2. **Image Generation AI Prompt**
   - 8-step sequence instructions
   - Minimalist geometric line drawing style
   - Alignment markers and visual cues
   - Common mistake corrections

#### 📌 **Footer**
```markdown
**Created by**: AI Yoga Guru
**Date**: 2026년 1월 11일
**Source References**: HathaYoga.pdf (Pages X, Y, Z), N citations verified
**Next in Sequence**: [Next Pose Name]
```

---

## 🔄 Workflow: Creating New Pose Guide

### Phase 1: Research (on `books` branch)

#### Step 1.1: Identify Next Pose
```bash
cd /Users/a2485/repos/aiegoo/yoga
git checkout books

# Check citation counts
grep -E '"pose_name"|"citation_count"' references/hatha/citations/pose_sourcebook.json | head -60
```

**Prioritization criteria:**
- ✅ High citation count (7+ pages ideal)
- ✅ Fundamental importance in sequence
- ✅ Variation diversity in source material

#### Step 1.2: Extract Page References
```python
import json

search_term = 'pawanmuktasana'  # lowercase pose name
with open('references/hatha/ocr-output/HathaYoga_data.json', 'r') as f:
    data = json.load(f)

pages_found = []
for page in data:
    page_num = page.get('page_number')
    text = page.get('text', '').lower()
    if search_term in text:
        pages_found.append({
            'page': page_num,
            'excerpt': page.get('text', '')[:200]
        })
        print(f"Page {page_num}: {page.get('text', '')[:200]}...")
```

#### Step 1.3: Document Findings
- 📄 Main instruction page
- 🔄 Variation pages
- 📊 Sequencing examples
- 📇 Index references
- 💡 Key quotes for citations

### Phase 2: Content Creation (on `master` branch)

#### Step 2.1: Switch to Master Branch
```bash
git checkout master
```

#### Step 2.2: Create Pose Guide
**File**: `hatha/materials/poses/{pose_name}.md`

**Use bhujangasana.md as reference template:**
- Copy structure from completed poses
- Follow 15-section template + AI prompts
- Maintain bilingual parallel structure
- Include page reference summary table at top

#### Step 2.3: Write Comprehensive Content
- **English sections**: Clear, instructional, safety-first
- **Korean translation**: Culturally appropriate, technically accurate
- **Source citations**: Direct quotes, exact page numbers
- **AI prompts**: Chatbot context + image generation specs

**Quality standards:**
- ✅ 800-1,100 lines total
- ✅ 3+ level variations (beginner/intermediate/advanced)
- ✅ Detailed alignment with visual cues (✓ and ✗)
- ✅ Common misalignments addressed
- ✅ Safety contraindications listed
- ✅ Teaching cues for instructors
- ✅ Chakra/energetic associations
- ✅ 7+ source citations minimum

### Phase 3: Curriculum Update

#### Step 3.1: Update Checklist
**File**: `hatha/materials/hatha_pose_curriculum.md`

Change from:
```markdown
- [ ] **Bhujangasana** (Cobra Pose) - 코브라 자세
```

To:
```markdown
- [x] **Bhujangasana** (Cobra Pose) ✓ - 코브라 자세
```

### Phase 4: Version Control

#### Step 4.1: Stage Files
```bash
git add hatha/materials/poses/{pose_name}.md
git add hatha/materials/hatha_pose_curriculum.md
```

#### Step 4.2: Commit with Descriptive Message
```bash
git commit -m "Add {Pose Name} comprehensive bilingual guide with N source citations"
```

**Example:**
```bash
git commit -m "Add Bhujangasana comprehensive bilingual guide with 7 source citations"
```

#### Step 4.3: Push to Remote
```bash
git push origin master
```

---

## 📚 Citation System

### Source Material
- **Primary Source**: HathaYoga.pdf (located in `references/`)
- **OCR Data**: HathaYoga_data.json (786MB, on `books` branch)
- **Citation Database**: pose_sourcebook.json
- **Vector Database**: vectordb_chunks.jsonl (for semantic search)

### Citation Format in Pose Guides

```markdown
## Source References

**Primary Source**: HathaYoga.pdf
**Key Pages for Further Reading**:

### Main Instruction & Benefits
- **Page 96**: Complete pose instruction with detailed benefits
  - *"Direct quote from PDF showing key teaching point..."*
  - Manipura Chakra (Solar Plexus) stimulation
  - Digestive organ benefits
  - Hold time: 30-60 seconds recommended

### Variations & Advanced Forms
- **Page 240**: Vistrit Bhujangasana (Extended Cobra Pose)
  - Legs hip-width or shoulder-width apart
  - Full instruction for extended variation

### Sequencing Context
- Listed in "Asanas of the Basic Sequence" table
- Recommended timing: 30-60 seconds

**Section Type**: Prone Backbends, Manipura Chakra (Solar Plexus)
**OCR Confidence**: 92% average

> *"Key quote summarizing essence of the pose from source material"*
> 
> — HathaYoga.pdf, Page 96

*(Source: HathaYoga.pdf OCR extraction, 7 citations verified)*
```

### Citation Quality Metrics
- ✅ **Direct quotes** from source material
- ✅ **Exact page numbers** for verification
- ✅ **OCR confidence scores** documented
- ✅ **Cross-references** to index pages
- ✅ **Minimum 7 citations** for depth

---

## 🎯 Next Poses Priority List

### High Citation Count (Ready to Document)
1. **Pawanmuktasana** (Wind-Relieving Pose) - 6 citations
   - Supine pose, digestive benefits
   - Apanasana variation
   
2. **Vajrasana** (Thunderbolt Pose) - 5 citations
   - Seated foundation pose
   - Meditation position
   
3. **Baddha Konasana** (Bound Angle / Butterfly) - 4 citations
   - Hip opener
   - Grounding pose

### Sequencing Complements (Backbend Series)
Following Bhujangasana (Cobra):
- **Salabhasana** (Locust Pose) - next in traditional sequence
- **Dhanurasana** (Bow Pose) - progression from Cobra
- **Setu Bandha Sarvangasana** (Bridge Pose) - gentle backbend

### Foundation Poses (High Priority)
- **Tadasana** (Mountain Pose) - standing foundation
- **Balasana** (Child's Pose) - restorative essential
- **Adho Mukha Svanasana** (Downward Dog) - vinyasa staple

---

## 🛠️ Tools & Resources

### Research Tools (books branch)
- **PDF Viewer**: Open `references/HathaYoga.pdf`
- **JSON Parser**: Read `ocr-output/HathaYoga_data.json`
- **Citation Database**: `citations/pose_sourcebook.json`
- **Vector Search**: `vectordb/vectordb_chunks.jsonl`

### Content Creation Tools (master branch)
- **Reference Templates**: Completed pose guides (bhujangasana.md, etc.)
- **Curriculum Checklist**: `hatha_pose_curriculum.md`
- **Style Guide**: GitHub Issue #2 workflow documentation

### Version Control
```bash
# Check current branch
git branch -v

# Switch branches
git checkout books     # Research
git checkout master    # Content creation

# View commit history
git log --oneline -10

# Check file differences
git diff master books -- hatha/materials/poses/
```

---

## 📊 Current Status Dashboard

### Completion Metrics
- **Total Poses in Curriculum**: 120+
- **Completed Guides**: 4
- **Completion Rate**: ~3%
- **Average Guide Length**: 900 lines
- **Average Citations per Pose**: 10

### Quality Metrics
- **Bilingual Coverage**: 100% (all completed guides)
- **Citation Verification**: 100% (all page numbers verified)
- **Variation Levels**: 3 per pose (beginner/intermediate/advanced)
- **AI Prompts Included**: 100% (chatbot + image generation)

### Repository Stats
- **Master Branch**: Latest commit "Add page reference tables and AI prompts to all pose guides"
- **Books Branch**: Contains 786MB OCR data (gitignored from master)
- **Total Repository Size**: ~800MB (with books branch)

---

## 💡 Workflow Tips

### Efficiency
- ✅ Research all page references before writing
- ✅ Use parallel structure for English/Korean sections
- ✅ Keep Sanskrit → English → Korean consistent
- ✅ Reference previous completed poses for format consistency
- ✅ Copy-paste template sections, then customize

### Quality Control
- ✅ Verify page numbers match OCR extraction
- ✅ Check Korean translations for cultural appropriateness
- ✅ Ensure alignment cues are clear and actionable
- ✅ Test that markdown formatting renders correctly
- ✅ Review AI prompts for accuracy and usefulness

### Knowledge Transfer (from Mac Copilot)
According to issue #2, the workflow was established through:
1. Initial template creation (Upavistha Konasana)
2. Citation system refinement (Paschimottanasana - 17 citations)
3. Standing pose template (Trikonasana - 1,083 lines)
4. Addition of summary tables and AI prompts (Bhujangasana)

**Human Feedback Loop:**
- Create guide → await feedback → refine → commit
- Document user corrections for future consistency
- Build knowledge base of preferred translations
- Iterate on template based on user preferences

---

## 📖 GitHub Issues Reference

### Issue #2: "Comprehensive Pose Documentation Workflow & Template"
- **Status**: OPEN
- **Created**: 2026-01-10
- **Updated**: 2026-01-11
- **Comment**: Added summary table + AI prompts sections

**Key additions from comment:**
1. **Page reference summary table** at top of each guide
2. **AI Assistant Prompts** section at bottom:
   - Chatbot conversation prompt (Q&A assistant)
   - Image generation prompt (8-step sequence, minimalist style)

---

## 🎨 Example: Bhujangasana Structure

**File**: `hatha/materials/poses/bhujangasana.md`  
**Length**: 1,402 lines  
**Citations**: 7 pages from HathaYoga.pdf

### Structure Breakdown
```
Lines 1-28:    Page References Summary Table
Lines 29-700:  English Version (15 sections)
Lines 701-1300: Korean Version (한국어 버전)
Lines 1301-1390: AI Assistant Prompts
Lines 1391-1402: Footer with metadata
```

### Citation Distribution
- Page 96: Main instruction (Manipura Chakra focus)
- Page 240: Vistrit Bhujangasana (Extended Cobra)
- Page 241: Raj Bhujangasana (King Cobra)
- Pages 94, 98, 100: Related poses (sequencing)
- Page 7: Index reference

---

## 🚀 Getting Started (Quick Reference)

### For New Pose Creation
```bash
# 1. Research phase
cd /home/aiegoo/repos/aiegoo/yoga
git checkout books
# Review: references/hatha/citations/pose_sourcebook.json

# 2. Content creation
git checkout master
# Create: hatha/materials/poses/{new_pose}.md
# Use bhujangasana.md as template

# 3. Update curriculum
# Edit: hatha/materials/hatha_pose_curriculum.md
# Mark as [x] completed

# 4. Commit
git add hatha/materials/poses/{new_pose}.md
git add hatha/materials/hatha_pose_curriculum.md
git commit -m "Add {Pose Name} comprehensive bilingual guide with N source citations"
git push origin master
```

### Key Files to Reference
1. **Template**: `bhujangasana.md` (most recent, includes all features)
2. **Curriculum**: `hatha_pose_curriculum.md` (checklist and next targets)
3. **Workflow**: GitHub Issue #2 (comprehensive process documentation)

---

**Document Maintained by**: AI Assistant  
**Last Updated**: January 11, 2026  
**Repository**: github.com/aiegoo/yoga  
**Branches**: master (content) + books (research)
