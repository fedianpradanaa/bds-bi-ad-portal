# TECHNICAL SPECIFICATION & ARCHITECTURE DOCUMENT

**System:** BDS Access Portal (Active Directory Group Approval & Automated Provisioning)  
**Target Environment:** Active Directory Tiered Architecture (`cmc.mhe.local`)  
**Host Server VM:** `BDS-WEB-GD01` (`10.111.167.30`)  
**Primary Domain Controller:** `CMC-SEC-DC01.cmc.mhe.local` (`10.111.121.10`)  
**Secondary Domain Controller:** `10.111.121.20`  
**Operating System:** Linux (Ubuntu Server 24.04 / 22.04 LTS 64-bit)  
**Deployment Model:** Containerized (Docker & Docker Compose) — Strictly Air-Gapped / Offline Ready  
**Document Version:** 3.0  
**Date:** September 1, 2026  

---

## 1. Executive Summary & Core Principles

The **BDS Access Portal** automates identity lifecycle management and privileged group assignments within Active Directory Domain Services (AD DS) for the BDS project.

### Core Security & Operational Governance
1. **Strict Segregation of Duties (Separation of Roles):**
   * **Portal Administrator (Requester):** Submits group membership requests. Administrators are strictly **prohibited** from approving or denying requests.
   * **Supervisor / Approver (Sole Authorizer):** Only designated Supervisors possess authorization power to **Approve** or **Deny** requests.
   * **Engineers / Operators (Target Directory Objects):** Engineers do not hold accounts in the web portal database; they exist purely as Active Directory user objects in `OU=Users,OU=Tier3-Test-UAT`.
2. **Principle of Least Privilege (Zero Domain Admin Dependency):**
   * The application uses a dedicated service account (`svc-ad-portal@cmc.mhe.local`) placed in `OU=ServiceAccounts`.
   * The service account does **not** possess Domain Admin privileges. It is granted granular delegation to read Tier-3 user objects and modify memberships for designated BDS security groups only.
3. **Single Identity (Direct Active Directory Login):**
   * Staff log into the portal using their standard Windows Active Directory **Username** (e.g. `bds-syahputraa`) and domain password.
   * Group-based role mapping automatically grants portal permissions based on membership in `BDS_Admin` or `BDS_Supervisor`.
4. **Air-Gapped Production Environment:**
   * Target server `BDS-WEB-GD01` (`10.111.167.30`) operates without direct internet access. All container images, PHP extensions (including `ext-ldap`), and front-end assets are bundled offline.

---

## 2. Active Directory Infrastructure & Verified Port Matrix

### 2.1. Verified Domain Controller Hierarchy

```
[ Active Directory Forest Root: cmc.mhe.local ]
  ├── Domain Controllers:
  │     ├── Primary DC   : CMC-SEC-DC01.cmc.mhe.local (10.111.121.10)
  │     └── Secondary DC : 10.111.121.20
  │
  └── OU=CMC.MHE
        ├── OU=ServiceAccounts
        │     └── CN=svc-ad-portal (Portal LDAP Service Account)
        │
        ├── OU=Groups
        │     ├── CN=BDS_Admin       (Security Group - Global)
        │     ├── CN=BDS_Operator    (Security Group - Global)
        │     ├── CN=BDS_Supervisor  (Security Group - Global)
        │     └── CN=BDS_Technician  (Security Group - Global)
        │
        └── OU=Tier3-Test-UAT
              ├── OU=Computers
              ├── OU=Servers
              └── OU=Users (Target Provisioning Scope)
                    ├── CN=BDS Niklas  [sAMAccountName: BDS-Niklas]
                    ├── CN=BDS Ghifari [sAMAccountName: BDS-Ghifari]
                    ├── CN=BDS Lahcen  [sAMAccountName: BDS-Lahcen]
                    └── CN=BDS Ari     [sAMAccountName: BDS-Ari]
```

### 2.2. Network Connectivity & Firewall Matrix

Live network connectivity testing confirmed that all essential ports between the application server / management subnet (`10.111.124.x` / `10.111.167.30`) and the Domain Controllers are **OPEN**:

| Target Host | Port / Protocol | Service | Status |
| :--- | :--- | :--- | :--- |
| `10.111.121.10` / `20` | `389/TCP` | LDAP (Directory Query & Bind) | 🟢 **OPEN (OK)** |
| `10.111.121.10` / `20` | `636/TCP` | LDAPS (Encrypted LDAP) | 🟢 **OPEN (OK)** |
| `10.111.121.10` / `20` | `88/TCP` | Kerberos Authentication | 🟢 **OPEN (OK)** |
| `10.111.121.10` / `20` | `53/TCP` / `UDP` | DNS Resolution (`cmc.mhe.local`) | 🟢 **OPEN (OK)** |
| `10.111.121.10` / `20` | `445/TCP` | Microsoft SMB / RPC | 🟢 **OPEN (OK)** |
| `10.111.121.10` / `20` | `3268/TCP` | Global Catalog LDAP | 🟢 **OPEN (OK)** |

