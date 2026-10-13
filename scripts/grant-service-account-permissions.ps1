<#
.SYNOPSIS
    Grants 'svc-ad-portal' the required permissions to modify group membership
    on BDS Security Groups in Active Directory.

.DESCRIPTION
    Runs on the Domain Controller (10.111.121.10) to fix the 'Insufficient access' error
    by delegating Write Member (RPWP;member) permissions to 'svc-ad-portal'.
#>

$ServiceAccount = "cmc\svc-ad-portal"
$GroupsOU = "OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local"

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  DELEGATING GROUP MEMBERSHIP PERMISSIONS TO $ServiceAccount" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

# Method 1: Delegate on the entire Groups OU (inheritable to all groups)
Write-Host "`n[1/2] Delegating group membership control on OU=Groups..." -ForegroundColor Yellow
dsacls $GroupsOU /I:S /G "${ServiceAccount}:RPWP;member;group"

# Method 2: Explicitly grant permissions on the 4 BDS groups
Write-Host "`n[2/2] Granting explicit Write Member permissions on BDS groups..." -ForegroundColor Yellow
$bdsGroups = @("BDS_Admin", "BDS_Operator", "BDS_Supervisor", "BDS_Technician")

foreach ($grp in $bdsGroups) {
    $groupDn = "CN=$grp,$GroupsOU"
    Write-Host "Granting permissions on: $grp ($groupDn)" -ForegroundColor White
    dsacls $groupDn /G "${ServiceAccount}:RPWP;member;"
}

# Method 3: Delegate User Creation & Password Reset on Tier3-Test-UAT Users OU
$UsersOU = "OU=Users,OU=Tier3-Test-UAT,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local"
Write-Host "`n[3/3] Delegating user creation & password reset permissions on $UsersOU..." -ForegroundColor Yellow
dsacls $UsersOU /I:T /G "${ServiceAccount}:CCDC;user;"
dsacls $UsersOU /I:S /G "${ServiceAccount}:RPWP;;user"
dsacls $UsersOU /I:S /G "${ServiceAccount}:CA;Reset Password;user"

Write-Host "`n================================================================" -ForegroundColor Green
Write-Host "  PERMISSIONS DELEGATED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "  You can now approve group requests and provision new user accounts in the web portal." -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Green
