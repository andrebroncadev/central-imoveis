<?php
require_once __DIR__ . '/app.php';
require_login();
require_post();
verify_csrf();

$data = property_data($_POST);
if (!empty($data['proprietario_id'])) { $ownerRows=supabase_request('GET','/rest/v1/proprietarios?select=nome&id=eq.'.(int)$data['proprietario_id'].'&limit=1'); $data['proprietario']=$ownerRows[0]['nome']??null; }
if (($data['latitude'] ?? null) === null || ($data['longitude'] ?? null) === null) { $geo = geocode_address((string)($data['endereco'] ?? ''),(string)($data['bairro'] ?? ''),(string)($data['cidade'] ?? 'São Sebastião'),(string)($data['uf'] ?? 'SP')); if ($geo) { $data['latitude']=$geo['latitude']; $data['longitude']=$geo['longitude']; } }
if ($data['codigo'] === '' || $data['nome'] === '') {
    if (($_POST['ajax'] ?? '') === '1') { http_response_code(422); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Código e nome são obrigatórios.']); exit; }
    flash('error','Código e nome são obrigatórios.'); redirect('cadastrar.php');
}

try {
    $created = supabase_request('POST','/rest/v1/imoveis',$data,['Prefer: return=representation']);
    $imovel = is_array($created) ? ($created[0] ?? null) : null;
    $id = (int)($imovel['id'] ?? 0);
    if ($id <= 0) throw new RuntimeException('O imóvel foi criado, mas o identificador não foi retornado.');

    if (($_POST['ajax'] ?? '') === '1') {
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'id'=>$id,'redirect'=>'editar.php?id='.$id]);
        exit;
    }
    flash('success','Imóvel cadastrado. Agora você pode adicionar as fotos.'); redirect('editar.php?id='.$id);
} catch (Throwable $e) {
    if (($_POST['ajax'] ?? '') === '1') { http_response_code(500); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>friendly_api_error($e)]); exit; }
    flash('error',friendly_api_error($e)); redirect('cadastrar.php');
}
