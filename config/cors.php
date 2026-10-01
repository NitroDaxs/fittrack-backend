<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    // Comma-separated list of front-end origins; defaults to the Vite dev server.
    'allowed_origins' => array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))),
];
