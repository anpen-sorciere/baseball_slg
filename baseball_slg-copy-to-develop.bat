@echo off
setlocal

REM === Defaults (can be overridden by caller) ===
if not defined SRC_DIR set "SRC_DIR=C:\xampp\htdocs\baseball_slg"
if not defined DEPLOY_DIR set "DEPLOY_DIR=C:\develop\baseball_slg"

REM === Validate source directory ===
if not exist "%SRC_DIR%" (
    echo [COPY][ERROR] Source directory not found: %SRC_DIR%
    endlocal & exit /b 1
)

echo [COPY][INFO] Source directory : %SRC_DIR%
echo [COPY][INFO] Target directory : %DEPLOY_DIR%

REM === Ensure target directory exists ===
if not exist "%DEPLOY_DIR%" (
    echo [COPY][INFO] Creating deployment directory...
    mkdir "%DEPLOY_DIR%"
    if errorlevel 1 (
        echo [COPY][ERROR] Failed to create deployment directory.
        endlocal & exit /b 1
    )
)

echo [COPY][STEP] Mirroring files to deployment directory...
robocopy "%SRC_DIR%" "%DEPLOY_DIR%" /MIR /XD ".git" ".github" /XF "*.log" "*.tmp"
set "RBCODE=%errorlevel%"
if %RBCODE% GEQ 8 (
    echo [COPY][ERROR] robocopy failed with exit code %RBCODE%.
    endlocal & exit /b %RBCODE%
)

echo [COPY][INFO] robocopy completed with code %RBCODE%.

echo [COPY][SUCCESS] Files synchronized to %DEPLOY_DIR%.
endlocal & exit /b 0
