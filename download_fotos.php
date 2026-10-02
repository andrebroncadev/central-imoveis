<?php
require_once __DIR__.'/app.php'; require_login();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id){http_response_code(400);exit('Imóvel inválido.');}
zip_download($id);
?>