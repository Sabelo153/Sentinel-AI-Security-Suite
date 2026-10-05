# Sentinel Engineering Handbook

## 09. Security

### Core Controls
- JWT authentication
- password_hash for password storage
- role-based access control
- prepared SQL statements
- request validation
- audit logging
- rate limiting
- HTTPS in deployed environments
- account lockout after repeated failures
- optional 2FA in later milestones

### Defense in Depth
Firewall -> Apache -> JWT -> Rate Limiter -> Permission Check -> Input Validation -> Business Rules -> Database

### File Upload Policy
Never trust uploaded files.

Required flow:
- validate MIME
- validate extension
- rename file
- hash file
- store safely
- persist metadata

### Logging Policy
- Every request must receive a request ID.
- Security events must be audit logged.
- Authentication failures must be traceable.
- Sensitive secrets must never be written to logs.
