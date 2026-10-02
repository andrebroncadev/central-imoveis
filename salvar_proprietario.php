<?php
require_once __DIR__.'/app.php'; require_login(); require_post(); verify_csrf();
$fields=['tipo','nome','cpf','profissao','genero','estado_civil','rg','cnpj','representante_legal','rg_representante','telefone','email','cep','endereco','numero','complemento','bairro','cidade','uf','banco_codigo','banco_agencia','tipo_conta','banco_conta','pix'];
$data=[]; foreach($fields as $f){$v=trim((string)($_POST[$f]??'')); $data[$f]=$v!==''?$v:null;}
$data['tipo']=in_array($data['tipo'],['pf','pj'],true)?$data['tipo']:'pf';
if(($data['nome']??'')===''){flash('error','Nome / razão social é obrigatório.');redirect('cadastrar_proprietario.php');}
try{supabase_request('POST','/rest/v1/proprietarios',$data,['Prefer: return=minimal']);flash('success','Proprietário cadastrado.');redirect('proprietarios.php');}catch(Throwable $e){flash('error',friendly_api_error($e));redirect('cadastrar_proprietario.php');}
?>