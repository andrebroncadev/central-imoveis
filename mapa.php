<?php
require_once __DIR__.'/app.php'; require_login();
$imoveis=supabase_request('GET','/rest/v1/imoveis?select=id,codigo,nome,bairro,latitude,longitude,ativo&order=nome.asc');$imoveis=is_array($imoveis)?array_values(array_filter($imoveis,fn($i)=>isset($i['latitude'],$i['longitude']))):[];
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Mapa · Central Imóveis</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"></head><body><main class="container">
<header class="page-head"><div><span class="eyebrow">LOCALIZAÇÃO</span><h1>Mapa</h1><p class="subtitulo">Todos os imóveis com localização cadastrada.</p></div></header><?php render_nav("mapa"); ?>
<div id="mapa" class="mapa"></div>
<?php if(!$imoveis):?><section class="vazio"><h2>Nenhuma localização cadastrada</h2><p>Edite um imóvel e informe endereço, latitude e longitude. O endereço também tenta ser geocodificado automaticamente.</p></section><?php endif;?>
<section class="map-list"><?php foreach($imoveis as $i):?><a class="map-list-item" href="editar.php?id=<?=$i['id']?>"><strong><?=e($i['nome'])?></strong><span><?=e($i['codigo'])?> · <?=e($i['bairro']??'')?></span></a><?php endforeach;?></section>
<?php render_footer(); ?></main><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
const places=<?=json_encode($imoveis,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;const map=L.map('mapa');L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
if(places.length){const bounds=[];places.forEach(p=>{const lat=Number(p.latitude),lng=Number(p.longitude);bounds.push([lat,lng]);L.marker([lat,lng]).addTo(map).bindPopup('<strong>'+escapeHtml(p.nome)+'</strong><br>'+escapeHtml(p.codigo)+'<br><a href="editar.php?id='+Number(p.id)+'">Abrir imóvel</a>');});map.fitBounds(bounds,{padding:[30,30]});}else map.setView([-23.76,-45.72],11);
function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
</script></body></html>