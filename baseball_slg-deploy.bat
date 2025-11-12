@echo off
setlocal

REM === Shared configuration ===
set "SRC_DIR=C:\xampp\htdocs\baseball_slg"
set "DEPLOY_DIR=C:\develop\baseball_slg"
set "REMOTE_URL=https://github.com/anpen-sorciere/baseball_slg.git"
set "BRANCH=main"

set "PAUSE_ON_EXIT=1"
set "GIT_ARGS="
set "RUN_GIT=1"

:parse_args
if "%~1"=="" goto :args_done
if /I "%~1"=="--no-commit" (
    set "GIT_ARGS=--no-commit"
    shift
    goto :parse_args
)
if /I "%~1"=="--no-pause" (
    set "PAUSE_ON_EXIT="
    shift
    goto :parse_args
)
if /I "%~1"=="--copy-only" (
    set "RUN_GIT="
    shift
    goto :parse_args
)
shift
goto :parse_args

:args_done

echo ================================
echo = baseball_slg Deployment Tool =
echo ================================
echo.

echo [MAIN][STEP] Syncing files to develop directory...
call "%~dp0baseball_slg-copy-to-develop.bat"
if errorlevel 1 (
    echo [MAIN][ERROR] Copy step failed. Aborting.
    goto :end
)

echo.
if defined RUN_GIT (
    echo [MAIN][STEP] Running Git upload step...
    call "%~dp0baseball_slg-git-upload.bat" %GIT_ARGS%
    if errorlevel 1 (
        echo [MAIN][ERROR] Git upload step failed.
        goto :end
    )
) else (
    echo [MAIN][INFO] --copy-only 指定のため Git アップロードはスキップします。
)

echo.
echo [MAIN][SUCCESS] All steps completed successfully.

goto :end

:end
echo.
if defined PAUSE_ON_EXIT (
    echo Press any key to exit...
    pause >nul
)
endlocal
exit /b 0