---

## 3. Service Account Provisioning & Delegation of Control

### 3.1. Account Identity & Attributes
* **sAMAccountName:** `svc-ad-portal`
* **UserPrincipalName:** `svc-ad-portal@cmc.mhe.local`
* **Distinguished Name:** `CN=svc-ad-portal,OU=ServiceAccounts,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local`
* **Account Flags:** `PasswordNeverExpires = True`, `CannotChangePassword = True`, `Enabled = True`.

### 3.2. PowerShell Creation Script (`scripts/create-service-account-dc.ps1`)
Run directly on the Domain Controller (`CMC-SEC-DC01`):
```powershell
Import-Module ActiveDirectory

$SamAccountName = "svc-ad-portal"
$UserPrincipalName = "svc-ad-portal@cmc.mhe.local"
$DisplayName = "Service Account - BDS Web Portal"
$Description = "Service account for BDS Access Web Portal (BDS-WEB-GD01 10.111.167.30) to query users and manage group memberships."
$TargetOU = "OU=ServiceAccounts,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local"

# Generate or define complex password
$PlainPassword = "YOUR_SECURE_PASSWORD"
$SecurePassword = ConvertTo-SecureString -String $PlainPassword -AsPlainText -Force

New-ADUser `
    -Name $SamAccountName `
    -SamAccountName $SamAccountName `
    -UserPrincipalName $UserPrincipalName `
    -DisplayName $DisplayName `
    -Description $Description `
    -Path $TargetOU `
    -AccountPassword $SecurePassword `
    -CannotChangePassword $true `
    -PasswordNeverExpires $true `
    -Enabled $true
```

### 3.3. Delegation of Control (Resolving `Insufficient access` / `ldap_mod_add` Error)
To allow `svc-ad-portal` to attach users to security groups without granting Domain Admin privileges, execute the following delegation commands via `dsacls` on the DC (`scripts/grant-service-account-permissions.ps1`):

```powershell
# 1. Delegate Write Member permission on the entire Groups OU
dsacls "OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local" /I:S /G "cmc\svc-ad-portal:RPWP;member;group"

# 2. Grant explicit Write Member permission on the 4 target BDS Security Groups
dsacls "CN=BDS_Admin,OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local" /G "cmc\svc-ad-portal:RPWP;member;"
dsacls "CN=BDS_Operator,OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local" /G "cmc\svc-ad-portal:RPWP;member;"
dsacls "CN=BDS_Supervisor,OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local" /G "cmc\svc-ad-portal:RPWP;member;"
dsacls "CN=BDS_Technician,OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local" /G "cmc\svc-ad-portal:RPWP;member;"
```

---

## 4. Authentication Architecture & Single Identity

The portal implements hybrid Active Directory LDAP and local database authentication:

```
                  +-----------------------------------+
                  |        User Enters Credentials    |
                  |     (e.g., 'bds-syahputraa')      |
                  +-----------------+-----------------+
                                    |
                    Is AD_MOCK_MODE=false in .env?
                                    |
                 +------------------+------------------+
                 | YES                                 | NO (Mock Mode)
                 v                                     v
+---------------------------------+   +---------------------------------+
|   Active Directory Live Bind    |   |    Local Database Auth Check    |
| Connect to 10.111.121.10 / 20   |   |     (username / email match)    |
| UPN: bds-syahputraa@cmc.mhe.local|   +----------------+----------------+
+----------------+----------------+                    |
                 |                                      |
         +-------+-------+                              |
         | Bind Succeeded?                              |
         |               |                              |
         v YES           v NO                           |
+-------------------+ +-------------------+             |
| 1. Query AD Info  | | Fallback to Local |             |
| 2. Sync Local User| | Database Match    |             |
| 3. Map AD Groups  | +---------+---------+             |
|    to Shield Roles|           |                       |
+--------+----------+           v                       |
         |               +--------------+               |
         +-------------->| Login Session|<--------------+
                         |  Established |
                         +--------------+
```

### Key Authentication Features:
1. **Plain Username Login:** Eliminates HTML email format requirements. Users log in directly with `bds-syahputraa` or `admin`.
2. **Automatic Role Synchronization:**
   * User in AD Group `BDS_Admin` ➔ Granted **Admin** role in portal.
   * User in AD Group `BDS_Supervisor` ➔ Granted **Supervisor** role in portal.
