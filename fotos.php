<?php
declare(strict_types=1);
require_once __DIR__ . '/app.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$token = trim((string)($_GET['t'] ?? ''));

if (!$id || !verify_gallery_token($id, $token)) {
    http_response_code(404);
    exit('Galeria não encontrada.');
}

try {
    $rows = supabase_request('GET', '/rest/v1/imoveis?select=id,codigo,nome,bairro,fotos&id=eq.' . $id . '&limit=1');
    $imovel = $rows[0] ?? null;
} catch (Throwable) {
    http_response_code(500);
    exit('Não foi possível carregar a galeria.');
}

if (!$imovel) {
    http_response_code(404);
    exit('Galeria não encontrada.');
}

$fotos = is_array($imovel['fotos'] ?? null) ? $imovel['fotos'] : [];
$fotos = array_values(array_filter($fotos, static fn($foto) => is_array($foto) && !empty($foto['url'])));
$galleryUrl = gallery_url((int)$imovel['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= e($imovel['nome']) ?> · Fotos</title>
<style>
:root{--ink:#111a24;--muted:#718092;--line:#e4e8ed;--red:#d71920;--cream:#f5f6f7}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--cream);color:var(--ink);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.wrap{width:min(1180px,calc(100% - 28px));margin:auto;padding:28px 0 54px}.top{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:18px}.eyebrow{font-size:10px;font-weight:900;letter-spacing:.16em;color:#7b8794}.title{margin:7px 0 5px;font-size:clamp(30px,5vw,48px);letter-spacing:-.055em;line-height:1}.sub{margin:0;color:var(--muted);font-size:13px}.actions{display:flex;gap:7px;flex-wrap:wrap}.btn{border:1px solid var(--ink);border-radius:11px;padding:10px 14px;font-weight:800;text-decoration:none;cursor:pointer;background:var(--ink);color:#fff}.btn.light{background:#fff;color:var(--ink);border-color:var(--line)}.intro{display:grid;grid-template-columns:1.7fr .8fr;gap:18px;margin:20px 0}.cover{position:relative;min-height:470px;border-radius:22px;overflow:hidden;background:#111;box-shadow:0 18px 55px rgba(16,24,40,.15)}.cover img{width:100%;height:100%;min-height:470px;object-fit:cover;display:block}.cover:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.05) 25%,rgba(0,0,0,.78) 100%)}.cover-info{position:absolute;z-index:2;left:28px;right:28px;bottom:26px;color:#fff}.cover-code{font-size:11px;letter-spacing:.15em;font-weight:900;opacity:.82}.cover-title{font-size:clamp(30px,4vw,48px);line-height:1;margin:7px 0 12px;letter-spacing:-.045em}.chips{display:flex;flex-wrap:wrap;gap:7px}.chip{padding:7px 10px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.13);backdrop-filter:blur(8px);border-radius:999px;font-size:11px;font-weight:800}.description{background:#fff;border:1px solid var(--line);border-radius:22px;padding:24px;box-shadow:0 10px 35px rgba(16,24,40,.06)}.description h2{font-size:18px;margin:0 0 11px}.description p{white-space:pre-line;color:#586677;font-size:14px;line-height:1.7;margin:0}.gallery-head{display:flex;justify-content:space-between;align-items:end;margin:30px 0 12px}.gallery-head h2{margin:0;font-size:21px}.gallery-head span{color:var(--muted);font-size:12px}.slides{display:grid;gap:14px}.slide{position:relative;height:min(78vh,720px);min-height:420px;border-radius:20px;overflow:hidden;background:#111;box-shadow:0 14px 45px rgba(16,24,40,.12);cursor:pointer;border:0;padding:0;width:100%}.slide img{width:100%;height:100%;object-fit:contain;background:#111;display:block}.slide:after{content:"";position:absolute;inset:auto 0 0;height:35%;background:linear-gradient(transparent,rgba(0,0,0,.48));pointer-events:none}.slide-label{position:absolute;left:18px;bottom:16px;z-index:2;color:#fff;font-size:12px;font-weight:850}.empty{padding:50px 20px;text-align:center;background:#fff;border-radius:18px;color:var(--muted)}.viewer{position:fixed;inset:0;background:rgba(7,10,14,.97);display:none;align-items:center;justify-content:center;padding:18px;z-index:10}.viewer.open{display:flex}.viewer img{max-width:96vw;max-height:91vh;object-fit:contain}.close,.nav{position:fixed;border:0;cursor:pointer;color:#fff;background:rgba(255,255,255,.12);backdrop-filter:blur(8px)}.close{right:18px;top:14px;border-radius:999px;width:44px;height:44px;font-size:25px}.nav{top:50%;transform:translateY(-50%);width:52px;height:70px;border-radius:14px;font-size:28px}.nav.prev{left:18px}.nav.next{right:18px}.count{position:fixed;left:20px;top:20px;color:#fff;font-weight:800;font-size:12px}.site-note{margin-top:14px;color:var(--muted);font-size:11px;text-align:center}@media(max-width:800px){.top{display:block}.actions{margin-top:15px}.intro{grid-template-columns:1fr}.cover,.cover img{min-height:390px}.description{padding:19px}.slide{min-height:320px;height:62vh}.nav{width:43px;height:56px}.nav.prev{left:8px}.nav.next{right:8px}}@media print{.actions,.viewer,.site-note{display:none!important}.wrap{width:100%;padding:0}.cover{break-inside:avoid}.slide{height:90vh;break-inside:avoid;box-shadow:none;margin-bottom:15px}.slide img{object-fit:contain}}
</style>
</head>
<body>
<main class="wrap">
<header class="top"><div><div class="eyebrow">CENTRAL IMÓVEIS · GALERIA</div><h1 class="title"><?= e($imovel['nome']) ?></h1><p class="sub"><?= e($imovel['codigo']) ?><?php if (!empty($imovel['bairro'])): ?> · <?= e($imovel['bairro']) ?><?php endif; ?></p></div><div class="actions"><?php if (is_logged_in()): ?><a class="btn" href="download_fotos.php?id=<?= (int)$imovel['id']?>">Baixar ZIP</a><?php endif; ?><?php if (($_GET['modo']??'')==='pdf'): ?><button class="btn" type="button" onclick="window.print()" title="Abre a impressão do navegador para salvar esta galeria como PDF">Salvar PDF</button><?php endif; ?><button class="btn" type="button" onclick="shareGallery()" title="Compartilha a galeria ou copia o link">Compartilhar</button></div></header>
<?php
$cover=null;$library=[];
foreach($fotos as $foto){if(($foto['tipo']??'biblioteca')==='capa' && !$cover)$cover=$foto;else $library[]=$foto;}
if(!$cover && $fotos){$cover=$fotos[0];$library=array_slice($fotos,1);}
$features=[];
if((int)($imovel['suites']??0)>0)$features[]=(int)$imovel['suites'].' suítes';
elseif((int)($imovel['dormitorios']??0)>0)$features[]=(int)$imovel['dormitorios'].' dormitórios';
if(!empty($imovel['piscina']))$features[]='Piscina';
if(!empty($imovel['churrasqueira']))$features[]='Churrasqueira';
if(!empty($imovel['ar_condicionado']))$features[]='Ar-condicionado';
if((int)($imovel['capacidade']??0)>0)$features[]=(int)$imovel['capacidade'].' pessoas';
if((int)($imovel['distancia_praia']??0)>0)$features[]=(int)$imovel['distancia_praia'].' m da praia';
?>
<?php if($cover): ?><section class="intro"><button class="cover" type="button" onclick="openViewer(0)" title="Abrir a foto de capa em tela cheia"><img src="<?=e($cover['url'])?>" alt="<?=e($imovel['nome'])?> — foto de capa"><div class="cover-info"><div class="cover-code"><?=e($imovel['codigo'])?></div><div class="cover-title"><?=e($imovel['nome'])?></div><div class="chips"><?php foreach($features as $feature): ?><span class="chip"><?=e($feature)?></span><?php endforeach; ?></div></div></button><aside class="description"><h2>Sobre o imóvel</h2><p><?=e($imovel['descricao']??'') ?: 'Consulte as informações deste imóvel na Central Imóveis.'?></p></aside></section><?php endif; ?>
<div class="gallery-head"><h2>Fotos do imóvel</h2><span><?=count($fotos)?> foto(s) · uma por vez</span></div>
<?php if(!$fotos): ?><section class="empty">Este imóvel ainda não possui fotos cadastradas.</section><?php else: ?><section class="slides" aria-label="Fotos do imóvel"><?php foreach($fotos as $i=>$foto): ?><button class="slide" type="button" onclick="openViewer(<?= (int)$i ?>)" title="Abrir foto em tela cheia"><img src="<?=e($foto['url'])?>" alt="<?=e($imovel['nome'])?> — foto <?= $i+1 ?>" loading="<?= $i<2?'eager':'lazy' ?>" decoding="async"><span class="slide-label"><?= $i===0?'Capa · ':'' ?>Foto <?= $i+1 ?> de <?=count($fotos)?></span></button><?php endforeach; ?></section><?php endif; ?>
<div class="site-note">Arraste/role para ver as fotos. No visualizador, use as setas ← → ou deslize no celular.</div>
</main>
<div class="viewer" id="viewer" role="dialog" aria-modal="true" aria-label="Visualização das fotos" onclick="if(event.target===this)closeViewer()"><span class="count" id="count"></span><button class="close" type="button" onclick="closeViewer()" aria-label="Fechar" title="Fechar visualização">×</button><button class="nav prev" type="button" onclick="move(-1)" aria-label="Foto anterior" title="Foto anterior">‹</button><img id="viewerImage" src="" alt=""><button class="nav next" type="button" onclick="move(1)" aria-label="Próxima foto" title="Próxima foto">›</button></div>
<script>
const photos=<?=json_encode(array_column($fotos,'url'),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;let current=0;
function show(){document.getElementById('viewerImage').src=photos[current];document.getElementById('count').textContent=(current+1)+' / '+photos.length}
function openViewer(i){if(!photos.length)return;current=i;show();document.getElementById('viewer').classList.add('open');document.body.style.overflow='hidden'}
function closeViewer(){document.getElementById('viewer').classList.remove('open');document.body.style.overflow=''}
function move(s){if(!photos.length)return;current=(current+s+photos.length)%photos.length;show()}
let touchX=0;document.getElementById('viewer').addEventListener('touchstart',e=>{touchX=e.changedTouches[0].clientX},{passive:true});document.getElementById('viewer').addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>45)move(dx<0?1:-1)},{passive:true});
document.addEventListener('keydown',e=>{if(!document.getElementById('viewer').classList.contains('open'))return;if(e.key==='Escape')closeViewer();if(e.key==='ArrowRight')move(1);if(e.key==='ArrowLeft')move(-1)});
async function shareGallery(){const data={title:<?=json_encode((string)$imovel['nome'],JSON_UNESCAPED_UNICODE)?>,text:'Fotos do imóvel <?=e($imovel['nome'])?>',url:<?=json_encode($galleryUrl,JSON_UNESCAPED_SLASHES)?>};if(navigator.share){try{await navigator.share(data);return}catch(e){}}try{await navigator.clipboard.writeText(data.url);alert('Link da galeria copiado.')}catch(e){prompt('Copie o link da galeria:',data.url)}}
</script>
</body>
</html>