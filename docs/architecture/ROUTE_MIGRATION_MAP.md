# Historical API Consolidation: Route Migration Map

> Superseded on 18 September 2026. Routes remain canonical in `routes/api.php`; the empty `apps/api` target was retired. See [`../EMPTY_FILE_AUDIT.md`](../EMPTY_FILE_AUDIT.md).

This document maps all legacy routes from `routes/api.php` (Root) to the canonical `apps/api/` backend.

## 1. Authentication & Identity

| Old Route | Target Route | Old Controller | Target Controller | Status |
| :--- | :--- | :--- | :--- | :--- |
| `POST /api/login` | `POST /api/v1/auth/login` | `AuthController` | `V1\AuthController` | Duplicate / Canonical Exists |
| `POST /api/v1/login` | `POST /api/v1/auth/login` | `AuthController` | `V1\AuthController` | Duplicate / Canonical Exists |
| `POST /api/v1/logout` | `POST /api/v1/auth/logout` | `AuthController` | `V1\AuthController` | Canonical Exists |
| `POST /api/v1/refresh` | `POST /api/v1/auth/refresh` | `AuthController` | `V1\AuthController` | Canonical Exists |
| `GET /api/v1/me` | `GET /api/v1/auth/me` | `AuthController` | `V1\AuthController` | Canonical Exists |

## 2. Tracking & Devices (Priority Migration)

| Old Route | Target Route | Old Controller | Target Controller | Status |
| :--- | :--- | :--- | :--- | :--- |
| `POST /api/v1/devices/session/start` | `POST /api/v1/tracking/session` | `DeviceController` | `V1\TrackingController` | **MIGRATION REQUIRED** |
| `POST /api/v1/devices/session/{id}/end` | `DELETE /api/v1/tracking/session/{id}` | `DeviceController` | `V1\TrackingController` | **MIGRATION REQUIRED** |
| `POST /api/v1/devices/session/{id}/location`| `POST /api/v1/tracking/session/{id}/location`| `DeviceController` | `V1\TrackingController` | **MIGRATION REQUIRED** |
| `POST /api/v1/devices/session/{id}/heartbeat`| `POST /api/v1/tracking/session/{id}/heartbeat`| `DeviceController` | `V1\TrackingController` | **MIGRATION REQUIRED** |
| `POST /api/v1/devices/register` | `POST /api/v1/devices` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |
| `GET /api/v1/devices` | `GET /api/v1/devices` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |
| `GET /api/v1/devices/active` | `GET /api/v1/devices/active` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |
| `GET /api/v1/devices/{id}` | `GET /api/v1/devices/{id}` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |
| `PUT /api/v1/devices/{id}` | `PATCH /api/v1/devices/{id}` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |
| `POST /api/v1/devices/{id}/activate` | `POST /api/v1/devices/{id}/activate` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |
| `POST /api/v1/devices/{id}/suspend` | `POST /api/v1/devices/{id}/suspend` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |
| `POST /api/v1/devices/{id}/retire` | `DELETE /api/v1/devices/{id}` | `DeviceController` | `V1\DeviceController` | **MIGRATION REQUIRED** |

## 3. Geofencing (Priority Migration)

| Old Route | Target Route | Old Controller | Target Controller | Status |
| :--- | :--- | :--- | :--- | :--- |
| `POST /api/v1/geofences/start` | `POST /api/v1/geofences` | `GeofenceController` | `V1\GeofenceController` | **MIGRATION REQUIRED** |
| `POST /api/v1/geofences/{id}/points` | `POST /api/v1/geofences/{id}/points` | `GeofenceController` | `V1\GeofenceController` | **MIGRATION REQUIRED** |
| `POST /api/v1/geofences/{id}/finish` | `POST /api/v1/geofences/{id}/finish` | `GeofenceController` | `V1\GeofenceController` | **MIGRATION REQUIRED** |
| `POST /api/v1/geofences/{id}/approve` | `POST /api/v1/geofences/{id}/approve` | `GeofenceController` | `V1\GeofenceController` | **MIGRATION REQUIRED** |
| `GET /api/v1/sites/{id}/geofences/active` | `GET /api/v1/sites/{id}/geofences/active` | `GeofenceController` | `V1\GeofenceController` | **MIGRATION REQUIRED** |

## 4. Contracts & Sites

| Old Route | Target Route | Old Controller | Target Controller | Status |
| :--- | :--- | :--- | :--- | :--- |
| `GET /api/v1/sites` | `GET /api/v1/sites` | `SiteController` | `V1\SiteController` | Duplicate / Canonical Exists |
| `POST /api/v1/sites` | `POST /api/v1/sites` | `SiteController` | `V1\SiteController` | Duplicate / Canonical Exists |
| `GET /api/v1/contracts` | `GET /api/v1/contracts` | `ContractController` | `V1\ContractController` | **MIGRATION REQUIRED** |
| `POST /api/v1/contracts` | `POST /api/v1/contracts` | `ContractController` | `V1\ContractController` | **MIGRATION REQUIRED** |
| `GET /api/v1/contracts/{id}/sites` | `GET /api/v1/contracts/{id}/sites` | `ContractController` | `V1\ContractController` | **MIGRATION REQUIRED** |

## 5. Summary of Actions

1.  **Phase 1 Migration**: Implement Missing `Tracking`, `Device`, `Geofence`, and `Contract` endpoints in `apps/api/app/Controllers/V1`.
2.  **Consolidation**: Point `routes/api.php` to the new canonical controllers once they are ready.
3.  **Deprecation**: Remove `Sentinel\` controller references from `routes/api.php`.
4.  **Standardization**: Ensure all `/api/v1` routes use `AuthMiddleware` and `TenantContextMiddleware` from the canonical backend.
