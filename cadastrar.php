<?php require_once __DIR__ . '/app.php'; require_login(); ?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Novo imóvel · Central Imóveis</title><link rel="stylesheet" href="css/style.css"></head><body>
<main class="container pequeno">
<a class="voltar" href="index.php">← Voltar</a><?php render_nav("imoveis"); ?><h1>Novo imóvel</h1><p class="subtitulo">Cadastre o imóvel. Depois do cadastro, a página de edição permite enviar a capa e toda a biblioteca diretamente para o Cloudinary.</p>
<?php if($error=flash('error')): ?><div class="alerta erro"><?=e($error)?></div><?php endif; ?>
<form id="cadastroForm" action="salvar.php" method="POST" class="formulario">
<?=csrf_field()?>
<div class="grid"><label>Código *<input name="codigo" required maxlength="50" placeholder="Ex.: JQY-001"></label><label>Nome do imóvel *<input name="nome" required maxlength="150" placeholder="Ex.: Casa Juquehy"></label></div>
<div class="grid"><label>Bairro<input name="bairro" maxlength="100"></label><label>Endereço<input name="endereco" maxlength="250"></label></div>
<div class="grid"><label>Proprietário<input name="proprietario" maxlength="150"></label><div class="location-note">A localização será posicionada automaticamente pelo endereço. Você poderá ajustar no mapa depois.</div></div>
<div class="grid"><label>Latitude<input type="number" name="latitude" step="any" placeholder="Automática"></label><label>Longitude<input type="number" name="longitude" step="any" placeholder="Automática"></label></div><div class="grid quatro"><label>Capacidade<input type="number" name="capacidade" min="0" value="0"></label><label>Dormitórios<input type="number" name="dormitorios" min="0" value="0"></label><label>Suítes<input type="number" name="suites" min="0" value="0"></label><label>Distância da praia (m)<input type="number" name="distancia_praia" min="0" value="0"></label></div>
<div class="checks"><label><input type="checkbox" name="suite_terrea"> Suíte térrea</label><label><input type="checkbox" name="piscina"> Piscina</label><label><input type="checkbox" name="churrasqueira"> Churrasqueira</label><label><input type="checkbox" name="ar_condicionado"> Ar-condicionado</label></div>
<label>Diária base<input type="number" name="diaria" min="0" step="0.01" value="0"></label><label>Descrição<textarea name="descricao" rows="7"></textarea></label><label class="check-unico"><input type="checkbox" name="ativo" checked> Imóvel ativo</label>
<div class="acoes-form"><a class="botao secundario" href="index.php">Cancelar</a><button id="salvarBtn" class="botao" type="submit">Cadastrar imóvel</button></div>
</form><?php render_footer(); ?></main>
<script>
document.getElementById('cadastroForm').addEventListener('submit',async e=>{e.preventDefault();const form=e.currentTarget,btn=document.getElementById('salvarBtn');btn.disabled=true;btn.textContent='Cadastrando…';const fd=new FormData(form);fd.append('ajax','1');try{const r=await fetch('salvar.php',{method:'POST',body:fd,credentials:'same-origin'});const j=await r.json();if(!j.ok)throw new Error(j.error||'Não foi possível cadastrar.');location.href=j.redirect;}catch(err){alert(err.message);btn.disabled=false;btn.textContent='Cadastrar imóvel';}});
</script></body></html>