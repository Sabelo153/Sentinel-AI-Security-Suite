# Sentinel Engineering Handbook

## 10. Deployment

### Environments
- development
- testing
- staging
- production

### Deployment Rules
- Use environment variables for secrets and per-environment settings.
- Never commit live credentials.
- Run migration validation before deploy.
- Verify health endpoint after deploy.
- Keep rollback strategy available for releases.

### Release Process
1. Merge tested work into develop.
2. Prepare release branch.
3. Run tests, schema checks, and smoke checks.
4. Tag semantic version.
5. Deploy to staging.
6. Approve and deploy to production.

### Monitoring Baseline
At minimum verify:
- API health
- database connectivity
- storage availability
- authentication flow
- recent error logs
