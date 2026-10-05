# Sentinel Engineering Handbook

## 04. Coding Standards

### PHP File Header
Every PHP file starts with:

```php
<?php

declare(strict_types=1);
```

### General Standards
- One class, one responsibility.
- Constructor injection by default.
- Typed properties and typed return values.
- No global mutable state.
- Prefer small methods with explicit names.
- Prefer composition over inheritance.

### Naming
- Variables: camelCase
- Methods: camelCase
- Classes: PascalCase
- Database objects: snake_case
- Routes: kebab-case

### Structural Suffixes
- Services end with Service
- Repositories end with Repository
- Controllers end with Controller
- Validators end with Validator
- Policies end with Policy
- Events end with Event

### Controller Standard
- Validate input first.
- Delegate business logic immediately.
- Return standardized responses.
- Avoid direct SQL.
- Keep controllers short and readable.

### Error Standard
Errors should follow this contract:

```json
{
  "success": false,
  "code": "AUTH_001",
  "message": "Invalid credentials."
}
```
