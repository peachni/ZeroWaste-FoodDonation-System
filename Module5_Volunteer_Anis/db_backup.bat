@echo off
set db_user=root
set db_password=
set db_name=volunteer
set backup_path=C:\DatabaseBackups

:: INI ADALAH LOKASI SQL XAMPP NADIA DI DRIVE D
set bin_path=D:\xampp\mysql\bin

set datestamp=%date:~-4,4%%date:~-10,2%%date:~-7,2%
set timestamp=%time:~0,2%%time:~3,2%
set timestamp=%timestamp: =0%
set filename=%db_name%_backup_%datestamp%_%timestamp%.sql

if not exist "%backup_path%" mkdir "%backup_path%"
cd /d "%bin_path%"

echo Memulakan Backup SQL XAMPP...

:: ARAHAN KHAS UNTUK MARIADB XAMPP
mysqldump.exe -u %db_user% %db_name% > "%backup_path%\%filename%"

echo.
echo ===========================================
echo   BACKUP BERJAYA (SQL XAMPP)!
echo   Fail: %backup_path%\%filename%
echo ===========================================
pause