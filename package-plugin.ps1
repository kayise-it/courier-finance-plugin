Param(
    [switch]$SkipTailwind
)

$ErrorActionPreference = 'Stop'

$pluginSlug = 'courier-finance-plugin'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not $root) {
    $root = Get-Location
}

$buildDir = Join-Path $root 'build'
$stageDir = Join-Path $buildDir $pluginSlug
$zipPath = Join-Path $buildDir ("$pluginSlug.zip")

Write-Host "Packaging $pluginSlug for production..."

if (Test-Path $buildDir) {
    Write-Host "Removing existing build directory..."
    Remove-Item $buildDir -Recurse -Force
}

New-Item -ItemType Directory -Path $stageDir | Out-Null

if (-not $SkipTailwind) {
    $npx = Get-Command npx -ErrorAction SilentlyContinue
    if ($npx) {
        Write-Host "Building Tailwind CSS (minified)..."
        Push-Location $root
        try {
            npx tailwindcss -c './tailwind.config.js' -i './assets/css/tailwind.css' -o './assets/css/frontend.css' --minify | Out-Null
        }
        catch {
            Write-Warning ("Tailwind build failed: {0}" -f $_.Exception.Message)
        }
        finally {
            Pop-Location
        }
    }
    else {
        Write-Warning 'npx not found; skipping Tailwind compilation. Pass -SkipTailwind to silence this warning.'
    }
}
else {
    Write-Warning 'Tailwind compilation skipped by request.'
}

Write-Host "Copying plugin files to staging area..."

$excludeDirs = @(
    '.git',
    '.github',
    '.idea',
    '.vscode',
    'build',
    'node_modules',
    'temp_excel',
    'waybill_excel'
)

$excludeFiles = @(
    'package-lock.json',
    'package.json',
    'composer.lock',
    'postcss.config.js',
    'tailwind.config.js',
    'clean-for-wordpress-org.sh',
    'package-plugin.sh',
    'package-plugin.ps1',
    'generate-sql.ps1',
    'generate_seed_sql.py',
    'generate_waybill_city_fix_sql.py',
    'generate_*.py',
    '*.sql',
    '*.xlsx',
    '*.csv',
    '*.zip',
    '*.log',
    '*.ps1',
    '*.sh',
    'test-data.json',
    'newSQL.sql',
    'hook-registered.txt',
    'unified-table-examples.php',
    '08600 Waybills.xlsx'
)

$robocopyArgs = @(
    $root,
    $stageDir,
    '/MIR',
    '/R:2',
    '/W:2',
    '/NFL',
    '/NDL',
    '/NJH',
    '/NJS',
    '/NC',
    '/NS'
)

if ($excludeDirs.Count -gt 0) {
    $robocopyArgs += '/XD'
    $robocopyArgs += $excludeDirs
}

if ($excludeFiles.Count -gt 0) {
    $robocopyArgs += '/XF'
    $robocopyArgs += $excludeFiles
}

robocopy @robocopyArgs | Out-Null
$robocopyExit = $LASTEXITCODE

if ($robocopyExit -gt 3) {
    throw "Robocopy failed with exit code $robocopyExit"
}

Write-Host "Removing development-only leftovers..."

$cleanupPatterns = @(
    '.DS_Store',
    'Thumbs.db'
)

foreach ($pattern in $cleanupPatterns) {
    Get-ChildItem -Path $stageDir -Recurse -Force -Filter $pattern -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
}

$pathsToRemove = @(
    'assets/css/tailwind-full.css',
    'temp_excel.zip',
    'requirements.txt',
    'vendor/dompdf/dompdf-backup'
)

foreach ($relativePath in $pathsToRemove) {
    $fullPath = Join-Path $stageDir $relativePath
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Recurse -Force
    }
}

Write-Host "Creating production zip..."

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Compress-Archive -Path (Join-Path $stageDir '*') -DestinationPath $zipPath -CompressionLevel Optimal

$zipInfo = Get-Item $zipPath
$sizeMb = [Math]::Round($zipInfo.Length / 1MB, 2)

Write-Host ("Package ready: {0} ({1} MB)" -f $zipPath, $sizeMb)


