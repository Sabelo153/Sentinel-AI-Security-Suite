# GitHub Setup - Step-by-Step Visual Guide

> Follow these exact steps to create your GitHub repository and project board

---

## 📋 Pre-Setup Checklist

- [ ] Your GitHub username: `Sabelo153`
- [ ] GitHub account logged in: https://github.com/login
- [ ] Repository should be: Private
- [ ] You have the project files ready locally at: `C:\Development\Sentinel-AI-Security-Suite\`

---

## 🚀 Step 1: Create GitHub Repository

### 1.1 - Go to Create New Repository

1. Go to: https://github.com/new
2. Should show "Create a new repository" form

### 1.2 - Fill in Repository Details

```
┌─────────────────────────────────────────────────────────────┐
│ Create a new repository                                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Repository name:     │Sentinel-AI-Security-Suite           │
│                                                              │
│ Description:         │Professional Security Operations    │
│                      │Intelligence Platform (optional)    │
│                                                              │
│ Visibility:          ⦿ Private                              │
│                      ○ Public                               │
│                                                              │
│ ☐ Initialize with:                                          │
│   ☐ Add a README file                                       │
│   ☐ Add .gitignore                                          │
│   ☐ Choose a license                                        │
│                                                              │
│                        [Create repository]                  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

**IMPORTANT**: Do NOT check any of the "Initialize with" boxes. We have files already.

### 1.3 - Click "Create repository"

After clicking, you'll see:

```
┌─────────────────────────────────────────────────────────────┐
│ Quick setup — if you've done this kind of thing before     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ ...or push an existing repository from the command line     │
│                                                              │
│ git remote add origin                                       │
│ https://github.com/Sabelo153/Sentinel-AI-Security-Suite.git │
│ git branch -M main                                          │
│ git push -u origin main                                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

Copy the URL: `https://github.com/Sabelo153/Sentinel-AI-Security-Suite.git`

---

## 🔗 Step 2: Connect Local Git to GitHub

### 2.1 - Open PowerShell and Navigate

```powershell
cd "C:\Development\Sentinel-AI-Security-Suite"
```

### 2.2 - Add GitHub as Remote

```powershell
git remote add origin https://github.com/Sabelo153/Sentinel-AI-Security-Suite.git
```

### 2.3 - Verify Remote is Added

```powershell
git remote -v
```

**Expected output**:
```
origin  https://github.com/Sabelo153/Sentinel-AI-Security-Suite.git (fetch)
origin  https://github.com/Sabelo153/Sentinel-AI-Security-Suite.git (push)
```

### 2.4 - Push Both Branches

```powershell
git push -u origin master
git push -u origin develop
```

You might be asked for GitHub credentials. Provide them.

---

## ⚙️ Step 3: Configure GitHub Settings

### 3.1 - Go to Repository Settings

1. Go to: https://github.com/Sabelo153/Sentinel-AI-Security-Suite
2. Click **Settings** tab (at the top)
3. In left menu, click **Branches**

### 3.2 - Set Default Branch

