<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'dbconnection.php';

// Fetch distinct categories from database
$categories = [];
$result = mysqli_query($connect, "SELECT cat_code, cat_name, MIN(sort_no) AS sort_order FROM category GROUP BY cat_code, cat_name ORDER BY sort_order ASC, cat_name ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = [
            'id' => $row['cat_code'],
            'name' => $row['cat_name'],
            'image' => null
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory - Categories</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
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

.page-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.section-title {
  font-family: 'Outfit', sans-serif;
  font-size: 22px;
  font-weight: 700;
  color: var(--text);
}

.cat-count {
  font-size: 13px;
  color: var(--text-muted);
  font-weight: 500;
}

.cat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.cat-card {
  background: var(--surface);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  cursor: pointer;
  transition: box-shadow var(--transition), transform var(--transition);
  text-decoration: none;
  color: var(--text);
}

.cat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); }
.cat-card .cat-img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; transition: transform 0.4s ease; background: var(--bg); }
.cat-card:hover .cat-img { transform: scale(1.04); }
.cat-card .img-wrap { overflow: hidden; position: relative; }

.cat-card .cat-name {
  padding: 12px 14px;
  font-family: 'Outfit', sans-serif;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.cat-card .product-count {
  display: block;
  font-family: 'DM Sans', sans-serif;
  font-size: 11px;
  font-weight: 500;
  color: var(--text-muted);
  text-transform: none;
  letter-spacing: 0;
  margin-top: 2px;
}

.no-img-placeholder {
  width: 100%;
  aspect-ratio: 4/3;
  background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 36px;
}

.empty-msg {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-muted);
  font-size: 15px;
}

@media (max-width: 992px) { .cat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .cat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } .section-title { font-size: 19px; } .cat-card .cat-name { font-size: 12px; padding: 10px; } }
@media (max-width: 480px) { .cat-card .cat-name { font-size: 11px; padding: 8px; } }

@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
.cat-card { animation: fadeUp 0.4s ease both; }
.cat-card:nth-child(1) { animation-delay: 0.05s; }
.cat-card:nth-child(2) { animation-delay: 0.1s; }
.cat-card:nth-child(3) { animation-delay: 0.15s; }
.cat-card:nth-child(4) { animation-delay: 0.2s; }
.cat-card:nth-child(5) { animation-delay: 0.25s; }
.cat-card:nth-child(6) { animation-delay: 0.3s; }
.cat-card:nth-child(7) { animation-delay: 0.35s; }
.cat-card:nth-child(8) { animation-delay: 0.4s; }
</style>
</head>
<body>

<?php include('navbar.php'); ?>

<main class="main">
  <section>
    <div class="page-heading">
      <h2 class="section-title">Categories</h2>
      <span class="cat-count" id="catCount"></span>
    </div>
    <div class="cat-grid" id="catGrid"></div>
  </section>
</main>

<?php include('mobile-bottombar.php'); ?>

<script>
var categories = <?php echo json_encode($categories); ?>;

function renderGrid(list) {
  var data = list || categories;
  var grid = document.getElementById('catGrid');

  if (data.length === 0) {
    grid.innerHTML = '<div class="empty-msg" style="grid-column:1/-1;">No categories found.</div>';
    document.getElementById('catCount').textContent = '';
    return;
  }

  document.getElementById('catCount').textContent = data.length + ' categories';

  grid.innerHTML = data.map(function(c, i) {
    var imgHtml = c.image
      ? '<img class="cat-img" src="' + c.image + '" alt="' + c.name + '" loading="lazy">'
      : '<div class="no-img-placeholder"><svg style="width:40px;height:40px;stroke:#9ca3af;fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;"><rect x="3" y="3" width="34" height="34" rx="4"/><circle cx="13" cy="15" r="4"/><polyline points="37 27 27 17 7 37"/></svg></div>';
    return '<a href="products.php?cat=' + c.id + '" class="cat-card" style="animation-delay:' + ((i+1)*0.05) + 's">' +
      '<div class="img-wrap">' + imgHtml + '</div>' +
      '<div class="cat-name">' + c.name + '</div>' +
    '</a>';
  }).join('');
}

renderGrid();
</script>
</body>
</html>
