# Historical Migration Matrix: Sentinel Root (SAF) to apps/api

> Superseded on 18 September 2026. The empty `apps/api` target was retired and the root `Sentinel\\` application is canonical. See [`../EMPTY_FILE_AUDIT.md`](../EMPTY_FILE_AUDIT.md).

This document outlines the disposition of legacy features from the root `app/` directory as we consolidate into the canonical `apps/api/` backend.

## 1. Feature Migration Map

| Feature | Legacy Component (Root) | Action | Target (apps/api) | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Authentication** | `AuthController`, `AuthService`, `JWT` | **RETIRE** | `V1\AuthController`, `JwtService` | Already Canonical |
| **Companies** | `CompanyController`, `CompanyService` | **RETIRE** | `V1\CompanyController`, `CompanyService` | Already Canonical |
| **Users** | `UserController`, `UserRepository` | **RETIRE** | `V1\UserController`, `UserRepository` | Already Canonical |
| **RBAC** | `PermissionRepository`, `RoleRepository` | **RETIRE** | `RoleController`, `PermissionService` | Already Canonical |
| **Clients** | `ClientController`, `ClientService` | **RETIRE** | `V1\ClientController`, `ClientService` | Already Canonical |
| **Branches** | `BranchRepository` (inferred) | **RETIRE** | `Domains\Company\BranchController` | Already Canonical |
| **Departments** | `Departments` (inferred) | **RETIRE** | `Domains\Company\DepartmentController`| Already Canonical |
| **Sites** | `SiteController`, `SiteRepository` | **RETIRE** | `V1\SiteController`, `SiteRepository` | Already Canonical |
| **Contracts** | `ContractController`, `ContractRepository` | **MIGRATE** | `V1\ContractController`, `ContractRepository` | MISSING in apps/api |
| **Guard Devices** | `DeviceController`, `GuardDeviceService` | **MIGRATE** | `Domains\Tracking\DeviceController` | MISSING in apps/api |
| **Device Sessions** | `GuardDeviceSessionService` | **MIGRATE** | `Domains\Tracking\SessionService` | MISSING in apps/api |
| **GPS Tracking** | `DeviceTrackingService`, `GpsEngine` | **MIGRATE** | `Domains\Tracking\TrackingService` | MISSING in apps/api |
| **Device Presence**| `DevicePresenceService` | **ADAPT** | `Domains\Tracking\PresenceService` | MISSING in apps/api |
| **Geofences** | `GeofenceController`, `GeofenceEngine` | **ADAPT** | `Domains\Geofence\GeofenceController` | MISSING in apps/api |
| **Audit Logging** | `AuditEngine` | **ADAPT** | `AuditService`, `AuditRepository` | Canonical but needs extension |
| **AI Processing** | `AiEngine` | **KEEP (Legacy)** | TBD | Not in Phase 1 scope |
| **Reports** | `ReportingService` (inferred) | **RETIRE** | TBD | Out of Scope |

## 2. Infrastructure Consolidation

| Component | Root Status | Action |
| :--- | :--- | :--- |
| **Kernel / Container** | `app\Core\Application.php` | **RETIRE** (Use `apps\api\app\Core\Kernel.php`) |
| **Router** | `app\Core\Router.php` | **RETIRE** (Use `apps\api\app\Core\Router.php`) |
| **Database Wrapper** | `app\Core\Database.php` | **RETIRE** (Use `apps\api\app\Core\Database\Database.php`) |
| **Middleware** | `app\Middleware\*` | **RETIRE** (Use `apps\api\app\Middleware\*`) |
| **Events** | `app\Services\EventBus.php` | **ADAPT** (Use `apps\api\app\Core\EventDispatcher.php`) |

## 3. Discovered Legacy Logic to Preserve

1.  **DevicePresenceService.php**: Contains complex state machine for device health (Online, Warning, Critical, Offline). This logic must be ported to the canonical backend.
2.  **GeofenceManagementService.php**: In-memory geofence logic. Needs to be adapted for persistence in Phase 1.
3.  **ContractAccessService.php / ContractLifecycleService.php**: Contains logic for site/contract relationships. Must be migrated to apps/api.

## 4. Phase 1 Priorities

1.  Migrate **Contract/Site** management to `apps/api/`.
2.  Migrate **Device/Session/Tracking** to `apps/api/` (as `Domains\Tracking`).
3.  Port **DevicePresence** logic to `apps/api/`.
4.  Implement **Persistent Geofences** in `apps/api/`.
5.  Standardize **Audit Logging** across all components.
