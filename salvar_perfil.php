<?php
require_once __DIR__.'/app.php'; require_login(); require_post(); verify_csrf();
$data=['nome'=>trim((string)($_POST['nome']??''))?:null,'creci'=>trim((string)($_POST['creci']??''))?:null,'telefone'=>trim((string)($_POST['telefone']??''))?:null,'bio'=>trim((string)($_POST['bio']??''))?:null,'updated_at'=>date('c')];
try{$rows=supabase_request('GET','/rest/v1/perfil?select=id&id=eq.1&limit=1');if($rows){supabase_request('PATCH','/rest/v1/perfil?id=eq.1',$data,['Prefer: return=minimal']);}else{ $data['id']=1; supabase_request('POST','/rest/v1/perfil',$data,['Prefer: return=minimal']); } flash('success','Perfil salvo.');}catch(Throwable $e){flash('error',friendly_api_error($e));} redirect('perfil.php');
?>