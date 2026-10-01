<?php
require_once __DIR__ . '/app.php';
require_login();

$q = trim($_GET['q'] ?? '');
$bairro = trim($_GET['bairro'] ?? '');
$ativo = $_GET['ativo'] ?? '1';

$filters = [];
if ($q !== '') {
    $safe = str_replace(['*', '(', ')', ','], ['%2A', '%28', '%29', '%2C'], $q);
    $filters[] = 'or=(codigo.ilike.*' . $safe . '*,nome.ilike.*' . $safe . '*,bairro.ilike.*' . $safe . '*,proprietario.ilike.*' . $safe . '*)';
}
if ($bairro !== '') {
    $safe = str_replace(['*', '(', ')', ','], ['%2A', '%28', '%29', '%2C'], $bairro);
    $filters[] = 'bairro=ilike.*' . $safe . '*';
}
if ($ativo === '1') {
    $filters[] = 'ativo=eq.true';
} elseif ($ativo === '0') {
    $filters[] = 'ativo=eq.false';
}

$imoveis = supabase_request('GET', '/rest/v1/imoveis?select=*&order=id.desc&' . implode('&', $filters));
$imoveis = is_array($imoveis) ? $imoveis : [];

$bairros = supabase_request('GET', '/rest/v1/imoveis?select=bairro&ativo=eq.true&order=bairro.asc');
$bairros = array_values(array_unique(array_filter(array_map(fn($x) => trim((string)($x['bairro'] ?? '')), is_array($bairros) ? $bairros : []))));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Central Imóveis</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="container">
<header class="topo">
<div>
<span class="eyebrow">ADMINISTRAÇÃO</span>
<h1>Central Imóveis</h1>
<p>Base central dos imóveis para consulta e atendimento.</p>
</div>
<div class="top-actions">
<span class="usuario"><?= e($_SESSION['admin_user'] ?? 'Admin') ?></span>
<a class="botao secundario" href="logout.php">Sair</a>
<a class="botao" href="cadastrar.php">+ Cadastrar imóvel</a>
</div>
</header>

<?php if ($message = flash('success')): ?><div class="alerta sucesso"><?= e($message) ?></div><?php endif; ?>
<?php if ($message = flash('error')): ?><div class="alerta erro"><?= e($message) ?></div><?php endif; ?>

<section class="painel">
<form class="filtros" method="GET">
<div class="campo-busca">
<label for="q">Pesquisar</label>
<input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Código, imóvel, bairro ou proprietário">
</div>
<div>
<label for="bairro">Bairro</label>
<select id="bairro" name="bairro">
<option value="">Todos</option>
<?php foreach ($bairros as $item): ?>
<option value="<?= e($item) ?>" <?= $bairro === $item ? 'selected' : '' ?>><?= e($item) ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label for="ativo">Status</label>
<select id="ativo" name="ativo">
<option value="1" <?= $ativo === '1' ? 'selected' : '' ?>>Ativos</option>
<option value="0" <?= $ativo === '0' ? 'selected' : '' ?>>Inativos</option>
<option value="all" <?= $ativo === 'all' ? 'selected' : '' ?>>Todos</option>
</select>
</div>
<button class="botao" type="submit">Filtrar</button>
<a class="botao secundario" href="index.php">Limpar</a>
</form>
</section>

<section class="cabecalho-lista">
<div>
<h2>Imóveis</h2>
<p><?= count($imoveis) ?> resultado(s)</p>
</div>
</section>

<?php if (!$imoveis): ?>
<section class="vazio">
<h2>Nenhum imóvel encontrado</h2>
<p>Ajuste os filtros ou cadastre um novo imóvel.</p>
<a class="botao" href="cadastrar.php">Cadastrar imóvel</a>
</section>
<?php else: ?>
<section class="lista">
<?php foreach ($imoveis as $imovel): ?>
<article class="card <?= !($imovel['ativo'] ?? true) ? 'inativo' : '' ?>">
<div class="card-principal">
<div class="codigo"><?= e($imovel['codigo']) ?></div>
<h3><?= e($imovel['nome']) ?></h3>
<p><?= e($imovel['bairro'] ?: 'Bairro não informado') ?></p>
<?php if (!empty($imovel['endereco'])): ?><p class="muted"><?= e($imovel['endereco']) ?></p><?php endif; ?>
</div>
<div class="resumo">
<span><strong><?= (int)($imovel['capacidade'] ?? 0) ?></strong> pessoas</span>
<span><strong><?= (int)($imovel['dormitorios'] ?? 0) ?></strong> dorm.</span>
<span><strong><?= (int)($imovel['suites'] ?? 0) ?></strong> suítes</span>
<?php if (!empty($imovel['piscina'])): ?><span>Piscina</span><?php endif; ?>
<?php if (!empty($imovel['churrasqueira'])): ?><span>Churrasqueira</span><?php endif; ?>
<?php if (!empty($imovel['ar_condicionado'])): ?><span>Ar-cond.</span><?php endif; ?>
</div>
<div class="card-extra">
<?php if (!empty($imovel['proprietario'])): ?><p><strong>Proprietário:</strong> <?= e($imovel['proprietario']) ?></p><?php endif; ?>
<?php if (!empty($imovel['telefone'])): ?><p><strong>Telefone:</strong> <?= e($imovel['telefone']) ?></p><?php endif; ?>
<?php if ((float)($imovel['diaria'] ?? 0) > 0): ?><p><strong>Diária:</strong> R$ <?= number_format((float)$imovel['diaria'], 2, ',', '.') ?></p><?php endif; ?>
</div>
<div class="acoes">
<a class="botao secundario" href="editar.php?id=<?= (int)$imovel['id'] ?>">Editar</a>
<form action="excluir.php" method="POST" onsubmit="return confirm('Excluir este imóvel permanentemente?')">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$imovel['id'] ?>">
<button class="botao perigo" type="submit">Excluir</button>
</form>
</div>
</article>
<?php endforeach; ?>
</section>
<?php endif; ?>
</main>
</body>
</html>