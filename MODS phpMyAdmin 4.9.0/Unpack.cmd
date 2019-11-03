@echo off
cd /d %~dp0

for %%a in (./*.spk) do set spk=%%~na
echo %spk%

echo Extract SPK
echo -----------
echo.
7z.exe x -aoa -ttar %spk%.spk
if errorlevel 1 (
   echo Error extracting spk.
   exit /b 2
)

echo Extract TGZ
echo -----------
echo.
7z.exe x package.tgz -so | 7z.exe x -aoa -si -ttar -opackage
if errorlevel 1 (
   echo Error while extracting package.
   exit /b 2
)
del package.tgz