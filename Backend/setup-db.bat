@echo off
cd /d "%~dp0"
echo ========================================
echo  NovaShop - Configuration PostgreSQL
echo  Base de donnees : ecom
echo ========================================
echo.

if not exist ".env.local" (
  echo [INFO] Creez .env.local avec votre mot de passe PostgreSQL.
  echo        Exemple : copiez .env.local.example vers .env.local
  echo.
)

php bin/console doctrine:database:create --if-not-exists
if errorlevel 1 goto erreur

php bin/console doctrine:migrations:migrate --no-interaction
if errorlevel 1 goto erreur

php bin/console doctrine:fixtures:load --no-interaction
if errorlevel 1 goto erreur

echo.
echo [OK] Base ecom prete (tables + donnees de demo).
echo      Lancez l'API avec start-api.bat
goto fin

:erreur
echo.
echo [ERREUR] Verifiez que PostgreSQL est demarre et que .env.local
echo          contient les bons identifiants (utilisateur / mot de passe).
echo.
echo  Exemple .env.local :
echo  POSTGRES_PASSWORD=votre_mot_de_passe
echo.

:fin
pause
