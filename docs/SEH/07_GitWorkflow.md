# Sentinel Engineering Handbook

## 07. Git Workflow

### Branching
- main: production-ready releases
- develop: integration branch
- feature/*: new feature work
- bugfix/*: bug fixes
- hotfix/*: urgent production repairs
- release/*: release preparation

### Commit Format
Use conventional commits:
- feat
- fix
- refactor
- docs
- test
- chore

Examples:
- feat(auth): add JWT login service
- fix(company): correct site validation
- docs(seh): add API standards

### Pull Request Rules
- One concern per PR.
- Include summary, risk, and test evidence.
- Update docs when behavior changes.
- Do not merge failing checks.

### Delivery Workflow
Requirements -> Design -> Database -> Backend -> API -> Frontend -> Android -> Testing -> Documentation -> Merge
