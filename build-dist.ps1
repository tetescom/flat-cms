# Flat CMS - Distribution ZIP Builder
# Usage: powershell -ExecutionPolicy Bypass -File build-dist.ps1

$SrcDir  = "$PSScriptRoot\src"
$TempDir = "$PSScriptRoot\_dist_tmp"
$OutZip  = "$PSScriptRoot\flatcms-dist.zip"

if (Test-Path $TempDir) { Remove-Item $TempDir -Recurse -Force }
if (Test-Path $OutZip)  { Remove-Item $OutZip  -Force }

Write-Host "Copying files..." -ForegroundColor Cyan

robocopy $SrcDir $TempDir /E `
    /XD "$SrcDir\contract_generator" `
    /XD "$SrcDir\data\trash" `
    /NFL /NDL /NJH /NJS | Out-Null

# Remove files not for distribution
$removeFiles = @(
    "data\config.json",
    "php\news-data.php",
    "php\news-detail.php"
)
foreach ($f in $removeFiles) {
    $p = Join-Path $TempDir $f
    if (Test-Path $p) { Remove-Item $p -Force }
}

# Clear demo news articles
$newsDir = "$TempDir\data\news"
if (Test-Path $newsDir) { Remove-Item "$newsDir\*" -Force }

# Keep only no-image.webp in uploads
$uploadsDir = "$TempDir\images\uploads"
if (Test-Path $uploadsDir) {
    Get-ChildItem $uploadsDir | Where-Object { $_.Name -ne "no-image.webp" } | Remove-Item -Force
}

# seo.json - sanitized sample (remove personal info)
@"
{
    "site_title": "Site Name",
    "title_separator": " | ",
    "description": "",
    "keywords": "",
    "og_image": "",
    "analytics_id": "",
    "console_verification": "",
    "copyright": "",
    "no_image": "/images/uploads/no-image.webp",
    "hero_image": "/images/uploads/hero.webp",
    "hero_sub": "Tagline Here",
    "hero_title": "Site",
    "hero_title_em": "Name",
    "hero_catch": "",
    "hero_desc": "",
    "contact_email": ""
}
"@ | Set-Content "$TempDir\data\seo.json" -Encoding UTF8

# sns.json - all empty
@"
{
    "x": "",
    "instagram": "",
    "youtube": "",
    "line": "",
    "facebook": "",
    "tiktok": ""
}
"@ | Set-Content "$TempDir\data\sns.json" -Encoding UTF8

# works_order.json - empty
"[]" | Set-Content "$TempDir\data\works_order.json" -Encoding UTF8

Write-Host "Creating ZIP..." -ForegroundColor Cyan
Compress-Archive -Path "$TempDir\*" -DestinationPath $OutZip

Remove-Item $TempDir -Recurse -Force

$size = [math]::Round((Get-Item $OutZip).Length / 1KB, 1)
Write-Host "Done: flatcms-dist.zip ($size KB)" -ForegroundColor Green
