<?php
require_once __DIR__.'/app.php'; require_login(); require_once __DIR__.'/ui.php';
$imoveis=supabase_request('GET','/rest/v1/imoveis?select=id,codigo,nome,proprietario_id,proprietario&order=nome.asc');$imoveis=is_array($imoveis)?$imoveis:[];
$owners=supabase_request('GET','/rest/v1/proprietarios?select=id,nome,tipo&order=nome.asc');$owners=is_array($owners)?$owners:[];

$contracts=supabase_request('GET','/rest/v1/contratos?select=id,imovel_id,proprietario_id,locatario_nome,data_contrato,checkin,checkout,valor_locacao,status,created_at&order=id.desc');$contracts=is_array($contracts)?$contracts:[];
$propId=(int)($_GET['proprietario']??0);$imovelId=(int)($_GET['imovel']??0);
?><!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Contratos · Central Imóveis</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<main class="container">
<header class="topo">
  <div><span class="eyebrow">CENTRAL IMÓVEIS</span><h1>Contratos</h1><p>Crie, acompanhe e reutilize contratos a partir dos cadastros da Central.</p></div>
  <?php render_nav('contratos'); ?>
</header>

<?php if($m=flash('success')):?><div class="alerta sucesso"><?=e($m)?></div><?php endif;?>
<?php if($m=flash('error')):?><div class="alerta erro"><?=e($m)?></div><?php endif;?>

<section class="form-card contract-start">
  <div class="form-card-title">
    <span>01</span>
    <div><strong>Novo contrato</strong><small>Escolha um imóvel já cadastrado. O proprietário vinculado é carregado automaticamente.</small></div>
  </div>

  <form action="salvar_contrato.php" method="POST" class="formulario">
    <?=csrf_field()?>
    <div class="grid">
      <label>Imóvel
        <select name="imovel_id" id="imovel_id" required>
          <option value="">Selecione um imóvel</option>
          <?php foreach($imoveis as $p):?>
            <option value="<?=(int)$p['id']?>" data-owner="<?=(int)($p['proprietario_id']??0)?>" <?=($imovelId===(int)$p['id'])?'selected':''?>>
              <?=e($p['codigo'])?> · <?=e($p['nome'])?>
            </option>
          <?php endforeach;?>
        </select>
      </label>
      <div class="field-action">
        <span class="field-label">Cadastro de imóvel</span>
        <a class="botao secundario" href="cadastrar.php">+ Cadastrar imóvel</a>
      </div>
    </div>

    <div class="grid">
      <label>Proprietário
        <select name="proprietario_id" id="proprietario_id">
          <option value="">Selecionar proprietário</option>
          <?php foreach($owners as $o):?>
            <option value="<?=(int)$o['id']?>" <?=($propId===(int)$o['id'])?'selected':''?>>
              <?=e($o['nome'])?><?=($o['tipo']??'pf')==='pj'?' · PJ':''?>
            </option>
          <?php endforeach;?>
        </select>
      </label>
      <div class="field-action">
        <span class="field-label">Cadastro de proprietário</span>
        <a class="botao secundario" href="cadastrar_proprietario.php">+ Cadastrar proprietário</a>
      </div>
    </div>

    <div id="ownerPreview" class="location-note">Selecione um imóvel para carregar o proprietário vinculado. Você pode trocar pelo cadastro de outro proprietário se necessário.</div>

    <div class="form-bottom">
      <span></span>
      <button class="botao" type="button" id="continueBtn">Gerar contrato →</button>
    </div>
  </form>
</section>

<section class="form-card">
  <div class="form-card-title">
    <span>02</span>
    <div><strong>Contratos feitos</strong><small>Histórico dos contratos salvos na Central.</small></div>
  </div>
  <section class="lista">
    <?php if(!$contracts):?>
      <div class="vazio"><h2>Nenhum contrato ainda</h2><p>O primeiro começa pelo botão acima.</p></div>
    <?php else: foreach($contracts as $c):?>
      <article class="card">
        <div class="card-principal">
          <div class="codigo">CONTRATO #<?=e($c['id'])?></div>
          <h3><?=e($c['locatario_nome']?:'Sem locatário')?></h3>
          <p><?=e($c['data_contrato']?:'Data não informada')?></p>
        </div>
        <div class="resumo"><span><?=e($c['status'])?></span><span>R$ <?=number_format((float)$c['valor_locacao'],2,',','.')?></span></div>
        <div class="acoes"><a class="botao secundario" href="contrato.php?imovel_id=<?=(int)$c['imovel_id']?><?php if(!empty($c['proprietario_id'])):?>&proprietario_id=<?=(int)$c['proprietario_id']?><?php endif;?>">Abrir contrato</a><a class="botao secundario" href="contratos.php?imovel=<?=(int)$c['imovel_id']?>">Usar imóvel</a></div>
      </article>
    <?php endforeach; endif;?>
  </section>
</section>

<?php render_footer();?>
</main>
<script>
const owners=<?=json_encode($owners,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
const pi=document.getElementById('imovel_id');
const po=document.getElementById('proprietario_id');
const prev=document.getElementById('ownerPreview');

function syncOwner(){
  const opt=pi.options[pi.selectedIndex];
  const id=Number(opt?.dataset?.owner||0);
  if(id){
    po.value=String(id);
    const o=owners.find(x=>Number(x.id)===id);
    prev.textContent=o
      ? 'Proprietário vinculado ao imóvel: '+o.nome+'. Se precisar, troque no campo acima.'
      : 'O imóvel aponta para um proprietário que não foi encontrado.';
  }else{
    if(!<?=json_encode($propId>0)?>) po.value='';
    prev.textContent='Selecione um imóvel para carregar o proprietário vinculado. Você pode trocar pelo cadastro de outro proprietário se necessário.';
  }
}
pi.addEventListener('change',syncOwner);
syncOwner();
</script>
</body></html>