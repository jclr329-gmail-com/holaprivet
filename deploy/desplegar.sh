#!/bin/bash
# ===========================================================================
#  holaprivet.com — script de despliegue
#  Se ejecuta desde las "acciones adicionales" de Git en el panel.
#  Registra todo en  <SUB>/despliegue.log  para poder verlo desde el navegador.
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
echo " DESPLIEGUE  $(date)"
echo "==========================================================="

paso () { echo; echo "----- $1 -----"; }

# --- 1. Comprobaciones previas --------------------------------------------
paso "1. Comprobaciones"
$PHP -v | head -1
cd "$APP" || { echo "ERROR: no existe $APP"; exit 1; }
echo "Directorio: $(pwd)"

# --- 2. Archivo de configuracion ------------------------------------------
paso "2. Configuracion (.env)"
if [ -f "$ENVFILE" ]; then
    cp "$ENVFILE" "$APP/.env"
    chmod 600 "$APP/.env"
    echo "Copiado desde $ENVFILE"
else
    echo "AVISO: no se encuentra $ENVFILE"
    if [ -f "$APP/.env" ]; then
        echo "Se conserva el .env existente en app/"
    else
        echo "ERROR: no hay ningun .env. Subelo a $ENVFILE por FTP."
        exit 1
    fi
fi

# --- 3. Composer -----------------------------------------------------------
paso "3. Composer"
mkdir -p "$TOOLS"
if [ ! -f "$TOOLS/composer.phar" ]; then
    echo "Descargando Composer..."
    curl -sS -L -o "$TOOLS/composer.phar" https://getcomposer.org/download/latest-stable/composer.phar
fi
export COMPOSER_HOME="$TOOLS/home"
export COMPOSER_ALLOW_SUPERUSER=1
$PHP -d memory_limit=-1 "$TOOLS/composer.phar" --version

paso "4. Instalacion de dependencias"
$PHP -d memory_limit=-1 "$TOOLS/composer.phar" install \
     --no-dev --optimize-autoloader --no-interaction --no-progress

# --- 5. Carpetas de trabajo ------------------------------------------------
paso "5. Carpetas de trabajo"
mkdir -p "$APP/storage/framework/cache/data" \
         "$APP/storage/framework/sessions" \
         "$APP/storage/framework/views" \
         "$APP/storage/logs" \
         "$APP/bootstrap/cache"
chmod -R 775 "$APP/storage" "$APP/bootstrap/cache"
echo "Listas."

# --- 6. Clave de aplicacion ------------------------------------------------
paso "6. Clave de aplicacion"
if grep -q '^APP_KEY=$' "$APP/.env"; then
    $PHP artisan key:generate --force
    cp "$APP/.env" "$ENVFILE" 2>/dev/null && echo "Clave guardada tambien en $ENVFILE"
else
    echo "Ya existe."
fi

# --- 7. Base de datos ------------------------------------------------------
paso "7. Migraciones"
$PHP artisan migrate --force

# --- 8. Cacheado -----------------------------------------------------------
paso "8. Optimizacion"
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan view:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

# --- 9. Publicacion --------------------------------------------------------
# La raiz web del subdominio es $SUB, no $APP/public.
# Copiamos el contenido de public/ a la raiz para que el sitio funcione
# sin tocar la configuracion del hosting.
paso "9. Publicacion de la carpeta public"
cp -f "$APP/public/index.php"   "$SUB/index.php"
cp -f "$APP/public/.htaccess"   "$SUB/.htaccess"
cp -f "$APP/public/robots.txt"  "$SUB/robots.txt"
[ -d "$APP/public/build" ] && cp -rf "$APP/public/build" "$SUB/"
rm -f "$SUB/index.html"
echo "Publicado."

# --- 10. Resumen -----------------------------------------------------------
paso "10. Resultado"
$PHP artisan --version
echo
echo "Tablas en la base de datos:"
$PHP artisan db:show --json 2>/dev/null | head -20 || echo "(db:show no disponible)"
echo
echo "==========================================================="
echo " DESPLIEGUE TERMINADO  $(date)"
echo "==========================================================="
