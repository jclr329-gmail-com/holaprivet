<?php

return [
    // Carpeta con los Markdown del curso. Esta FUERA de la web y FUERA del
    // repositorio: el contenido y el codigo tienen vidas separadas.
    'ruta' => env('CONTENIDO_RUTA', base_path('../../private/contenido')),
];
