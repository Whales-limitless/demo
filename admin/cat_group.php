<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'cat_group';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Category Groups</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root {
    --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6;
    --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px;
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }

.cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.cat-card {
    background: var(--surface); border-radius: var(--radius); overflow: hidden;
    box-shadow: var(--shadow-md); position: relative; transition: transform var(--transition), box-shadow var(--transition);
}
.cat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
.cat-card.inactive { opacity: 0.55; }
.cat-card .card-img {
    width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; background: var(--bg);
}
.cat-card .no-img {
    width: 100%; aspect-ratio: 4/3; background: linear-gradient(135deg, #e5e7eb, #d1d5db);
    display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 36px;
}
.cat-card .card-body { padding: 12px 14px; }
.cat-card .card-title {
    font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 14px;
    text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 4px;
}
.cat-card .card-meta { font-size: 11px; color: var(--text-muted); display: flex; gap: 10px; }
.cat-card .card-actions { display: flex; gap: 4px; margin-top: 8px; }
.cat-card .card-actions button {
    padding: 4px 10px; border: none; border-radius: 6px; font-size: 11px; font-weight: 600;
    cursor: pointer; color: #fff; transition: opacity var(--transition);
}
.cat-card .card-actions button:hover { opacity: 0.85; }
.cat-card .badge-inactive {
    position: absolute; top: 8px; right: 8px; background: #ef4444; color: #fff;
    padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase;
}
.cat-card .badge-sort {
    position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.6); color: #fff;
    padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;
}

.search-box { position: relative; max-width: 320px; min-width: 180px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }

.item-count { font-size: 13px; color: var(--text-muted); }
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }

/* Modal form */
.img-preview { width: 100%; max-height: 200px; object-fit: contain; border-radius: 8px; background: var(--bg); display: none; margin-bottom: 10px; }
.img-preview.show { display: block; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

.empty-msg { text-align: center; padding: 60px 20px; color: var(--text-muted); font-size: 15px; grid-column: 1/-1; }

@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .cat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .cat-card .card-title { font-size: 12px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-layer-group" style="color:var(--primary);margin-right:8px;"></i>Category Groups</h1>
        <button class="btn-add" onclick="openCreateModal();"><i class="fas fa-plus"></i> Add Category Group</button>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search category groups..." oninput="filterCards();">
        </div>
        <span class="item-count" id="itemCount">Loading...</span>
    </div>

    <div class="cat-grid" id="catGrid">
        <div class="empty-msg"><i class="fas fa-spinner fa-spin" style="font-size:24px;display:block;margin-bottom:8px;"></i>Loading...</div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="catGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-layer-group"></i> Add Category Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="fName" class="form-control" placeholder="e.g. HOUSEWARE">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Code <span class="text-danger">*</span></label>
                    <input type="text" id="fCode" class="form-control" placeholder="e.g. 13">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Sort No</label>
                        <input type="number" id="fSortNo" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Main Page</label>
                        <input type="text" id="fMainPage" class="form-control" placeholder="e.g. 10">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Image</label>
                    <img id="imgPreview" class="img-preview" src="" alt="Preview">
                    <input type="file" id="fImage" class="form-control" accept="image/*" onchange="previewImage(this);">
                    <small class="text-muted">Leave empty to keep current image (when editing)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveCatGroup();"><i class="fas fa-check"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var modal = null;
var allCards = [];

document.addEventListener('DOMContentLoaded', function() {
    modal = new bootstrap.Modal(document.getElementById('catGroupModal'));
    loadCatGroups();
});

function loadCatGroups() {
    $.post('cat_group_ajax.php', { action: 'list' }, function(data) {
        allCards = data || [];
        renderCards(allCards);
    }, 'json');
}

function renderCards(items) {
    var grid = document.getElementById('catGrid');
    if (items.length === 0) {
        grid.innerHTML = '<div class="empty-msg"><i class="fas fa-layer-group" style="font-size:24px;display:block;margin-bottom:8px;"></i>No category groups found</div>';
        document.getElementById('itemCount').textContent = '0 groups';
        return;
    }

    var html = '';
    for (var i = 0; i < items.length; i++) {
        var c = items[i];
        var isInactive = c.status === 'INACTIVE';
        html += '<div class="cat-card' + (isInactive ? ' inactive' : '') + '">';

        // Sort badge
        if (c.sort_no) html += '<span class="badge-sort">#' + escHtml(c.sort_no) + '</span>';
        if (isInactive) html += '<span class="badge-inactive">Inactive</span>';

        // Image
        if (c.cat_img) {
            html += '<img class="card-img" src="../category_img/' + escHtml(c.cat_img) + '" alt="' + escHtml(c.cat_name) + '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">';
            html += '<div class="no-img" style="display:none;"><i class="fas fa-image"></i></div>';
        } else {
            html += '<div class="no-img"><i class="fas fa-image"></i></div>';
        }

        html += '<div class="card-body">';
        html += '<div class="card-title">' + escHtml(c.cat_name) + '</div>';
        html += '<div class="card-meta">';
        html += '<span>Code: ' + escHtml(c.ccode) + '</span>';
        html += '<span>' + (c.sub_count || 0) + ' sub-cats</span>';
        html += '</div>';
        html += '<div class="card-actions">';
        html += '<button style="background:#3b82f6;" onclick="openEditModal(' + c.id + ');"><i class="fas fa-pen"></i> Edit</button>';
        if (isInactive) {
            html += '<button style="background:#16a34a;" onclick="toggleStatus(' + c.id + ',\'activate\');"><i class="fas fa-check"></i> Activate</button>';
        } else {
            html += '<button style="background:#ef4444;" onclick="toggleStatus(' + c.id + ',\'deactivate\');"><i class="fas fa-ban"></i></button>';
        }
        html += '</div></div></div>';
    }
    grid.innerHTML = html;
    document.getElementById('itemCount').textContent = items.length + ' group(s)';
}

function filterCards() {
    var q = document.getElementById('searchInput').value.trim().toLowerCase();
    if (!q) { renderCards(allCards); return; }
    var filtered = allCards.filter(function(c) {
        return c.cat_name.toLowerCase().indexOf(q) !== -1 || c.ccode.indexOf(q) !== -1;
    });
    renderCards(filtered);
}

function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('fCode').value = '';
    document.getElementById('fSortNo').value = '0';
    document.getElementById('fMainPage').value = '';
    document.getElementById('fImage').value = '';
    document.getElementById('imgPreview').classList.remove('show');
    document.getElementById('imgPreview').src = '';
}

function openCreateModal() {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-layer-group"></i> Add Category Group';
    modal.show();
}

function openEditModal(id) {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-layer-group"></i> Edit Category Group';
    $.post('cat_group_ajax.php', { action: 'get', id: id }, function(data) {
        if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
        document.getElementById('editId').value = data.id;
        document.getElementById('fName').value = data.cat_name || '';
        document.getElementById('fCode').value = data.ccode || '';
        document.getElementById('fSortNo').value = data.sort_no || '0';
        document.getElementById('fMainPage').value = data.main_page || '';
        if (data.cat_img) {
            document.getElementById('imgPreview').src = '../category_img/' + data.cat_img;
            document.getElementById('imgPreview').classList.add('show');
        }
        modal.show();
    }, 'json');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('imgPreview').classList.add('show');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function saveCatGroup() {
    var name = document.getElementById('fName').value.trim();
    var code = document.getElementById('fCode').value.trim();
    if (!name || !code) {
        Swal.fire({ icon: 'warning', text: 'Category name and code are required.' });
        return;
    }

    var fd = new FormData();
    fd.append('action', document.getElementById('editId').value ? 'update' : 'create');
    if (document.getElementById('editId').value) fd.append('id', document.getElementById('editId').value);
    fd.append('cat_name', name);
    fd.append('ccode', code);
    fd.append('sort_no', document.getElementById('fSortNo').value || '0');
    fd.append('main_page', document.getElementById('fMainPage').value.trim());

    var fileInput = document.getElementById('fImage');
    if (fileInput.files.length > 0) {
        fd.append('cat_img', fileInput.files[0]);
    }

    $.ajax({
        type: 'POST', url: 'cat_group_ajax.php', data: fd,
        processData: false, contentType: false, dataType: 'json',
        success: function(data) {
            if (data.success) {
                modal.hide();
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                    loadCatGroups();
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}

function toggleStatus(id, action) {
    var msg = action === 'activate' ? 'Activate this category group?' : 'Deactivate this category group?';
    Swal.fire({
        text: msg, icon: 'question', showCancelButton: true,
        confirmButtonColor: action === 'activate' ? '#16a34a' : '#ef4444',
        confirmButtonText: action === 'activate' ? 'Activate' : 'Deactivate'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('cat_group_ajax.php', { action: action, id: id }, function(data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                        loadCatGroups();
                    });
                } else {
                    Swal.fire({ icon: 'error', text: data.error || 'Failed.' });
                }
            }, 'json');
        }
    });
}

function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s || ''));
    return d.innerHTML;
}

document.getElementById('catGroupModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('fName').focus();
});
</script>
</body>
</html>