```
┌─────────────────────────────────────────────────────────────┐
│ Branches                                                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Default branch                                              │
│                                                              │
│ The default branch is where new pull requests and code     │
│ commits are automatically made.                            │
│                                                              │
│ [master ▼] → Change to [develop ▼]                         │
│                                                              │
│ Click switch, select develop, confirm                       │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 3.3 - Add Branch Protection for `develop`

1. Click **Add rule** button
2. Branch name pattern: `develop`
3. Check these boxes:
   - ✅ Require pull request reviews before merging
   - ✅ Require status checks to pass before merging
   - ✅ Require branches to be up to date before merging
4. Click **Create**

```
┌─────────────────────────────────────────────────────────────┐
│ Protect matching branches                                   │
├─────────────────────────────────────────────────────────────┤
│ Branch name pattern: develop                                │
│                                                              │
│ ☑ Require pull request reviews before merging               │
│    Required approving reviews: 1                            │
│    Dismiss stale pull request approvals: unchecked          │
│                                                              │
│ ☑ Require status checks to pass before merging              │
│    Status checks that must pass:                            │
│    (none required yet - we'll add these later)              │
│                                                              │
│ ☑ Require branches to be up to date before merging          │
│                                                              │
│ ☐ Require conversations resolved before merging             │
│                                                              │
│                          [Create]                           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 3.4 - Add Branch Protection for `master`

Repeat above but:
- Branch name pattern: `master`
- Same settings as `develop`
- This is your production branch, so it should be extra protected

---

## 🎯 Step 4: Create Project Board

### 4.1 - Go to Projects

1. Go to: https://github.com/Sabelo153/Sentinel-AI-Security-Suite
2. Click **Projects** tab (top menu)
3. Click **New project** button

### 4.2 - Create New Project

```
┌─────────────────────────────────────────────────────────────┐
│ Create a project                                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Project board name:                                         │
│ │Sentinel AI - Development Roadmap                         │
│                                                              │
│ Description:                                                │
│ │4-Sprint development plan for v1.0 release               │
│                                                              │
│ Template:                                                   │
│ ⦿ Table (recommended for task management)                  │
│ ○ Kanban                                                   │
│ ○ Spreadsheet                                              │
│                                                              │
│                        [Create project]                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 4.3 - After Creation

You'll see an empty project board. Add columns by clicking **+ Add column**:

```
Backlog | To Do | In Progress | In Review | Done
```

---

## 📌 Step 5: Add Sprints as Epics

### 5.1 - In "Backlog" Column, Add Epics

For each epic, click "Add item" and create:

#### Epic 1 - Sprint 1: Foundation

```
Title: Sprint 1 - Foundation (v1.0 Alpha)
Body:
Build core infrastructure and authentication

Includes:
- Database schema (40+ tables)
- REST API foundation
- JWT authentication
- RBAC system
- Company/Site/User management

Duration: 2 weeks
Status: To Do
Priority: Critical
```

#### Epic 2 - Sprint 2: Workforce

```
Title: Sprint 2 - Workforce Management

Includes:
- Employee profiles
- Attendance system
- Shift management
- Leave management

Duration: 2 weeks
Dependencies: Sprint 1
```

#### Epic 3 - Sprint 3: Security Operations

```
Title: Sprint 3 - Security Operations

Includes:
- Incident management
- Occurrence book
- Visitor management
- Patrol management
- Vehicle management

Duration: 2 weeks
Dependencies: Sprint 1-2
```

#### Epic 4 - Sprint 4: AI & Intelligence

```
Title: Sprint 4 - AI & Intelligence Engine

Includes:
- AI Report Writer
- Risk Assessment
- Recommendations
- Analytics Dashboard

Duration: 2 weeks
Dependencies: Sprint 1-3
```

---

## ✅ Step 6: Add Sprint 1 Tasks

### 6.1 - Click on "Sprint 1 - Foundation" Epic

Click to expand and see child tasks area.

### 6.2 - Add Tasks to "To Do" Column

Move these tasks to the "To Do" column:

```
Database Tasks:
  - [ ] Create Database Schema (ERD) - 8 hrs
  - [ ] Create Database Migrations - 6 hrs
  - [ ] Create Database Seeds - 3 hrs

API Tasks:
  - [ ] Set Up API Project Structure - 4 hrs
  - [ ] Implement Core Middleware - 3 hrs

Authentication Tasks:
  - [ ] Implement JWT Authentication - 6 hrs
  - [ ] Implement RBAC System - 5 hrs

Features:
  - [ ] Implement Company Management - 4 hrs
  - [ ] Implement Site Management - 4 hrs
  - [ ] Implement User Management - 5 hrs

Documentation:
  - [ ] Create API Documentation - 3 hrs
```

---

## 🔄 Step 7: Workflow Rules

### When Starting a Task

1. Click task, move to "In Progress"
2. Create branch: `git checkout -b feature/task-name`
3. Make commits
4. Push to GitHub
5. Create PR

### When Task Complete

1. Create PR on GitHub
2. Move task to "In Review"
3. Review and merge PR
4. Move task to "Done"

---

## ✨ Verification Checklist

After all steps, verify:

```
[ ] GitHub repository created
    URL: https://github.com/Sabelo153/Sentinel-AI-Security-Suite

[ ] Local connected to GitHub
    Command: git remote -v shows origin

[ ] Both branches pushed
    Command: git branch -r shows origin/master and origin/develop

[ ] Default branch set to develop
    GitHub Settings confirmed

[ ] Branch protection enabled
    For both master and develop

[ ] Project board created
    With 5 columns: Backlog, To Do, In Progress, In Review, Done

[ ] 4 Epics added
    Sprint 1, 2, 3, 4

[ ] Sprint 1 tasks added to "To Do"
    ~11 tasks visible
```

---

## 🚀 Once Complete

When you've completed all steps:

1. ✅ Reply that GitHub is set up
2. ✅ We'll start **Phase 1: Database Design**
3. ✅ You'll move "Create Database Schema" to "In Progress"
4. ✅ We build the ERD together
5. ✅ Commit and push as you work

---

## 🆘 Troubleshooting

### If you get "Repository already exists"

You might have accidentally created it twice. Check:
```powershell
git remote -v
```

If it shows multiple origins, fix with:
```powershell
git remote remove origin
git remote add origin https://github.com/Sabelo153/Sentinel-AI-Security-Suite.git
```

### If branches won't push

Try:
```powershell
git push --all -u origin
```

### If you can't see the project board

Refresh GitHub page, or go directly to:
https://github.com/Sabelo153/Sentinel-AI-Security-Suite/projects

---

**Follow these steps exactly, then let me know when you're done! 🎯**

**Next Step**: Ready for Phase 1 coding
