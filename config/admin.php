<?php

return [
    'username' => env('ADMIN_USERNAME', ''),
    'password' => env('ADMIN_PASSWORD', ''),
    'session_timeout' => (int) env('ADMIN_SESSION_TIMEOUT', 30),
];
