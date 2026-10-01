<?php
declare(strict_types=1);

$sessionPath = '/tmp/php-sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0770, true);
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function env(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function is_logged_in(): bool {
    return !empty($_SESSION['logged_in']);
}

function require_login(): void {
    if (!is_logged_in()) redirect('login.php');
}

function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método não permitido.');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Sessão expirada. Volte e tente novamente.');
    }
}

function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function supabase_request(string $method, string $path, ?array $body = null, array $extraHeaders = []): mixed {
    $base = rtrim((string)env('SUPABASE_URL'), '/');
    $key = (string)env('SUPABASE_SERVER_KEY');

    if ($base === '' || $key === '') {
        throw new RuntimeException('Banco não está configurado no servidor.');
    }

    $ch = curl_init($base . $path);
    $headers = [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    foreach ($extraHeaders as $header) $headers[] = $header;

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);

    if ($body !== null && in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($raw === false) throw new RuntimeException('Falha de conexão com o banco: ' . $error);
    if ($status < 200 || $status >= 300) {
        $detail = json_decode($raw, true);
        $message = is_array($detail) ? ($detail['message'] ?? $detail['details'] ?? '') : '';
        throw new RuntimeException($message ?: 'O banco retornou HTTP ' . $status . '.');
    }

    if ($raw === '' || $status === 204) return null;
    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}

function property_data(array $input): array {
    $int = static fn(string $key): int => max(0, (int)($input[$key] ?? 0));
    $money = max(0, (float)str_replace(',', '.', (string)($input['diaria'] ?? '0')));

    return [
        'codigo' => trim((string)($input['codigo'] ?? '')),
        'nome' => trim((string)($input['nome'] ?? '')),
        'bairro' => trim((string)($input['bairro'] ?? '')) ?: null,
        'endereco' => trim((string)($input['endereco'] ?? '')) ?: null,
        'proprietario' => trim((string)($input['proprietario'] ?? '')) ?: null,
        'telefone' => trim((string)($input['telefone'] ?? '')) ?: null,
        'capacidade' => $int('capacidade'),
        'dormitorios' => $int('dormitorios'),
        'suites' => $int('suites'),
        'suite_terrea' => isset($input['suite_terrea']),
        'piscina' => isset($input['piscina']),
        'churrasqueira' => isset($input['churrasqueira']),
        'ar_condicionado' => isset($input['ar_condicionado']),
        'distancia_praia' => $int('distancia_praia'),
        'diaria' => $money,
        'descricao' => trim((string)($input['descricao'] ?? '')) ?: null,
        'ativo' => isset($input['ativo']),
    ];
}

function upload_property_photos(int $propertyId, array $files): array {
    $base = rtrim((string)env('SUPABASE_URL'), '/');
    $key = (string)env('SUPABASE_SERVER_KEY');
    if ($base === '' || $key === '') throw new RuntimeException('Storage não está configurado.');

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $uploaded = [];

    if (!isset($files['name']) || !is_array($files['name'])) return [];

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($files['error'][$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Uma das fotos não pôde ser enviada.');
        if (($files['size'][$i] ?? 0) > 10 * 1024 * 1024) throw new RuntimeException('Cada foto pode ter no máximo 10 MB.');

        $tmp = $files['tmp_name'][$i] ?? '';
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset($allowed[$mime]) || @getimagesize($tmp) === false) {
            throw new RuntimeException('Envie apenas fotos JPG, PNG, WEBP ou GIF.');
        }

        $path = 'imoveis/' . $propertyId . '/' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        $binary = file_get_contents($tmp);
        if ($binary === false) throw new RuntimeException('Não foi possível ler uma das fotos.');

        $ch = curl_init($base . '/storage/v1/object/' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $key,
                'apikey: ' . $key,
                'Content-Type: ' . $mime,
                'Cache-Control: max-age=31536000',
                'x-upsert: false',
            ],
            CURLOPT_POSTFIELDS => $binary,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) {
            $detail = is_string($raw) ? json_decode($raw, true) : null;
            $message = is_array($detail) ? ($detail['message'] ?? $detail['error'] ?? '') : '';
            throw new RuntimeException($message ?: ($error ?: 'Falha ao enviar uma foto.'));
        }

        $uploaded[] = [
            'path' => $path,
            'url' => $base . '/storage/v1/object/public/' . $path,
        ];
    }

    return $uploaded;
}

function delete_property_photo(string $path): void {
    if (!preg_match('#^imoveis/[0-9]+/[a-f0-9]{24}\.(jpg|png|webp|gif)$#', $path)) {
        throw new RuntimeException('Foto inválida.');
    }

    $base = rtrim((string)env('SUPABASE_URL'), '/');
    $key = (string)env('SUPABASE_SERVER_KEY');
    $ch = curl_init($base . '/storage/v1/object/' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key,
            'apikey: ' . $key,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($raw === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('Não foi possível remover a foto.');
    }
}

function friendly_api_error(Throwable $e): string {
    $message = $e->getMessage();
    if (stripos($message, 'duplicate') !== false || stripos($message, 'unique') !== false) {
        return 'Esse código de imóvel já existe. Escolha outro.';
    }
    return $message;
}
