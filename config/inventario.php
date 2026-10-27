<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Correo de alerta de stock bajo
    |--------------------------------------------------------------------------
    | Destinatario(s) del resumen diario de stock bajo que envía el comando
    | `inventario:revisar-stock`. Varios correos separados por coma.
    | Si queda vacío, el comando sigue generando las órdenes pero NO envía correo.
    |
    | Para que el correo salga de verdad hay que configurar un MAIL_MAILER real
    | (smtp) en el .env; con MAIL_MAILER=log solo se escribe en storage/logs.
    */
    'alerta_correo' => env('ALERTA_STOCK_CORREO'),

];
