<?php
require_once __DIR__ . '/app.php';
require_login();
require_post();
verify_csrf();

$data = property_data($_POST);
if ($data['codigo'] === '' || $data['nome'] === '') { flash('error','Código e nome são obrigatórios.'); redirect('cadastrar.php'); }

try {
    $created = supabase_request('POST','/rest/v1/imoveis',$data,['Prefer: return=representation']);
    $imovel = is_array($created) ? ($created[0] ?? null) : null;
    $id = (int)($imovel['id'] ?? 0);
    if ($id <= 0) throw new RuntimeException('O imóvel foi criado, mas o identificador não foi retornado.');

    $fotos = [];
    if (isset($_FILES['capa'])) $fotos = array_merge($fotos, upload_property_photos($id, $_FILES['capa'], 'capa'));
    if (isset($_FILES['fotos'])) $fotos = array_merge($fotos, upload_property_photos($id, $_FILES['fotos'], 'biblioteca'));
    if ($fotos) supabase_request('PATCH','/rest/v1/imoveis?id=eq.'.$id,['fotos'=>$fotos],['Prefer: return=minimal']);

    flash('success','Imóvel cadastrado com sucesso.'); redirect('index.php');
} catch (Throwable $e) { flash('error',friendly_api_error($e)); redirect('cadastrar.php'); }
