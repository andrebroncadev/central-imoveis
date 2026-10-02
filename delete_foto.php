<?php
require_once __DIR__ . '/app.php';
require_login();
require_post();
verify_csrf();
header('Content-Type: application/json; charset=utf-8');

try {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $publicId = trim((string)($_POST['public_id'] ?? ''));

    if (!$id || $publicId === '') {
        throw new RuntimeException('Foto inválida.');
    }

    $rows = supabase_request('GET', '/rest/v1/imoveis?select=fotos&id=eq.' . $id . '&limit=1');
    $imovel = $rows[0] ?? null;
    if (!$imovel) {
        throw new RuntimeException('Imóvel não encontrado.');
    }

    $fotos = is_array($imovel['fotos'] ?? null) ? $imovel['fotos'] : [];
    $found = false;
    $remaining = [];

    foreach ($fotos as $foto) {
        if (($foto['public_id'] ?? '') === $publicId) {
            $found = true;
            continue;
        }
        $remaining[] = $foto;
    }

    if (!$found) {
        throw new RuntimeException('Essa foto não está mais cadastrada.');
    }

    delete_property_photo($publicId);

    supabase_request(
        'PATCH',
        '/rest/v1/imoveis?id=eq.' . $id,
        ['fotos' => array_values($remaining)],
        ['Prefer: return=minimal']
    );

    echo json_encode(['ok' => true, 'total' => count($remaining)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
