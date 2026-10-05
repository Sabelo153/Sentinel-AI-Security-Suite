# Sentinel Engineering Handbook

## 08. Testing

### Test Layers
- Unit
- Feature
- Integration
- API
- Security
- Performance

### Rules
- Every milestone must include executable validation.
- New business logic requires tests.
- API changes require request/response verification.
- Security-sensitive changes require negative-path testing.
- Bug fixes should add a regression test when practical.

### Performance Targets
- Login API: under 500 ms
- Dashboard load: under 2 s
- Incident save: under 1 s
- Visitor check-in: under 2 s
- Patrol scan: under 500 ms
- AI summary: under 10 s
- Typical database query: under 100 ms

### Definition of Done
A feature is done when:
- code works
- tests pass
- docs are updated
- logs and errors are understandable
