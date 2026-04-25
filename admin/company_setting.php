<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Defensive: ensure table exists (matches sql/add_company_setting.sql)
$connect->query("CREATE TABLE IF NOT EXISTS `company_setting` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `business_name` VARCHAR(255) NOT NULL DEFAULT '',
    `business_register_no` VARCHAR(100) NOT NULL DEFAULT '',
    `address_line1` VARCHAR(255) NOT NULL DEFAULT '',
    `address_line2` VARCHAR(255) NOT NULL DEFAULT '',
    `address_line3` VARCHAR(255) NOT NULL DEFAULT '',
    `tel_no` VARCHAR(50) NOT NULL DEFAULT '',
    `email` VARCHAR(150) NOT NULL DEFAULT '',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Ensure singleton row exists
$check = $connect->query("SELECT `id` FROM `company_setting` WHERE `id` = 1 LIMIT 1");
if ($check && $check->num_rows === 0) {
    $connect->query("INSERT INTO `company_setting` (`id`) VALUES (1)");
}

$row = $connect->query("SELECT * FROM `company_setting` WHERE `id` = 1 LIMIT 1")->fetch_assoc() ?: [];
$currentPage = 'company_setting';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Company Setting</title>
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
.card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 24px; max-width: 720px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text); }
.form-group label .req { color: var(--primary); margin-left: 2px; }
.form-group input { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color var(--transition); }
.form-group input:focus { border-color: var(--primary); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 720px) { .form-row { grid-template-columns: 1fr; } }
.actions { display: flex; gap: 8px; margin-top: 24px; }
.btn-save { background: var(--primary); color: #fff; border: none; padding: 11px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 8px; }
.btn-save:hover { background: var(--primary-dark); }
.help-note { background: #fef9c3; border: 1px solid #fde047; color: #713f12; padding: 12px 14px; border-radius: 8px; font-size: 12px; margin-bottom: 16px; }
.preview { margin-top: 28px; border-top: 1px dashed #d1d5db; padding-top: 20px; }
.preview h4 { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 12px; }
.preview-letterhead { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 22px 16px; text-align: center; font-family: 'Times New Roman', Times, serif; }
.preview-letterhead .biz-name { font-size: 20px; font-weight: 700; color: #1a1a1a; margin-bottom: 6px; letter-spacing: 0.02em; }
.preview-letterhead .addr { font-size: 13px; color: #1a1a1a; line-height: 1.5; }
.preview-letterhead .contact { font-size: 13px; color: #1a1a1a; line-height: 1.5; margin-top: 6px; }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-building" style="color:var(--primary);margin-right:8px;"></i>Company Setting</h1>
    </div>

    <div class="card">
        <div class="help-note">
            <i class="fas fa-info-circle"></i> These details are used as the letterhead on every Purchase Order PDF (admin and staff).
        </div>

        <div class="form-group">
            <label>Business Name <span class="req">*</span></label>
            <input type="text" id="fBusinessName" value="<?php echo htmlspecialchars($row['business_name'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. PARKWAY DEPARTMENTAL STORE SDN BHD">
        </div>

        <div class="form-group">
            <label>Business Register Number</label>
            <input type="text" id="fRegNo" value="<?php echo htmlspecialchars($row['business_register_no'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. 1016088-D">
        </div>

        <div class="form-group">
            <label>Address Line 1</label>
            <input type="text" id="fAddr1" value="<?php echo htmlspecialchars($row['address_line1'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. LOT 338, JALAN PENGHULU DURIN,">
        </div>

        <div class="form-group">
            <label>Address Line 2</label>
            <input type="text" id="fAddr2" value="<?php echo htmlspecialchars($row['address_line2'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. 94000 BAU, SARAWAK.">
        </div>

        <div class="form-group">
            <label>Address Line 3</label>
            <input type="text" id="fAddr3" value="<?php echo htmlspecialchars($row['address_line3'] ?? '', ENT_QUOTES); ?>" placeholder="(optional)">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Business Tel No</label>
                <input type="text" id="fTel" value="<?php echo htmlspecialchars($row['tel_no'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. 082-764677">
            </div>
            <div class="form-group">
                <label>Business Email</label>
                <input type="email" id="fEmail" value="<?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. contact@company.com">
            </div>
        </div>

        <div class="actions">
            <button class="btn-save" onclick="saveCompany();"><i class="fas fa-save"></i> Save Changes</button>
        </div>

        <div class="preview">
            <h4>Letterhead Preview</h4>
            <div class="preview-letterhead" id="letterheadPreview"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function renderPreview() {
    var name = document.getElementById('fBusinessName').value || '';
    var reg = document.getElementById('fRegNo').value || '';
    var a1 = document.getElementById('fAddr1').value || '';
    var a2 = document.getElementById('fAddr2').value || '';
    var a3 = document.getElementById('fAddr3').value || '';
    var tel = document.getElementById('fTel').value || '';
    var email = document.getElementById('fEmail').value || '';

    var line1 = escHtml(name);
    if (reg) line1 += ' (' + escHtml(reg) + ')';

    var html = '';
    if (line1) html += '<div class="biz-name">' + line1 + '</div>';
    var addrParts = [];
    if (a1) addrParts.push(escHtml(a1));
    if (a2) addrParts.push(escHtml(a2));
    if (a3) addrParts.push(escHtml(a3));
    if (addrParts.length) html += '<div class="addr">' + addrParts.join('<br>') + '</div>';
    var contactParts = [];
    if (tel) contactParts.push('TEL NO: ' + escHtml(tel));
    if (email) contactParts.push('EMAIL: ' + escHtml(email));
    if (contactParts.length) html += '<div class="contact">' + contactParts.join('<br>') + '</div>';

    document.getElementById('letterheadPreview').innerHTML = html || '<em style="color:#9ca3af;">Fill in fields above to preview letterhead</em>';
}

['fBusinessName','fRegNo','fAddr1','fAddr2','fAddr3','fTel','fEmail'].forEach(function(id) {
    document.getElementById(id).addEventListener('input', renderPreview);
});
renderPreview();

function saveCompany() {
    var data = {
        action: 'save',
        business_name: document.getElementById('fBusinessName').value.trim(),
        business_register_no: document.getElementById('fRegNo').value.trim(),
        address_line1: document.getElementById('fAddr1').value.trim(),
        address_line2: document.getElementById('fAddr2').value.trim(),
        address_line3: document.getElementById('fAddr3').value.trim(),
        tel_no: document.getElementById('fTel').value.trim(),
        email: document.getElementById('fEmail').value.trim()
    };
    if (!data.business_name) {
        Swal.fire({ icon: 'warning', text: 'Business name is required.' });
        return;
    }
    $.ajax({
        type: 'POST', url: 'company_setting_ajax.php', data: data, dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                Swal.fire({ icon: 'success', title: 'Saved', text: resp.success, timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: resp.error || 'Failed to save.' });
            }
        },
        error: function() { Swal.fire({ icon: 'error', text: 'Network error.' }); }
    });
}
</script>
</body>
</html>
