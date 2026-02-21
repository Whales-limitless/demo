<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Category</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,500;0,9..40,700;1,9..40,400&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="components.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary: #C8102E;
  --primary-dark: #a00d24;
  --surface: #ffffff;
  --bg: #f3f4f6;
  --text: #1a1a1a;
  --text-muted: #6b7280;
  --radius: 14px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
  --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

html { scroll-behavior: smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

.main { max-width: 960px; margin: 0 auto; padding: 24px 16px 90px; }

.section-title {
  font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;
  margin-bottom: 16px; color: var(--text);
}

.cat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }

.cat-card {
  background: var(--surface); border-radius: var(--radius); overflow: hidden;
  box-shadow: var(--shadow-sm); cursor: pointer;
  transition: box-shadow var(--transition), transform var(--transition);
  text-decoration: none; color: var(--text);
}

.cat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); }
.cat-card .cat-img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; transition: transform 0.4s ease; }
.cat-card:hover .cat-img { transform: scale(1.04); }
.cat-card .img-wrap { overflow: hidden; }
.cat-card .cat-name { padding: 12px 14px; font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 14px; text-align: center; text-transform: uppercase; letter-spacing: 0.02em; }

.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; justify-content: center; align-items: center; padding: 16px; }
.modal-overlay.active { display: flex; }
.modal { background: var(--surface); border-radius: var(--radius); width: 100%; max-width: 440px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); animation: modalIn 0.25s ease; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; }
.modal-close { background: none; border: none; cursor: pointer; width: 32px; height: 32px; display: grid; place-items: center; border-radius: 6px; color: var(--text-muted); transition: background var(--transition); }
.modal-close:hover { background: var(--bg); }
.modal-search { padding: 12px 20px; border-bottom: 1px solid #e5e7eb; }
.modal-search input { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color var(--transition); }
.modal-search input:focus { border-color: var(--primary); }
.modal-body { overflow-y: auto; padding: 8px 20px; flex: 1; }
.modal-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background var(--transition); font-size: 14px; }
.modal-item:last-child { border-bottom: none; }
.modal-item:hover { background: #f9fafb; margin: 0 -20px; padding: 10px 20px; }
.modal-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0; }
.modal-item label { cursor: pointer; flex: 1; }
.modal-footer { padding: 14px 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end; }
.btn { font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 14px; border: none; padding: 10px 24px; border-radius: 8px; cursor: pointer; transition: all var(--transition); }
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-ghost { background: none; color: var(--text-muted); }
.btn-ghost:hover { background: var(--bg); }
.active-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.filter-tag { display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: var(--primary); border: 1px solid #fecaca; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; animation: fadeUp 0.2s ease; }
.filter-tag button { background: none; border: none; cursor: pointer; color: var(--primary); font-size: 14px; line-height: 1; padding: 0; }

@media (max-width: 992px) { .cat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .cat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } .section-title { font-size: 19px; } .cat-card .cat-name { font-size: 12px; padding: 10px; } }
@media (max-width: 480px) { .cat-card .cat-name { font-size: 11px; padding: 8px; } }

@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
.cat-card { animation: fadeUp 0.4s ease both; }
.cat-card:nth-child(1) { animation-delay: 0.05s; } .cat-card:nth-child(2) { animation-delay: 0.1s; } .cat-card:nth-child(3) { animation-delay: 0.15s; } .cat-card:nth-child(4) { animation-delay: 0.2s; }
.cat-card:nth-child(5) { animation-delay: 0.25s; } .cat-card:nth-child(6) { animation-delay: 0.3s; } .cat-card:nth-child(7) { animation-delay: 0.35s; } .cat-card:nth-child(8) { animation-delay: 0.4s; }
</style>
</head>
<body>

<?php $searchPlaceholder = 'Search categories…'; include('navbar.php'); ?>

<main class="main">
  <section>
    <h2 class="section-title" id="catFilterBtn" style="cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
      Category
      <svg style="width:18px;height:18px;stroke:var(--text);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46"/></svg>
    </h2>
    <div class="active-filters" id="activeFilters"></div>
    <div class="cat-grid" id="catGrid"></div>
  </section>
</main>

<div class="modal-overlay" id="filterModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Filter Categories</h3>
      <button class="modal-close" id="modalClose">
        <svg style="width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-search"><input type="text" placeholder="Search categories…" id="modalSearchInput"></div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="clearAll">Clear All</button>
      <button class="btn btn-primary" id="applyFilter">Apply</button>
    </div>
  </div>
