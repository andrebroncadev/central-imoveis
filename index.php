<?php
require_once 'conexao.php';

$stmt = $db->query("SELECT * FROM imoveis ORDER BY id DESC");
$imoveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
<h1>Central Imóveis</h1>
<p>Minha base pessoal de imóveis.</p>
</div>
<a class="botao" href="cadastrar.php">+ Cadastrar imóvel</a>
</header>
<?php if (count($imoveis) === 0): ?>
<section class="vazio">
<h2>Nenhum imóvel cadastrado</h2>
<p>Comece cadastrando o primeiro imóvel.</p>
<a class="botao" href="cadastrar.php">Cadastrar primeiro imóvel</a>
</section>
<?php else: ?>
<section class="lista">
<?php foreach ($imoveis as $imovel): ?>
<article class="card">
<div>
<span class="codigo"><?= htmlspecialchars($imovel['codigo']) ?></span>
<h2><?= htmlspecialchars($imovel['nome']) ?></h2>
<p><?= htmlspecialchars($imovel['bairro']) ?></p>
</div>
<div class="resumo">
<span><?= (int) $imovel['capacidade'] ?> pessoas</span>
<span><?= (int) $imovel['dormitorios'] ?> dormitórios</span>
<span><?= (int) $imovel['suites'] ?> suítes</span>
</div>
<div class="acoes">
<a href="editar.php?id=<?= (int) $imovel['id'] ?>">Editar</a>
<a class="perigo" href="excluir.php?id=<?= (int) $imovel['id'] ?>" onclick="return confirm('Excluir este imóvel?')">Excluir</a>
</div>
</article>
<?php endforeach; ?>
</section>
<?php endif; ?>
</main>
</body>
</html>
