<?php
require_once __DIR__.'/app.php'; require_login(); require_post(); verify_csrf();
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);$imovel=filter_input(INPUT_POST,'imovel_id',FILTER_VALIDATE_INT);try{if(!$id||!$imovel)throw new RuntimeException('Período inválido.');supabase_request('DELETE','/rest/v1/disponibilidade?id=eq.'.$id,null,['Prefer: return=minimal']);flash('success','Período excluído.');}catch(Throwable $e){flash('error',friendly_api_error($e));}redirect('calendario.php?imovel='.$imovel.'&mes='.rawurlencode($_POST['mes']??date('Y-m')));
?>