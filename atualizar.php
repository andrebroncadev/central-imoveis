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

$data = property_data($_POST);
if ($data['codigo'] === '' || $data['nome'] === '') {
    flash('error', 'Código e nome são obrigatórios.');
    redirect('editar.php?id=' . $id);
}
$data['updated_at'] = date('c');

try {
    supabase_request('PATCH', '/rest/v1/imoveis?id=eq.' . $id, $data, ['Prefer: return=minimal']);
    flash('success', 'Imóvel atualizado com sucesso.');
    redirect('index.php');
} catch (Throwable $e) {
    flash('error', friendly_api_error($e));
    redirect('editar.php?id=' . $id);
}