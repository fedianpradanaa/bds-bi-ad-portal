#!/usr/bin/env bash
# ==============================================================================
# SCRIPT MOUNT WINDOWS SMB SHARED FOLDER (MHS SERVER \\10.111.166.11\Reports)
# Jalankan di Ubuntu VM (10.111.167.30): sudo bash scripts/mount-mhs-share.sh
# ==============================================================================

set -e

MHS_IP="10.111.166.11"
MHS_SHARE="Reports"
MOUNT_TARGET="/opt/ad-portal/mhs-reports"
CRED_FILE="/etc/cifs-mhs.credentials"

echo "================================================================"
echo "  MHS REPORT SMB/CIFS MOUNT UTILITY"
echo "  Target: //${MHS_IP}/${MHS_SHARE} -> ${MOUNT_TARGET}"
echo "================================================================"

# 1. Check network connectivity & port 445 (SMB)
echo "[1/5] Checking connectivity to MHS Server ${MHS_IP}:445..."
PORT_OPEN=false
if command -v nc >/dev/null 2>&1; then
    if nc -zvw3 "$MHS_IP" 445 2>/dev/null; then
        PORT_OPEN=true
    fi
else
    if timeout 3 bash -c "</dev/tcp/${MHS_IP}/445" 2>/dev/null; then
        PORT_OPEN=true
    fi
fi

if [ "$PORT_OPEN" = true ]; then
    echo "Port 445 is OPEN! Firewall connection OK."
else
    echo "WARNING: Cannot connect to ${MHS_IP}:445."
    echo "Pastikan request pembukaan port TCP 445 ke pihak BI Network sudah aktif."
fi

# 2. Ensure cifs-utils is installed
echo "[2/5] Checking cifs-utils package..."
if ! dpkg -s cifs-utils >/dev/null 2>&1; then
    echo "Installing cifs-utils..."
    sudo apt-get update -y && sudo apt-get install -y cifs-utils
fi

# 3. Create mount target directory
echo "[3/5] Preparing mount point ${MOUNT_TARGET}..."
sudo mkdir -p "${MOUNT_TARGET}"

# 4. Prompt credentials if credential file doesn't exist
if [ ! -f "${CRED_FILE}" ]; then
    echo "[4/5] Credentials file ${CRED_FILE} not found."
    read -rp "Enter MHS Windows Username (or guest if no password): " MHS_USER
    read -rsp "Enter MHS Windows Password: " MHS_PASS
    echo ""
    read -rp "Enter MHS Domain (optional, press Enter if local account): " MHS_DOMAIN

    sudo bash -c "cat <<EOF > ${CRED_FILE}
username=${MHS_USER}
password=${MHS_PASS}
domain=${MHS_DOMAIN}
EOF"
    sudo chmod 600 "${CRED_FILE}"
    echo "Saved secure credentials to ${CRED_FILE}."
else
    echo "[4/5] Using existing credentials file: ${CRED_FILE}"
fi

# 5. Mount SMB share
echo "[5/5] Mounting //${MHS_IP}/${MHS_SHARE} -> ${MOUNT_TARGET}..."
if mountpoint -q "${MOUNT_TARGET}"; then
    sudo umount "${MOUNT_TARGET}" || true
fi

sudo mount -t cifs "//${MHS_IP}/${MHS_SHARE}" "${MOUNT_TARGET}" \
    -o credentials="${CRED_FILE}",ro,iocharset=utf8,vers=3.0,dir_mode=0777,file_mode=0777 || \
sudo mount -t cifs "//${MHS_IP}/${MHS_SHARE}" "${MOUNT_TARGET}" \
    -o credentials="${CRED_FILE}",ro,iocharset=utf8,vers=2.1,dir_mode=0777,file_mode=0777

if mountpoint -q "${MOUNT_TARGET}"; then
    echo "MOUNT SUCCESSFUL!"
    ls -la "${MOUNT_TARGET}"
    
    if ! grep -q "${MOUNT_TARGET}" /etc/fstab; then
        echo "Adding auto-mount on boot to /etc/fstab..."
        echo "//${MHS_IP}/${MHS_SHARE} ${MOUNT_TARGET} cifs credentials=${CRED_FILE},ro,iocharset=utf8,vers=3.0,nofail,_netdev,dir_mode=0777,file_mode=0777 0 0" | sudo tee -a /etc/fstab
    fi
    
    echo ""
    echo "Untuk mengaktifkan pembacaan live di Web Portal, pastikan di .env terdapat:"
    echo "BDS_REPORTS_DIR=/var/www/html/mhs-reports"
    echo "Lalu jalankan: docker compose restart app"
else
    echo "FAILED to mount share. Please check username, password, or port 445 connectivity."
fi
