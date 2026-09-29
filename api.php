<?php
declare(strict_types=1);

require_once __DIR__ . '/servidor/config.php';

date_default_timezone_set('America/Mazatlan');

if (isset($_GET['upload'])) {
    $filename = (string)$_GET['upload'];
    if (!preg_match('/^[a-f0-9]{24}\.(jpg|png|webp|gif)$/', $filename)) {
        http_response_code(404);
        exit;
    }
    $imagePath = __DIR__ . '/servidor/uploads/' . $filename;
    $imageInfo = is_file($imagePath) ? @getimagesize($imagePath) : false;
    if (!$imageInfo) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $imageInfo['mime']);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($imagePath);
    exit;
}

final class ApiError extends RuntimeException
{
    public int $status;

    public function __construct(int $status, string $message)
    {
        parent::__construct($message);
        $this->status = $status;
    }
}

function responder($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function conexion(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function consulta(string $sql, array $params = []): PDOStatement
{
    $statement = conexion()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function unaFila(string $sql, array $params = []): ?array
{
    $row = consulta($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function todas(string $sql, array $params = []): array
{
    return consulta($sql, $params)->fetchAll();
}

function transaccion(callable $callback)
{
    $pdo = conexion();
    $pdo->beginTransaction();
    try {
        $result = $callback($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function texto($value, int $max): string
{
    $value = trim((string)($value ?? ''));
    return mb_substr($value, 0, $max, 'UTF-8');
}

function entero($value): int
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    return $number !== false && $number >= 0 && $number < 100000 ? $number : 0;
}

function bodyJson(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '') {
        return [];
    }
    $body = json_decode($raw, true);
    if ($body === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new ApiError(400, 'El cuerpo de la solicitud no es JSON válido.');
    }
    return is_array($body) ? $body : [];
}

function tokenCodificar(array $payload): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $encode = static function (string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    };
    $unsigned = $encode((string)json_encode($header)) . '.' . $encode((string)json_encode($payload));
    $signature = hash_hmac('sha256', $unsigned, JWT_SECRET, true);
    return $unsigned . '.' . $encode($signature);
}

function usuarioAutenticado(): int
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        throw new ApiError(401, 'Inicia sesión para continuar.');
    }

    $parts = explode('.', $matches[1]);
    if (count($parts) !== 3) {
        throw new ApiError(401, 'Tu sesión expiró. Inicia sesión de nuevo.');
    }
    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $unsigned = $encodedHeader . '.' . $encodedPayload;
    $signature = base64_decode(strtr($encodedSignature, '-_', '+/') . str_repeat('=', (4 - strlen($encodedSignature) % 4) % 4), true);
    $payloadJson = base64_decode(strtr($encodedPayload, '-_', '+/') . str_repeat('=', (4 - strlen($encodedPayload) % 4) % 4), true);
    $payload = $payloadJson === false ? null : json_decode($payloadJson, true);
    $expected = hash_hmac('sha256', $unsigned, JWT_SECRET, true);

    if ($signature === false || !hash_equals($expected, $signature) || !is_array($payload)
        || !isset($payload['id'], $payload['exp']) || (int)$payload['exp'] < time()) {
        throw new ApiError(401, 'Tu sesión expiró. Inicia sesión de nuevo.');
    }
    return (int)$payload['id'];
}

function firmarUsuario(int $id): string
{
    return tokenCodificar(['id' => $id, 'exp' => time() + 7 * 24 * 60 * 60]);
}

function filaStaff(int $id): array
{
    $staff = unaFila('SELECT * FROM staff WHERE id_staff = ?', [$id]);
    if (!$staff) {
        throw new ApiError(404, 'No encontramos a este asesor.');
    }
    return $staff;
}

function horarioTexto(array $rows): string
{
    if (!$rows) {
        return 'Por definir';
    }
    $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
    $bonitos = ['Miercoles' => 'Miércoles', 'Sabado' => 'Sábado'];
    $grupos = [];
    foreach ($rows as $row) {
        $key = $row['hora_inicio'] . '-' . $row['hora_fin'];
        $index = array_search($row['dias_semana'], $dias, true);
        if (!isset($grupos[$key])) {
            $grupos[$key] = ['ini' => $row['hora_inicio'], 'fin' => $row['hora_fin'], 'dias' => []];
        }
        if ($index !== false) {
            $grupos[$key]['dias'][] = $index;
        }
    }
    $formatearHora = static function (string $time): string {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        return ($hour % 12 ?: 12) . ':' . str_pad((string)$minute, 2, '0', STR_PAD_LEFT) . ($hour >= 12 ? ' PM' : ' AM');
    };
    $result = [];
    foreach ($grupos as $group) {
        sort($group['dias']);
        $names = array_map(static function (int $index) use ($dias, $bonitos): string {
            return $bonitos[$dias[$index]] ?? $dias[$index];
        }, $group['dias']);
        $sequential = true;
        for ($i = 1; $i < count($group['dias']); $i++) {
            if ($group['dias'][$i] !== $group['dias'][$i - 1] + 1) {
                $sequential = false;
                break;
            }
        }
        $dayText = count($names) === 1 ? $names[0]
            : ($sequential ? $names[0] . ' a ' . $names[count($names) - 1] : implode(', ', $names));
        $result[] = $dayText . ' de ' . $formatearHora($group['ini']) . ' a ' . $formatearHora($group['fin']);
    }
    return implode(' · ', $result);
}

function armarStaff(array $staff, array $horarios): array
{
    $digits = preg_replace('/\D/', '', (string)$staff['telefono']);
    $phone = strlen($digits) === 10
        ? substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6)
        : $staff['telefono'];
    $firstName = explode(' ', $staff['nombre'])[0];
    return [
        'id' => (int)$staff['id_staff'],
        'nombre' => $staff['nombre'],
        'puesto' => $staff['puesto'],
        'lugar' => $staff['lugar_trabajo'],
        'correo' => $staff['correo'],
        'telefono' => $phone,
        'whatsapp' => strlen($digits) === 10 ? '52' . $digits : $digits,
        'foto' => $staff['url_imagen_staff'],
        'descripcion' => $staff['descripcion'] ?: $firstName . ' forma parte del equipo de Hogary\'s y puede ayudarte a encontrar el inmueble que estás buscando.',
        'horario' => horarioTexto($horarios),
    ];
}

function filasPropiedad(string $where, array $params = []): array
{
    return todas(
        "SELECT p.id_propiedad AS id, p.id_usuario, p.operacion, p.tipo, p.precio, p.recamaras, p.banios,
                p.metros_cuadrados, p.descripcion, p.amenidades, p.estatus, d.ciudad, d.colonia, u.nombre AS agente
         FROM propiedades p JOIN usuarios u ON u.id_usuario = p.id_usuario
         LEFT JOIN direcciones_propiedad d ON d.id_propiedad = p.id_propiedad $where",
        $params
    );
}

function armarPropiedades(array $rows): array
{
    $unique = [];
    foreach ($rows as $row) {
        $unique[(int)$row['id']] = $row;
    }
    if (!$unique) {
        return [];
    }
    $ids = array_keys($unique);
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $images = todas(
        "SELECT id_propiedad, url_imagen_propiedad AS url FROM imagenes_propiedad WHERE id_propiedad IN ($marks) ORDER BY id_imagen_propiedad",
        $ids
    );
    $photos = [];
    foreach ($images as $image) {
        $photos[(int)$image['id_propiedad']][] = $image['url'];
    }
    $result = [];
    foreach ($unique as $id => $row) {
        $amenities = json_decode((string)($row['amenidades'] ?? ''), true);
        $result[] = [
            'id' => $id,
            'op' => $row['operacion'] === 'Renta' ? 'rentar' : 'comprar',
            'tipo' => $row['tipo'],
            'precio' => (float)$row['precio'],
            'ciudad' => $row['ciudad'] ?? '',
            'colonia' => $row['colonia'] ?? '',
            'recamaras' => (int)($row['recamaras'] ?? 0),
            'banos' => (int)($row['banios'] ?? 0),
            'm2' => (int)($row['metros_cuadrados'] ?? 0),
            'desc' => $row['descripcion'] ?? '',
            'amenidades' => is_array($amenities) ? $amenities : [],
            'agente' => $row['agente'],
            'estatus' => $row['estatus'],
            'fotos' => $photos[$id] ?? [],
        ];
    }
    return $result;
}

function validarPropiedad(array $body): array
{
    $operation = ($body['op'] ?? '') === 'rentar' ? 'Renta'
        : (($body['op'] ?? '') === 'comprar' ? 'Venta' : null);
    if (!$operation) {
        throw new ApiError(400, 'Operación inválida.');
    }
    if (!in_array($body['tipo'] ?? '', ['Casa', 'Departamento', 'Terreno'], true)) {
        throw new ApiError(400, 'Tipo de inmueble inválido.');
    }
    $city = texto($body['ciudad'] ?? '', 100);
    $neighborhood = texto($body['colonia'] ?? '', 100);
    $price = filter_var($body['precio'] ?? null, FILTER_VALIDATE_FLOAT);
    if (!$city || !$neighborhood || $price === false || $price <= 0 || $price >= 1e10) {
        throw new ApiError(400, 'Completa al menos ciudad, colonia y precio.');
    }
    $amenities = is_array($body['amenidades'] ?? null) ? $body['amenidades'] : [];
    $amenities = array_slice(array_values(array_filter(array_map(static function ($item): string {
        return texto($item, 80);
    }, $amenities))), 0, 20);
    $photos = is_array($body['fotos'] ?? null) ? array_slice($body['fotos'], 0, 10) : [];
    return [
        'operacion' => $operation,
        'tipo' => $body['tipo'],
        'ciudad' => $city,
        'colonia' => $neighborhood,
        'precio' => $price,
        'recamaras' => entero($body['recamaras'] ?? 0),
        'banios' => entero($body['banos'] ?? 0),
        'm2' => entero($body['m2'] ?? 0),
        'descripcion' => texto($body['desc'] ?? '', 5000) ?: null,
        'amenidades' => $amenities,
        'fotos' => $photos,
    ];
}

function procesarFotos(array $photos, array $existingPhotos = []): array
{
    // Una URL subida solo se puede conservar en la propiedad que ya la utiliza.
    foreach ($photos as $photo) {
        if (is_string($photo) && str_starts_with($photo, '/uploads/') && !in_array($photo, $existingPhotos, true)) {
            throw new ApiError(403, 'No puedes utilizar fotos subidas de otra propiedad.');
        }
    }
    $uploadsDir = __DIR__ . '/servidor/uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
        throw new RuntimeException('No se pudo crear el directorio de fotos.');
    }
    $urls = [];
    $dataPattern = '#^data:image/(png|jpeg|jpg|webp|gif);base64,([A-Za-z0-9+/=]+)$#';
    $pathPattern = '#^(?:/uploads/[\w.-]+|video_imagenes/[\w./-]+)$#';
    foreach ($photos as $photo) {
        if (!is_string($photo)) {
            continue;
        }
        if (strlen($photo) > 100 && preg_match($dataPattern, $photo, $matches)) {
            $bytes = base64_decode($matches[2], true);
            if ($bytes === false || strlen($bytes) > 8 * 1024 * 1024) {
                throw new ApiError(413, 'Una de las fotos pesa más de 8 MB.');
            }
            $imageInfo = @getimagesizefromstring($bytes);
            $allowedMimes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
            if (!$imageInfo || !in_array($imageInfo['mime'], $allowedMimes, true)) {
                throw new ApiError(400, 'Una de las fotos no es una imagen válida.');
            }
            $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$imageInfo['mime']];
            $filename = bin2hex(random_bytes(12)) . '.' . $extension;
            if (file_put_contents($uploadsDir . '/' . $filename, $bytes, LOCK_EX) === false) {
                throw new RuntimeException('No se pudo guardar una foto.');
            }
            $urls[] = '/uploads/' . $filename;
        } elseif (preg_match($pathPattern, $photo) && strpos($photo, '..') === false) {
            $urls[] = $photo;
        }
    }
    return $urls;
}

