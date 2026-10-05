# Milestone 01 Test Checklist

## Purpose
This checklist defines the manual QA baseline for Milestone 1 and must be updated as new features become testable.

## Status Key
- PASS
- FAIL
- BLOCKED
- NOT RUN

---

## TEST 001 - Folder Structure
### Verify
The following folders exist:

- Sentinel-AI-Security-Suite/app/
- Sentinel-AI-Security-Suite/bootstrap/
- Sentinel-AI-Security-Suite/config/
- Sentinel-AI-Security-Suite/database/
- Sentinel-AI-Security-Suite/docs/
- Sentinel-AI-Security-Suite/public/
- Sentinel-AI-Security-Suite/resources/
- Sentinel-AI-Security-Suite/routes/
- Sentinel-AI-Security-Suite/storage/
- Sentinel-AI-Security-Suite/tests/
- Sentinel-AI-Security-Suite/vendor/

### Pass Criteria
- Every folder exists

### Result
- Status: PASS
- Notes: Verified all required top-level folders exist in `C:\Development\Sentinel-AI-Security-Suite`.

---

## TEST 002 - Composer
### Run
```powershell
composer --version
```

### Expected
```text
Composer version 2.x.x
```

### Result
- Status: PASS
- Notes: `Composer version 2.9.5 2026-01-29 11:40:53`.

---

## TEST 003 - PHP
### Run
```powershell
php -v
```

### Expected
```text
PHP 8.3+
```

### Result
- Status: FAIL
- Notes: Current runtime is `PHP 8.2.12`; checklist target is PHP 8.3+.

---

## TEST 004 - MariaDB Connection
### Verify
Open DBeaver and connect to the Sentinel database.

### Expected
```text
Connected
```

### Result
- Status: NOT RUN
- Notes: Requires manual DBeaver connection validation.

---

## TEST 005 - Database Exists
### Execute
```sql
SHOW DATABASES;
```

### Expected
```text
sentinel_ai_security
```

### Result
- Status: NOT RUN
- Notes: DBeaver connection was not used in this session. Database connectivity was verified through live PHP/PDO checks.

---

## TEST 006 - Company Table
### Execute
```sql
SHOW TABLES;
```

### Expected
```text
companies
```

### Result
- Status: PASS
- Notes: `sentinel_ai_security` exists on the MariaDB server.

---

## TEST 007 - Clients Table
### Expected
```text
clients
```

### Result
- Status: PASS
- Notes: `companies` table present.

---

## TEST 008 - Branches Table
### Expected
```text
branches
```

### Result
- Status: PASS
- Notes: `clients` table present.

---

## TEST 009 - Sites Table
### Expected
```text
sites
```

### Result
- Status: PASS
- Notes: `branches` table present.

---

## TEST 010 - People Table
### Expected
```text
people
```

### Result
- Status: PASS
- Notes: `sites` table present.

---

## TEST 011 - Employees Table
### Expected
```text
employees
```

### Result
- Status: PASS
- Notes: `people` table present.

---

## TEST 012 - Users Table
### Expected
```text
users
```

### Result
- Status: PASS
- Notes: `employees` table present.

---

## Database Integrity Tests
Now we start testing relationships.

---

## TEST 013 - Insert Company
### Execute
```sql
INSERT INTO companies(company_name)
VALUES('Sentinel Security');
```

### Expected
```text
1 row inserted
```

### Result
- Status: PASS
- Notes: `users` table present.

---

## TEST 014 - Insert Client
### Verify
Use `company_id = 1`.

### Expected
```text
1 row inserted
```

### Result
- Status: PASS
- Notes: Verified over HTTP at `http://127.0.0.1:8080/`. Exact Apache path `http://localhost/Sentinel-AI-Security-Suite/public/` still needs environment-specific validation.

---

## TEST 015 - Insert Branch
### Expected
```text
Links to client
```

### Result
- Status: NOT RUN
- Notes:

---

## TEST 016 - Insert Site
### Expected
```text
Links to branch
```

### Result
- Status: NOT RUN
- Notes:

---

## TEST 017 - Foreign Key Test
### Verify
Delete company.

### Expected
Children deleted only if cascade is configured.

### Result
- Status: NOT RUN
- Notes:

---

## API Tests
These will be executed with Postman or scripted HTTP checks.

### Example
```http
POST /api/v1/login
```

### Expected
```json
{
  "success": true
}
```

---

## UI Tests
Later we will verify:

- Login
- Dashboard
- Navigation
- Forms
- Tables
- Dark Mode
- Responsive Design

---

## Android Tests
Later we will verify:

- Install APK
- Login
- Clock In
- Patrol
- Incident
- Logout

---

## AI Tests
Later we will verify:

### Prompt
```text
Truck driver refused inspection.
```

### Expected
- Professional report
- Risk assessment
- Recommended actions
- Grammar improvement
- PDF output

---

## Security Tests
We will eventually verify:

- SQL injection protection
- XSS protection
- JWT validation
- Permission escalation resistance
- File upload validation
- Session hijacking resistance
- Brute force protection

---

## Performance Tests
- Login < 500ms
- Dashboard < 2 sec
- Incident Save < 1 sec

---

## Recovery Tests
- Power failure
- Restart
- No data lost

---

## Backup Tests
- Backup database
- Restore database
- Everything works

---

## Release Tests
Before every release:

- 100% manual QA
- Unit tests
- Integration tests
- Performance tests
- Security tests
- Release sign-off

---

## Milestone 1.1 - Framework Running
### Goal
When visiting:

```text
http://localhost/Sentinel-AI-Security-Suite/public/
```

you should see a boot confirmation page proving:

- PHP is running
- Composer autoloading works
- Bootstrap loads
- Router is handling requests
- Configuration is loading
- Database connection succeeds

### Expected Page
```text
==================================

Sentinel AI Security Suite

Version 0.1.0
Environment: Development
Database: Connected
Router: Running
Framework: SAF Initialized

==================================
```

### Result
- Status: NOT RUN
- Notes:
