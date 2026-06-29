@echo off
cd /d "%~dp0"

if not exist ".env.local" (
  echo [ATTENTION] Fichier .env.local manquant.
  echo Copiez .env.local.example vers .env.local et mettez votre mot de passe PostgreSQL.
  echo   copy .env.local.example .env.local
  echo.
)

echo Verification PostgreSQL...
php bin/console doctrine:query:sql "SELECT 1" >nul 2>&1
if errorlevel 1 (
  echo [ERREUR] Connexion PostgreSQL impossible. Verifiez .env.local puis lancez setup-db.bat
  pause
  exit /b 1
)

echo Demarrage de l'API NovaShop sur http://127.0.0.1:8000 ...
echo Base PostgreSQL : ecom
echo.
php -S 127.0.0.1:8000 -t public
