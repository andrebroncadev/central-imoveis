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
<a class="voltar" href="index.php">← Voltar</a>
<h1>Cadastrar imóvel</h1>
<form action="salvar.php" method="POST">
<label>Código<input type="text" name="codigo" placeholder="Ex.: JQY-001" required></label>
<label>Nome<input type="text" name="nome" placeholder="Ex.: Casa Juquehy" required></label>
<label>Bairro<input type="text" name="bairro"></label>
<label>Proprietário<input type="text" name="proprietario"></label>
<label>Telefone do proprietário<input type="text" name="telefone"></label>
<div class="grid">
<label>Capacidade<input type="number" name="capacidade" min="0"></label>
<label>Dormitórios<input type="number" name="dormitorios" min="0"></label>
<label>Suítes<input type="number" name="suites" min="0"></label>
<label>Distância da praia (m)<input type="number" name="distancia_praia" min="0"></label>
</div>
<div class="checks">
<label><input type="checkbox" name="suite_terrea"> Suíte térrea</label>
<label><input type="checkbox" name="piscina"> Piscina</label>
<label><input type="checkbox" name="churrasqueira"> Churrasqueira</label>
<label><input type="checkbox" name="ar_condicionado"> Ar-condicionado</label>
</div>
<label>Diária base<input type="number" name="diaria" min="0" step="0.01"></label>
<label>Descrição<textarea name="descricao" rows="6"></textarea></label>
<button class="botao" type="submit">Salvar imóvel</button>
</form>
</main>
</body>
</html>