</div>

<?php include('mobile-bottombar.php'); ?>

<script>
const categories = [
  { id: 1, name: 'Drawer', img: 'https://picsum.photos/seed/drawer/600/450' },
  { id: 2, name: 'Plastic Cup / Mould / Pet Container', img: 'https://picsum.photos/seed/plastic/600/450' },
  { id: 3, name: 'Glassware', img: 'https://picsum.photos/seed/glass/600/450' },
  { id: 4, name: 'Electrical', img: 'https://picsum.photos/seed/electrical/600/450' },
  { id: 5, name: 'Houseware', img: 'https://picsum.photos/seed/houseware/600/450' },
  { id: 6, name: 'Storage / Rack', img: 'https://picsum.photos/seed/storage/600/450' },
  { id: 7, name: 'Dessini', img: 'https://picsum.photos/seed/dessini/600/450' },
  { id: 8, name: 'Cookware / Pot', img: 'https://picsum.photos/seed/cookware/600/450' },
];

let selectedFilters = new Set();

function renderGrid(filtered) {
  const grid = document.getElementById('catGrid');
  const list = filtered || categories;
  grid.innerHTML = list.map((c, i) => `
    <a href="products.php?cat=${c.id}" class="cat-card" style="animation-delay:${(i+1)*0.05}s">
      <div class="img-wrap"><img class="cat-img" src="${c.img}" alt="${c.name}"></div>
      <div class="cat-name">${c.name}</div>
    </a>
  `).join('');
}

function renderFilterTags() {
  const container = document.getElementById('activeFilters');
  if (selectedFilters.size === 0) { container.innerHTML = ''; return; }
  container.innerHTML = Array.from(selectedFilters).map(id => {
    const cat = categories.find(c => c.id === id);
    return `<span class="filter-tag">${cat.name}<button onclick="removeFilter(${id})">×</button></span>`;
  }).join('');
}

function removeFilter(id) { selectedFilters.delete(id); applyCurrentFilters(); renderFilterTags(); }

function applyCurrentFilters() {
  if (selectedFilters.size === 0) { renderGrid(); return; }
  renderGrid(categories.filter(c => selectedFilters.has(c.id)));
}

const filterModal = document.getElementById('filterModal');
const modalBody = document.getElementById('modalBody');
const modalSearch = document.getElementById('modalSearchInput');

function openModal() { renderModalList(); filterModal.classList.add('active'); modalSearch.value = ''; }
function closeModal() { filterModal.classList.remove('active'); }

function renderModalList(query) {
  const q = (query || '').toLowerCase();
  const filtered = categories.filter(c => c.name.toLowerCase().includes(q));
  modalBody.innerHTML = filtered.map(c => `
    <div class="modal-item" onclick="this.querySelector('input').click()">
      <input type="checkbox" id="chk_${c.id}" ${selectedFilters.has(c.id) ? 'checked' : ''} onclick="event.stopPropagation(); toggleCheck(${c.id}, this.checked)">
      <label for="chk_${c.id}" onclick="event.preventDefault()">${c.name}</label>
    </div>
  `).join('');
  if (!filtered.length) modalBody.innerHTML = '<p style="padding:20px 0;text-align:center;color:var(--text-muted);">No categories found</p>';
}

function toggleCheck(id, checked) { if (checked) selectedFilters.add(id); else selectedFilters.delete(id); }

document.getElementById('catFilterBtn').addEventListener('click', openModal);
document.getElementById('modalClose').addEventListener('click', closeModal);
filterModal.addEventListener('click', function(e) { if (e.target === filterModal) closeModal(); });
modalSearch.addEventListener('input', function() { renderModalList(this.value); });
document.getElementById('applyFilter').addEventListener('click', function() { applyCurrentFilters(); renderFilterTags(); closeModal(); });
document.getElementById('clearAll').addEventListener('click', function() { selectedFilters.clear(); renderModalList(modalSearch.value); });

document.getElementById('searchInput').addEventListener('input', function() {
  var q = this.value.toLowerCase();
  document.querySelectorAll('.cat-card').forEach(function(card) {
    card.style.display = card.querySelector('.cat-name').textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

renderGrid();
</script>
</body>
</html>