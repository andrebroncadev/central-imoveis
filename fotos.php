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
:root{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#17202a;background:#f5f6f8}
*{box-sizing:border-box}body{margin:0}.wrap{width:min(1180px,calc(100% - 28px));margin:0 auto;padding:28px 0 48px}.top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:24px}.eyebrow{font-size:12px;font-weight:800;letter-spacing:.12em;color:#68717d}.title{margin:6px 0 4px;font-size:clamp(26px,5vw,42px);line-height:1.05}.sub{margin:0;color:#68717d}.actions{display:flex;gap:8px;flex-wrap:wrap}.btn{border:0;border-radius:12px;padding:11px 15px;font-weight:700;text-decoration:none;cursor:pointer;background:#17202a;color:#fff}.btn.light{background:#fff;color:#17202a;border:1px solid #dfe3e8}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}.photo{border:0;padding:0;border-radius:16px;overflow:hidden;background:#ddd;cursor:pointer;aspect-ratio:4/3}.photo img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .2s}.photo:hover img{transform:scale(1.025)}.empty{padding:40px 20px;text-align:center;background:#fff;border-radius:16px;color:#68717d}.viewer{position:fixed;inset:0;background:rgba(0,0,0,.94);display:none;align-items:center;justify-content:center;padding:18px;z-index:10}.viewer.open{display:flex}.viewer img{max-width:96vw;max-height:92vh;object-fit:contain}.close{position:fixed;right:18px;top:14px;border:0;background:#fff;color:#111;border-radius:999px;width:42px;height:42px;font-size:24px;cursor:pointer}.count{position:fixed;left:18px;top:18px;color:#fff;font-weight:700}@media(max-width:700px){.top{display:block}.actions{margin-top:16px}.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.photo{border-radius:11px}.wrap{width:min(100% - 18px,1180px);padding-top:18px}}
</style>
</head>
<body>
<main class="wrap">
<header class="top">
<div>
<div class="eyebrow">GALERIA DO IMÓVEL</div>
<h1 class="title"><?= e($imovel['nome']) ?></h1>
<p class="sub"><?= e($imovel['codigo']) ?><?php if (!empty($imovel['bairro'])): ?> · <?= e($imovel['bairro']) ?><?php endif; ?> · <?= count($fotos) ?> foto(s)</p>
</div>
<div class="actions">
<button class="btn" type="button" onclick="shareGallery()">Compartilhar</button>
<a class="btn light" href="<?= e($galleryUrl) ?>">Atualizar</a>
</div>
</header>

<?php if (!$fotos): ?>
<section class="empty">Este imóvel ainda não possui fotos cadastradas.</section>
<?php else: ?>
<section class="grid" aria-label="Fotos do imóvel">
<?php foreach ($fotos as $i => $foto): ?>
<button class="photo" type="button" onclick="openViewer(<?= (int)$i ?>)" aria-label="Abrir foto <?= $i + 1 ?>">
<img src="<?= e($foto['url']) ?>" alt="<?= e($imovel['nome']) ?> — foto <?= $i + 1 ?>" loading="lazy" decoding="async">
</button>
<?php endforeach; ?>
</section>
<?php endif; ?>
</main>

<div class="viewer" id="viewer" role="dialog" aria-modal="true" aria-label="Visualização da foto" onclick="if(event.target===this)closeViewer()">
<span class="count" id="count"></span>
<button class="close" type="button" onclick="closeViewer()" aria-label="Fechar">×</button>
<img id="viewerImage" src="" alt="">
</div>

<script>
const photos = <?= json_encode(array_column($fotos, 'url'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
let current = 0;
function openViewer(index){current=index;document.getElementById('viewerImage').src=photos[current];document.getElementById('count').textContent=(current+1)+' / '+photos.length;document.getElementById('viewer').classList.add('open');document.body.style.overflow='hidden';}
function closeViewer(){document.getElementById('viewer').classList.remove('open');document.body.style.overflow='';}
function move(step){if(!photos.length)return;current=(current+step+photos.length)%photos.length;document.getElementById('viewerImage').src=photos[current];document.getElementById('count').textContent=(current+1)+' / '+photos.length;}
async function shareGallery(){const data={title:<?= json_encode((string)$imovel['nome'], JSON_UNESCAPED_UNICODE) ?>,text:'Fotos do imóvel <?= e($imovel['nome']) ?>',url:<?= json_encode($galleryUrl, JSON_UNESCAPED_SLASHES) ?>};if(navigator.share){try{await navigator.share(data);return;}catch(e){}}try{await navigator.clipboard.writeText(data.url);alert('Link da galeria copiado.');}catch(e){prompt('Copie o link da galeria:',data.url);}}
document.addEventListener('keydown',e=>{if(!document.getElementById('viewer').classList.contains('open'))return;if(e.key==='Escape')closeViewer();if(e.key==='ArrowRight')move(1);if(e.key==='ArrowLeft')move(-1);});
</script>
</body>
</html>