@echo off
setlocal enabledelayedexpansion

:: --- CONFIGURATION SETTINGS ---
set "db_user=root"
set "db_password="
set "db_name=workshop2"
set "backup_path=C:\Degree Journey\YEAR 3 SEM 4\WORKSHOP 2\DatabaseBackups"
set "bin_path=C:\xampp2\mysql\bin"

:: --- FIX THE DATE/TIME FILENAME ---
set "datestr=%date:~-4,4%%date:~-7,2%%date:~-10,2%"
set "hour=%time:~0,2%"
if "%hour:~0,1%"==" " set "hour=0%hour:~1,1%"
set "timestamp=%hour%%time:~3,2%%time:~6,2%"

set "filename=%db_name%_backup_%datestr%_%timestamp%.sql"
set "full_path=%backup_path%\%filename%"

:: Create backup folder if it doesn't exist
if not exist "%backup_path%" mkdir "%backup_path%"

echo Starting Backup for %db_name%...

:: Run mysqldump (Removed --column-statistics=0)
if "%db_password%"=="" (
    "%bin_path%\mysqldump.exe" --user=%db_user% --host=127.0.0.1 --protocol=tcp --databases %db_name% --result-file="%full_path%"
) else (
    "%bin_path%\mysqldump.exe" --user=%db_user% --password=%db_password% --host=127.0.0.1 --protocol=tcp --databases %db_name% --result-file="%full_path%"
)

:: --- VERIFICATION ---
if exist "%full_path%" (
    for %%I in ("%full_path%") do (
        if %%~zI GTR 0 (
            echo.
            echo ===========================================
            echo  BACKUP SUCCESSFUL!
            echo  File: %full_path%
            echo  Size: %%~zI bytes
            echo ===========================================
        ) else (
            echo.
            echo ERROR: Backup file is EMPTY (0 KB). 
            echo Check if database "%db_name%" exists in phpMyAdmin.
            del "%full_path%"
        )
    )
) else (
    echo.
    echo ERROR: mysqldump failed to create the file.
)

pause