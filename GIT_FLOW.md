# Git Flow Branching Strategy

This project follows **Git Flow** for version control and release management.

## Branch Types

### Main Branches (Long-lived)

#### `main`
- **Purpose**: Production-ready code only
- **Source**: Merges from `release/*` branches
- **Deployment**: Deployed to production
- **Tagging**: All commits are tagged with version numbers (v1.0.0, v1.1.0, etc.)
- **Protection**: Requires pull request reviews, all checks must pass

#### `develop`
- **Purpose**: Integration branch for features
- **Source**: Merges from `feature/*` and `bugfix/*` branches
- **Deployment**: Deployed to staging environment
- **Update Frequency**: Multiple times per day
- **Protection**: Requires pull request reviews

### Support Branches (Short-lived)

#### `feature/*`
- **Purpose**: Develop new features
- **Branch from**: `develop`
- **Merge back to**: `develop` via Pull Request
- **Naming**: `feature/descriptive-name`
- **Example**: `feature/visitor-management`, `feature/ai-report-writer`
- **Lifetime**: Deleted after merge
- **PR Title**: `[FEATURE] Descriptive title`

#### `bugfix/*`
- **Purpose**: Fix bugs found during development
- **Branch from**: `develop`
- **Merge back to**: `develop` via Pull Request
- **Naming**: `bugfix/issue-description`
- **Example**: `bugfix/login-token-expiry`, `bugfix/incident-date-validation`
- **Lifetime**: Deleted after merge
- **PR Title**: `[BUGFIX] Descriptive title`

#### `release/*`
- **Purpose**: Prepare release (testing, version bumps, hotfixes)
- **Branch from**: `develop`
- **Merge back to**: `main` and `develop` with tags
- **Naming**: `release/X.Y.Z` (semantic versioning)
- **Example**: `release/1.0.0`, `release/1.1.0-beta`
- **Lifetime**: Deleted after merge to main
- **Activities**:
  - Update version numbers
  - Update CHANGELOG.md
  - Fix release-related bugs only
  - No new features

#### `hotfix/*`
- **Purpose**: Emergency fixes for production issues
- **Branch from**: `main`
- **Merge back to**: `main` and `develop` with tags
- **Naming**: `hotfix/issue-description`
- **Example**: `hotfix/critical-security-patch`, `hotfix/database-connection-error`
- **Lifetime**: Deleted after merge
- **Version**: Increment patch version (1.0.0 → 1.0.1)
- **Priority**: Highest

## Workflow Examples

### Creating a Feature

```bash
# Update develop with latest changes
git checkout develop
git pull origin develop

# Create feature branch
git checkout -b feature/visitor-check-in

# Make commits
git add .
git commit -m "feat(visitors): add check-in functionality"

# Push to remote
git push -u origin feature/visitor-check-in

# On GitHub: Create Pull Request to develop
# After review and approval, merge via GitHub UI
# Delete branch after merge
```

### Creating a Release

```bash
# Create release branch from develop
git checkout -b release/1.1.0 develop

# Update version numbers in config files
# Update CHANGELOG.md
git add .
git commit -m "chore(release): bump version to 1.1.0"

# Fix any release-blocking bugs
git commit -m "fix(auth): resolve session issue in production"

# When ready, merge to main
git checkout main
git pull origin main
git merge --no-ff release/1.1.0
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin main --tags

# Merge back to develop to sync
git checkout develop
git merge --no-ff release/1.1.0
git push origin develop

# Delete release branch
git branch -d release/1.1.0
git push origin --delete release/1.1.0
```

### Creating a Hotfix

```bash
# Create hotfix from main
git checkout -b hotfix/critical-security-bug main

# Fix the issue
git commit -m "fix(security): patch critical vulnerability"

# Merge to main and tag
git checkout main
git merge --no-ff hotfix/critical-security-bug
git tag -a v1.0.1 -m "Hotfix version 1.0.1"
git push origin main --tags

# Merge back to develop
git checkout develop
git merge --no-ff hotfix/critical-security-bug
git push origin develop

# Delete hotfix branch
git branch -d hotfix/critical-security-bug
git push origin --delete hotfix/critical-security-bug
```

## Commit Message Convention

All commits must follow **Conventional Commits** format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Type
- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation changes
- `style` - Code style changes (formatting, semicolons, etc.)
- `refactor` - Code refactoring without feature/bug changes
- `perf` - Performance improvements
- `test` - Adding or updating tests
- `chore` - Build process, dependency updates, etc.
- `ci` - CI/CD configuration changes

### Scope
Domain/area affected by the change:
- `auth` - Authentication system
- `incidents` - Incident management
- `visitors` - Visitor management
- `reports` - Reporting system
- `ai` - AI engine
- `api` - REST API
- `web` - Web dashboard
- `android` - Mobile app

### Subject
- Lowercase
- Imperative mood ("add" not "added" or "adds")
- No period at the end
- Max 50 characters

### Body (Optional)
- More detailed explanation
- Wrapped at 72 characters
- Explain what and why, not how

### Footer (Optional)
- Reference related issues: `Closes #123`
- Breaking changes: `BREAKING CHANGE: description`

### Examples

```
feat(incidents): add severity level classification

Incidents can now be classified as critical, high, medium, or low.
This helps prioritize response actions.

Closes #456
```

```
fix(auth): resolve token expiry validation

The JWT token expiry check was comparing timestamps incorrectly.
Now correctly validates token expiration.

Closes #789
```

```
docs(api): update authentication endpoint documentation
```

## Rules

✅ **DO**
- Create feature branches from `develop`
- Use descriptive branch names
- Write meaningful commit messages
- Review code before merging
- Delete branches after merge
- Tag releases with version numbers
- Keep commits atomic and focused

❌ **DON'T**
- Merge directly to `main` without going through release process
- Commit directly to `main` or `develop`
- Use vague branch names or commit messages
- Mix multiple features in one PR
- Leave merged branches in remote
- Force push to `main` or `develop`

## Protection Rules (GitHub)

**Main**
- Require pull request reviews (≥1)
- Require status checks to pass
- Require branches to be up to date
- Restrict who can push (admin only)

**Develop**
- Require pull request reviews (≥1)
- Require status checks to pass
- Require branches to be up to date

## Tools & Resources

- **Git Flow visualization**: `git flow` extension
- **Semantic Versioning**: https://semver.org
- **Conventional Commits**: https://www.conventionalcommits.org
- **GitHub Flow Docs**: https://guides.github.com/

## Questions?

- Ask in team discussions
- Review project contributing guide
- Check existing commits for examples
