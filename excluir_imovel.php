<?php
require_once __DIR__ . '/app.php';
require_login();
require_post();
verify_csrf();
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
if(!$id){flash('error','Imóvel inválido.');redirect('index.php');}
try{
  $rows=supabase_request('GET','/rest/v1/imoveis?select=id,nome,fotos&id=eq.'.$id.'&limit=1');
  $imovel=$rows[0]??null;
  if(!$imovel) throw new RuntimeException('Imóvel não encontrado.');
  $fotos=is_array($imovel['fotos']??null)?$imovel['fotos']:[];
  supabase_request('DELETE','/rest/v1/imoveis?id=eq.'.$id,null,['Prefer: return=minimal']);
  foreach($fotos as $foto){
    $publicId=(string)($foto['public_id']??'');
    if($publicId!==''){try{delete_property_photo($publicId);}catch(Throwable $cleanup){}}
  }
  flash('success','Imóvel excluído com sucesso.');
}catch(Throwable $e){flash('error',friendly_api_error($e));}
redirect('index.php');
?>