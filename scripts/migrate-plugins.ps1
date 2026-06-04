# migrate-plugins.ps1
# Automates copying external plugin repositories into this monorepo under the 'plugins/' folder.

$Repos = @(
    "woocommerce-simple-customizations",
    "chatbot-for-woocommerce-sites",
    "wordpress-site-generator",
    "event-manager",
    "wordpress-simple-customise-helper",
    "woocommerce-conditional-shipping-and-payments",
    "HestiaCP-WordPress-Plugin",
    "wp-plugin-sahajanand-customise-helper"
)

$MonorepoPath = (Get-Item -Path ".").FullName
$PluginsRoot = Join-Path -Path $MonorepoPath -ChildPath "plugins"
$TempPath = Join-Path -Path $env:TEMP -ChildPath "wp_migration_$(Get-Random)"

# Ensure plugins directory exists
New-Item -ItemType Directory -Path $PluginsRoot -Force | Out-Null
New-Item -ItemType Directory -Path $TempPath -Force | Out-Null

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "Starting Monorepo Copy Migration of $($Repos.Count) plugins to 'plugins/'" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

foreach ($Repo in $Repos) {
    Write-Host ""
    Write-Host ">>> Processing $Repo..." -ForegroundColor Yellow
    
    $RepoUrl = "https://github.com/sahajananddigital/$Repo.git"
    $DestPath = Join-Path -Path $PluginsRoot -ChildPath $Repo
    $RepoTempPath = Join-Path -Path $TempPath -ChildPath $Repo
    
    # Clean target folder if it already exists
    if (Test-Path $DestPath) {
        Remove-Item -Path $DestPath -Recurse -Force | Out-Null
    }
    New-Item -ItemType Directory -Path $DestPath -Force | Out-Null
    
    # 1. Clone source repo to temp folder
    Write-Host "Cloning $RepoUrl..."
    git clone --depth 1 $RepoUrl $RepoTempPath
    
    if ($?) {
        # 2. Copy all files from temp to destination folder (including hidden files, excluding .git)
        Write-Host "Copying files to monorepo subdirectory 'plugins/$Repo/'..."
        
        # Copy regular contents
        Copy-Item -Path "$RepoTempPath\*" -Destination $DestPath -Recurse -Force -ErrorAction SilentlyContinue
        
        # Copy hidden contents (excluding .git)
        Get-ChildItem -Path $RepoTempPath -Force | Where-Object { $_.Name -ne ".git" } | ForEach-Object {
            Copy-Item -Path $_.FullName -Destination $DestPath -Recurse -Force -ErrorAction SilentlyContinue
        }
        
        Write-Host "Successfully imported $Repo!" -ForegroundColor Green
    } else {
        Write-Warning "Failed to clone $Repo. Skipping."
    }
}

# Cleanup temp files
Write-Host ""
Write-Host "Cleaning up temporary clone directory..." -ForegroundColor Cyan
Remove-Item -Path $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "==================================================" -ForegroundColor Green
Write-Host "Copy migration complete! Add and commit files to trigger releases." -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green
