<?php
require_once __DIR__ . '/app.php';
require_login();
require_post();
verify_csrf();
header('Content-Type: application/json; charset=utf-8');

try {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $tipo = ($_POST['tipo'] ?? 'biblioteca') === 'capa' ? 'capa' : 'biblioteca';
    if (!$id) throw new RuntimeException('Imóvel inválido.');
    if (!isset($_FILES['foto'])) throw new RuntimeException('Nenhuma foto recebida.');

    $rows = supabase_request('GET','/rest/v1/imoveis?select=fotos&id=eq.'.$id.'&limit=1');
    if (!$rows) throw new RuntimeException('Imóvel não encontrado.');
    $fotos = is_array($rows[0]['fotos'] ?? null) ? $rows[0]['fotos'] : [];

    $foto = upload_property_photos($id, $_FILES['foto'], $tipo)[0] ?? null;
    if (!$foto) throw new RuntimeException('A foto não pôde ser enviada.');

    if ($tipo === 'capa') {
        foreach ($fotos as $old) {
            if (($old['tipo'] ?? '') === 'capa') {
                try { delete_property_photo((string)($old['public_id'] ?? '')); } catch (Throwable) {}
            }
        }
        $fotos = array_values(array_filter($fotos, fn($item) => ($item['tipo'] ?? '') !== 'capa'));
        array_unshift($fotos, $foto);
    } else {
        $fotos[] = $foto;
    }

    supabase_request('PATCH','/rest/v1/imoveis?id=eq.'.$id,['fotos'=>$fotos],['Prefer: return=minimal']);
    echo json_encode(['ok'=>true,'foto'=>$foto,'total'=>count($fotos)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
