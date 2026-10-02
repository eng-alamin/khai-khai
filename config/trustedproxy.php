<?php

/*
| Reverse-proxy trust list (Nginx, load balancer, Cloudflare ...).
|
| TRUSTED_PROXIES in .env:
|   empty          -> trust nobody (safe default, server is reached directly)
|   10.0.0.1,10.0.0.2 -> trust these proxy IPs
|   *              -> trust every proxy (only if the server cannot be reached
|                     from the internet except through the proxy)
|
| This lives in config/ (not read with env() in bootstrap/app.php) because
| env() returns null after "php artisan config:cache".
*/

return [
    'proxies' => env('TRUSTED_PROXIES'),
];
