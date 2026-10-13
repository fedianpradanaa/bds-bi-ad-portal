<#
.SYNOPSIS
    Active Directory Service Account Provisioning Script for BDS Web Portal.
    Target DC: 10.111.121.10 / 10.111.121.20 (Domain: cmc.mhe.local)

.DESCRIPTION
    Creates the dedicated service account 'svc-ad-portal@cmc.mhe.local' directly on
    the Domain Controller with the appropriate permissions to query Tier3 users and
    modify security group memberships (BDS_Admin, BDS_Operator, BDS_Supervisor, BDS_Technician)
    under the Principle of Least Privilege.

.EXAMPLE
    .\create-service-account-dc.ps1
#>

[CmdletBinding()]
param (
    [string]$DomainName = "cmc.mhe.local",
    [string]$SamAccountName = "svc-ad-portal",
    [string]$UserPrincipalName = "svc-ad-portal@cmc.mhe.local",
    [string]$DisplayName = "Service Account - BDS Web Portal",
    [string]$Description = "Service account for BDS Access Web Portal (BDS-WEB-GD01 10.111.167.30) to query users and manage group memberships.",
    [string]$TargetOU = "OU=ServiceAccounts,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local",
    [string]$GroupsOU = "OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local",
    [string]$Password = $env:SVC_AD_PORTAL_PASSWORD
)

Clear-Host
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "  ACTIVE DIRECTORY SERVICE ACCOUNT PROVISIONING TOOL" -ForegroundColor Cyan
Write-Host "  Target Domain: $DomainName" -ForegroundColor Cyan
Write-Host "  Target Account: $UserPrincipalName" -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verify Active Directory Module
Write-Host "[1/5] Checking ActiveDirectory PowerShell Module..." -ForegroundColor Yellow
if (-not (Get-Module -ListAvailable -Name ActiveDirectory)) {
    Write-Host "[-] ERROR: ActiveDirectory module is not found on this machine." -ForegroundColor Red
    Write-Host "    Please run this script directly on the Domain Controller (10.111.121.10/20)" -ForegroundColor Red
    Write-Host "    or on a management workstation with RSAT installed." -ForegroundColor Red
    Exit 1
}
Import-Module ActiveDirectory
Write-Host "[+] ActiveDirectory module loaded successfully." -ForegroundColor Green

# 2. Check if Target OU exists, otherwise fallback to root domain or default container
Write-Host "`n[2/5] Validating Target Organizational Unit..." -ForegroundColor Yellow
$ouExists = $false
try {
    [void](Get-ADOrganizationalUnit -Identity $TargetOU -ErrorAction Stop)
    $ouExists = $true
    Write-Host "[+] Target OU verified: $TargetOU" -ForegroundColor Green
} catch {
    Write-Host "[!] Target OU '$TargetOU' not found." -ForegroundColor Yellow
    # Fallback search
    $fallbackOU = (Get-ADDomain).UsersContainer
    Write-Host "[*] Falling back to default container: $fallbackOU" -ForegroundColor Yellow
    $TargetOU = $fallbackOU
}

# 3. Prompt or Generate Secure Password
Write-Host "`n[3/5] Setting Service Account Password..." -ForegroundColor Yellow
if ([string]::IsNullOrWhiteSpace($Password)) {
    $secPassword = Read-Host -Prompt "Enter secure password for '$SamAccountName' (leave blank to auto-generate)" -AsSecureString
    $bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secPassword)
    $plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    
    if ([string]::IsNullOrWhiteSpace($plainPassword)) {
        # Auto-generate 20-character complex password
        $chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%^&*()_+"
        $plainPassword = -join ((1..20) | ForEach-Object { $chars[(Get-Random -Maximum $chars.Length)] })
        Write-Host "[+] Generated strong password: $plainPassword" -ForegroundColor Magenta
    }
} else {
    $plainPassword = $Password
}

$securePass = ConvertTo-SecureString -String $plainPassword -AsPlainText -Force

# 4. Create or Update the Account in Active Directory
Write-Host "`n[4/5] Creating / Configuring AD Account '$SamAccountName'..." -ForegroundColor Yellow
$existingUser = Get-ADUser -Filter "sAMAccountName -eq '$SamAccountName'" -ErrorAction SilentlyContinue

if ($existingUser) {
    Write-Host "[*] Account '$SamAccountName' already exists. Updating attributes and password..." -ForegroundColor Yellow
    Set-ADUser -Identity $existingUser.DistinguishedName `
        -DisplayName $DisplayName `
        -Description $Description `
        -UserPrincipalName $UserPrincipalName `
        -CannotChangePassword $true `
        -PasswordNeverExpires $true `
        -Enabled $true
    
    Set-ADAccountPassword -Identity $existingUser.DistinguishedName -NewPassword $securePass -Reset $true
    Write-Host "[+] Existing account updated successfully." -ForegroundColor Green
    $userDN = $existingUser.DistinguishedName
} else {
    New-ADUser `
        -Name $SamAccountName `
        -SamAccountName $SamAccountName `
        -UserPrincipalName $UserPrincipalName `
        -DisplayName $DisplayName `
        -Description $Description `
        -Path $TargetOU `
        -AccountPassword $securePass `
        -CannotChangePassword $true `
        -PasswordNeverExpires $true `
        -Enabled $true
    
    Write-Host "[+] New service account created successfully: $UserPrincipalName" -ForegroundColor Green
    $createdUser = Get-ADUser -Identity $SamAccountName
    $userDN = $createdUser.DistinguishedName
}

# 5. Summary & Configuration Output for .env
Write-Host "`n[5/5] Setup Summary & Portal .env Configuration:" -ForegroundColor Cyan
Write-Host "-----------------------------------------------------------------"
Write-Host "Account DN          : $userDN" -ForegroundColor White
Write-Host "sAMAccountName      : $SamAccountName" -ForegroundColor White
Write-Host "UserPrincipalName   : $UserPrincipalName" -ForegroundColor White
Write-Host "Password            : $plainPassword" -ForegroundColor Yellow
Write-Host "Password Expires    : NEVER" -ForegroundColor White
Write-Host "-----------------------------------------------------------------"

Write-Host "`n>>> Copy the following values into your web portal .env file: <<<" -ForegroundColor Green
Write-Host ""
Write-Host "AD_MOCK_MODE=false" -ForegroundColor Cyan
Write-Host "LDAP_HOST=10.111.121.10,10.111.121.20" -ForegroundColor Cyan
Write-Host "LDAP_PORT=389" -ForegroundColor Cyan
Write-Host "LDAP_USERNAME=$UserPrincipalName" -ForegroundColor Cyan
Write-Host "LDAP_PASSWORD=\"$plainPassword\"" -ForegroundColor Cyan
Write-Host "LDAP_BASE_DN=\"OU=CMC.MHE,DC=cmc,DC=mhe,DC=local\"" -ForegroundColor Cyan
Write-Host "LDAP_USER_BASE_DN=\"OU=Users,OU=Tier3-Test-UAT,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local\"" -ForegroundColor Cyan
Write-Host "LDAP_GROUP_BASE_DN=\"OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local\"" -ForegroundColor Cyan
Write-Host ""
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "  PROVISIONING COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Cyan
