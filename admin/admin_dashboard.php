<?php
// ============================================================
// FILE: rafiq/admin/admin_dashboard.php
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminInitials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', $adminName))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Rafiq — Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
/* ── RESET & BASE ─────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito','Segoe UI',Arial,sans-serif;background:#f5f6fb;color:#1e1b4b;min-height:100vh}
button{cursor:pointer;font-family:inherit}
input,select,textarea{font-family:inherit}
table{border-collapse:collapse;width:100%}

/* ── LAYOUT ───────────────────────────────────────────── */
.app{display:flex;min-height:100vh}

/* ── SIDEBAR ──────────────────────────────────────────── */
.sidebar{width:228px;background:linear-gradient(180deg,#1e1b4b,#312e81);display:flex;flex-direction:column;flex-shrink:0;transition:width .22s ease;overflow:hidden}
.sidebar.collapsed{width:62px}
.sidebar-logo{padding:22px 16px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.08)}
.sidebar-logo-icon{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.13);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.sidebar-logo-text{color:#fff;font-weight:900;font-size:20px;letter-spacing:-.5px;line-height:1}
.sidebar-logo-text span{color:#a5b4fc}
.sidebar-logo-sub{font-size:9px;font-weight:800;color:rgba(255,255,255,.35);letter-spacing:.12em;text-transform:uppercase;margin-top:2px}
.sidebar-nav{padding:16px 8px;flex:1}
.nav-btn{width:100%;display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:11px;border:none;background:transparent;color:rgba(255,255,255,.55);font-size:13px;font-weight:500;transition:all .14s;text-align:left;margin-bottom:3px;white-space:nowrap}
.nav-btn:hover{background:rgba(255,255,255,.08);color:rgba(255,255,255,.85)}
.nav-btn.active{background:rgba(255,255,255,.14);color:#fff;font-weight:800}
.nav-btn .nav-icon{font-size:16px;flex-shrink:0}
.sidebar-footer{padding:12px 8px;border-top:1px solid rgba(255,255,255,.07)}
.collapse-btn{width:100%;padding:8px;border-radius:9px;border:none;background:rgba(255,255,255,.07);color:rgba(255,255,255,.5);font-size:13px;font-weight:600}
.collapse-btn:hover{background:rgba(255,255,255,.13)}
.sidebar.collapsed .nav-label,.sidebar.collapsed .sidebar-logo-text,.sidebar.collapsed .sidebar-logo-sub{display:none}
.nav-badge{background:#ef4444;color:#fff;font-size:10px;font-weight:900;padding:1px 7px;border-radius:99px;margin-left:auto;line-height:1.6}
.logout-btn{width:100%;display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:11px;border:none;background:rgba(239,68,68,.13);color:rgba(255,100,100,.9);font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;text-align:left;margin-top:4px;white-space:nowrap;transition:all .14s}
.logout-btn:hover{background:rgba(239,68,68,.22);color:#fca5a5}

/* ── MAIN ─────────────────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}

/* ── TOP BAR ──────────────────────────────────────────── */
.topbar{background:#fff;border-bottom:1px solid #f1f5f9;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.topbar-title{font-size:15px;font-weight:800;color:#1e1b4b}
.topbar-date{font-size:11px;color:#94a3b8;margin-top:1px}
.topbar-right{display:flex;align-items:center;gap:10px}
.bell-btn{width:34px;height:34px;border-radius:10px;background:#f5f6fb;border:1.5px solid #f1f5f9;font-size:18px;display:flex;align-items:center;justify-content:center}
.admin-name{font-size:12px;font-weight:800;color:#1e1b4b}
.admin-role{font-size:10px;color:#94a3b8}

/* ── PAGE CONTENT ─────────────────────────────────────── */
.page-content{flex:1;overflow-y:auto;padding:28px 28px 60px}
.page{display:none}
.page.active{display:block}
.page-title{font-size:22px;font-weight:900;color:#1e1b4b;margin-bottom:4px}
.page-sub{color:#64748b;font-size:14px;margin-bottom:24px}

/* ── AVATAR ───────────────────────────────────────────── */
.avatar{border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:800;flex-shrink:0}

/* ── BADGES ───────────────────────────────────────────── */
.badge{padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:5px;white-space:nowrap}
.badge-dot{width:5px;height:5px;border-radius:50%}
.badge-pending  {background:#fffbeb;color:#92400e} .badge-pending .badge-dot  {background:#f59e0b}
.badge-accepted {background:#ecfdf5;color:#065f46} .badge-accepted .badge-dot {background:#10b981}
.badge-rejected {background:#fef2f2;color:#991b1b} .badge-rejected .badge-dot {background:#ef4444}
.badge-active   {background:#ecfdf5;color:#065f46} .badge-active .badge-dot   {background:#10b981}
.badge-hidden   {background:#f1f5f9;color:#374151} .badge-hidden .badge-dot   {background:#9ca3af}
.badge-completed{background:#eef2ff;color:#4338ca} .badge-completed .badge-dot{background:#4f46e5}
.badge-cancelled{background:#fef2f2;color:#991b1b} .badge-cancelled .badge-dot{background:#ef4444}
.badge-paid     {background:#ecfdf5;color:#065f46} .badge-paid .badge-dot     {background:#10b981}
.badge-unpaid   {background:#fffbeb;color:#92400e} .badge-unpaid .badge-dot   {background:#f59e0b}

/* ── STAT CARDS ───────────────────────────────────────── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:16px;padding:18px 20px;box-shadow:0 1px 8px rgba(30,27,75,.07);border:1px solid #f1f5f9;display:flex;align-items:center;gap:14px}
.stat-icon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.stat-value{font-size:26px;font-weight:900;line-height:1}
.stat-label{font-size:12px;color:#64748b;margin-top:3px;font-weight:600}

/* ── CARDS / PANELS ───────────────────────────────────── */
.card{background:#fff;border-radius:16px;box-shadow:0 1px 8px rgba(30,27,75,.07);border:1px solid #f1f5f9;overflow:hidden}
.card-pad{padding:20px 22px}
.card-title{font-weight:800;font-size:14px;color:#1e1b4b;margin-bottom:2px}
.card-sub{font-size:12px;color:#94a3b8;margin-bottom:14px}
.chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px}

/* ── BAR CHART ────────────────────────────────────────── */
.bar-chart{display:flex;align-items:flex-end;gap:5px;height:65px}
.bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px}
.bar-fill{width:100%;background:#4f46e5;border-radius:3px 3px 0 0;opacity:.82;transition:height .3s}
.bar-label{font-size:9px;color:#94a3b8}

/* ── DONUT ────────────────────────────────────────────── */
.donut-wrap{display:flex;align-items:center;gap:18px}
.donut-legend{display:flex;flex-direction:column;gap:8px}
.donut-row{display:flex;align-items:center;gap:8px;font-size:12px;color:#64748b}
.donut-dot{width:10px;height:10px;border-radius:2px;flex-shrink:0}
.donut-count{font-size:13px;font-weight:800;color:#1e1b4b;margin-left:auto;padding-left:10px}

/* ── RECENT BOOKINGS ──────────────────────────────────── */
.recent-row{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid #f1f5f9}
.recent-row:last-child{border-bottom:none}
.recent-info{flex:1}
.recent-name{font-size:13px;font-weight:700;color:#1e1b4b}
.recent-meta{font-size:11px;color:#94a3b8;margin-top:2px;text-transform:capitalize}
.urgent-tag{background:#fef2f2;color:#ef4444;font-size:9px;font-weight:800;padding:1px 5px;border-radius:99px;margin-left:4px}
.booking-id{font-size:11px;color:#94a3b8}

/* ── FILTER BAR ───────────────────────────────────────── */
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.filter-input{flex:1 1 200px;padding:9px 14px;border-radius:10px;border:1.5px solid #f1f5f9;font-size:13px;color:#1e1b4b;outline:none}
.filter-input:focus{border-color:#a5b4fc}
.filter-select{padding:9px 12px;border-radius:10px;border:1.5px solid #f1f5f9;font-size:13px;color:#1e1b4b;background:#fff}
.filter-select:focus{border-color:#a5b4fc;outline:none}

/* ── TABLE ────────────────────────────────────────────── */
.table-wrap{overflow-x:auto}
table thead tr{background:#f8fafc;border-bottom:1px solid #f1f5f9}
table thead th{padding:12px 16px;text-align:left;font-size:10px;font-weight:800;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}
table tbody tr{border-bottom:1px solid #f8fafc;transition:background .12s}
table tbody tr:hover{background:#fafaff}
table tbody tr:last-child{border-bottom:none}
table tbody td{padding:13px 16px;font-size:13px}
.td-name{display:flex;align-items:center;gap:10px}
.td-name-text{font-weight:700;color:#1e1b4b}
.td-name-email{font-size:11px;color:#94a3b8}
.cat-badge{background:#eef2ff;color:#4338ca;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700}
.service-badge{background:#eef2ff;color:#4338ca;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:700;text-transform:capitalize}
.td-muted{color:#64748b}
.td-bold{font-weight:800;color:#1e1b4b}
.td-actions{display:flex;gap:5px;flex-wrap:nowrap}

/* ── BUTTONS ──────────────────────────────────────────── */
.btn{padding:9px 20px;border-radius:11px;border:none;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit}
.btn-primary{background:linear-gradient(135deg,#4f46e5,#312e81);color:#fff}
.btn-primary:hover{opacity:.92}
.btn-ghost{padding:6px 12px;border-radius:8px;border:1.5px solid #f1f5f9;background:#fff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;white-space:nowrap}
.btn-ghost:hover{border-color:#a5b4fc;color:#4f46e5}
.btn-ghost-purple{padding:6px 12px;border-radius:8px;border:1.5px solid #f1f5f9;background:#fff;color:#4f46e5;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit}
.btn-green{padding:6px 10px;border-radius:8px;border:none;background:#ecfdf5;color:#065f46;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit}
.btn-green:hover{background:#d1fae5}
.btn-red{padding:6px 10px;border-radius:8px;border:none;background:#fef2f2;color:#991b1b;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit}
.btn-red:hover{background:#fee2e2}
.btn-note{padding:6px 10px;border-radius:8px;border:1.5px solid #f1f5f9;background:#fff;color:#64748b;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}

/* ── PLACE CARDS ──────────────────────────────────────── */
.places-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.place-card{background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 1px 8px rgba(30,27,75,.07);border:1px solid #f1f5f9;display:flex;flex-direction:column}
.place-card-top{background:linear-gradient(135deg,#1e1b4b,#4f46e5);padding:20px 18px 16px;position:relative}
.place-card-emoji{font-size:28px;margin-bottom:6px}
.place-card-name{font-size:15px;font-weight:800;color:#fff}
.place-card-addr{font-size:12px;color:#a5b4fc;margin-top:4px}
.place-card-status{position:absolute;top:12px;right:12px}
.place-card-body{padding:14px 18px;flex:1}
.place-features{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px}
.feature-chip{background:#eef2ff;color:#4338ca;font-size:11px;padding:2px 9px;border-radius:99px;font-weight:600}
.place-desc{font-size:12px;color:#64748b;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.place-card-footer{padding:10px 18px 14px;border-top:1px solid #f1f5f9;display:flex;gap:6px;align-items:center}
.place-status-select{flex:1;padding:5px 8px;border-radius:8px;border:1.5px solid #f1f5f9;font-size:11px;color:#64748b;background:#fff;font-family:inherit}

/* ── MODALS ───────────────────────────────────────────── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(10,8,40,.62);z-index:9000;align-items:center;justify-content:center;padding:16px}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:20px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 32px 80px rgba(30,27,75,.28)}
.modal-header{padding:20px 24px 14px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f5f9;position:sticky;top:0;background:#fff;z-index:1}
.modal-title{font-size:17px;font-weight:800;color:#1e1b4b}
.modal-close{background:#f5f6fb;border:none;border-radius:99px;width:30px;height:30px;font-size:18px;color:#64748b;display:flex;align-items:center;justify-content:center;cursor:pointer}
.modal-body{padding:20px 24px 28px}
.confirm-overlay{display:none;position:fixed;inset:0;background:rgba(10,8,40,.62);z-index:9999;align-items:center;justify-content:center}
.confirm-overlay.open{display:flex}
.confirm-box{background:#fff;border-radius:16px;width:370px;padding:28px;box-shadow:0 24px 60px rgba(30,27,75,.22)}
.confirm-title{font-size:16px;font-weight:800;color:#1e1b4b;margin-bottom:10px}
.confirm-msg{font-size:14px;color:#64748b;margin-bottom:22px;line-height:1.6}
.confirm-btns{display:flex;gap:10px;justify-content:flex-end}

/* ── DETAIL MODAL ─────────────────────────────────────── */
.detail-header{background:linear-gradient(135deg,#1e1b4b,#4f46e5);border-radius:14px;padding:18px 20px;margin-bottom:18px;display:flex;align-items:center;gap:16px}
.detail-header-info{flex:1}
.detail-header-name{font-size:19px;font-weight:900;color:#fff;margin:0}
.detail-cat-chip{background:rgba(255,255,255,.18);color:#fff;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:700;display:inline-block;margin-top:4px}
.fields-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.field-box{padding:11px 14px;background:#f5f6fb;border-radius:10px}
.field-box.wide{grid-column:1/-1}
.field-label{font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px}
.field-value{font-size:13px;color:#1e1b4b;font-weight:600;word-break:break-word}
.bookings-history{margin-bottom:14px}
.history-row{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f5f6fb;border-radius:8px;margin-bottom:5px}
.history-id{font-size:12px;font-weight:700;color:#1e1b4b}
.history-meta{font-size:11px;color:#64748b;margin-left:8px}
.history-right{display:flex;gap:6px;align-items:center}
.note-box{padding:13px 15px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:14px}
.note-label{font-size:10px;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
.note-text{font-size:13px;color:#78350f}
.detail-actions{display:flex;gap:10px;padding-top:16px;border-top:1px solid #f1f5f9}
.btn-accept-full{flex:1;padding:11px;border-radius:11px;border:none;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-weight:800;font-size:14px;cursor:pointer;font-family:inherit}
.btn-reject-full{flex:1;padding:11px;border-radius:11px;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-weight:800;font-size:14px;cursor:pointer;font-family:inherit}

/* ── FORM ─────────────────────────────────────────────── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group.full{grid-column:1/-1}
.form-label{font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em}
.form-input{padding:9px 12px;border-radius:10px;border:1.5px solid #f1f5f9;font-size:13px;color:#1e1b4b;outline:none;font-family:inherit}
.form-input:focus{border-color:#a5b4fc}
.form-textarea{padding:9px 12px;border-radius:10px;border:1.5px solid #f1f5f9;font-size:13px;color:#1e1b4b;outline:none;resize:vertical;font-family:inherit}
.form-textarea:focus{border-color:#a5b4fc}
.feature-toggle{padding:7px 14px;border-radius:99px;font-size:12px;font-weight:700;border:none;background:#f5f6fb;color:#64748b;cursor:pointer;font-family:inherit;transition:all .15s}
.feature-toggle.on{background:#4f46e5;color:#fff}
.form-footer{display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #f1f5f9;padding-top:16px;margin-top:4px}

/* ── PENDING REQUESTS PANEL ───────────────────────────── */
.pending-panel{background:#fff;border-radius:18px;box-shadow:0 1px 8px rgba(30,27,75,.07);border:2px solid #fde68a;overflow:hidden;margin-bottom:22px}
.pending-panel-header{background:linear-gradient(135deg,#fffbeb,#fef3c7);padding:16px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid #fde68a}
.pending-panel-title{font-size:15px;font-weight:900;color:#92400e;display:flex;align-items:center;gap:10px}
.pending-count-badge{background:#f59e0b;color:#fff;font-size:12px;font-weight:900;padding:3px 10px;border-radius:99px;line-height:1.5}
.pending-panel-sub{font-size:12px;color:#b45309}
.pending-view-all{padding:7px 14px;border-radius:9px;border:1.5px solid #f59e0b;background:transparent;color:#92400e;font-size:12px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .15s}
.pending-view-all:hover{background:#fef3c7}
.pending-row{display:flex;align-items:center;gap:14px;padding:14px 22px;border-bottom:1px solid #f8fafc;transition:background .12s}
.pending-row:last-child{border-bottom:none}
.pending-row:hover{background:#fffbeb}
.pending-row-info{flex:1;min-width:0}
.pending-row-name{font-size:14px;font-weight:800;color:#1e1b4b}
.pending-row-meta{font-size:12px;color:#94a3b8;margin-top:2px}
.pending-row-actions{display:flex;gap:6px;flex-shrink:0}
.pending-empty{padding:32px;text-align:center;color:#a3a3a3;font-size:14px}

/* ── SPINNER ──────────────────────────────────────────── */
.spinner-wrap{display:flex;justify-content:center;align-items:center;padding:56px}
.spinner{width:34px;height:34px;border:3px solid #eef2ff;border-top-color:#4f46e5;border-radius:50%;animation:spin .65s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.empty-state{padding:48px 24px;text-align:center;color:#94a3b8;font-size:14px}

/* ── TOAST ────────────────────────────────────────────── */
.toast{position:fixed;bottom:24px;right:24px;background:#1e1b4b;color:#fff;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:700;z-index:99999;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}

@media(max-width:768px){
  .chart-grid{grid-template-columns:1fr}
  .stat-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}
  .fields-grid,.form-grid{grid-template-columns:1fr}
  .page-content{padding:16px}
}
</style>
</head>
<body>

<div class="app">

  <!-- ══════════════════════════════════════════════════
       SIDEBAR
  ══════════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">♿</div>
      <div>
        <div class="sidebar-logo-text">Rafi<span>Q</span></div>
        <div class="sidebar-logo-sub">Admin Panel</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <button class="nav-btn active" onclick="showPage('overview',this)">
        <span class="nav-icon">▣</span><span class="nav-label">Dashboard</span>
      </button>
      <button class="nav-btn" onclick="showPage('providers',this)" id="navProviders">
        <span class="nav-icon">👤</span><span class="nav-label">Providers</span>
        <span class="nav-badge" id="pendingBadge" style="display:none"></span>
      </button>
      <button class="nav-btn" onclick="showPage('patients',this)">
        <span class="nav-icon">🧑‍🦽</span><span class="nav-label">Patients</span>
      </button>
      <button class="nav-btn" onclick="showPage('places',this)">
        <span class="nav-icon">📍</span><span class="nav-label">Places</span>
      </button>
      <button class="nav-btn" onclick="showPage('bookings',this)">
        <span class="nav-icon">📋</span><span class="nav-label">Bookings</span>
      </button>
    </nav>
    <div class="sidebar-footer">
      <button class="logout-btn" onclick="window.location.href='admin_logout.php'">
        <span class="nav-icon">🚪</span><span class="nav-label">Logout</span>
      </button>
      <button class="collapse-btn" onclick="toggleSidebar()" id="collapseBtn" style="margin-top:6px">← Collapse</button>
    </div>
  </aside>

  <!-- ══════════════════════════════════════════════════
       MAIN
  ══════════════════════════════════════════════════ -->
  <div class="main">

    <!-- Top Bar -->
    <header class="topbar">
      <div>
        <div class="topbar-title" id="pageTitle">Dashboard Overview</div>
        <div class="topbar-date" id="pageDate"></div>
      </div>
      <div class="topbar-right">
        <div style="position:relative;cursor:pointer" onclick="showPage('providers',document.getElementById('navProviders'))" title="Pending provider requests">
          <div class="bell-btn">🔔</div>
          <span id="bellBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:9px;font-weight:900;padding:1px 5px;border-radius:99px;line-height:1.6"></span>
        </div>
        <div class="avatar" style="width:36px;height:36px;font-size:13px;font-weight:800;background:#4f46e5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff"><?= $adminInitials ?></div>
        <div>
          <div class="admin-name"><?= htmlspecialchars($adminName) ?></div>
          <div class="admin-role">Super Admin</div>
        </div>
      </div>
    </header>

    <!-- Pages -->
    <div class="page-content">

      <!-- ════════════════════════════════════════════
           PAGE: OVERVIEW
      ════════════════════════════════════════════ -->
      <div class="page active" id="page-overview">
        <div class="page-title">Dashboard Overview</div>
        <div class="page-sub">Here's what's happening on Rafiq right now.</div>

        <!-- Stat Cards -->
        <div class="stat-grid" id="statGrid">
          <div class="spinner-wrap"><div class="spinner"></div></div>
        </div>

        <!-- Pending Requests Panel -->
        <div class="pending-panel" id="pendingPanel" style="display:none">
          <div class="pending-panel-header">
            <div>
              <div class="pending-panel-title">
                ⏳ Pending Provider Requests
                <span class="pending-count-badge" id="pendingCountBadge">0</span>
              </div>
              <div class="pending-panel-sub">These providers are waiting for your approval to join Rafiq.</div>
            </div>
            <button class="pending-view-all" onclick="showPage('providers', document.getElementById('navProviders'))">View All Providers →</button>
          </div>
          <div id="pendingRows"></div>
        </div>

        <!-- Charts -->
        <div class="chart-grid">
          <div class="card card-pad">
            <div class="card-title">Monthly Bookings</div>
            <div class="card-sub">Last 6 months</div>
            <div class="bar-chart" id="barChart"></div>
          </div>
          <div class="card card-pad">
            <div class="card-title">Services Breakdown</div>
            <div class="card-sub">By booking type</div>
            <div class="donut-wrap">
              <svg id="donutSvg" viewBox="0 0 100 100" width="95" height="95" style="flex-shrink:0"></svg>
              <div class="donut-legend" id="donutLegend"></div>
            </div>
          </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card card-pad">
          <div class="card-title" style="margin-bottom:16px">Recent Bookings</div>
          <div id="recentList"></div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════
           PAGE: PROVIDERS
      ════════════════════════════════════════════ -->
      <div class="page" id="page-providers">
        <div class="page-title">Provider Management</div>
        <div class="page-sub">Review, accept, or reject service provider applications.</div>

        <div class="filter-bar">
          <input class="filter-input" id="provSearch" placeholder="Search name or email…" oninput="loadProviders()"/>
          <select class="filter-select" id="provStatus" onchange="loadProviders()">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
          </select>
          <select class="filter-select" id="provCat" onchange="loadProviders()">
            <option value="all">All Categories</option>
            <option value="driver">Driver</option>
            <option value="doctor">Doctor</option>
            <option value="caregiver">Caregiver</option>
            <option value="interpreter">Interpreter</option>
          </select>
        </div>

        <div class="card">
          <div class="table-wrap">
            <table>
              <thead><tr>
                <th>Provider</th><th>Category</th><th>Location</th>
                <th>Bookings</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="provTable"><tr><td colspan="6"><div class="spinner-wrap"><div class="spinner"></div></div></td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════
           PAGE: PLACES
      ════════════════════════════════════════════ -->
      <div class="page" id="page-places">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
          <div>
            <div class="page-title">Accessible Places</div>
            <div class="page-sub" style="margin-bottom:0">Manage all accessibility-verified locations.</div>
          </div>
          <button class="btn btn-primary" onclick="openAddPlace()">+ Add Place</button>
        </div>

        <div class="filter-bar">
          <input class="filter-input" id="placeSearch" placeholder="Search places…" oninput="loadPlaces()"/>
          <select class="filter-select" id="placeType" onchange="loadPlaces()">
            <option value="all">All Types</option>
            <option>Hospital</option><option>Clinic</option><option>Mall</option>
            <option>Park</option><option>Museum</option><option>Restaurant</option>
            <option>Hotel</option><option>Mosque</option><option>Church</option>
            <option>Pharmacy</option><option>School</option><option>University</option>
            <option>Government Office</option><option>Other</option>
          </select>
          <select class="filter-select" id="placeStatus" onchange="loadPlaces()">
            <option value="all">All Statuses</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="hidden">Hidden</option>
          </select>
        </div>

        <div class="places-grid" id="placesGrid">
          <div class="spinner-wrap"><div class="spinner"></div></div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════
           PAGE: BOOKINGS
      ════════════════════════════════════════════ -->
      <div class="page" id="page-bookings">
        <div class="page-title">Bookings</div>
        <div class="page-sub">All patient–provider booking transactions.</div>

        <div class="filter-bar">
          <input class="filter-input" id="bookSearch" placeholder="Search patient or provider…" oninput="loadBookings()"/>
          <select class="filter-select" id="bookStatus" onchange="loadBookings()">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <select class="filter-select" id="bookService" onchange="loadBookings()">
            <option value="all">All Services</option>
            <option value="caregiver">Caregiver</option>
            <option value="driver">Driver</option>
            <option value="doctor">Doctor</option>
            <option value="interpreter">Interpreter</option>
          </select>
        </div>

        <div class="card">
          <div class="table-wrap">
            <table>
              <thead><tr>
                <th>#</th><th>Patient</th><th>Provider</th><th>Service</th>
                <th>Date</th><th>Amount</th><th>Payment</th><th>Rating</th><th>Status</th>
              </tr></thead>
              <tbody id="bookTable"><tr><td colspan="9"><div class="spinner-wrap"><div class="spinner"></div></div></td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════
           PAGE: PATIENTS
      ════════════════════════════════════════════ -->
      <div class="page" id="page-patients">
        <div class="page-title">Patients</div>
        <div class="page-sub">All registered patients on the Rafiq platform.</div>

        <div class="filter-bar">
          <input class="filter-input" id="patSearch" placeholder="Search name or email…" oninput="loadPatients()"/>
        </div>

        <div class="card">
          <div class="table-wrap">
            <table>
              <thead><tr>
                <th>Patient</th><th>Phone</th><th>Gender</th><th>Disability</th><th>Address</th><th>Bookings</th>
              </tr></thead>
              <tbody id="patTable"><tr><td colspan="6"><div class="spinner-wrap"><div class="spinner"></div></div></td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- end page-content -->
  </div><!-- end main -->
</div><!-- end app -->


<!-- ══════════════════════════════════════════════════════════
     MODALS
══════════════════════════════════════════════════════════ -->

<!-- Provider Detail Modal -->
<div class="modal-overlay" id="modalDetail">
  <div class="modal-box" style="max-width:740px">
    <div class="modal-header">
      <span class="modal-title">Provider Details</span>
      <button class="modal-close" onclick="closeModal('modalDetail')">×</button>
    </div>
    <div class="modal-body" id="modalDetailBody"></div>
  </div>
</div>

<!-- Note Modal -->
<div class="modal-overlay" id="modalNote">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header">
      <span class="modal-title" id="noteModalTitle">Add Note</span>
      <button class="modal-close" onclick="closeModal('modalNote')">×</button>
    </div>
    <div class="modal-body">
      <textarea class="form-textarea" id="noteText" rows="5" style="width:100%" placeholder="Write a note about this provider…"></textarea>
      <div class="form-footer" style="margin-top:14px;padding-top:14px">
        <button class="btn-ghost" onclick="closeModal('modalNote')">Cancel</button>
        <button class="btn btn-primary" onclick="saveNote()">Save Note</button>
      </div>
    </div>
  </div>
</div>

<!-- Place Form Modal -->
<div class="modal-overlay" id="modalPlace">
  <div class="modal-box" style="max-width:660px">
    <div class="modal-header">
      <span class="modal-title" id="placeModalTitle">Add New Place</span>
      <button class="modal-close" onclick="closeModal('modalPlace')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="placeEditId"/>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Place Name *</label>
          <input class="form-input" id="fName" placeholder="e.g. Cairo Festival City"/>
        </div>
        <div class="form-group">
          <label class="form-label">Type *</label>
          <select class="form-input" id="fType">
            <option value="">Select type…</option>
            <option>Hospital</option><option>Clinic</option><option>Mall</option>
            <option>Park</option><option>Museum</option><option>Restaurant</option>
            <option>Hotel</option><option>Mosque</option><option>Church</option>
            <option>Pharmacy</option><option>School</option><option>University</option>
            <option>Government Office</option><option>Other</option>
          </select>
        </div>
        <div class="form-group full">
          <label class="form-label">Address *</label>
          <input class="form-input" id="fAddress" placeholder="Full address"/>
        </div>
        <div class="form-group">
          <label class="form-label">Latitude</label>
          <input class="form-input" id="fLat" placeholder="e.g. 30.0444"/>
        </div>
        <div class="form-group">
          <label class="form-label">Longitude</label>
          <input class="form-input" id="fLng" placeholder="e.g. 31.2357"/>
        </div>
        <div class="form-group full">
          <label class="form-label">Accessibility Features</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:2px">
            <button type="button" class="feature-toggle" id="ft-elevator" onclick="toggleFeature('elevator')">Elevator</button>
            <button type="button" class="feature-toggle" id="ft-ramp"     onclick="toggleFeature('ramp')">Wheelchair Ramp</button>
            <button type="button" class="feature-toggle" id="ft-toilet"   onclick="toggleFeature('toilet')">Accessible Restroom</button>
            <button type="button" class="feature-toggle" id="ft-parking"  onclick="toggleFeature('parking')">Disabled Parking</button>
          </div>
        </div>
        <div class="form-group full">
          <label class="form-label">Description</label>
          <textarea class="form-textarea" id="fComment" rows="3" placeholder="Describe the accessibility setup…"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Photo URL</label>
          <input class="form-input" id="fPhoto" placeholder="https://…"/>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-input" id="fStatus">
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="hidden">Hidden</option>
          </select>
        </div>
      </div>
      <div class="form-footer">
        <button class="btn-ghost" onclick="closeModal('modalPlace')">Cancel</button>
        <button class="btn btn-primary" onclick="savePlace()">Save Place</button>
      </div>
    </div>
  </div>
</div>

<!-- Place View Modal -->
<div class="modal-overlay" id="modalPlaceView">
  <div class="modal-box" style="max-width:520px">
    <div class="modal-header">
      <span class="modal-title">Place Details</span>
      <button class="modal-close" onclick="closeModal('modalPlaceView')">×</button>
    </div>
    <div class="modal-body" id="modalPlaceViewBody"></div>
  </div>
</div>

<!-- Confirm Modal -->
<div class="confirm-overlay" id="confirmModal">
  <div class="confirm-box">
    <div class="confirm-title" id="confirmTitle">Are you sure?</div>
    <div class="confirm-msg"   id="confirmMsg"></div>
    <div class="confirm-btns">
      <button class="btn-ghost" onclick="closeConfirm()">Cancel</button>
      <button class="btn" id="confirmYesBtn" onclick="confirmYes()">Confirm</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>


<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script>
// ── CONFIG ─────────────────────────────────────────────────
const API = 'admin_api.php'; // same folder — change if needed

// ── STATE ──────────────────────────────────────────────────
let currentNoteProviderId = null;
let confirmCallback = null;
let placeFeatures = { elevator:false, ramp:false, toilet:false, parking:false };
let sidebarOpen = true;

// ── INIT ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('pageDate').textContent =
    new Date().toLocaleDateString('en-EG', {weekday:'long',year:'numeric',month:'long',day:'numeric'});
  loadOverview();
});

// ── SIDEBAR ────────────────────────────────────────────────
function toggleSidebar() {
  sidebarOpen = !sidebarOpen;
  document.getElementById('sidebar').classList.toggle('collapsed', !sidebarOpen);
  document.getElementById('collapseBtn').textContent = sidebarOpen ? '← Collapse' : '→';
}

// ── PAGE ROUTING ───────────────────────────────────────────
const pageTitles = { overview:'Dashboard Overview', providers:'Provider Management', patients:'Patients', places:'Accessible Places', bookings:'Bookings' };

function showPage(id, btn) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('page-' + id).classList.add('active');
  btn.classList.add('active');
  document.getElementById('pageTitle').textContent = pageTitles[id];
  if (id === 'providers') loadProviders();
  if (id === 'patients')  loadPatients();
  if (id === 'places')    loadPlaces();
  if (id === 'bookings')  loadBookings();
}

// ── API HELPER ─────────────────────────────────────────────
async function apiFetch(action, opts = {}) {
  const url = `${API}?action=${action}` + (opts.id ? `&id=${opts.id}` : '') + (opts.qs ? `&${opts.qs}` : '');
  const res = await fetch(url, {
    method:  opts.method || 'GET',
    headers: { 'Content-Type': 'application/json' },
    body:    opts.body   ? JSON.stringify(opts.body) : undefined,
  });
  if (!res.ok) throw new Error('HTTP ' + res.status);
  return res.json();
}

// ── HELPERS ────────────────────────────────────────────────
function badge(status) {
  const s = (status || 'pending').toLowerCase();
  return `<span class="badge badge-${s}"><span class="badge-dot"></span>${cap(status)}</span>`;
}
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—'; }
function avatar(name, size = 34) {
  const letters = (name || '?').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
  const colors = ['#4f46e5','#7c3aed','#0891b2','#10b981','#d97706'];
  const bg = colors[(name || '?').charCodeAt(0) % colors.length];
  return `<div class="avatar" style="width:${size}px;height:${size}px;font-size:${size*.36}px;background:${bg}">${letters}</div>`;
}
function stars(r) { return r ? '★'.repeat(parseInt(r)) : '—'; }
function catIcon(c) { return {Driver:'🚗',Doctor:'🩺',Caregiver:'🤝',Interpreter:'🤟'}[c] || '👤'; }
function placeEmoji(t) { return {Hospital:'🏥',Clinic:'🩺',Mall:'🛍️',Park:'🌳',Museum:'🏛️',Restaurant:'🍽️',Hotel:'🏨',Mosque:'🕌',Church:'⛪',Pharmacy:'💊',School:'🏫',University:'🎓','Government Office':'🏢',Other:'📍'}[t] || '📍'; }
function featureChips(p) {
  const f = [];
  if (p.elevator) f.push('Elevator');
  if (p.ramp)     f.push('Wheelchair Ramp');
  if (p.toilet)   f.push('Accessible Restroom');
  if (p.parking)  f.push('Disabled Parking');
  return f;
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

// ── MODAL HELPERS ──────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openConfirm(title, msg, danger, cb) {
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmMsg').textContent   = msg;
  const btn = document.getElementById('confirmYesBtn');
  btn.textContent = danger ? 'Yes, Confirm' : 'Yes';
  btn.style.background = danger ? '#ef4444' : '#10b981';
  confirmCallback = cb;
  document.getElementById('confirmModal').classList.add('open');
}
function closeConfirm() { document.getElementById('confirmModal').classList.remove('open'); confirmCallback = null; }
function confirmYes()   { if (confirmCallback) confirmCallback(); closeConfirm(); }

// ════════════════════════════════════════════════════════════
//  OVERVIEW
// ════════════════════════════════════════════════════════════
async function loadOverview() {
  try {
    const s = await apiFetch('stats');
    renderStats(s);
    renderBarChart(s.monthly || []);
    renderDonut(s.services  || []);
    renderRecent(s.recent   || []);
    // pending badge on sidebar + bell
    const pending = s.pendingProviders || 0;
    const badge = document.getElementById('pendingBadge');
    const bell  = document.getElementById('bellBadge');
    if (pending > 0) {
      badge.textContent = pending; badge.style.display = 'inline-block';
      bell.textContent  = pending; bell.style.display  = 'inline-block';
    } else {
      badge.style.display = 'none';
      bell.style.display  = 'none';
    }
    // load pending panel
    await renderPendingPanel(pending);
  } catch(e) { console.error(e); }
}

async function renderPendingPanel(pendingCount) {
  const panel = document.getElementById('pendingPanel');
  const rows  = document.getElementById('pendingRows');
  const badge = document.getElementById('pendingCountBadge');
  if (!pendingCount) { panel.style.display = 'none'; return; }
  panel.style.display = 'block';
  badge.textContent = pendingCount;
  rows.innerHTML = '<div class="spinner-wrap"><div class="spinner"></div></div>';
  try {
    const list = await apiFetch('providers&status=pending&category=all&search=');
    if (!list.length) { rows.innerHTML = '<div class="pending-empty">✅ No pending requests right now.</div>'; return; }
    // show up to 5 in the panel
    const shown = list.slice(0, 5);
    rows.innerHTML = shown.map(p => {
      const name = (p.first_name || '') + ' ' + (p.last_name || '');
      const cat  = p.category || '';
      return `
      <div class="pending-row">
        ${avatar(name, 38)}
        <div class="pending-row-info">
          <div class="pending-row-name">${name.trim() || '—'}</div>
          <div class="pending-row-meta">${cat} · ${p.email || ''}</div>
        </div>
        <div class="pending-row-actions">
          <button class="btn-green" onclick="confirmStatusChange(${p.user_id},'${(name.trim()).replace(/'/g,"\\'")}','accepted')">✓ Accept</button>
          <button class="btn-red"   onclick="confirmStatusChange(${p.user_id},'${(name.trim()).replace(/'/g,"\\'")}','rejected')">✕ Reject</button>
          <button class="btn-note"  onclick="openProviderDetail(${p.user_id})">View</button>
        </div>
      </div>`;
    }).join('');
    if (list.length > 5) {
      rows.innerHTML += `<div style="padding:12px 22px;font-size:13px;color:#94a3b8;border-top:1px solid #f8fafc">
        +${list.length - 5} more — <a href="#" onclick="showPage('providers',document.getElementById('navProviders'));return false" style="color:#f59e0b;font-weight:800">View all pending</a>
      </div>`;
    }
  } catch(e) { rows.innerHTML = '<div class="pending-empty">Failed to load pending requests.</div>'; }
}

function renderStats(s) {
  const cards = [
    { label:'Total Providers',   value:s.totalProviders,    icon:'👤', color:'#4f46e5', bg:'#eef2ff' },
    { label:'Pending Providers', value:s.pendingProviders,  icon:'⏳', color:'#f59e0b', bg:'#fffbeb' },
    { label:'Accepted',          value:s.acceptedProviders, icon:'✅', color:'#10b981', bg:'#ecfdf5' },
    { label:'Rejected',          value:s.rejectedProviders, icon:'❌', color:'#ef4444', bg:'#fef2f2' },
    { label:'Total Places',      value:s.totalPlaces,       icon:'📍', color:'#d97706', bg:'#fffbeb' },
    { label:'Active Places',     value:s.activePlaces,      icon:'🟢', color:'#10b981', bg:'#ecfdf5' },
    { label:'Total Patients',    value:s.totalPatients,     icon:'🧑‍🦽', color:'#0891b2', bg:'#ecfeff' },
    { label:'Total Bookings',    value:s.totalBookings,     icon:'📋', color:'#1e1b4b', bg:'#eff0fa' },
  ];
  document.getElementById('statGrid').innerHTML = cards.map(c => `
    <div class="stat-card">
      <div class="stat-icon" style="background:${c.bg}">${c.icon}</div>
      <div>
        <div class="stat-value" style="color:${c.color}">${c.value ?? 0}</div>
        <div class="stat-label">${c.label}</div>
      </div>
    </div>`).join('');
}

function renderBarChart(data) {
  if (!data.length) { document.getElementById('barChart').innerHTML = '<div class="empty-state">No data yet</div>'; return; }
  const max = Math.max(...data.map(d => parseInt(d.count) || 0), 1);
  document.getElementById('barChart').innerHTML = data.map(d => {
    const h = Math.round(((parseInt(d.count)||0) / max) * 52);
    return `<div class="bar-col"><div class="bar-fill" style="height:${h}px"></div><span class="bar-label">${d.month}</span></div>`;
  }).join('');
}

function renderDonut(services) {
  const colors = { caregiver:'#4f46e5', driver:'#0891b2', doctor:'#10b981', interpreter:'#f59e0b' };
  const total  = services.reduce((s, x) => s + parseInt(x.count), 0) || 1;
  const r = 40, cx = 50, cy = 50, sw = 14;
  const circ = 2 * Math.PI * r;
  let offset = 0, paths = '';
  const legend = services.map(s => {
    const color = colors[s.service_type] || '#94a3b8';
    const v     = parseInt(s.count);
    const dash  = (v / total) * circ;
    paths += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${color}" stroke-width="${sw}" stroke-dasharray="${dash} ${circ-dash}" stroke-dashoffset="${-offset + circ*0.25}"/>`;
    offset += dash;
    return `<div class="donut-row"><div class="donut-dot" style="background:${color}"></div><span style="text-transform:capitalize">${s.service_type}</span><span class="donut-count">${v}</span></div>`;
  });
  document.getElementById('donutSvg').innerHTML = paths +
    `<text x="50" y="47" text-anchor="middle" style="font-size:12px;font-weight:700;fill:#1e1b4b">${total}</text>
     <text x="50" y="57" text-anchor="middle" style="font-size:7px;fill:#94a3b8">total</text>`;
  document.getElementById('donutLegend').innerHTML = legend.join('');
}

function renderRecent(list) {
  if (!list.length) { document.getElementById('recentList').innerHTML = '<div class="empty-state">🔍 No bookings yet</div>'; return; }
  document.getElementById('recentList').innerHTML = list.map(b => `
    <div class="recent-row">
      ${avatar(b.patient_name || '?', 34)}
      <div class="recent-info">
        <div class="recent-name">${b.patient_name||'—'} → ${b.provider_name||'—'}
          ${b.is_urgent ? '<span class="urgent-tag">URGENT</span>' : ''}
        </div>
        <div class="recent-meta">${b.service_type||''} · ${b.date||''}</div>
      </div>
      ${badge(b.status)}
      <span class="booking-id">#${b.booking_id}</span>
    </div>`).join('');
}

// ════════════════════════════════════════════════════════════
//  PROVIDERS
// ════════════════════════════════════════════════════════════
let debounceTimer;
function loadProviders() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(_loadProviders, 300);
}

async function _loadProviders() {
  document.getElementById('provTable').innerHTML = '<tr><td colspan="6"><div class="spinner-wrap"><div class="spinner"></div></div></td></tr>';
  const search   = document.getElementById('provSearch').value;
  const status   = document.getElementById('provStatus').value;
  const category = document.getElementById('provCat').value;
  const qs = new URLSearchParams({ search, status, category }).toString();
  try {
    const list = await apiFetch('providers&' + qs);
    if (!list.length) { document.getElementById('provTable').innerHTML = '<tr><td colspan="6"><div class="empty-state">🔍 No providers found</div></td></tr>'; return; }
    document.getElementById('provTable').innerHTML = list.map(p => `
      <tr>
        <td><div class="td-name">
          ${avatar(`${p.first_name} ${p.last_name}`, 34)}
          <div><div class="td-name-text">${p.first_name} ${p.last_name}</div><div class="td-name-email">${p.email}</div></div>
        </div></td>
        <td><span class="cat-badge">${catIcon(p.category)} ${p.category}</span></td>
        <td class="td-muted" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.address||'—'}</td>
        <td class="td-bold" style="text-align:center">${p.total_bookings||0}</td>
        <td>${badge(p.status||'pending')}</td>
        <td><div class="td-actions">
          <button class="btn-ghost-purple" onclick="openProviderDetail(${p.user_id})">View</button>
          ${p.status !== 'accepted' ? `<button class="btn-green" onclick="confirmStatusChange(${p.user_id},'${p.first_name} ${p.last_name}','accepted')">✓</button>` : ''}
          ${p.status !== 'rejected' ? `<button class="btn-red"   onclick="confirmStatusChange(${p.user_id},'${p.first_name} ${p.last_name}','rejected')">✗</button>` : ''}
          <button class="btn-note" onclick="openNote(${p.user_id},'${p.first_name} ${p.last_name}','${(p.admin_note||'').replace(/'/g,"\\'")}')">📝</button>
        </div></td>
      </tr>`).join('');
  } catch(e) { console.error(e); }
}

async function openProviderDetail(id) {
  document.getElementById('modalDetailBody').innerHTML = '<div class="spinner-wrap"><div class="spinner"></div></div>';
  openModal('modalDetail');
  try {
    const p = await apiFetch('provider_detail', { id });
    let extras = '';
    if (p.category === 'Driver') extras = `
      <div class="fields-grid">
        <div class="field-box"><div class="field-label">Driving License</div><div class="field-value">${p.driving_license||'—'}</div></div>
        <div class="field-box"><div class="field-label">Car</div><div class="field-value">${p.car_make||''} ${p.car_model||'—'}</div></div>
        <div class="field-box"><div class="field-label">Plate Number</div><div class="field-value">${p.license_plate||'—'}</div></div>
        <div class="field-box"><div class="field-label">Wheelchair Van</div><div class="field-value">${p.wheelchair_accessible=='t'||p.wheelchair_accessible==true ? 'Yes ✅':'No'}</div></div>
        <div class="field-box"><div class="field-label">Total Trips</div><div class="field-value">${p.total_trips||0}</div></div>
        <div class="field-box"><div class="field-label">Balance</div><div class="field-value">EGP ${p.available_balance||0}</div></div>
      </div>`;
    if (p.category === 'Doctor') extras = `
      <div class="fields-grid">
        <div class="field-box"><div class="field-label">Medical License</div><div class="field-value">${p.medical_license||'—'}</div></div>
        <div class="field-box"><div class="field-label">Speciality</div><div class="field-value">${p.speciality||'—'}</div></div>
      </div>`;
    if (p.category === 'Caregiver') extras = `
      <div class="field-box" style="margin-bottom:14px"><div class="field-label">Shift Preference</div><div class="field-value">${p.shift_preference||'—'}</div></div>`;
    if (p.category === 'Interpreter') extras = `
      <div class="field-box" style="margin-bottom:14px"><div class="field-label">Languages</div><div class="field-value">${p.languages||'—'}</div></div>`;

    const bookingsHtml = (p.bookings||[]).slice(0,6).map(b => `
      <div class="history-row">
        <div><span class="history-id">#${b.booking_id}</span><span class="history-meta">${b.patient_name||'—'} · ${b.date||''}</span></div>
        <div class="history-right">${badge(b.status)}${b.payment_total ? `<span style="font-size:11px;color:#64748b">EGP ${b.payment_total}</span>`:''}</div>
      </div>`).join('');

    document.getElementById('modalDetailBody').innerHTML = `
      <div class="detail-header">
        ${avatar(`${p.first_name} ${p.last_name}`, 54)}
        <div class="detail-header-info">
          <h3 class="detail-header-name">${p.first_name} ${p.last_name}</h3>
          <span class="detail-cat-chip">${catIcon(p.category)} ${p.category}</span>
        </div>
        ${badge(p.status||'pending')}
      </div>
      <div class="fields-grid">
        <div class="field-box"><div class="field-label">Email</div><div class="field-value">${p.email||'—'}</div></div>
        <div class="field-box"><div class="field-label">Phone</div><div class="field-value">${(p.phone||'').trim()||'—'}</div></div>
        <div class="field-box"><div class="field-label">Address</div><div class="field-value">${p.address||'—'}</div></div>
        <div class="field-box"><div class="field-label">Gender</div><div class="field-value">${p.gender||'—'}</div></div>
        <div class="field-box"><div class="field-label">Date of Birth</div><div class="field-value">${p.dob||'—'}</div></div>
        <div class="field-box"><div class="field-label">National ID</div><div class="field-value">${p.national_id||'—'}</div></div>
      </div>
      ${extras}
      ${p.cv ? `<div class="field-box" style="margin-bottom:14px"><div class="field-label">CV / Description</div><div class="field-value" style="white-space:pre-wrap;font-weight:400;color:#475569">${p.cv}</div></div>` : ''}
      ${p.admin_note ? `<div class="note-box"><div class="note-label">Admin Note</div><div class="note-text">${p.admin_note}</div></div>` : ''}
      ${p.bookings?.length ? `<div class="bookings-history"><div class="field-label" style="margin-bottom:8px">Booking History (${p.bookings.length})</div>${bookingsHtml}</div>` : ''}
      <div class="detail-actions">
        ${p.status !== 'accepted' ? `<button class="btn-accept-full" onclick="changeStatus(${p.user_id},'accepted')">✓ Accept Provider</button>` : ''}
        ${p.status !== 'rejected' ? `<button class="btn-reject-full" onclick="changeStatus(${p.user_id},'rejected')">✗ Reject Provider</button>` : ''}
      </div>`;
  } catch(e) { document.getElementById('modalDetailBody').innerHTML = '<div class="empty-state">Failed to load provider details.</div>'; }
}

function confirmStatusChange(id, name, newStatus) {
  const isDanger = newStatus === 'rejected';
  openConfirm(
    isDanger ? 'Reject Provider?' : 'Accept Provider?',
    `Are you sure you want to ${newStatus} ${name}?`,
    isDanger,
    () => changeStatus(id, newStatus)
  );
}

async function changeStatus(id, status) {
  try {
    await apiFetch('update_provider_status', { id, method:'PATCH', body:{ status, note:'' } });
    showToast(`Provider ${status} successfully`);
    loadProviders();
    loadOverview();
    closeModal('modalDetail');
  } catch(e) { showToast('Error: ' + e.message); }
}

function openNote(id, name, existingNote) {
  currentNoteProviderId = id;
  document.getElementById('noteModalTitle').textContent = `Add Note — ${name}`;
  document.getElementById('noteText').value = existingNote;
  openModal('modalNote');
}

async function saveNote() {
  const note = document.getElementById('noteText').value.trim();
  if (!note) return;
  try {
    await apiFetch('update_provider_status', { id:currentNoteProviderId, method:'PATCH', body:{ status:'pending', note } });
    showToast('Note saved!');
    closeModal('modalNote');
    loadProviders();
  } catch(e) { showToast('Error: ' + e.message); }
}

// ════════════════════════════════════════════════════════════
//  PLACES
// ════════════════════════════════════════════════════════════
async function loadPlaces() {
  document.getElementById('placesGrid').innerHTML = '<div class="spinner-wrap"><div class="spinner"></div></div>';
  const search = document.getElementById('placeSearch').value;
  const type   = document.getElementById('placeType').value;
  const status = document.getElementById('placeStatus').value;
  const qs = new URLSearchParams({ search, type, status }).toString();
  try {
    const list = await apiFetch('places&' + qs);
    if (!list.length) { document.getElementById('placesGrid').innerHTML = '<div class="empty-state">🔍 No places found</div>'; return; }
    document.getElementById('placesGrid').innerHTML = list.map(pl => {
      const chips = featureChips(pl);
      return `
        <div class="place-card">
          <div class="place-card-top">
            <div class="place-card-emoji">${placeEmoji(pl.type)}</div>
            <div class="place-card-name">${pl.name}</div>
            <div class="place-card-addr">${pl.address}</div>
            <div class="place-card-status">${badge(pl.status||'active')}</div>
          </div>
          <div class="place-card-body">
            <div class="place-features">
              ${chips.length ? chips.map(c=>`<span class="feature-chip">${c}</span>`).join('') : '<span style="font-size:12px;color:#94a3b8">No features recorded</span>'}
            </div>
            ${pl.comment ? `<div class="place-desc">${pl.comment}</div>` : ''}
          </div>
          <div class="place-card-footer">
            <button class="btn-ghost-purple" onclick="openPlaceView(${pl.place_id})">View</button>
            <button class="btn-ghost" onclick="openEditPlace(${pl.place_id})" style="color:#312e81">Edit</button>
            <select class="place-status-select" onchange="changePlaceStatus(${pl.place_id},this.value)">
              <option value="active"   ${pl.status==='active'  ?'selected':''}>Active</option>
              <option value="pending"  ${pl.status==='pending' ?'selected':''}>Pending</option>
              <option value="hidden"   ${pl.status==='hidden'  ?'selected':''}>Hidden</option>
            </select>
            <button class="btn-red" onclick="confirmDeletePlace(${pl.place_id})">🗑</button>
          </div>
        </div>`;
    }).join('');
  } catch(e) { console.error(e); }
}

// place data cache for edit
let placesCache = {};

async function openPlaceView(id) {
  openModal('modalPlaceView');
  try {
    const list = await apiFetch('places');
    const pl = list.find(x => x.place_id == id);
    if (!pl) return;
    const chips = featureChips(pl);
    document.getElementById('modalPlaceViewBody').innerHTML = `
      <div style="background:linear-gradient(135deg,#1e1b4b,#4f46e5);border-radius:14px;padding:20px;margin-bottom:18px">
        <div style="font-size:34px;margin-bottom:8px">${placeEmoji(pl.type)}</div>
        <h3 style="color:#fff;font-size:18px;font-weight:900;margin:0">${pl.name}</h3>
        <p style="color:#a5b4fc;font-size:13px;margin:4px 0 8px">${pl.address}</p>
        ${badge(pl.status||'active')}
      </div>
      <div class="fields-grid" style="margin-bottom:14px">
        <div class="field-box"><div class="field-label">Type</div><div class="field-value">${pl.type||'—'}</div></div>
        <div class="field-box"><div class="field-label">Bookings</div><div class="field-value">${pl.booking_count||0}</div></div>
        <div class="field-box"><div class="field-label">Latitude</div><div class="field-value">${pl.latitude||'—'}</div></div>
        <div class="field-box"><div class="field-label">Longitude</div><div class="field-value">${pl.longitude||'—'}</div></div>
      </div>
      <div style="margin-bottom:14px">
        <div class="field-label" style="margin-bottom:8px">Accessibility Features</div>
        <div style="display:flex;flex-wrap:wrap;gap:7px">
          ${chips.length ? chips.map(c=>`<span style="background:#eef2ff;color:#4338ca;font-size:12px;padding:4px 12px;border-radius:99px;font-weight:700">✓ ${c}</span>`).join('') : '<span style="color:#94a3b8;font-size:13px">None recorded.</span>'}
        </div>
      </div>
      ${pl.comment ? `<div class="field-box wide"><div class="field-label">Description</div><div class="field-value" style="font-weight:400;color:#475569">${pl.comment}</div></div>` : ''}`;
  } catch(e) {}
}

function openAddPlace() {
  document.getElementById('placeEditId').value = '';
  document.getElementById('placeModalTitle').textContent = 'Add New Place';
  ['fName','fAddress','fLat','fLng','fPhoto','fComment'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('fType').value   = '';
  document.getElementById('fStatus').value = 'active';
  placeFeatures = { elevator:false, ramp:false, toilet:false, parking:false };
  ['elevator','ramp','toilet','parking'].forEach(k => document.getElementById('ft-'+k).classList.remove('on'));
  openModal('modalPlace');
}

async function openEditPlace(id) {
  try {
    const list = await apiFetch('places');
    const pl   = list.find(x => x.place_id == id);
    if (!pl) return;
    document.getElementById('placeEditId').value  = id;
    document.getElementById('placeModalTitle').textContent = 'Edit Place';
    document.getElementById('fName').value    = pl.name    || '';
    document.getElementById('fType').value    = pl.type    || '';
    document.getElementById('fAddress').value = pl.address || '';
    document.getElementById('fLat').value     = pl.latitude  || '';
    document.getElementById('fLng').value     = pl.longitude || '';
    document.getElementById('fComment').value = pl.comment  || '';
    document.getElementById('fPhoto').value   = pl.photo    || '';
    document.getElementById('fStatus').value  = pl.status   || 'active';
    placeFeatures = {
      elevator: pl.elevator == 't' || pl.elevator == true,
      ramp:     pl.ramp     == 't' || pl.ramp     == true,
      toilet:   pl.toilet   == 't' || pl.toilet   == true,
      parking:  pl.parking  == 't' || pl.parking  == true,
    };
    ['elevator','ramp','toilet','parking'].forEach(k => document.getElementById('ft-'+k).classList.toggle('on', placeFeatures[k]));
    openModal('modalPlace');
  } catch(e) {}
}

function toggleFeature(k) {
  placeFeatures[k] = !placeFeatures[k];
  document.getElementById('ft-'+k).classList.toggle('on', placeFeatures[k]);
}

async function savePlace() {
  const name    = document.getElementById('fName').value.trim();
  const type    = document.getElementById('fType').value;
  const address = document.getElementById('fAddress').value.trim();
  if (!name || !type || !address) { alert('Please fill in Name, Type, and Address.'); return; }
  const editId = document.getElementById('placeEditId').value;
  const body = {
    name, type, address,
    latitude:  document.getElementById('fLat').value     || null,
    longitude: document.getElementById('fLng').value     || null,
    comment:   document.getElementById('fComment').value,
    photo:     document.getElementById('fPhoto').value,
    status:    document.getElementById('fStatus').value,
    ...placeFeatures,
  };
  try {
    if (editId) { await apiFetch('edit_place',   { id:editId, method:'PUT',  body }); showToast('Place updated!'); }
    else        { await apiFetch('add_place',     {            method:'POST', body }); showToast('Place added!');   }
    closeModal('modalPlace');
    loadPlaces();
  } catch(e) { showToast('Error: ' + e.message); }
}

function confirmDeletePlace(id) {
  openConfirm('Delete Place?', 'This will permanently delete this place. Cannot be undone.', true, () => deletePlace(id));
}

async function deletePlace(id) {
  try {
    await apiFetch('delete_place', { id, method:'DELETE' });
    showToast('Place deleted.');
    loadPlaces();
  } catch(e) { showToast('Error: ' + e.message); }
}

async function changePlaceStatus(id, status) {
  try {
    await apiFetch('update_place_status', { id, method:'PATCH', body:{ status } });
    showToast('Status updated!');
  } catch(e) { showToast('Error: ' + e.message); }
}

// ════════════════════════════════════════════════════════════
//  BOOKINGS
// ════════════════════════════════════════════════════════════
let bookDebounce;
function loadBookings() {
  clearTimeout(bookDebounce);
  bookDebounce = setTimeout(_loadBookings, 300);
}

async function _loadBookings() {
  document.getElementById('bookTable').innerHTML = '<tr><td colspan="9"><div class="spinner-wrap"><div class="spinner"></div></div></td></tr>';
  const search  = document.getElementById('bookSearch').value;
  const status  = document.getElementById('bookStatus').value;
  const service = document.getElementById('bookService').value;
  const qs = new URLSearchParams({ search, status, service_type:service }).toString();
  try {
    const list = await apiFetch('bookings&' + qs);
    if (!list.length) { document.getElementById('bookTable').innerHTML = '<tr><td colspan="9"><div class="empty-state">🔍 No bookings found</div></td></tr>'; return; }
    document.getElementById('bookTable').innerHTML = list.map(b => `
      <tr>
        <td class="td-muted" style="font-weight:700">#${b.booking_id}${b.is_urgent ? '<span class="urgent-tag">URGENT</span>' : ''}</td>
        <td><div class="td-name">${avatar(b.patient_name||'?',28)}<span class="td-name-text">${b.patient_name||'—'}</span></div></td>
        <td class="td-muted">${b.provider_name||'—'}</td>
        <td><span class="service-badge">${b.service_type||'—'}</span></td>
        <td class="td-muted" style="white-space:nowrap">${b.date||'—'}</td>
        <td class="td-bold">${b.payment_total ? 'EGP '+b.payment_total : '—'}</td>
        <td>${badge(b.payment_status)}</td>
        <td style="color:${b.rating?'#f59e0b':'#94a3b8'};font-weight:700">${stars(b.rating)}</td>
        <td>${badge(b.status)}</td>
      </tr>`).join('');
  } catch(e) { console.error(e); }
}

// ════════════════════════════════════════════════════════════
//  PATIENTS
// ════════════════════════════════════════════════════════════
let patDebounce;
function loadPatients() {
  clearTimeout(patDebounce);
  patDebounce = setTimeout(_loadPatients, 300);
}

async function _loadPatients() {
  document.getElementById('patTable').innerHTML = '<tr><td colspan="6"><div class="spinner-wrap"><div class="spinner"></div></div></td></tr>';
  const search = document.getElementById('patSearch').value;
  const qs = new URLSearchParams({ search }).toString();
  try {
    const list = await apiFetch('patients&' + qs);
    if (!list.length) {
      document.getElementById('patTable').innerHTML = '<tr><td colspan="6"><div class="empty-state">🔍 No patients found</div></td></tr>';
      return;
    }
    document.getElementById('patTable').innerHTML = list.map(p => `
      <tr>
        <td><div class="td-name">
          ${avatar(`${p.first_name} ${p.last_name}`, 34)}
          <div>
            <div class="td-name-text">${p.first_name} ${p.last_name}</div>
            <div class="td-name-email">${p.email}</div>
          </div>
        </div></td>
        <td class="td-muted">${p.phone || '—'}</td>
        <td class="td-muted" style="text-transform:capitalize">${p.gender || '—'}</td>
        <td class="td-muted">${p.disability || '—'}</td>
        <td class="td-muted" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.address || '—'}</td>
        <td class="td-bold" style="text-align:center">${p.total_bookings || 0}</td>
      </tr>`).join('');
  } catch(e) { console.error(e); }
}
</script>
</body>
</html>