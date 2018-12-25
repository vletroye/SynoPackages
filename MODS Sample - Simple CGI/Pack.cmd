@echo off
cd /d %~dp0

if exist mods.spk (del mods.spk)
cd package
"..\7z.exe" a -ttar -so "..\package" * -xr!*.git -xr!*.gitignore | "..\7z.exe" a -si package.tgz
if errorlevel 1 (
   cd ..
   echo Error while archiving package.
   exit /b 2
)
move package.tgz ..\
cd ..
Mods.exe hash:.
7z.exe a -ttar mods.spk package.tgz scripts WIZARD_UIFILES CHANGELOG INFO LICENSE PACKAGE_ICON*.PNG screen_*.png
if errorlevel 1 (
   cd ..
   echo Error while creating package.
   exit /b 2
)
del package.tgz