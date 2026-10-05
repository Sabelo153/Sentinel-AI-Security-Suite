# Contributing to Sentinel AI Security Suite

Thank you for your interest in contributing! This document provides guidelines for contributing to the project.

## Development Workflow

### 1. Fork and Clone

```bash
git clone https://github.com/yourusername/Sentinel-AI-Security-Suite.git
cd Sentinel-AI-Security-Suite
git remote add upstream https://github.com/original/Sentinel-AI-Security-Suite.git
```

### 2. Create Feature Branch

Always create a new branch from `develop`:

```bash
git checkout develop
git pull upstream develop
git checkout -b feature/descriptive-name
```

### 3. Commit Guidelines

Use conventional commits:

```bash
git commit -m "feat(domain): description of change"
```

**Types**: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`
**Scopes**: `auth`, `incidents`, `visitors`, `reports`, `ai`, etc.

### 4. Push and Create PR

```bash
git push origin feature/descriptive-name
```

Create Pull Request on GitHub with:
- Clear title and description
- Reference to related issues
- Screenshots (if UI changes)

### 5. Code Review

- Address review comments
- Keep PR focused on single feature
- Ensure all tests pass

### 6. Merge

Once approved, merge into `develop`. Release to `main` during scheduled releases.

## Code Standards

### PHP (Web & API)
- PSR-12 coding standard
- Use type hints for all functions
- Write PHPUnit tests for new functionality
- Document complex logic with comments

### Android (Kotlin)
- Follow Kotlin conventions
- Use MVVM architecture
- Write Espresso tests for UI
- Use AndroidX libraries

### Python (AI Engine)
- PEP 8 style guide
- Type hints required
- Write pytest tests
- Document with docstrings

## Testing Requirements

- All new features must have tests
- Maintain >80% code coverage
- Run tests locally before pushing
- Include integration tests for APIs

## Documentation

- Update relevant docs for new features
- Add API endpoint docs if backend changes
- Include inline code comments for complex logic
- Update CHANGELOG.md

## Git Commit Best Practices

✅ **Good**
```
feat(incidents): add incident severity levels
fix(auth): resolve session timeout issue
docs(api): update authentication endpoints
```

❌ **Avoid**
```
fixed stuff
updated code
changes
```

## Branch Naming

- `feature/visitor-management`
- `bugfix/login-error`
- `hotfix/critical-security-issue`
- `release/1.1.0`

## Questions?

- Check existing issues
- Review documentation in `/docs`
- Ask in discussions

Thank you for contributing! 🙌