function borrarFotos(array $urls): void
{
    foreach ($urls as $url) {
        if (is_string($url) && str_starts_with($url, '/uploads/')) {
            // Protege también fotos compartidas antes de esta validación.
            if (unaFila('SELECT 1 FROM imagenes_propiedad WHERE url_imagen_propiedad = ? LIMIT 1', [$url])) {
                continue;
            }
            $path = __DIR__ . '/servidor/uploads/' . basename($url);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}

function exigirPropiedadUsuario(int $propertyId, int $userId): void
{
    $property = unaFila('SELECT id_usuario FROM propiedades WHERE id_propiedad = ?', [$propertyId]);
    if (!$property) {
        throw new ApiError(404, 'No encontramos esta propiedad.');
    }
    if ((int)$property['id_usuario'] !== $userId) {
        throw new ApiError(403, 'Esta propiedad no es tuya.');
    }
}

function hora24($value): ?string
{
    $value = trim((string)$value);
    if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $value, $matches)) {
        if ((int)$matches[1] < 1 || (int)$matches[1] > 12 || (int)$matches[2] > 59) {
            return null;
        }
        $hour = (int)$matches[1] % 12;
        if (strtoupper($matches[3]) === 'PM') {
            $hour += 12;
        }
        return sprintf('%02d:%02d:00', $hour, (int)$matches[2]);
    }
    if (preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', $value, $matches)
        && (int)$matches[1] < 24 && (int)$matches[2] < 60) {
        return sprintf('%02d:%02d:00', (int)$matches[1], (int)$matches[2]);
    }
    return null;
}

