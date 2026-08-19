#!/bin/bash
# ===========================================================================
#  holaprivet.com — script de despliegue  (v2)
#  Se ejecuta desde las "acciones adicionales" de Git del panel.
#  Todo el registro queda en  <SUB>/despliegue.log
# ===========================================================================

VHOST=/var/www/vhosts/41580813.servicio-online.net
SUB=$VHOST/beta.holaprivet.com
APP=$SUB/app
TOOLS=$SUB/.tools
ENVFILE=$VHOST/private/beta.env
LOG=$SUB/despliegue.log
PHP=/usr/bin/php

exec > "$LOG" 2>&1
echo "==========================================================="
echo " DESPLIEGUE v2   $(date)"
echo "==========================================================="

paso ()  { echo; echo "----- $1 -----"; }
morir () { echo; echo "###########################################"; \
           echo "### FALLO: $1"; echo "###########################################"; exit 1; }

# --- 1. Comprobaciones previas --------------------------------------------
paso "1. Comprobaciones"
$PHP -v | head -1
cd "$APP" || morir "no existe la carpeta $APP"
echo "Directorio: $(pwd)"

echo
echo "Archivos que deberian estar presentes:"
for f in composer.json artisan bootstrap/app.php public/index.php deploy/htaccess \
         routes/web.php resources/views/bienvenida.blade.php; do
    if [ -f "$APP/$f" ]; then echo "  OK   $f"; else echo "  FALTA  $f"; FALTAN=1; fi
done
[ -n "$FALTAN" ] && morir "faltan archivos en el repositorio (mira la lista de arriba)"

# --- 2. Archivo de configuracion ------------------------------------------
paso "2. Configuracion (.env)"
if [ -f "$ENVFILE" ]; then
    cp "$ENVFILE" "$APP/.env" && chmod 600 "$APP/.env"
    echo "Copiado desde $ENVFILE"
elif [ -f "$APP/.env" ]; then
    echo "AVISO: no esta $ENVFILE, se conserva el .env existente"
else
    morir "no hay ningun .env — subelo a $ENVFILE"
fi

# --- 3. Composer -----------------------------------------------------------
paso "3. Composer"
mkdir -p "$TOOLS"
[ -f "$TOOLS/composer.phar" ] || curl -sS -L -o "$TOOLS/composer.phar" \
    https://getcomposer.org/download/latest-stable/composer.phar
export COMPOSER_HOME="$TOOLS/home"
export COMPOSER_NO_INTERACTION=1
$PHP -d memory_limit=-1 "$TOOLS/composer.phar" --version || morir "Composer no funciona"

# --- 4. Dependencias -------------------------------------------------------
paso "4. Instalacion de dependencias"
echo "(la primera vez tarda uno o dos minutos)"
$PHP -d memory_limit=-1 "$TOOLS/composer.phar" install \
     --no-dev --optimize-autoloader --no-progress \
  || morir "Composer no pudo instalar las dependencias — mira el detalle arriba"

[ -f "$APP/vendor/autoload.php" ] || morir "no se genero vendor/autoload.php"
echo
echo "Version instalada de Laravel:"
$PHP -r 'echo json_decode(file_get_contents("vendor/composer/installed.json"), true) ? "" : "";' 2>/dev/null
grep -m1 '"laravel/framework"' -A2 composer.lock 2>/dev/null | head -3

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

# --- 6. Clave de aplicacion ------------------------------------------------
paso "6. Clave de aplicacion"
if grep -q '^APP_KEY=$' "$APP/.env"; then
    $PHP artisan key:generate --force || morir "no se pudo generar la clave"
    cp "$APP/.env" "$ENVFILE" && echo "Clave guardada tambien en $ENVFILE"
else
    echo "Ya existe."
fi

# --- 7. Base de datos ------------------------------------------------------
paso "7. Migraciones"
$PHP artisan migrate --force || morir "fallaron las migraciones — revisa los datos de la base"

# --- 8. Optimizacion -------------------------------------------------------
paso "8. Optimizacion"
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan view:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

# --- 9. Publicacion --------------------------------------------------------
# La raiz web del subdominio es $SUB (el hosting no permite cambiarla),
# asi que copiamos ahi el contenido de public/.
paso "9. Publicacion"
cp -f "$APP/public/index.php"  "$SUB/index.php"   || morir "no se pudo copiar index.php"
cp -f "$APP/deploy/htaccess"   "$SUB/.htaccess"   || morir "no se pudo copiar el .htaccess"
cp -f "$APP/public/robots.txt" "$SUB/robots.txt"  2>/dev/null
[ -d "$APP/public/build" ] && cp -rf "$APP/public/build" "$SUB/"
rm -f "$SUB/index.html"
echo "Publicado en $SUB:"
ls -la "$SUB" | grep -E 'index.php|htaccess|robots'

# --- 10. Resumen -----------------------------------------------------------
paso "10. Resultado"
$PHP artisan --version
echo
echo "Tablas creadas:"
$PHP artisan db:table --json 2>/dev/null | head -5 || $PHP artisan migrate:status 2>&1 | head -20

echo
echo "==========================================================="
echo " DESPLIEGUE CORRECTO   $(date)"
echo " Comprueba:  https://beta.holaprivet.com"
echo "==========================================================="