3. **Emergency Local Fallback:** Standard administrative accounts (`admin`, `supervisor`) remain accessible for maintenance.

---

## 5. UI/UX Workflow & Segregation of Duties

### 5.1. Requests Overview Table
Designed to fit standard 1080p screens without horizontal scrolling:
* **Columns (5 total):**
  1. `Date Requested` (Formatted: `d M Y, H:i`)
  2. `Requester` (Name of submitting Admin)
  3. `Target AD User` (Clean display name: `BDS Niklas`, `BDS Ari`, etc.)
  4. `Target Group` (`BDS_Technician`, `BDS_Operator`, `BDS_Admin`, `BDS_Supervisor`)
  5. `Status` (Badged: `PENDING`, `APPROVED`, `REJECTED`)
* **Row Action:** Single prominent **`Proceed / View`** button.

### 5.2. View Detail & Authorization Page
Balanced 2-column layout:
* **Left Column (2/3 width):** Target user identity, requested security group, and operational justification.
* **Right Column (1/3 width):** Governance metadata, initiator, designated approver, and status timestamps.
* **Header Authorization Actions (Supervisor Only):**
  * 🟢 **`Approve Request`:** Prompts confirmation dialog, triggers live LDAP member addition, updates status to `APPROVED`, and writes immutable audit log.
  * 🔴 **`Deny Request`:** Opens modal requiring mandatory rejection justification, updates status to `REJECTED`, and logs event.
  * *Note: Administrators viewing this page do not see approve/deny actions.*

### 5.3. Request Creation Form
* **Target User:** Live dropdown querying `OU=Users,OU=Tier3-Test-UAT` with plain names.
* **Requested Group:** Filtered strictly to the 4 BDS security groups (`BDS_Admin`, `BDS_Operator`, `BDS_Supervisor`, `BDS_Technician`).
* **Approver Email:** Optional (`nullable`). If omitted, the request is open to any authorized Supervisor.
* Single primary submit button (**`Create`**).

---

## 6. Database Schema & Audit Trail

### 6.1. `users` Table
```sql
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL UNIQUE,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `role` varchar(255) NOT NULL DEFAULT 'requester',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

### 6.2. `group_approval_requests` Table
```sql
CREATE TABLE `group_approval_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` bigint unsigned DEFAULT NULL,
  `requester_name` varchar(255) NOT NULL,
  `target_username` varchar(100) NOT NULL,
  `target_user_dn` text,
  `target_group_name` varchar(100) NOT NULL,
  `target_group_dn` text,
  `business_justification` text NOT NULL,
  `approver_email` varchar(255) DEFAULT NULL,
  `approval_token` varchar(64) NOT NULL UNIQUE,
  `status` enum('PENDING','APPROVED','REJECTED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  `rejection_reason` text,
  `actioned_at` timestamp NULL DEFAULT NULL,
  `actioned_by_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
```

### 6.3. `audit_logs` Table
```sql
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_approval_request_id` bigint unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `actor` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `details` longtext,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_request_audit` FOREIGN KEY (`group_approval_request_id`) REFERENCES `group_approval_requests` (`id`) ON DELETE CASCADE
);
```

---

## 7. Air-Gapped Packaging & Deployment Guide

### 7.1. Offline Packaging (Developer Laptop)
Run the automated packaging script:
```powershell
powershell -ExecutionPolicy Bypass -File scripts\export-offline-bundle.ps1
```
This generates the standalone `release-bundle/` directory containing:
* `ad-portal-app.tar` (Application image with pre-installed `ext-ldap` and compiled assets)
* `nginx-alpine.tar`
* `mysql-8.0.tar`
* `docker-compose.yml`
* `.env`
* `deploy.sh`

### 7.2. Production Deployment on Server `BDS-WEB-GD01` (`10.111.167.30`)
1. Transfer `release-bundle` to `/opt/ad-approval-portal` on `BDS-WEB-GD01`.
2. SSH into the server and execute:
```bash
cd /opt/ad-approval-portal
chmod +x deploy.sh
./deploy.sh
```

---

## 8. Automated Testing & Verification

The portal is covered by an automated test suite verifying all UI routes, authorization policies, mock Active Directory provisioning, and token approvals:

```bash
php artisan test
```

### Test Results Summary:
* **Total Tests:** 21 passed
* **Total Assertions:** 63 passed
* **Code Coverage:** Policies, LDAP Service abstraction, Infolists, Custom Login, and Role-Based Guards.
