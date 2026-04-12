# Flat CMS - Demo ZIP Builder (includes demo data + manual, no credentials)
# Usage: powershell -ExecutionPolicy Bypass -File build-demo.ps1

$SrcDir  = "$PSScriptRoot\src"
$TempDir = "$PSScriptRoot\_demo_tmp"
$OutZip  = "$PSScriptRoot\flatcms-demo.zip"

if (Test-Path $TempDir) { Remove-Item $TempDir -Recurse -Force }
if (Test-Path $OutZip)  { Remove-Item $OutZip  -Force }

Write-Host "Copying files..." -ForegroundColor Cyan

robocopy $SrcDir $TempDir /E `
    /XD "$SrcDir\contract_generator" `
    /XD "$SrcDir\data\trash" `
    /NFL /NDL /NJH /NJS | Out-Null

# Remove files not for distribution
$removeFiles = @(
    "admin\config.json",
    "php\news-data.php",
    "php\news-detail.php"
)
foreach ($f in $removeFiles) {
    $p = Join-Path $TempDir $f
    if (Test-Path $p) { Remove-Item $p -Force }
}

# seo.json - remove personal info only (keep demo content)
$seoPath = "$TempDir\data\seo.json"
if (Test-Path $seoPath) {
    $seo = Get-Content $seoPath -Raw -Encoding UTF8 | ConvertFrom-Json
    $seo.contact_email = ""
    $utf8NoBom = [System.Text.UTF8Encoding]::new($false)
    [System.IO.File]::WriteAllText($seoPath, ($seo | ConvertTo-Json -Depth 3), $utf8NoBom)
}

# sns.json - clear actual URLs
@"
{
    "x": "",
    "instagram": "",
    "youtube": "",
    "line": "",
    "facebook": "",
    "tiktok": ""
}
"@ | ForEach-Object { [System.IO.File]::WriteAllText("$TempDir\data\sns.json", $_, [System.Text.UTF8Encoding]::new($false)) }

# Minify style.css
$cssPath = "$TempDir\data\style.css"
if (Test-Path $cssPath) {
    $css = Get-Content $cssPath -Raw -Encoding UTF8
    $css = [regex]::Replace($css, '/\*[\s\S]*?\*/', '')  # remove comments
    $css = [regex]::Replace($css, '\s+', ' ')             # collapse whitespace
    $css = $css -replace ' *\{ *', '{' -replace ' *\} *', '}' -replace ' *: *', ':' -replace ' *; *', ';' -replace ' *, *', ','
    $css = $css.Trim()
    [System.IO.File]::WriteAllText($cssPath, $css, [System.Text.UTF8Encoding]::new($false))
}

Write-Host "Creating ZIP..." -ForegroundColor Cyan

# Create zip with manual at root and src files in a subfolder
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zipFile = [System.IO.Compression.ZipFile]::Open($OutZip, 'Create')

function Add-ToZip($zip, $sourcePath, $entryName) {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $sourcePath, $entryName, 'Optimal') | Out-Null
}

# Add all files from TempDir
Get-ChildItem $TempDir -Recurse -File | ForEach-Object {
    $rel = $_.FullName.Substring($TempDir.Length + 1).Replace('\', '/')
    Add-ToZip $zipFile $_.FullName $rel
}

# Add manual.html at root (if exists)
if (Test-Path "$PSScriptRoot\manual.html") {
    Add-ToZip $zipFile "$PSScriptRoot\manual.html" "manual.html"
}

$zipFile.Dispose()

Remove-Item $TempDir -Recurse -Force

$size = [math]::Round((Get-Item $OutZip).Length / 1KB, 1)
Write-Host "Done: flatcms-demo.zip ($size KB)" -ForegroundColor Green
