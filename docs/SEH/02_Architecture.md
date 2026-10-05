# Sentinel Engineering Handbook

## 02. Architecture

### Layered Model
1. Presentation Layer
2. API Layer
3. Application Layer
4. Domain Layer
5. Infrastructure Layer
6. MariaDB

### Rules Per Layer
- Presentation: render UI and collect user input.
- API: expose HTTP contracts and response standards.
- Application: coordinate use cases and transactions.
- Domain: own business rules, policies, events, and invariants.
- Infrastructure: database, file storage, mail, SMS, AI providers, PDF, queues.

### Architectural Rules
- Dependencies must point inward.
- Controllers stay thin and delegate immediately.
- Repositories do persistence only.
- Services coordinate work across repositories and domain rules.
- External systems are wrapped behind infrastructure services.
- Modules should communicate through events where practical.

### Standard Request Flow
Apache -> public/index.php -> bootstrap/app.php -> Router -> Middleware -> Controller -> Application Service -> Repository -> MariaDB -> JSON Response

### Module Layout
Each module should follow the same structure when introduced:
- Controllers
- Services
- Repositories
- Models
- Validators
- Events
- Rules
- Policies
- Routes
- Tests
