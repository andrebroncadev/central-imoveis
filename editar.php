<?php
require_once __DIR__ . '/app.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'Imóvel inválido.');
    redirect('index.php');
}

$rows = supabase_request('GET', '/rest/v1/imoveis?select=*&id=eq.' . $id . '&limit=1');
$imovel = $rows[0] ?? null;
if (!$imovel) {
    flash('error', 'Imóvel não encontrado.');
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar imóvel</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="container pequeno">
<a class="voltar" href="index.php">← Voltar para imóveis</a>
<h1>Editar imóvel</h1>
<p class="subtitulo"><strong><?= e($imovel['codigo']) ?></strong> · <?= e($imovel['nome']) ?></p>
<?php if ($error = flash('error')): ?><div class="alerta erro"><?= e($error) ?></div><?php endif; ?>

<form action="atualizar.php" method="POST" enctype="multipart/form-data" class="formulario">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$imovel['id'] ?>">
<div class="grid">
<label>Código *
<input type="text" name="codigo" value="<?= e($imovel['codigo']) ?>" required maxlength="50">
</label>
<label>Nome do imóvel *
<input type="text" name="nome" value="<?= e($imovel['nome']) ?>" required maxlength="150">
</label>
</div>
<div class="grid">
<label>Bairro<input type="text" name="bairro" value="<?= e($imovel['bairro']) ?>" maxlength="100"></label>
<label>Endereço<input type="text" name="endereco" value="<?= e($imovel['endereco']) ?>" maxlength="250"></label>
</div>
<div class="grid">
<label>Proprietário<input type="text" name="proprietario" value="<?= e($imovel['proprietario']) ?>" maxlength="150"></label>
<label>Telefone do proprietário<input type="text" name="telefone" value="<?= e($imovel['telefone']) ?>" maxlength="30"></label>
</div>
<div class="grid quatro">
<label>Capacidade<input type="number" name="capacidade" min="0" value="<?= (int)$imovel['capacidade'] ?>"></label>
<label>Dormitórios<input type="number" name="dormitorios" min="0" value="<?= (int)$imovel['dormitorios'] ?>"></label>
<label>Suítes<input type="number" name="suites" min="0" value="<?= (int)$imovel['suites'] ?>"></label>
<label>Distância da praia (m)<input type="number" name="distancia_praia" min="0" value="<?= (int)$imovel['distancia_praia'] ?>"></label>
</div>
<div class="checks">
<label><input type="checkbox" name="suite_terrea" <?= !empty($imovel['suite_terrea']) ? 'checked' : '' ?>> Suíte térrea</label>
<label><input type="checkbox" name="piscina" <?= !empty($imovel['piscina']) ? 'checked' : '' ?>> Piscina</label>
<label><input type="checkbox" name="churrasqueira" <?= !empty($imovel['churrasqueira']) ? 'checked' : '' ?>> Churrasqueira</label>
<label><input type="checkbox" name="ar_condicionado" <?= !empty($imovel['ar_condicionado']) ? 'checked' : '' ?>> Ar-condicionado</label>
</div>

<section class="fotos-editor">
<div class="secao-titulo"><div><span class="eyebrow">IMAGENS</span><h2>Fotos do imóvel</h2><p>Escolha as fotos que você quer deixar na ficha.</p></div></div>
<?php $fotos = is_array($imovel['fotos'] ?? null) ? $imovel['fotos'] : []; ?>
<?php if ($fotos): ?>
<div class="galeria-editavel">
<?php foreach ($fotos as $foto): ?>
<label class="foto-editavel">
<img src="<?= e($foto['url'] ?? '') ?>" alt="Foto do imóvel">
<span><input type="checkbox" name="remover_foto[]" value="<?= e($foto['public_id'] ?? $foto['path'] ?? '') ?>"> Remover</span>
</label>
<?php endforeach; ?>
</div>
<?php endif; ?>
<label class="upload-box"><span class="upload-icone">＋</span><span><strong>Adicionar fotos</strong><small>JPG, PNG ou WEBP · até 10 MB por foto</small></span><input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple></label>
</section>
<label>Diária base<input type="number" name="diaria" min="0" step="0.01" value="<?= e($imovel['diaria']) ?>"></label>
<label>Descrição<textarea name="descricao" rows="7"><?= e($imovel['descricao']) ?></textarea></label>
<label class="check-unico"><input type="checkbox" name="ativo" <?= !empty($imovel['ativo']) ? 'checked' : '' ?>> Imóvel ativo</label>
<div class="acoes-form">
<a class="botao secundario" href="index.php">Cancelar</a>
<button class="botao" type="submit">Salvar alterações</button>
</div>
</form>
</main>
</body>
</html>