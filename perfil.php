<?php
require_once __DIR__.'/app.php'; require_login();
$p=profile_data();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Perfil · Central Imóveis</title><link rel="stylesheet" href="css/style.css"></head><body>
<main class="container pequeno">
<header class="page-head"><div><span class="eyebrow">CONFIGURAÇÃO</span><h1>Perfil</h1><p class="subtitulo">Esses dados aparecem no rodapé da Central Imóveis.</p></div></header>
<?php render_nav("perfil"); ?>
<?php if($m=flash('success')):?><div class="alerta sucesso"><?=e($m)?></div><?php endif;?><?php if($m=flash('error')):?><div class="alerta erro"><?=e($m)?></div><?php endif;?>
<section class="profile-card">
<div class="profile-photo"><?php if(!empty($p['foto_url'])):?><img src="<?=e($p['foto_url'])?>" alt="Foto do perfil"><?php else:?><span><?=e(mb_strtoupper(mb_substr((string)($p['nome']??'A'),0,1)))?></span><?php endif;?></div>
<form action="salvar_perfil.php" method="POST" class="formulario"><?=csrf_field()?>
<div class="grid"><label>Nome<input name="nome" maxlength="150" value="<?=e($p['nome']??'')?>"></label><label>CRECI<input name="creci" maxlength="50" value="<?=e($p['creci']??'')?>"></label></div>
<div class="grid"><label>Telefone<input name="telefone" maxlength="30" value="<?=e($p['telefone']??'')?>" placeholder="Opcional"></label><label>Bio curta<input name="bio" maxlength="250" value="<?=e($p['bio']??'')?>"></label></div>
<div class="acoes-form"><button class="botao" type="submit">Salvar perfil</button></div>
</form>
<div class="profile-upload"><div><strong>Foto do perfil</strong><p>Ela será usada no rodapé do sistema.</p></div><form action="upload_perfil.php" method="POST" enctype="multipart/form-data"><?=csrf_field()?><label class="upload-box"><span class="upload-icone">◎</span><span><strong>Escolher foto</strong><small>JPG, PNG ou WEBP</small></span><input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required></label><button class="botao" type="submit">Enviar foto</button></form></div><div class="profile-account"><a class="botao perigo" href="logout.php">Sair da conta</a></div>
</section>
<?php render_footer(); ?></main></body></html>