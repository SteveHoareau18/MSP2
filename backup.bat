@echo off
set "backupdir=C:\Users\hoare\OneDrive\MSP2 - EPCF2\backups_sql\"
set "databases=symfapp_fresh_db"
set "mysqldumpcmd=mysqldump"
set "userpassword= --user=root --password=P*7k2UZ.bws6*X!E"
set "dumpoptions= --quick --add-drop-table --add-locks --extended-insert --lock-tables"

for /f "tokens=1-6 delims=/:. " %%a in ("%date% %time%") do (
    set "TS=%%c%%b%%a%%d%%e%%f"
)

mkdir "%backupdir%" 2>nul
if not exist "%backupdir%" (
    echo Not a directory: %backupdir%
    exit /b 1
)

echo Backup symfapp_fresh_db
for %%d in (%databases%) do (
    %mysqldumpcmd% %userpassword% %dumpoptions% %%d > "%backupdir%\%TS%-%%d.sql"
)
