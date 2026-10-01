<?php
$dbPath = __DIR__ . '/banco/imoveis.sqlite';

if (!is_dir(__DIR__ . '/banco')) {
    mkdir(__DIR__ . '/banco', 0777, true);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->exec("CREATE TABLE IF NOT EXISTS imoveis (
id INTEGER PRIMARY KEY AUTOINCREMENT,
codigo TEXT NOT NULL,
nome TEXT NOT NULL,
bairro TEXT,
proprietario TEXT,
telefone TEXT,
capacidade INTEGER DEFAULT 0,
dormitorios INTEGER DEFAULT 0,
suites INTEGER DEFAULT 0,
suite_terrea INTEGER DEFAULT 0,
piscina INTEGER DEFAULT 0,
churrasqueira INTEGER DEFAULT 0,
ar_condicionado INTEGER DEFAULT 0,
distancia_praia INTEGER DEFAULT 0,
diaria REAL DEFAULT 0,
descricao TEXT
)");
