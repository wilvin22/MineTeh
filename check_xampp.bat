@echo off
echo Checking XAMPP Apache Status...
echo.

netstat -ano | findstr :80
echo.

if %ERRORLEVEL% EQU 0 (
    echo Port 80 is in use. Apache might be running or another program is using port 80.
) else (
    echo Port 80 is NOT in use. Apache is likely not running.
)

echo.
echo Press any key to exit...
pause > nul
