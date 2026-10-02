<?php
require_once __DIR__.'/app.php'; require_login(); require_post(); verify_csrf();
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); if(!$id) redirect('proprietarios.php');
$fields=['tipo','nome','cpf','profissao','genero','estado_civil','rg','cnpj','representante_legal','rg_representante','telefone','email','cep','endereco','numero','complemento','bairro','cidade','uf','banco_codigo','banco_agencia','tipo_conta','banco_conta','pix'];
$data=[]; foreach($fields as $f){$v=trim((string)($_POST[$f]??''));$data[$f]=$v!==''?$v:null;}$data['updated_at']=date('c'); if(($data['nome']??'')===''){flash('error','Nome / razão social é obrigatório.');redirect('editar_proprietario.php?id='.$id);}
try{supabase_request('PATCH','/rest/v1/proprietarios?id=eq.'.$id,$data,['Prefer'=>'return=minimal']);supabase_request('PATCH','/rest/v1/imoveis?proprietario_id=eq.'.$id,['proprietario'=>$data['nome']],['Prefer'=>'return=minimal']);flash('success','Proprietário atualizado.');redirect('proprietarios.php');}catch(Throwable $e){flash('error',friendly_api_error($e));redirect('editar_proprietario.php?id='.$id);}
?>