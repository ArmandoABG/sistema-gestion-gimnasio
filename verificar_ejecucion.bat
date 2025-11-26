@echo off
echo ========================================= >> "C:\xampp\htdocs\Proyecto Final\logs\verificacion.log"
echo EJECUCION INICIADA: %date% %time% >> "C:\xampp\htdocs\Proyecto Final\logs\verificacion.log"
echo Usuario: %username% >> "C:\xampp\htdocs\Proyecto Final\logs\verificacion.log"
echo Directorio: %cd% >> "C:\xampp\htdocs\Proyecto Final\logs\verificacion.log"

:: Ejecutar PHP
"C:\xampp\php\php.exe" "C:\xampp\htdocs\Proyecto Final\funciones\enviar_notificaciones.php"

echo EJECUCION FINALIZADA: %date% %time% >> "C:\xampp\htdocs\Proyecto Final\logs\verificacion.log"
echo ========================================= >> "C:\xampp\htdocs\Proyecto Final\logs\verificacion.log"