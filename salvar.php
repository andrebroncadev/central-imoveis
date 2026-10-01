<?php
require_once __DIR__ . '/app.php';
require_login();
require_post();
verify_csrf();

$data = property_data($_POST);
if ($data['codigo'] === '' || $data['nome'] === '') {
    flash('error', 'Código e nome são obrigatórios.');
    redirect('cadastrar.php');
}

try {
    supabase_request('POST', '/rest/v1/imoveis', $data, ['Prefer: return=minimal']);
    flash('success', 'Imóvel cadastrado com sucesso.');
    redirect('index.php');
} catch (Throwable $e) {
    flash('error', friendly_api_error($e));
    redirect('cadastrar.php');
}