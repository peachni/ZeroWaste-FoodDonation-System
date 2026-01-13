@echo off
:: --- CONFIGURATION SETTINGS ---
set db_user=root
set db_password=Hunger@Safe2026!
set db_name=food_donation_db
set backup_path=C:\DatabaseBackups

:: Paths for MySQL 9.1.0
set bin_path=C:\wamp64\bin\mysql\mysql9.1.0\bin
set plugin_path=C:\wamp64\bin\mysql\mysql9.1.0\lib\plugin

:: --- DO NOT EDIT BELOW THIS LINE ---
set datestamp=%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%
set filename=%db_name%_backup_%datestamp%.sql

:: Create backup folder if it doesn't exist
if not exist "%backup_path%" mkdir "%backup_path%"

:: Move to the bin folder
cd /d "%bin_path%"

echo Starting Backup for %db_name%...

:: THE FIX: Added -p%db_password% (Note: No space after the -p)
:: This tells the script to use the password you set.
mysqldump.exe --host=127.0.0.1 --protocol=tcp --plugin-dir="%plugin_path%" --column-statistics=0 -u %db_user% -p%db_password% %db_name% > "%backup_path%\%filename%"

echo.
echo ===========================================
echo  BACKUP SUCCESFULLY COMPLETED!
echo  File: %backup_path%\%filename%
echo ===========================================
pause