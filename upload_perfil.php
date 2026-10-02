<?php
require_once __DIR__.'/app.php'; require_login(); require_post(); verify_csrf();
try{
 if(!isset($_FILES['foto'])||($_FILES['foto']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Selecione uma foto válida.');
 $tmp=$_FILES['foto']['tmp_name'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
 if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)||@getimagesize($tmp)===false) throw new RuntimeException('Use JPG, PNG ou WEBP.');
 $old=profile_data();$photo=cloudinary_request(['folder'=>'central-imoveis/perfil','public_id'=>'perfil'], $tmp, $mime);
 $data=['id'=>1,'foto_url'=>$photo['url'],'foto_public_id'=>$photo['public_id'],'updated_at'=>date('c')];
 $rows=supabase_request('GET','/rest/v1/perfil?select=id&id=eq.1&limit=1');
 if($rows)supabase_request('PATCH','/rest/v1/perfil?id=eq.1',$data,['Prefer: return=minimal']);else supabase_request('POST','/rest/v1/perfil',$data,['Prefer: return=minimal']);
 if(!empty($old['foto_public_id'])&&$old['foto_public_id']!==$photo['public_id']){try{delete_property_photo((string)$old['foto_public_id']);}catch(Throwable){}}
 flash('success','Foto do perfil atualizada.');
}catch(Throwable $e){flash('error',friendly_api_error($e));}
redirect('perfil.php');
?>