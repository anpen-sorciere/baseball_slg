@echo off
setlocal

REM === Defaults (can be overridden by caller) ===
if not defined DEPLOY_DIR set "DEPLOY_DIR=C:\develop\baseball_slg"
if not defined REMOTE_URL set "REMOTE_URL=https://github.com/anpen-sorciere/baseball_slg.git"
if not defined BRANCH set "BRANCH=main"

set "DO_COMMIT=1"

:parse_args
if "%~1"=="" goto :args_done
if /I "%~1"=="--no-commit" (
    set "DO_COMMIT="
    shift
    goto :parse_args
)
shift
goto :parse_args

:args_done

echo [GIT][INFO] Deployment directory : %DEPLOY_DIR%
if not exist "%DEPLOY_DIR%" (
    echo [GIT][ERROR] Deployment directory not found. Run copy step first.
    endlocal & exit /b 1
)

pushd "%DEPLOY_DIR%"

REM === Ensure Git repo ===
if not exist ".git" (
    echo [GIT][INFO] Initializing Git repository...
    git init
    if errorlevel 1 (
        echo [GIT][ERROR] git init failed.
        popd & endlocal & exit /b 1
    )
    git remote add origin "%REMOTE_URL%" 2>nul
    if errorlevel 1 (
        echo [GIT][WARN] Remote origin may already exist. Continuing.
    )
)

REM === Checkout / create branch ===
git rev-parse --verify %BRANCH% >nul 2>nul
if errorlevel 1 (
    echo [GIT][INFO] Creating branch %BRANCH%.
    git checkout -b %BRANCH%
    if errorlevel 1 (
        echo [GIT][ERROR] Failed to create branch %BRANCH%.
        popd & endlocal & exit /b 1
    )
) else (
    echo [GIT][INFO] Checking out branch %BRANCH%.
    git checkout %BRANCH%
    if errorlevel 1 (
        echo [GIT][ERROR] Failed to checkout branch %BRANCH%.
        popd & endlocal & exit /b 1
    )
)

git pull origin %BRANCH% >nul 2>nul
if errorlevel 1 (
    echo [GIT][WARN] git pull failed (branch may not exist upstream yet or repository is clean).
)

echo [GIT][STEP] Staging files...
git add -A
if errorlevel 1 (
    echo [GIT][ERROR] git add failed.
    popd & endlocal & exit /b 1
)

git status --short

if defined DO_COMMIT (
    for /f %%A in ('powershell -NoProfile -Command "(Get-Date).ToString(\"yyyy-MM-dd HH:mm:ss\")"') do set "NOW=%%A"
    set "COMMIT_MESSAGE=Deploy %NOW%"

    git commit -m "%COMMIT_MESSAGE%"
    if errorlevel 1 (
        echo [GIT][WARN] git commit failed (possibly no changes). Skipping push.
        popd & endlocal & exit /b 0
    )

    git push origin %BRANCH%
    if errorlevel 1 (
        echo [GIT][ERROR] git push failed.
        popd & endlocal & exit /b 1
    )
    echo [GIT][SUCCESS] Changes pushed to %REMOTE_URL% (%BRANCH%).
) else (
    echo [GIT][INFO] --no-commit flag detected. Skipping commit and push.
)

popd
endlocal & exit /b 0