function enviarCors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = array_filter(array_map('trim', explode(',', CORS_ORIGINS)));
    if ($origin !== '' && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
}

enviarCors();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$route = trim((string)($_GET['route'] ?? ''), '/');
$userId = null;

try {
    $body = bodyJson();
    if ($route === 'registro' && $method === 'POST') {
        $name = texto($body['nombre'] ?? '', 100);
        $email = mb_strtolower(texto($body['email'] ?? '', 100), 'UTF-8');
        $password = (string)($body['clave'] ?? '');
        if (!$name || !$email || !$password) {
            throw new ApiError(400, 'Llena todos los campos.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiError(400, 'Escribe un correo válido.');
        }
        if (strlen($password) < 6) {
            throw new ApiError(400, 'La contraseña debe tener al menos 6 caracteres.');
        }
        if (unaFila('SELECT 1 FROM usuarios WHERE correo = ?', [$email])) {
            throw new ApiError(409, 'Ese correo ya está registrado.');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        consulta('INSERT INTO usuarios (nombre, correo, telefono, contrasena_hash) VALUES (?, ?, ?, ?)', [$name, $email, '', $hash]);
        $id = (int)conexion()->lastInsertId();
        responder(['usuario' => ['id' => $id, 'nombre' => $name, 'email' => $email], 'token' => firmarUsuario($id)], 201);
    }

    if ($route === 'login' && $method === 'POST') {
        $email = mb_strtolower(texto($body['email'] ?? '', 100), 'UTF-8');
        $password = (string)($body['clave'] ?? '');
        if (!$email || !$password) {
            throw new ApiError(400, 'Llena todos los campos.');
        }
        $user = unaFila('SELECT id_usuario, nombre, correo, contrasena_hash FROM usuarios WHERE correo = ?', [$email]);
        if (!$user || !password_verify($password, $user['contrasena_hash'])) {
            throw new ApiError(401, 'Correo o contraseña incorrectos.');
        }
        $id = (int)$user['id_usuario'];
        responder(['usuario' => ['id' => $id, 'nombre' => $user['nombre'], 'email' => $user['correo']], 'token' => firmarUsuario($id)]);
    }

    if ($route === 'yo' && $method === 'GET') {
        $userId = usuarioAutenticado();
        $user = unaFila('SELECT id_usuario, nombre, correo, telefono, descripcion FROM usuarios WHERE id_usuario = ?', [$userId]);
        if (!$user) {
            throw new ApiError(401, 'Tu cuenta ya no existe.');
        }
        responder(['id' => (int)$user['id_usuario'], 'nombre' => $user['nombre'], 'email' => $user['correo'], 'telefono' => $user['telefono'] ?: '', 'bio' => $user['descripcion'] ?: '']);
    }

    if ($route === 'yo' && $method === 'PUT') {
        $userId = usuarioAutenticado();
        $name = texto($body['nombre'] ?? '', 100);
        $phone = texto($body['telefono'] ?? '', 15);
        $bio = texto($body['bio'] ?? '', 2000);
        if (!$name) {
            throw new ApiError(400, 'El nombre no puede quedar vacío.');
        }
        consulta('UPDATE usuarios SET nombre = ?, telefono = ?, descripcion = ? WHERE id_usuario = ?', [$name, $phone, $bio ?: null, $userId]);
        $user = unaFila('SELECT id_usuario, nombre, correo FROM usuarios WHERE id_usuario = ?', [$userId]);
        responder(['usuario' => ['id' => (int)$user['id_usuario'], 'nombre' => $user['nombre'], 'email' => $user['correo']]]);
    }

    if ($route === 'propiedades' && $method === 'GET') {
        responder(armarPropiedades(filasPropiedad("WHERE p.estatus = 'Disponible' ORDER BY p.id_propiedad")));
    }
    if (preg_match('#^propiedades/(\d+)$#', $route, $matches)) {
        $id = (int)$matches[1];
        if ($method === 'GET') {
            $rows = filasPropiedad('WHERE p.id_propiedad = ?', [$id]);
            if (!$rows) {
                throw new ApiError(404, 'No encontramos esta propiedad.');
            }
            responder(armarPropiedades($rows)[0]);
        }
        if ($method === 'PUT') {
            $userId = usuarioAutenticado();
            exigirPropiedadUsuario($id, $userId);
            $property = validarPropiedad($body);
            $oldPhotos = todas('SELECT url_imagen_propiedad AS url FROM imagenes_propiedad WHERE id_propiedad = ?', [$id]);
            $photos = procesarFotos($property['fotos'], array_column($oldPhotos, 'url'));
            transaccion(static function () use ($property, $photos, $id): void {
                consulta('UPDATE propiedades SET operacion = ?, tipo = ?, precio = ?, recamaras = ?, banios = ?, metros_cuadrados = ?, descripcion = ?, amenidades = ? WHERE id_propiedad = ?', [
                    $property['operacion'], $property['tipo'], $property['precio'], $property['recamaras'], $property['banios'], $property['m2'], $property['descripcion'], json_encode($property['amenidades'], JSON_UNESCAPED_UNICODE), $id,
                ]);
                $address = unaFila('SELECT id_direccion_propiedad FROM direcciones_propiedad WHERE id_propiedad = ? LIMIT 1', [$id]);
                if ($address) {
                    consulta('UPDATE direcciones_propiedad SET ciudad = ?, colonia = ? WHERE id_direccion_propiedad = ?', [$property['ciudad'], $property['colonia'], $address['id_direccion_propiedad']]);
                } else {
                    consulta('INSERT INTO direcciones_propiedad (id_propiedad, ciudad, colonia) VALUES (?, ?, ?)', [$id, $property['ciudad'], $property['colonia']]);
                }
                consulta('DELETE FROM imagenes_propiedad WHERE id_propiedad = ?', [$id]);
                foreach ($photos as $url) {
                    consulta('INSERT INTO imagenes_propiedad (id_propiedad, url_imagen_propiedad) VALUES (?, ?)', [$id, $url]);
                }
            });
            borrarFotos(array_diff(array_column($oldPhotos, 'url'), $photos));
            responder(armarPropiedades(filasPropiedad('WHERE p.id_propiedad = ?', [$id]))[0]);
        }
        if ($method === 'DELETE') {
            $userId = usuarioAutenticado();
            exigirPropiedadUsuario($id, $userId);
            $oldPhotos = todas('SELECT url_imagen_propiedad AS url FROM imagenes_propiedad WHERE id_propiedad = ?', [$id]);
            transaccion(static function () use ($id): void {
                consulta('DELETE FROM favoritos WHERE id_propiedad = ?', [$id]);
                consulta('DELETE FROM imagenes_propiedad WHERE id_propiedad = ?', [$id]);
                consulta('DELETE FROM direcciones_propiedad WHERE id_propiedad = ?', [$id]);
                consulta('DELETE FROM propiedades WHERE id_propiedad = ?', [$id]);
            });
            borrarFotos(array_column($oldPhotos, 'url'));
            responder(['ok' => true]);
        }
    }
    if ($route === 'propiedades' && $method === 'POST') {
        $userId = usuarioAutenticado();
        $property = validarPropiedad($body);
        $photos = procesarFotos($property['fotos']);
        $id = transaccion(static function () use ($property, $photos, $userId): int {
            consulta('INSERT INTO propiedades (id_usuario, operacion, tipo, precio, recamaras, banios, metros_cuadrados, descripcion, amenidades) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $userId, $property['operacion'], $property['tipo'], $property['precio'], $property['recamaras'], $property['banios'], $property['m2'], $property['descripcion'], json_encode($property['amenidades'], JSON_UNESCAPED_UNICODE),
            ]);
            $propertyId = (int)conexion()->lastInsertId();
            consulta('INSERT INTO direcciones_propiedad (id_propiedad, ciudad, colonia) VALUES (?, ?, ?)', [$propertyId, $property['ciudad'], $property['colonia']]);
            foreach ($photos as $url) {
                consulta('INSERT INTO imagenes_propiedad (id_propiedad, url_imagen_propiedad) VALUES (?, ?)', [$propertyId, $url]);
            }
            return $propertyId;
        });
        responder(armarPropiedades(filasPropiedad('WHERE p.id_propiedad = ?', [$id]))[0], 201);
    }
    if ($route === 'mis-propiedades' && $method === 'GET') {
        $userId = usuarioAutenticado();
        responder(armarPropiedades(filasPropiedad('WHERE p.id_usuario = ? ORDER BY p.id_propiedad DESC', [$userId])));
    }

    if ($route === 'favoritos' && $method === 'GET') {
        $userId = usuarioAutenticado();
        responder(array_map(static fn(array $row): int => (int)$row['id_propiedad'], todas('SELECT id_propiedad FROM favoritos WHERE id_usuario = ? ORDER BY fecha_agregado', [$userId])));
    }
    if (preg_match('#^favoritos/(\d+)$#', $route, $matches) && $method === 'POST') {
        $userId = usuarioAutenticado();
        $id = (int)$matches[1];
        if (!unaFila('SELECT 1 FROM propiedades WHERE id_propiedad = ?', [$id])) {
            throw new ApiError(404, 'No encontramos esta propiedad.');
        }
        if (unaFila('SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_propiedad = ?', [$userId, $id])) {
            consulta('DELETE FROM favoritos WHERE id_usuario = ? AND id_propiedad = ?', [$userId, $id]);
            responder(['guardado' => false]);
        }
        consulta('INSERT INTO favoritos (id_usuario, id_propiedad) VALUES (?, ?)', [$userId, $id]);
        responder(['guardado' => true]);
    }

    if ($route === 'staff' && $method === 'GET') {
        $staffList = todas('SELECT * FROM staff ORDER BY id_staff');
        $schedules = todas('SELECT * FROM horarios_staff');
        responder(array_map(static function (array $staff) use ($schedules): array {
            $staffSchedules = array_values(array_filter($schedules, static fn(array $schedule): bool => (int)$schedule['id_staff'] === (int)$staff['id_staff']));
            return armarStaff($staff, $staffSchedules);
        }, $staffList));
    }
    if (preg_match('#^staff/(\d+)(?:/(resenias|citas))?$#', $route, $matches)) {
        $staff = filaStaff((int)$matches[1]);
        $subroute = $matches[2] ?? '';
        if ($subroute === '' && $method === 'GET') {
            responder(armarStaff($staff, todas('SELECT * FROM horarios_staff WHERE id_staff = ?', [$staff['id_staff']])));
        }
        if ($subroute === 'resenias' && $method === 'GET') {
            $reviews = todas('SELECT u.nombre, r.calificacion, r.mensaje FROM resenias r JOIN usuarios u ON u.id_usuario = r.id_usuario WHERE r.id_staff = ? ORDER BY r.id_resenia', [$staff['id_staff']]);
            $total = count($reviews);
            $average = $total ? array_sum(array_column($reviews, 'calificacion')) / $total : 0;
            responder([
                'total' => $total,
                'promedio' => $average,
                'resenias' => array_map(static fn(array $review): array => ['nombre' => $review['nombre'], 'calificacion' => (int)$review['calificacion'], 'comentario' => $review['mensaje'] ?: ''], $reviews),
            ]);
        }
        if ($subroute === 'resenias' && $method === 'POST') {
            $userId = usuarioAutenticado();
            $rating = filter_var($body['calificacion'] ?? null, FILTER_VALIDATE_INT);
            $comment = texto($body['comentario'] ?? '', 1000);
            if ($rating === false || $rating < 1 || $rating > 5) {
                throw new ApiError(400, 'La calificación debe ser de 1 a 5.');
            }
            if (!$comment) {
                throw new ApiError(400, 'Escribe un comentario.');
            }
            consulta('INSERT INTO resenias (id_usuario, id_staff, calificacion, mensaje) VALUES (?, ?, ?, ?)', [$userId, $staff['id_staff'], $rating, $comment]);
            responder(['ok' => true], 201);
        }
        if ($subroute === 'citas' && $method === 'POST') {
            $userId = usuarioAutenticado();
            $date = (string)($body['fecha'] ?? '');
            $time = hora24($body['hora'] ?? '');
            $message = texto($body['mensaje'] ?? '', 2000);
            $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
            if (!$dateObject || $dateObject->format('Y-m-d') !== $date || !$time) {
                throw new ApiError(400, 'Elige una fecha y una hora válidas.');
            }
            if ($date < date('Y-m-d')) {
                throw new ApiError(400, 'La fecha no puede ser anterior a hoy.');
            }
            $weekday = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'][(int)$dateObject->format('N') - 1];
            if (!unaFila('SELECT 1 FROM horarios_staff WHERE id_staff = ? AND dias_semana = ? AND ? BETWEEN hora_inicio AND hora_fin LIMIT 1', [$staff['id_staff'], $weekday, $time])) {
                throw new ApiError(400, 'El asesor no atiende en ese día u hora. Revisa su horario de atención.');
            }
            if (unaFila('SELECT 1 FROM agendas WHERE id_staff = ? AND fecha = ? AND hora = ?', [$staff['id_staff'], $date, $time])) {
                throw new ApiError(409, 'Ese horario ya está reservado. Elige otro.');
            }
            consulta('INSERT INTO agendas (id_usuario, id_staff, fecha, hora, mensaje) VALUES (?, ?, ?, ?, ?)', [$userId, $staff['id_staff'], $date, $time, $message ?: null]);
            responder(['ok' => true], 201);
        }
    }

    throw new ApiError(404, 'Ruta no encontrada.');
} catch (ApiError $error) {
    responder(['error' => $error->getMessage()], $error->status);
} catch (Throwable $error) {
    error_log((string)$error);
    responder(['error' => 'Error del servidor. Intenta de nuevo.'], 500);
}
