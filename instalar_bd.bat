@echo off
echo Instalando base de datos del sistema de inventario...
echo.

cd /d "c:\xampp\htdocs\P PERSON"

echo Ejecutando inventario_db.sql...
"c:\xampp\mysql\bin\mysql.exe" -u root < inventario_db.sql

if %errorlevel% equ 0 (
    echo.
    echo ✓ Base de datos instalada correctamente!
    echo.
    echo Usuario de prueba: admin / 123456
    echo.
    echo Presiona cualquier tecla para continuar...
) else (
    echo.
    echo ✗ Error al instalar la base de datos.
    echo Verifica que XAMPP esté ejecutándose y que MySQL esté activo.
    echo.
    echo Presiona cualquier tecla para continuar...
)

pause > nul