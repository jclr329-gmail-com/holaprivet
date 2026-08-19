#!/bin/bash
# ===========================================================================
#  holaprivet.com - script de despliegue  (v3)
#
#  Particularidades de este hosting que condicionan el script:
#    - proc_open esta desactivado  -> Composer no puede ejecutar scripts
#    - no hay Composer instalado   -> se descarga el .phar
#    - la raiz web no se puede cambiar -> se publica public/ en la raiz
#
#  Registro completo en  <SUB>/despliegue.log
# ===========================================================================

VHOST=/var/www/vhosts/41580813.servicio-online.net
SUB=$VHOST/beta.holaprivet.com
APP=$SUB/app
TOOLS=$SUB/.tools
ENVFILE=$VHOST/private/beta.env
LOG=$SUB/despliegue.log
PHP=/usr/bin/php
COMPOSER="$PHP -d memory_limit=-1 $TOOLS/composer.phar"

exec > "$LOG" 2>&1
echo "==========================================================="
echo " DESPLIEGUE v3   $(date)"
echo "==========================================================="

paso ()  { echo; echo "----- $1 -----"; }
morir () { echo; echo "###########################################"; \
           echo "### FALLO: $1"; echo "###########################################"; exit 1; }

# --- 1. Comprobaciones -----------------------------------------------------
paso "1. Comprobaciones"
$PHP -v | head -1
cd "$APP" || morir "no existe la carpeta $APP"
echo "Directorio: $(pwd)"
echo
echo "Archivos necesarios:"
for f in composer.json artisan bootstrap/app.php public/index.php deploy/htaccess \
         routes/web.php resources/views/bienvenida.blade.php; do
    if [ -f "$APP/$f" ]; then echo "  OK     $f"; else echo "  FALTA  $f"; FALTAN=1; fi
done
[ -n "$FALTAN" ] && morir "faltan archivos en el repositorio"

# --- 2. Configuracion ------------------------------------------------------
paso "2. Configuracion (.env)"
if [ -f "$ENVFILE" ]; then
    cp "$ENVFILE" "$APP/.env" && chmod 600 "$APP/.env"
    echo "Copiado desde $ENVFILE"
elif [ -f "$APP/.env" ]; then
    echo "AVISO: no esta $ENVFILE, se conserva el .env existente"
else
    morir "no hay ningun .env - subelo a $ENVFILE"
fi

# --- 3. Composer -----------------------------------------------------------
paso "3. Composer"
mkdir -p "$TOOLS"
[ -f "$TOOLS/composer.phar" ] || curl -sS -L -o "$TOOLS/composer.phar" \
    https://getcomposer.org/download/latest-stable/composer.phar
export COMPOSER_HOME="$TOOLS/home"
export COMPOSER_NO_INTERACTION=1
$COMPOSER --version || morir "Composer no funciona"

# --- 4. Dependencias -------------------------------------------------------
# --no-scripts es imprescindible: proc_open esta desactivado en este servidor
# y Composer no puede lanzar procesos hijos.
paso "4. Dependencias"
echo "(la primera vez tarda uno o dos minutos)"

if [ -f "$APP/composer.lock" ]; then
    echo ">> Intento con el archivo de bloqueo existente"
    $COMPOSER install --no-dev --optimize-autoloader --no-progress --no-scripts
    RES=$?
else
    RES=1
fi

if [ $RES -ne 0 ]; then
    echo
    echo ">> No hay bloqueo valido: resolviendo versiones desde cero"
    rm -f "$APP/composer.lock"
    $COMPOSER update --no-dev --optimize-autoloader --no-progress --no-scripts \
        || morir "Composer no pudo resolver las dependencias"
fi

[ -f "$APP/vendor/autoload.php" ] || morir "no se genero vendor/autoload.php"

echo
echo "Version de Laravel instalada:"
$PHP -r '$j=json_decode(file_get_contents("vendor/composer/installed.json"),true);
foreach(($j["packages"] ?? $j) as $p){ if(($p["name"] ?? "")==="laravel/framework"){ echo "  ",$p["version"],PHP_EOL; } }' 2>/dev/null

# --- 5. Carpetas de trabajo ------------------------------------------------
paso "5. Carpetas de trabajo"
mkdir -p "$APP/storage/framework/cache/data" \
         "$APP/storage/framework/sessions" \
         "$APP/storage/framework/views" \
         "$APP/storage/app/public" \
         "$APP/storage/logs" \
         "$APP/bootstrap/cache"
chmod -R 775 "$APP/storage" "$APP/bootstrap/cache"
echo "Listas."

# --- 6. Descubrimiento de paquetes -----------------------------------------
# Se llama a artisan DIRECTAMENTE desde bash, no a traves de Composer,
# para no depender de proc_open.
paso "6. Descubrimiento de paquetes"
$PHP artisan package:discover --ansi || echo "(se generara solo al primer uso)"

# --- 7. Clave de aplicacion ------------------------------------------------
paso "7. Clave de aplicacion"
if grep -q '^APP_KEY=$' "$APP/.env"; then
    $PHP artisan key:generate --force || morir "no se pudo generar la clave"
    cp "$APP/.env" "$ENVFILE" && echo "Clave guardada tambien en $ENVFILE"
else
    echo "Ya existe."
fi

# --- 8. Base de datos ------------------------------------------------------
paso "8. Migraciones"
$PHP artisan migrate --force || morir "fallaron las migraciones - revisa los datos de la base"

# --- 9. Optimizacion -------------------------------------------------------
paso "9. Optimizacion"
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan view:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

# --- 10. Publicacion -------------------------------------------------------
paso "10. Publicacion"
cp -f "$APP/public/index.php"  "$SUB/index.php"  || morir "no se pudo copiar index.php"
cp -f "$APP/deploy/htaccess"   "$SUB/.htaccess"  || morir "no se pudo copiar el .htaccess"
cp -f "$APP/public/robots.txt" "$SUB/robots.txt" 2>/dev/null
[ -d "$APP/public/build" ] && cp -rf "$APP/public/build" "$SUB/"
rm -f "$SUB/index.html"
ls -la "$SUB" | grep -E 'index.php|htaccess|robots'

# --- 11. Resumen -----------------------------------------------------------
paso "11. Resultado"
$PHP artisan --version
echo
echo "Estado de las migraciones:"
$PHP artisan migrate:status 2>&1 | head -15

echo
echo "==========================================================="
echo " DESPLIEGUE CORRECTO   $(date)"
echo " Comprueba:  https://beta.holaprivet.com"
echo "==========================================================="
