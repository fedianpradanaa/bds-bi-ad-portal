# PANDUAN PENGGUNAAN SKRIP (SCRIPTS DIRECTORY)
## BDS Access Portal — Active Directory & Server Deployment

Direktori ini berisi skrip operasional resmi yang telah dirapikan untuk pemeliharaan Active Directory, Virtual Machine Hyper-V, dan deployment portal.

---

### 1. Active Directory Domain Controller Scripts (Jalankan di DC `10.111.121.10`)

* **`create-service-account-dc.ps1`**
  * **Fungsi:** Membuat akun service `svc-ad-portal@cmc.mhe.local` di `OU=ServiceAccounts,OU=CMC.MHE`.
  * **Penggunaan:** Jalankan sekali di PowerShell Domain Controller.

* **`grant-service-account-permissions.ps1`**
  * **Fungsi:** Mendelegasikan izin kontrol akses (*Least Privilege Delegation*) kepada `svc-ad-portal` untuk membuat akun user di `OU=Users,OU=Tier3-Test-UAT` dan memodifikasi keanggotaan 4 security group BDS (`BDS_Admin`, `BDS_Operator`, `BDS_Supervisor`, `BDS_Technician`).
  * **Penggunaan:** Jalankan di PowerShell Domain Controller.

---

### 2. Hyper-V Virtual Machine Management (Jalankan di Laptop / Server Host)

* **`create-hyperv-vm.ps1`**
  * **Fungsi:** Membuat VM Generation 2 `BDS-WEB-GD01` baru di Hyper-V lengkap dengan UEFI SecureBoot Linux, VHDX 60GB, RAM dinamis, dan boot ISO Ubuntu.
  * **Penggunaan:** `powershell -ExecutionPolicy Bypass -File scripts\create-hyperv-vm.ps1`

* **`export-hyperv-vm.ps1`**
  * **Fungsi:** Mengekspor VM `BDS-WEB-GD01` secara utuh ke folder `D:\VM\Exports\BDS-WEB-GD01` untuk kebutuhan backup atau migrasi ke host server lain.
  * **Penggunaan:** `powershell -ExecutionPolicy Bypass -File scripts\export-hyperv-vm.ps1`

---

### 3. Deployment & Update Portal

* **`deploy-to-vm.ps1` (Jalankan di PowerShell Laptop)**
  * **Fungsi:** Mengemas kode aplikasi terbaru dan mengunggahnya ke server VM via SCP.
  * **Penggunaan:** `powershell -ExecutionPolicy Bypass -File scripts\deploy-to-vm.ps1 -VmIp 10.111.167.30 -VmUser gd`

* **`scripts/push/` (Shortcut 1-Klik File .BAT Windows):**
  * 🔑 **`1_setup_ssh_key.bat`**: Double-click untuk mengatur login SSH tanpa password (cukup dijalankan 1x).
  * 👁️ **`2_auto_watch_sync.bat`**: Double-click untuk menyalakan mode auto-sync realtime (setiap Ctrl+S langsung sync otomatis ke server).
  * ⚡ **`3_sync_now.bat`**: Double-click untuk mengirim pembaruan kode 1x secara instan ke server.

* **`setup-portal-offline.sh` (Jalankan di Terminal Ubuntu VM Server)**
  * **Fungsi:** Menjalankan deployment dan update portal secara **100% Offline / Air-Gapped** (tanpa koneksi internet). Mengatur konfigurasi `.env` Active Directory Live, memperbaiki izin file/database, menyalakan container Docker, dan membersihkan cache.
  * **Penggunaan:** `sudo bash scripts/setup-portal-offline.sh`
