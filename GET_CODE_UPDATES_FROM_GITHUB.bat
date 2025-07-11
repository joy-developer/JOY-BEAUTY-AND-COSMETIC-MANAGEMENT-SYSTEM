@echo off
title Git Pull Updater
color 0A

echo Running Git Pull for Joy Beauty System...
echo ---------------------------------------

:: Change directory to the project folder
cd /d "C:\xampp\htdocs\projects\JOY-BEAUTY-AND-COSMETIC-MANAGEMENT-SYSTEM"

:: Run git pull
git pull origin main --force

echo.
echo Update completed!
echo.
pause