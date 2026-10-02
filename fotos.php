<?php
declare(strict_types=1);
require_once __DIR__ . '/app.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$token = trim((string)($_GET['t'] ?? ''));

if (!$id || !verify_gallery_token($id, $token)) {
    http_response_code(404);
    exit('Anúncio não encontrado.');
}

try {
    $rows = supabase_request('GET', '/rest/v1/imoveis?select=*&id=eq.' . $id . '&limit=1');
    $imovel = $rows[0] ?? null;
} catch (Throwable) {
    http_response_code(500);
    exit('Não foi possível carregar o anúncio.');
}

if (!$imovel) {
    http_response_code(404);
    exit('Anúncio não encontrado.');
}

$fotos = is_array($imovel['fotos'] ?? null) ? $imovel['fotos'] : [];
$fotos = array_values(array_filter($fotos, static fn($foto) => is_array($foto) && !empty($foto['url'])));
$galleryUrl = gallery_url((int)$imovel['id']);
$profile = profile_data();

$cover = null;
foreach ($fotos as $foto) {
    if (($foto['tipo'] ?? 'biblioteca') === 'capa') {
        $cover = $foto;
        break;
    }
}
if (!$cover && $fotos) $cover = $fotos[0];

$features = [];
if ((int)($imovel['suites'] ?? 0) > 0) $features[] = (int)$imovel['suites'] . ' suítes';
elseif ((int)($imovel['dormitorios'] ?? 0) > 0) $features[] = (int)$imovel['dormitorios'] . ' dormitórios';
if (!empty($imovel['piscina'])) $features[] = 'Piscina';
if (!empty($imovel['churrasqueira'])) $features[] = 'Churrasqueira';
if (!empty($imovel['ar_condicionado'])) $features[] = 'Ar-condicionado';
if ((int)($imovel['capacidade'] ?? 0) > 0) $features[] = (int)$imovel['capacidade'] . ' pessoas';
if ((int)($imovel['distancia_praia'] ?? 0) > 0) $features[] = (int)$imovel['distancia_praia'] . ' m da praia';

