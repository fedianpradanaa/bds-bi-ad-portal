# Walkthrough: Active Directory Group Approval & Provisioning Web Portal

All components of the **Active Directory Group Approval & Provisioning Web Portal** have been developed, configured, tested, and prepared for air-gapped / offline deployment on host server **`BDS-WEB-GD01` (`10.111.167.30`)**.

---

## 1. Summary of Changes & Architecture

### **Core Stack & Frameworks**
- **Framework:** Laravel 11.x
- **Admin Panel UI:** Filament PHP with custom themes, badges, confirmation modals, and role-based data scoping.
- **Active Directory Engine:** `directorytree/ldaprecord-laravel` + OpenLDAP (`php-ldap`).
- **Database & Audit Trail:** SQLite (local development) / MySQL 8.0 (production container) with append-only compliance logs.
- **Containerization:** Production Docker multi-container stack (`ad-portal-app`, `ad-portal-nginx`, `ad-portal-db`).

---

## 2. Key Modules & Implementation Details

| Component | File Path | Key Features |
| :--- | :--- | :--- |
| **Active Directory Service** | [ActiveDirectoryService.php](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/app/Services/ActiveDirectoryService.php) | Tier3 AD User & Group query, assignment execution, and local simulation mode (`AD_MOCK_MODE=true`). |
| **Approval Resource** | [GroupApprovalRequestResource.php](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/app/Filament/Resources/GroupApprovalRequests/GroupApprovalRequestResource.php) | Dynamic access request form, status badges, Supervisor **Approve** (LDAP sync) and **Reject** (with reason) actions. |
| **Audit Logs Resource** | [AuditLogResource.php](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/app/Filament/Resources/AuditLogs/AuditLogResource.php) | Immutable, read-only compliance logging tracking every creation, approval, rejection, and LDAP response. |
| **Magic-Link Controller** | [ExternalApprovalController.php](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/app/Http/Controllers/ExternalApprovalController.php) | Remote authorization endpoint with responsive Tailwind CSS view ([show.blade.php](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/resources/views/approval/show.blade.php)). |
| **Docker Configuration** | [Dockerfile](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/docker/php/Dockerfile) & [docker-compose.yml](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/docker-compose.yml) | Multi-stage container build with `ext-ldap`, PHP 8.3-FPM, Nginx, and MySQL 8.0 with persistent volumes. |
| **Air-Gapped Scripts** | [export-offline-bundle.ps1](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/scripts/export-offline-bundle.ps1) & [deploy.sh](file:///c:/Users/Administrator/Documents/Porto/WebPortal-Account%20Approvemnt/scripts/deploy.sh) | One-click export script generating compressed image tarballs for server `BDS-WEB-GD01`. |

---

## 3. Pre-Seeded Default Accounts (Local Testing)

The database is pre-seeded with the following credentials for development and demonstration:

| Role | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- |
| **Portal Administrator** | `admin@cmc.mhe.local` | `password` | Full view of all requests, audit logs, and system operations. |
| **Supervisor / Approver** | `supervisor@cmc.mhe.local` | `password` | Authorizes or rejects assigned Tier3 group requests. |
| **Engineer / Requester** | `engineer@cmc.mhe.local` | `password` | Submits group assignment access requests. |

---

## 4. Automated Test Validation

All unit and feature tests passed successfully:
```bash
php artisan test
```
**Results:**
- `test_the_application_returns_a_successful_response` (Passed)
- `test_group_approval_request_creation_generates_token_and_default_status` (Passed)
- `test_ad_service_mock_mode_returns_users_and_executes_assignment` (Passed)
- `test_magic_link_approval_view_renders_successfully` (Passed)
- `test_magic_link_approve_action_updates_status_and_creates_audit_log` (Passed)
- `test_magic_link_reject_action_updates_status_and_rejection_reason` (Passed)
- `test_cannot_re_action_already_approved_request` (Passed)
- `test_non_admin_supervisor_only_queries_assigned_requests` (Passed)

**Summary: 9 passed tests, 26 assertions.**

---

## 5. How to Run & Test on Developer Laptop

### Option A: Running with Local PHP Server (Fastest)
```powershell
php artisan serve
```
- Admin Panel: `http://localhost:8000/admin`
- Remote Magic Link Test: `http://localhost:8000/approval/{approval_token}`

### Option B: Running with Docker Compose Locally
```powershell
docker compose up -d
```
- Access at: `http://localhost`

---

## 6. How to Deploy to Air-Gapped Server `BDS-WEB-GD01` (`10.111.167.30`)

### Step 1: Export Docker Bundle on Developer Laptop (With Internet)
Run the automated PowerShell script:
```powershell
powershell -ExecutionPolicy Bypass -File scripts\export-offline-bundle.ps1
```
This generates the `release-bundle/` folder containing:
- `ad-portal-app.tar`
- `nginx-alpine.tar`
- `mysql-8.0.tar`
- `docker-compose.yml`
- `.env`
- `deploy.sh`

### Step 2: Transfer and Deploy on Server `BDS-WEB-GD01` (Without Internet)
1. Copy the `release-bundle` folder to `BDS-WEB-GD01` (via SCP / WinSCP).
2. SSH into `BDS-WEB-GD01` (`10.111.167.30`):
   ```bash
   cd release-bundle
   chmod +x deploy.sh
   ./deploy.sh
   ```
3. The portal is immediately live at `http://10.111.167.30/admin`.
