<?php
require_once __DIR__ . '/app.php';
require_login();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastrar imóvel</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="container pequeno">
<a class="voltar" href="index.php">← Voltar para imóveis</a>
<h1>Cadastrar imóvel</h1>
<p class="subtitulo">Cadastre uma vez e use a base para consulta durante os atendimentos.</p>

<?php if ($error = flash('error')): ?><div class="alerta erro"><?= e($error) ?></div><?php endif; ?>

<form action="salvar.php" method="POST" enctype="multipart/form-data" class="formulario">
<?= csrf_field() ?>
<div class="grid">
<label>Código *
<input type="text" name="codigo" placeholder="Ex.: JQY-001" required maxlength="50">
</label>
<label>Nome do imóvel *
<input type="text" name="nome" placeholder="Ex.: Casa Juquehy" required maxlength="150">
</label>
</div>
<div class="grid">
<label>Bairro<input type="text" name="bairro" maxlength="100"></label>
<label>Endereço<input type="text" name="endereco" maxlength="250"></label>
</div>
<div class="grid">
<label>Proprietário<input type="text" name="proprietario" maxlength="150"></label>
<label>Telefone do proprietário<input type="text" name="telefone" maxlength="30"></label>
</div>
<div class="grid quatro">
<label>Capacidade<input type="number" name="capacidade" min="0" value="0"></label>
<label>Dormitórios<input type="number" name="dormitorios" min="0" value="0"></label>
<label>Suítes<input type="number" name="suites" min="0" value="0"></label>
<label>Distância da praia (m)<input type="number" name="distancia_praia" min="0" value="0"></label>
</div>
<div class="checks">
<label><input type="checkbox" name="suite_terrea"> Suíte térrea</label>
<label><input type="checkbox" name="piscina"> Piscina</label>
<label><input type="checkbox" name="churrasqueira"> Churrasqueira</label>
<label><input type="checkbox" name="ar_condicionado"> Ar-condicionado</label>
</div>

<section class="fotos-editor">
<div class="secao-titulo"><div><span class="eyebrow">IMAGENS</span><h2>Fotos do imóvel</h2><p>As fotos completas ficam no Cloudinary; o Central guarda apenas os links e a capa.</p></div></div>
<label class="upload-box"><span class="upload-icone">＋</span><span><strong>Escolher fotos</strong><small>JPG, PNG, WEBP ou GIF · até 100 MB por foto · até 100 fotos por envio</small></span><input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple></label>
</section>
<label>Diária base<input type="number" name="diaria" min="0" step="0.01" value="0"></label>
<label>Descrição<textarea name="descricao" rows="7" placeholder="Características, observações e informações úteis para atendimento."></textarea></label>
<label class="check-unico"><input type="checkbox" name="ativo" checked> Imóvel ativo</label>
<div class="acoes-form">
<a class="botao secundario" href="index.php">Cancelar</a>
<button class="botao" type="submit">Salvar imóvel</button>
</div>
</form>
</main>
</body>
</html>