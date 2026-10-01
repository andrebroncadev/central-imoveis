<?php
declare(strict_types=1);

$sessionPath = '/tmp/php-sessions';
if (!is_dir($sessionPath)) @mkdir($sessionPath, 0770, true);
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (is_dir($sessionPath) && is_writable($sessionPath)) session_save_path($sessionPath);
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

function env(string $key, ?string $default = null): ?string { $value = getenv($key); return $value === false ? $default : $value; }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . $path); exit; }
function is_logged_in(): bool { return !empty($_SESSION['logged_in']); }
function require_login(): void { if (!is_logged_in()) redirect('login.php'); }
function require_post(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido.'); } }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { $token=$_POST['csrf']??''; if(!$token || !hash_equals($_SESSION['csrf']??'', $token)){http_response_code(419);exit('Sessão expirada. Volte e tente novamente.');} }
function flash(string $key, ?string $value=null): ?string { if($value!==null){$_SESSION['_flash'][$key]=$value;return null;} $m=$_SESSION['_flash'][$key]??null;unset($_SESSION['_flash'][$key]);return $m; }

function supabase_request(string $method,string $path,?array $body=null,array $extraHeaders=[]):mixed{
    $base=rtrim((string)env('SUPABASE_URL'),'/');$key=(string)env('SUPABASE_SERVER_KEY');
    if($base===''||$key==='')throw new RuntimeException('Banco não está configurado no servidor.');
    $ch=curl_init($base.$path);$headers=['apikey: '.$key,'Authorization: Bearer '.$key,'Content-Type: application/json','Accept: application/json'];foreach($extraHeaders as $h)$headers[]=$h;
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>8]);
    if($body!==null&&in_array($method,['POST','PATCH','PUT'],true))curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    $raw=curl_exec($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    if($raw===false)throw new RuntimeException('Falha de conexão com o banco: '.$error);
    if($status<200||$status>=300){$detail=json_decode($raw,true);$message=is_array($detail)?($detail['message']??$detail['details']??''):'';throw new RuntimeException($message?:'O banco retornou HTTP '.$status.'.');}
    if($raw===''||$status===204)return null;return json_decode($raw,true,512,JSON_THROW_ON_ERROR);
}
function property_data(array $input):array{
    $int=static fn(string $key):int=>max(0,(int)($input[$key]??0));$money=max(0,(float)str_replace(',','.',(string)($input['diaria']??'0')));
    return ['codigo'=>trim((string)($input['codigo']??'')),'nome'=>trim((string)($input['nome']??'')),'bairro'=>trim((string)($input['bairro']??''))?:null,'endereco'=>trim((string)($input['endereco']??''))?:null,'proprietario'=>trim((string)($input['proprietario']??''))?:null,'telefone'=>trim((string)($input['telefone']??''))?:null,'capacidade'=>$int('capacidade'),'dormitorios'=>$int('dormitorios'),'suites'=>$int('suites'),'suite_terrea'=>isset($input['suite_terrea']),'piscina'=>isset($input['piscina']),'churrasqueira'=>isset($input['churrasqueira']),'ar_condicionado'=>isset($input['ar_condicionado']),'distancia_praia'=>$int('distancia_praia'),'diaria'=>$money,'descricao'=>trim((string)($input['descricao']??''))?:null,'ativo'=>isset($input['ativo'])];
}
function cloudinary_signature(array $params,string $secret):string{ksort($params);$parts=[];foreach($params as $key=>$value){if($value===null||$value==='')continue;$parts[]=$key.'='.$value;}return sha1(implode('&',$parts).$secret);}
function cloudinary_request(array $params,string $filePath,string $mime):array{
    $cloud=(string)env('CLOUDINARY_CLOUD_NAME');$key=(string)env('CLOUDINARY_API_KEY');$secret=(string)env('CLOUDINARY_API_SECRET');if($cloud===''||$key===''||$secret==='')throw new RuntimeException('Cloudinary não está configurado no servidor.');
    $params['timestamp']=time();$toSign=$params;$params['api_key']=$key;$params['signature']=cloudinary_signature($toSign,$secret);$post=$params;$post['file']=new CURLFile($filePath,$mime,basename($filePath));
    $ch=curl_init('https://api.cloudinary.com/v1_1/'.rawurlencode($cloud).'/image/upload');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post,CURLOPT_TIMEOUT=>300,CURLOPT_CONNECTTIMEOUT=>10]);$raw=curl_exec($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    if($raw===false||$status<200||$status>=300){$detail=is_string($raw)?json_decode($raw,true):null;$message=is_array($detail)?($detail['error']['message']??''):'';throw new RuntimeException($message?:($error?:'Falha ao enviar a foto para o Cloudinary.'));}
    $result=json_decode($raw,true,512,JSON_THROW_ON_ERROR);return ['public_id'=>(string)($result['public_id']??''),'url'=>(string)($result['secure_url']??''),'format'=>(string)($result['format']??'')];
}
function upload_property_photos(int $propertyId,array $files,string $type='biblioteca'):array{
    $allowed=['image/jpeg','image/png','image/webp','image/gif'];$uploaded=[];if(!isset($files['name'])||!is_array($files['name']))return [];
    $count=count($files['name']);
    for($i=0;$i<$count;$i++){
        if(($files['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)continue;if(($files['error'][$i]??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('Uma das fotos não pôde ser enviada. Tente novamente.');
        if(($files['size'][$i]??0)>100*1024*1024)throw new RuntimeException('Cada foto pode ter no máximo 100 MB.');
        $tmp=$files['tmp_name'][$i]??'';$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);if(!in_array($mime,$allowed,true)||@getimagesize($tmp)===false)throw new RuntimeException('Envie apenas fotos JPG, PNG, WEBP ou GIF.');
        $uploadedPhoto=cloudinary_request(['folder'=>'central-imoveis/imoveis/'.$propertyId,'public_id'=>'foto-'.bin2hex(random_bytes(12))],$tmp,$mime);
        if($uploadedPhoto['public_id']===''||$uploadedPhoto['url']==='')throw new RuntimeException('O Cloudinary não retornou os dados da foto.');
        $uploadedPhoto['tipo']=$type;$uploaded[]=$uploadedPhoto;
    }
    return $uploaded;
}
function delete_property_photo(string $path):void{
    $cloud=(string)env('CLOUDINARY_CLOUD_NAME');$key=(string)env('CLOUDINARY_API_KEY');$secret=(string)env('CLOUDINARY_API_SECRET');if($cloud===''||$key===''||$secret==='')throw new RuntimeException('Cloudinary não está configurado no servidor.');
    $timestamp=time();$params=['public_id'=>$path,'timestamp'=>$timestamp];$post=['public_id'=>$path,'timestamp'=>$timestamp,'api_key'=>$key,'signature'=>cloudinary_signature($params,$secret)];
    $ch=curl_init('https://api.cloudinary.com/v1_1/'.rawurlencode($cloud).'/image/destroy');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post,CURLOPT_TIMEOUT=>60,CURLOPT_CONNECTTIMEOUT=>10]);$raw=curl_exec($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);if($raw===false||$status<200||$status>=300){$detail=is_string($raw)?json_decode($raw,true):null;$message=is_array($detail)?($detail['error']['message']??''):'';throw new RuntimeException($message?:($error?:'Não foi possível remover a foto do Cloudinary.'));}
}
function gallery_token(int $propertyId):string{$secret=(string)(env('SHARE_SECRET')?:env('SUPABASE_SERVER_KEY'));return hash_hmac('sha256',(string)$propertyId,$secret);}
function gallery_url(int $propertyId):string{$base=trim((string)env('APP_URL',''));if($base===''){$forwarded=strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''));$https=$forwarded==='https'||(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');$base=($https?'https':'http').'://'.(string)($_SERVER['HTTP_HOST']??'');}return rtrim($base,'/').'/fotos.php?id='.$propertyId.'&t='.gallery_token($propertyId);}
function verify_gallery_token(int $propertyId,string $token):bool{return $token!==''&&hash_equals(gallery_token($propertyId),$token);}
function friendly_api_error(Throwable $e):string{$message=$e->getMessage();if(stripos($message,'duplicate')!==false||stripos($message,'unique')!==false)return 'Esse código de imóvel já existe. Escolha outro.';return $message;}
