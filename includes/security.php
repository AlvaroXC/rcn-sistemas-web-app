<?php

if (!defined('ADMIN_SESSION_TIMEOUT')) {
    define('ADMIN_SESSION_TIMEOUT', 1800);
}

function is_https_request() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
}

function start_secure_session($requireAdmin = false) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => is_https_request(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if ($requireAdmin) {
        if (!isset($_SESSION['admin_id'])) {
            header("Location: login.php");
            exit;
        }

        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ($lastActivity && time() - $lastActivity > ADMIN_SESSION_TIMEOUT) {
            $_SESSION = [];
            session_destroy();
            header("Location: login.php?timeout=1");
            exit;
        }

        $_SESSION['last_activity'] = time();
    }
}

function current_admin_id() {
    start_secure_session(false);
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
}

function current_admin_role() {
    start_secure_session(false);
    $role = trim((string) ($_SESSION['admin_role'] ?? 'admin'));
    return $role !== '' ? $role : 'admin';
}

function current_admin_user_label() {
    start_secure_session(false);
    return trim((string) ($_SESSION['admin_user'] ?? ''));
}

function current_admin_is_supervisor() {
    return current_admin_role() === 'admin';
}

function require_admin_role() {
    start_secure_session(true);
    if (!current_admin_is_supervisor()) {
        http_response_code(403);
        exit('No tienes permisos para acceder a esta sección.');
    }
}

function csrf_token() {
    start_secure_session(false);
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token) {
    start_secure_session(false);
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function verify_csrf_or_fail() {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
}

function redirect_to($url) {
    header("Location: " . $url);
    exit;
}

function is_allowed_choice($value, array $allowed) {
    return in_array($value, $allowed, true);
}

function validate_date_value($value) {
    if (!$value) {
        return false;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

function save_uploaded_file($field, $destinationDir, $publicPrefix, array $rules) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'error' => null];
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => 'No se pudo subir el archivo.'];
    }

    if ($file['size'] > $rules['max_size']) {
        return ['ok' => false, 'path' => null, 'error' => 'El archivo supera el tamaño permitido.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'path' => null, 'error' => 'Archivo inválido.'];
    }

    $originalName = $file['name'] ?? '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $rules['extensions'], true)) {
        return ['ok' => false, 'path' => null, 'error' => 'Tipo de archivo no permitido.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $rules['mimes'], true)) {
        return ['ok' => false, 'path' => null, 'error' => 'El contenido del archivo no coincide con el tipo permitido.'];
    }

    if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true)) {
        return ['ok' => false, 'path' => null, 'error' => 'No se pudo preparar la carpeta de destino.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $target = rtrim($destinationDir, '/') . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'path' => null, 'error' => 'No se pudo guardar el archivo.'];
    }

    return [
        'ok' => true,
        'path' => rtrim($publicPrefix, '/') . '/' . $filename,
        'error' => null,
    ];
}

function validate_uploaded_file($field, array $rules) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'file' => null, 'error' => null];
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'file' => null, 'error' => 'No se pudo subir el archivo.'];
    }

    if ($file['size'] > $rules['max_size']) {
        return ['ok' => false, 'file' => null, 'error' => 'El archivo supera el tamaño permitido.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'file' => null, 'error' => 'Archivo inválido.'];
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($extension, $rules['extensions'], true)) {
        return ['ok' => false, 'file' => null, 'error' => 'Tipo de archivo no permitido.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $rules['mimes'], true)) {
        return ['ok' => false, 'file' => null, 'error' => 'El contenido del archivo no coincide con el tipo permitido.'];
    }

    return ['ok' => true, 'file' => $file, 'error' => null];
}

function image_upload_rules() {
    return [
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_size' => 8 * 1024 * 1024,
    ];
}
