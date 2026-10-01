<?php
require_once __DIR__ . '/app.php';
if (is_logged_in()) redirect('index.php');

$error = flash('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar · Central Imóveis</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
<main class="login-box">
<span class="eyebrow">ACESSO RESTRITO</span>
<h1>Central Imóveis</h1>
<p>Entre para acessar sua base de imóveis.</p>
<?php if ($error): ?><div class="alerta erro"><?= e($error) ?></div><?php endif; ?>
<form action="autenticar.php" method="POST" class="formulario">
<?= csrf_field() ?>
<label>Usuário<input type="text" name="username" autocomplete="username" required></label>
<label>Senha<input type="password" name="password" autocomplete="current-password" required></label>
<button class="botao" type="submit">Entrar</button>
</form>
</main>
</body>
</html>