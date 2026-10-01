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

try {
    supabase_request('DELETE', '/rest/v1/imoveis?id=eq.' . $id, [], ['Prefer: return=minimal']);
    flash('success', 'Imóvel excluído.');
} catch (Throwable $e) {
    flash('error', friendly_api_error($e));
}
redirect('index.php');