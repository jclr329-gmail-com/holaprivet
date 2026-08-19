# holaprivet.com

Plataforma del curso de español para rusohablantes.

## Qué es esto

Aplicación Laravel 11 sobre PHP 8.3 y MySQL 8.4.
El **contenido del curso no vive aquí**: son 95 archivos Markdown que se
importan a la base de datos.

## Estructura

```
app/          código de la aplicación
bootstrap/    arranque
database/     migraciones
deploy/       script de despliegue
public/       front controller y recursos públicos
resources/    vistas
routes/       rutas
storage/      caché, sesiones, registros  (no versionado)
vendor/       dependencias                (no versionado)
```

## Despliegue

Automático desde el panel del hosting. El script `deploy/desplegar.sh`:

1. Copia el `.env` desde fuera del repositorio
2. Descarga Composer si hace falta e instala dependencias
3. Prepara las carpetas de trabajo
4. Genera la clave de aplicación la primera vez
5. Ejecuta las migraciones
6. Cachea configuración, rutas y vistas
7. Publica `public/` en la raíz del subdominio

El registro queda en `despliegue.log`.

## Lo que nunca se sube al repositorio

- `.env` — vive en `private/beta.env`, fuera de la web
- `vendor/` — lo genera Composer en el servidor
- `storage/` y `bootstrap/cache` — contenido temporal

## Entornos

| | |
|---|---|
| Pruebas | https://beta.holaprivet.com |
| Producción | https://holaprivet.com *(pendiente)* |
