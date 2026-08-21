<?php

/* El muro de palabras: la aportacion voluntaria anual que mantiene el curso
   gratis. Los precios en centimos; la palabra se reserva media hora mientras
   se paga; la propiedad dura un año con un mes de gracia para renovar. */

return [
    'precios'        => ['normal' => 300, 'especial' => 600],
    'moneda'         => 'eur',
    'reserva_min'    => 30,
    'duracion_dias'  => 365,
    'gracia_dias'    => 30,
];
