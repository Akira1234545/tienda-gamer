<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

load_env(__DIR__ . '/../.env');

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'tienda_gamer'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', '1800'));

function load_env(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function db(): ?PDO
{
    static $pdo = null;
    static $failed = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($failed) {
        return null;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (PDOException $exception) {
        $failed = true;
        $GLOBALS['db_error'] = $exception->getMessage();
        return null;
    }
}

function db_all(string $sql, array $params = []): array
{
    $pdo = db();

    if (!$pdo) {
        return [];
    }

    try {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    } catch (PDOException $exception) {
        $GLOBALS['db_error'] = $exception->getMessage();
        return [];
    }
}

function db_one(string $sql, array $params = []): ?array
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

function db_value(string $sql, array $params = [], mixed $default = 0): mixed
{
    $row = db_one($sql, $params);

    if (!$row) {
        return $default;
    }

    return array_values($row)[0] ?? $default;
}

function db_error(): ?string
{
    return $GLOBALS['db_error'] ?? null;
}

function ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $sessionPath = __DIR__ . '/../storage/sessions';

        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0775, true);
        }

        if (is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }

        session_name(env('SESSION_NAME', 'GAMEZONESESSID'));
        session_start();
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|string|null $value): string
{
    return '$' . number_format((float) $value, 2);
}

function product_image(?string $image, string $fallback = 'producto1.jpg'): string
{
    if (!$image) {
        return 'assets/img/' . $fallback;
    }

    if (str_starts_with($image, 'assets/')) {
        return $image;
    }

    return 'assets/img/' . $image;
}

function store_info(): array
{
    $defaults = [
        'nombre' => 'GameZone Store',
        'descripcion' => 'Tienda especializada en hardware, perifericos y equipos gamer.',
        'ubicacion' => 'Av. Gamer 123, Zona Central, La Paz.',
        'horario' => 'Lunes a sabado de 09:00 a 19:00.',
        'correo' => 'ventas@gamezone.test',
        'telefono' => '+591 70000000',
    ];

    $info = db_one(
        'SELECT nombre, descripcion, ubicacion, horario, correo, telefono
         FROM configuracion_tienda
         WHERE id_configuracion = 1'
    );

    return array_merge($defaults, $info ?? []);
}

function current_user(): ?array
{
    ensure_session();

    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    return db_one(
        'SELECT id_usuario, nombre, correo, rol, estado_2fa, fecha_registro FROM usuario WHERE id_usuario = ?',
        [$_SESSION['usuario_id']]
    );
}

function flash(string $type, string $message): void
{
    ensure_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_messages(): array
{
    ensure_session();
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_logged_in(): bool
{
    ensure_session();
    return !empty($_SESSION['usuario_id']);
}

function is_admin(): bool
{
    ensure_session();
    return ($_SESSION['usuario_rol'] ?? null) === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Debes iniciar sesion para continuar.');
        redirect('login.php');
    }

    if (!empty($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > SESSION_LIFETIME) {
        $_SESSION = [];
        session_destroy();
        ensure_session();
        flash('warning', 'Tu sesion expiro. Inicia sesion nuevamente.');
        redirect('login.php');
    }

    $_SESSION['last_activity'] = time();
}

function require_role(string $role): void
{
    require_login();

    if (($_SESSION['usuario_rol'] ?? null) !== $role) {
        flash('danger', 'No tienes permiso para acceder a esa seccion.');
        redirect('index.php');
    }
}

function column_password(): string
{
    return '`contrase' . "\xC3\xB1" . 'a`';
}

function cart_items(): array
{
    ensure_session();
    return $_SESSION['carrito'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart_items());
}

function csrf_token(): string
{
    ensure_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    ensure_session();
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new RuntimeException('Token de seguridad invalido. Recarga la pagina e intenta nuevamente.');
    }
}

function start_user_session(array $usuario): void
{
    ensure_session();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
    $_SESSION['last_activity'] = time();
}

function send_2fa_email(string $to, string $name, string $code): bool
{
    if (env('SMTP_ENABLED', 'false') !== 'true') {
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = env('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USER');
        $mail->Password = env('SMTP_PASS');
        $mail->Port = (int) env('SMTP_PORT', '587');
        $mail->SMTPSecure = env('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(env('SMTP_FROM', env('SMTP_USER')), env('SMTP_FROM_NAME', 'GameZone Store'));
        $mail->addAddress($to, $name);
        $mail->Subject = 'Codigo de verificacion GameZone';
        $mail->Body = "Hola {$name}, tu codigo de verificacion es: {$code}. Expira en 5 minutos.";
        $mail->send();

        return true;
    } catch (MailException) {
        return false;
    }
}

function upload_product_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('La imagen no debe superar 2MB.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $mime = mime_content_type($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');
    }

    $directory = __DIR__ . '/../assets/img/productos';

    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $filename = 'producto_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $directory . '/' . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return 'productos/' . $filename;
}