$lat = isset($imovel['latitude']) && $imovel['latitude'] !== '' ? (float)$imovel['latitude'] : null;
$lng = isset($imovel['longitude']) && $imovel['longitude'] !== '' ? (float)$imovel['longitude'] : null;
$hasMap = $lat !== null && $lng !== null;
$locationParts = array_values(array_filter([
    trim((string)($imovel['bairro'] ?? '')),
    trim((string)($imovel['cidade'] ?? '')),
    trim((string)($imovel['uf'] ?? ''))
]));
$locationTitle = $locationParts ? implode(' · ', $locationParts) : 'Localização aproximada';
$locationTextParts = [];
if (!empty($imovel['bairro'])) $locationTextParts[] = 'O imóvel está localizado na região de ' . (string)$imovel['bairro'];
if (!empty($imovel['cidade'])) $locationTextParts[] = (string)$imovel['cidade'];
if ((int)($imovel['distancia_praia'] ?? 0) > 0) $locationTextParts[] = 'a aproximadamente ' . (int)$imovel['distancia_praia'] . ' metros da praia';
$locationDescription = $locationTextParts ? implode(', ', $locationTextParts) . '.' : 'A localização exibida no mapa é aproximada para preservar a privacidade do imóvel.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= e($imovel['nome']) ?> · Central Imóveis</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
:root{--ink:#17212b;--muted:#697786;--line:#e1e5e9;--surface:#fff;--page:#f4f5f6;--accent:#1f6f8b}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--page);color:var(--ink);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.wrap{width:min(1120px,calc(100% - 32px));margin:auto;padding:34px 0 70px}.top{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin-bottom:24px}.eyebrow{font-size:10px;font-weight:800;letter-spacing:.15em;color:#7c8791}.title{margin:7px 0 6px;font-size:clamp(30px,5vw,48px);line-height:1.02;letter-spacing:-.045em}.sub{margin:0;color:var(--muted);font-size:13px}.actions{display:flex;gap:8px;flex-wrap:wrap}.btn{border:1px solid var(--ink);border-radius:10px;padding:10px 14px;font-weight:750;text-decoration:none;cursor:pointer;background:var(--ink);color:#fff}.btn.light{background:var(--surface);border-color:var(--line);color:var(--ink)}
.hero{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:14px;box-shadow:0 12px 40px rgba(20,30,40,.07)}.main-photo{height:min(68vh,680px);min-height:360px;background:#111;border-radius:13px;overflow:hidden;display:flex;align-items:center;justify-content:center}.main-photo img{width:100%;height:100%;object-fit:contain;display:block}.thumbs{display:flex;gap:8px;overflow-x:auto;padding:10px 0 2px;scrollbar-width:thin}.thumb{flex:0 0 72px;width:72px;height:54px;padding:0;border:1px solid transparent;border-radius:7px;overflow:hidden;background:#e9ecef;cursor:pointer}.thumb.active{border-color:#17212b}.thumb img{width:100%;height:100%;object-fit:cover;display:block}.slider-controls{display:flex;align-items:center;gap:12px;margin-top:9px}.next-btn{flex:0 0 auto;border:1px solid var(--line);background:var(--surface);color:var(--ink);width:40px;height:34px;border-radius:8px;font-size:22px;line-height:1;cursor:pointer}.range{width:100%;accent-color:#596b78;cursor:pointer}.counter{font-size:11px;color:var(--muted);white-space:nowrap;min-width:42px;text-align:right}.details{margin-top:28px;background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:26px}.details h2{font-size:21px;margin:0 0 13px;letter-spacing:-.02em}.description{white-space:pre-line;color:#536170;font-size:14px;line-height:1.75;margin:0}.chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:18px}.chip{padding:7px 10px;border:1px solid var(--line);border-radius:999px;font-size:11px;font-weight:750;color:#52606d;background:#fafafa}.location{margin-top:28px}.location-map{height:390px;border-radius:18px;overflow:hidden;border:1px solid var(--line);background:#dfe4e7}.location-info{margin-top:16px;background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:20px}.location-info h2{margin:0 0 8px;font-size:20px}.location-info .location-name{font-weight:750;margin-bottom:8px}.location-info p{margin:0;color:#536170;font-size:14px;line-height:1.7}.privacy{margin-top:9px;color:#89939c;font-size:11px}.profile{display:flex;align-items:center;gap:11px;margin-top:30px;padding-top:22px;border-top:1px solid var(--line)}.profile img,.profile-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;background:#e7eaed;display:grid;place-items:center;font-weight:800}.profile-text strong{display:block;font-size:13px}.profile-text span{display:block;color:var(--muted);font-size:11px;margin-top:2px}.empty{padding:60px 20px;text-align:center;background:var(--surface);border:1px solid var(--line);border-radius:18px;color:var(--muted)}.house-marker{background:transparent;border:0}.area-marker{width:150px;height:150px;border-radius:50%;background:rgba(75,145,180,.25);border:2px solid rgba(75,145,180,.55);box-shadow:0 0 0 8px rgba(75,145,180,.07)}.leaflet-control-attribution{font-size:9px}@media(max-width:720px){.wrap{width:min(100% - 20px,1120px);padding-top:22px}.top{display:block}.actions{margin-top:15px}.main-photo{height:56vh;min-height:280px}.thumb{flex-basis:64px;width:64px;height:48px}.details{padding:21px}.location-map{height:310px}}
</style>
</head>
<body>
<main class="wrap">
<header class="top">
<div><div class="eyebrow">CENTRAL IMÓVEIS · LOCAÇÃO</div><h1 class="title"><?= e($imovel['nome']) ?></h1><p class="sub"><?= e($imovel['codigo']) ?><?php if (!empty($imovel['bairro'])): ?> · <?= e($imovel['bairro']) ?><?php endif; ?></p></div>
<div class="actions"><?php if (is_logged_in()): ?><a class="btn light" href="download_fotos.php?id=<?= (int)$imovel['id'] ?>">Baixar fotos</a><?php endif; ?><button class="btn" type="button" onclick="shareGallery()">Compartilhar</button></div>
</header>
<?php if (!$fotos): ?><section class="empty">Este imóvel ainda não possui fotos cadastradas.</section><?php else: ?>
<section class="hero" aria-label="Galeria de fotos">
<div class="main-photo"><img id="mainImage" src="<?= e($fotos[0]['url']) ?>" alt="<?= e($imovel['nome']) ?>"></div>
<div class="thumbs" id="thumbs" aria-label="Miniaturas das fotos"><?php foreach ($fotos as $i=>$foto): ?><button class="thumb <?= $i===0?'active':'' ?>" type="button" data-index="<?= $i ?>" onclick="selectPhoto(<?= $i ?>)" aria-label="Foto <?= $i+1 ?>"><img src="<?= e($foto['url']) ?>" alt=""></button><?php endforeach; ?></div>
<div class="slider-controls"><input class="range" id="photoRange" type="range" min="0" max="<?= max(0,count($fotos)-1) ?>" value="0" aria-label="Selecionar foto"><button class="next-btn" type="button" onclick="nextPhoto()" aria-label="Próxima foto">›</button><span class="counter" id="counter">1/<?= count($fotos) ?></span></div>
</section>
<?php endif; ?>
<section class="details">
<h2>Sobre o imóvel</h2>
<p class="description"><?= e($imovel['descricao'] ?? '') ?: 'Entre em contato para receber mais informações sobre este imóvel.' ?></p>
<?php if ($features): ?><div class="chips"><?php foreach ($features as $feature): ?><span class="chip"><?= e($feature) ?></span><?php endforeach; ?></div><?php endif; ?>
<?php if ((float)($imovel['diaria'] ?? 0) > 0): ?><div style="margin-top:20px;font-size:20px;font-weight:800">R$ <?= number_format((float)$imovel['diaria'],2,',','.') ?> <span style="font-size:12px;font-weight:500;color:#697786">/ diária</span></div><?php endif; ?>
</section>
<?php if ($hasMap): ?>
<section class="location">
<div id="locationMap" class="location-map" aria-label="Mapa com localização aproximada"></div>
<div class="location-info"><h2>Localização</h2><div class="location-name"><?= e($locationTitle) ?></div><p><?= e($locationDescription) ?></p><div class="privacy">A área mostrada é aproximada. O ponto exato do imóvel não é exibido ao locatário.</div></div>
</section>
<?php endif; ?>
<?php if (!empty($profile['nome'])): ?><div class="profile"><?php if (!empty($profile['foto_url'])): ?><img src="<?= e($profile['foto_url']) ?>" alt="<?= e($profile['nome']) ?>"><?php else: ?><div class="profile-avatar"><?= e(mb_strtoupper(mb_substr((string)$profile['nome'],0,1))) ?></div><?php endif; ?><div class="profile-text"><strong><?= e($profile['nome']) ?></strong><?php if (!empty($profile['creci'])): ?><span>CRECI <?= e($profile['creci']) ?></span><?php endif; ?></div></div><?php endif; ?>
</main>
<?php if ($hasMap): ?><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><?php endif; ?>
<script>
const photos=<?= json_encode(array_column($fotos,'url'),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;let current=0;
const mainImage=document.getElementById('mainImage'),range=document.getElementById('photoRange'),counter=document.getElementById('counter'),thumbs=[...document.querySelectorAll('.thumb')];
function selectPhoto(index){if(!photos.length)return;current=Math.max(0,Math.min(index,photos.length-1));mainImage.src=photos[current];range.value=current;counter.textContent=(current+1)+'/'+photos.length;thumbs.forEach((el,i)=>el.classList.toggle('active',i===current));const active=thumbs[current];if(active)active.scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'})}
function nextPhoto(){selectPhoto((current+1)%photos.length)}
if(range)range.addEventListener('input',e=>selectPhoto(Number(e.target.value)));
document.addEventListener('keydown',e=>{if(!photos.length)return;if(e.key==='ArrowRight')nextPhoto();if(e.key==='ArrowLeft')selectPhoto((current-1+photos.length)%photos.length)});
let touchX=0;const photoArea=document.querySelector('.main-photo');if(photoArea){photoArea.addEventListener('touchstart',e=>touchX=e.changedTouches[0].clientX,{passive:true});photoArea.addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>45)dx<0?nextPhoto():selectPhoto((current-1+photos.length)%photos.length)},{passive:true})}
async function shareGallery(){const data={title:<?=json_encode((string)$imovel['nome'],JSON_UNESCAPED_UNICODE)?>,text:'<?=e($imovel['nome'])?> · Central Imóveis',url:<?=json_encode($galleryUrl,JSON_UNESCAPED_SLASHES)?>};if(navigator.share){try{await navigator.share(data);return}catch(e){}}try{await navigator.clipboard.writeText(data.url);alert('Link do anúncio copiado.')}catch(e){prompt('Copie o link do anúncio:',data.url)}}
<?php if ($hasMap): ?>
const map=L.map('locationMap',{zoomControl:false,scrollWheelZoom:false,dragging:true});L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OpenStreetMap'}).addTo(map);L.control.zoom({position:'bottomright'}).addTo(map);const lat=<?= json_encode($lat) ?>,lng=<?= json_encode($lng) ?>;map.setView([lat,lng],15);L.marker([lat,lng],{icon:L.divIcon({className:'house-marker',html:'<div class="area-marker"></div>',iconSize:[150,150],iconAnchor:[75,75]})}).addTo(map);
<?php endif; ?>
</script>
</body>
</html>