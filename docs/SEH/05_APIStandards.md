# Sentinel Engineering Handbook

## 05. API Standards

### Versioning
- Base path: /api/v1
- Breaking changes require a new API version.
- New optional fields may be added within the same version.

### Route Style
- Use plural resources.
- Use kebab-case for multi-word route segments.
- Expose UUIDs publicly.

Examples:
- GET /api/v1/incidents
- POST /api/v1/incidents
- PUT /api/v1/incidents/{uuid}
- DELETE /api/v1/incidents/{uuid}
- POST /api/v1/attendance/check-in

### Response Standard
Successful response:

```json
{
  "success": true,
  "message": "Request completed.",
  "data": {}
}
```

Error response:

```json
{
  "success": false,
  "code": "GENERIC_001",
  "message": "Request failed."
}
```

### API Rules
- Validate before service execution.
- Never expose password hashes or internal secrets.
- Use HTTP status codes correctly.
- Use pagination for list endpoints.
- Return machine-readable error codes.
- Include audit context where relevant.

### Health Endpoint
- GET /api/v1/health
- Must report service status and current version.
