<?php
require_once __DIR__ . '/app.php';
require_login();
require_post();
verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'Imóvel inválido.');
    redirect('index.php');
}

$rows = supabase_request('GET', '/rest/v1/imoveis?select=fotos&id=eq.' . $id . '&limit=1');
$imovel = $rows[0] ?? [];
$fotos = is_array($imovel['fotos'] ?? null) ? $imovel['fotos'] : [];

$data = property_data($_POST);
if (($data['latitude'] ?? null) === null || ($data['longitude'] ?? null) === null) { $geo = geocode_address((string)($data['endereco'] ?? ''),(string)($data['bairro'] ?? ''),(string)($data['cidade'] ?? 'São Sebastião'),(string)($data['uf'] ?? 'SP')); if ($geo) { $data['latitude']=$geo['latitude']; $data['longitude']=$geo['longitude']; } }
if ($data['codigo'] === '' || $data['nome'] === '') {
    flash('error', 'Código e nome são obrigatórios.');
    redirect('editar.php?id=' . $id);
}
$data['updated_at'] = date('c');

try {
    foreach ((array)($_POST['remover_foto'] ?? []) as $path) {
        $path = (string)$path;
        foreach ($fotos as $key => $foto) {
            if (($foto['public_id'] ?? $foto['path'] ?? '') === $path) {
                delete_property_photo($path);
                unset($fotos[$key]);
            }
        }
    }

    $fotos = array_values($fotos);
    if (isset($_FILES['fotos'])) {
        $fotos = array_merge($fotos, upload_property_photos($id, $_FILES['fotos']));
    }

    $data['fotos'] = $fotos;
    supabase_request('PATCH', '/rest/v1/imoveis?id=eq.' . $id, $data, ['Prefer: return=minimal']);
    flash('success', 'Imóvel atualizado com sucesso.');
    redirect('index.php');
} catch (Throwable $e) {
    flash('error', friendly_api_error($e));
    redirect('editar.php?id=' . $id);
}
