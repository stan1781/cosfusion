# Repo Normalization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stabilize repository text-file normalization by adding `.gitattributes` and repair the top-level `README.md` so Chinese content is stored and displayed as UTF-8 correctly.

**Architecture:** Keep the change tightly scoped to repository metadata and documentation. Use `.gitattributes` to define default text normalization with LF endings and mark common binary assets as binary, then replace the corrupted `README.md` contents with a clean UTF-8 Chinese baseline that matches the project's current Phase 1 scope.

**Tech Stack:** Git attributes, Markdown, existing WordPress theme repository

---

### Task 1: Plan record

**Files:**
- Create: `docs/superpowers/plans/2026-05-19-repo-normalization.md`

- [ ] **Step 1: Save the plan document**

Create this file with the final agreed scope and execution steps.

### Task 2: Add repository line-ending rules

**Files:**
- Create: `.gitattributes`

- [ ] **Step 1: Add Git attributes**

Write a small attributes file that:
- sets `* text=auto eol=lf`
- marks common image/archive/font outputs as `binary`
- keeps the repository policy readable and minimal

- [ ] **Step 2: Review the file content**

Confirm the file contains only normalization rules needed for the current repository.

### Task 3: Repair README encoding and content

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Replace corrupted text with clean UTF-8 Chinese content**

Write a concise README that states:
- what TailwindScore is
- the current Phase 1 goals
- the basic local commands

- [ ] **Step 2: Review rendered text**

Open the file in the shell and confirm the Chinese text is no longer garbled.

### Task 4: Verify and commit

**Files:**
- Modify: `.gitattributes`
- Modify: `README.md`
- Create: `docs/superpowers/plans/2026-05-19-repo-normalization.md`

- [ ] **Step 1: Check git diff**

Run: `git diff -- .gitattributes README.md docs/superpowers/plans/2026-05-19-repo-normalization.md`

Expected: only the new attributes file, repaired README text, and the plan doc appear.

- [ ] **Step 2: Check git status**

Run: `git status --short`

Expected: exactly the three intended files are modified or added.

- [ ] **Step 3: Commit**

Run: `git add .gitattributes README.md docs/superpowers/plans/2026-05-19-repo-normalization.md`

Run: `git commit -m "规范仓库文本编码与行尾策略"`
