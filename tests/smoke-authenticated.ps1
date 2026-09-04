param(
    [string] $BaseUrl = 'http://localhost:8080',
    [string] $Username = 'admin-neo'
)

$ErrorActionPreference = 'Stop'
$password = $env:NEO_DASHBOARD_SMOKE_PASSWORD
if ([string]::IsNullOrWhiteSpace($password)) {
    throw 'Set NEO_DASHBOARD_SMOKE_PASSWORD before running the authenticated smoke test.'
}

$base = $BaseUrl.TrimEnd('/')
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
Invoke-WebRequest -Uri "$base/wp-login.php" -WebSession $session -UseBasicParsing -TimeoutSec 20 | Out-Null

$loginBody = @{
    log = $Username
    pwd = $password
    'wp-submit' = 'Log In'
    redirect_to = "$base/neo-dashboard/"
    testcookie = '1'
}

try {
    Invoke-WebRequest `
        -Uri "$base/wp-login.php" `
        -Method Post `
        -Body $loginBody `
        -WebSession $session `
        -UseBasicParsing `
        -MaximumRedirection 0 `
        -TimeoutSec 30 | Out-Null
} catch {
    if ($_.FullyQualifiedErrorId -notlike 'MaximumRedirectExceeded*') {
        throw
    }
}

$cookieNames = @($session.Cookies.GetCookies("$base/") | ForEach-Object { $_.Name })
if (-not ($cookieNames -match '^wordpress_logged_in_')) {
    throw 'WordPress did not create an authenticated session.'
}

$dashboard = Invoke-WebRequest `
    -Uri "$base/neo-dashboard/" `
    -WebSession $session `
    -UseBasicParsing `
    -TimeoutSec 60
if ([int] $dashboard.StatusCode -ne 200) {
    throw "Dashboard returned HTTP $([int] $dashboard.StatusCode)."
}

$calendar = Invoke-WebRequest `
    -Uri "$base/neo-dashboard/neo-calendar/overview/" `
    -WebSession $session `
    -UseBasicParsing `
    -TimeoutSec 90
if ([int] $calendar.StatusCode -ne 200 -or $calendar.Content -notmatch 'neo-calendar-common\.js') {
    throw 'Neo Calendar page or its core script is unavailable.'
}
if ($calendar.Content -notmatch 'var\s+neoCalendarAjax') {
    throw 'Neo Calendar localization is unavailable.'
}

$nonce = if ($dashboard.Content -match '"widgetNonce":"([^"]+)"') { $matches[1] } else { '' }
$widgetMatch = [regex]::Match($dashboard.Content, 'data-widget-id="([^"]+)"')
if ($nonce -eq '' -or -not $widgetMatch.Success) {
    throw 'Dashboard widget nonce or widget identifier is unavailable.'
}

$widgetResponse = Invoke-WebRequest `
    -Uri "$base/wp-admin/admin-ajax.php" `
    -Method Post `
    -Body @{
        action = 'neo_dashboard_widget'
        nonce = $nonce
        widget_id = $widgetMatch.Groups[1].Value
    } `
    -WebSession $session `
    -UseBasicParsing `
    -TimeoutSec 60
if ([int] $widgetResponse.StatusCode -ne 200 -or $widgetResponse.Content -notmatch '"success":true') {
    throw 'Widget AJAX smoke test failed.'
}

Write-Output 'AUTHENTICATED_SMOKE_TEST=passed'
Write-Output 'DASHBOARD_STATUS=200'
Write-Output 'CALENDAR_STATUS=200'
Write-Output 'CALENDAR_LOCALIZATION=available'
Write-Output 'WIDGET_AJAX=success'
