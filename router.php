<?php
declare(strict_types=1);

$requestPath = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

if (preg_match('#^/api(?:/(.*))?$#', $requestPath, $matches)) {
    $_GET['route'] = trim($matches[1] ?? '', '/');
    require __DIR__ . '/api.php';
    return true;
}

if (preg_match('#^/uploads/([a-f0-9]{24}\.(?:jpg|png|webp|gif))$#', $requestPath, $matches)) {
    $_GET['upload'] = $matches[1];
    require __DIR__ . '/api.php';
    return true;
}

if (preg_match('#(^|/)(?:servidor|\.git)(?:/|$)#i', $requestPath)
    || preg_match('#(^|/)\.[^/]+#', $requestPath)
    || preg_match('#\.(?:php|sql|json|env)$#i', $requestPath)) {
    http_response_code(404);
    return true;
}

$root = realpath(__DIR__);
$file = realpath($root . DIRECTORY_SEPARATOR . ltrim($requestPath, '/\\'));
if ($file !== false && ($file === $root || str_starts_with($file, $root . DIRECTORY_SEPARATOR)) && is_file($file)) {
    return false;
}

if ($requestPath === '/' || str_ends_with($requestPath, '/')) {
    return false;
}

http_response_code(404);
return true;