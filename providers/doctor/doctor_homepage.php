<?php
session_start();
require __DIR__ . '/../../pgdb/db.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 2); }
function calc_platform_fee(float $total): float { return round($total * 0.15, 2); }
function calc_doctor_net(float $total): float { return round($total * 0.85, 2); }

function has_col(PDO $pdo, string $table, string $col): bool {
    $q = $pdo->prepare("
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = :t
          AND column_name = :c
        LIMIT 1
    ");
    $q->execute([':t'=>$table, ':c'=>$col]);
    return (bool)$q->fetchColumn();
}

function status_label($status): string {
    $map = [
        'pending'=>'Pending',
        'accepted'=>'Accepted',
        'arrived'=>'Arrived',
        'in_trip'=>'In Visit',
        'completed'=>'Completed',
        'rejected'=>'Rejected',
        'cancelled'=>'Cancelled'
    ];
    $s = strtolower(trim((string)$status));
    return $map[$s] ?? ucfirst($s);
}

function render_stars_html($rating): string {
    $rating = (float)$rating;
    $filled = (int)round($rating);
    $html = '<span class="rating-stars">';
    for($i=1;$i<=5;$i++){
        $html .= '<span class="star '.($i <= $filled ? 'filled' : '').'">★</span>';
    }
    $html .= '</span>';
    return $html;
}

$doctor_id = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';

if ($doctor_id <= 0 || $role !== 'provider') {
    header("Location: ../general/login.php");
    exit;
}

$hasPaymentStatus = has_col($pdo, 'booking', 'payment_status');
$hasWalletProcessed = has_col($pdo, 'booking', 'wallet_processed');
$hasEndAt = has_col($pdo, 'booking', 'end_at');

if (!$hasWalletProcessed) {
    try {
        $pdo->exec("ALTER TABLE booking ADD COLUMN wallet_processed BOOLEAN DEFAULT FALSE");
        $hasWalletProcessed = true;
    } catch(Exception $e) {}
}

$success = "";
$error = "";

if (isset($_SESSION['doctor_success'])) {
    $success = $_SESSION['doctor_success'];
    unset($_SESSION['doctor_success']);
}
if (isset($_SESSION['doctor_error'])) {
    $error = $_SESSION['doctor_error'];
    unset($_SESSION['doctor_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($action === 'logout') {
        session_unset();
        session_destroy();
        header("Location: ../general/login.php");
        exit;
    }

    if ($booking_id <= 0) {
        $_SESSION['doctor_error'] = "Invalid booking id.";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    try {
        if ($action === 'accept') {
            $setPayment = $hasPaymentStatus ? ", payment_status = 'accepted'" : "";
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'accepted' $setPayment
                WHERE booking_id = :booking_id
                  AND provider_id = :doctor_id
                  AND (status = 'pending' OR status IS NULL)
            ");
            $stmt->execute([':booking_id'=>$booking_id, ':doctor_id'=>$doctor_id]);

            $_SESSION['doctor_success'] = $stmt->rowCount() ? "Request #{$booking_id} accepted." : "Request could not be accepted.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'reject') {
            $setPayment = $hasPaymentStatus ? ", payment_status = 'rejected'" : "";
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'rejected' $setPayment
                WHERE booking_id = :booking_id
                  AND provider_id = :doctor_id
                  AND (status = 'pending' OR status IS NULL)
            ");
            $stmt->execute([':booking_id'=>$booking_id, ':doctor_id'=>$doctor_id]);

            $_SESSION['doctor_success'] = $stmt->rowCount() ? "Request #{$booking_id} rejected." : "Request could not be rejected.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'arrived') {
            $setPayment = $hasPaymentStatus ? ", payment_status = 'arrived'" : "";
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'arrived' $setPayment
                WHERE booking_id = :booking_id
                  AND provider_id = :doctor_id
                  AND status = 'accepted'
            ");
            $stmt->execute([':booking_id'=>$booking_id, ':doctor_id'=>$doctor_id]);

            $_SESSION['doctor_success'] = $stmt->rowCount() ? "Visit #{$booking_id} marked as arrived." : "Could not update visit.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'start') {
            $setPayment = $hasPaymentStatus ? ", payment_status = 'in_trip'" : "";
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'in_trip' $setPayment
                WHERE booking_id = :booking_id
                  AND provider_id = :doctor_id
                  AND status IN ('accepted','arrived')
            ");
            $stmt->execute([':booking_id'=>$booking_id, ':doctor_id'=>$doctor_id]);

            $_SESSION['doctor_success'] = $stmt->rowCount() ? "Visit #{$booking_id} started." : "Could not start visit.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'complete') {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT booking_id, payment_total, status, ".($hasPaymentStatus ? "payment_status," : "")." wallet_processed
                FROM booking
                WHERE booking_id = :booking_id
                  AND provider_id = :doctor_id
                FOR UPDATE
            ");
            $stmt->execute([':booking_id'=>$booking_id, ':doctor_id'=>$doctor_id]);
            $visit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$visit) throw new Exception("Visit not found.");
            if (($visit['status'] ?? '') !== 'in_trip') throw new Exception("Only active visits can be completed.");
            if (!empty($visit['wallet_processed'])) throw new Exception("This visit was already processed.");

            $setPayment = $hasPaymentStatus ? ", payment_status = 'completed'" : "";
            $setEnd = $hasEndAt ? ", end_at = COALESCE(end_at, CURRENT_TIMESTAMP)" : "";

            $upd = $pdo->prepare("
                UPDATE booking
                SET status = 'completed',
                    wallet_processed = TRUE
                    $setPayment
                    $setEnd
                WHERE booking_id = :booking_id
                  AND provider_id = :doctor_id
                  AND status = 'in_trip'
            ");
            $upd->execute([':booking_id'=>$booking_id, ':doctor_id'=>$doctor_id]);

            if ($upd->rowCount() < 1) throw new Exception("Completion failed.");

            $pdo->commit();

            $_SESSION['doctor_success'] = "Visit #{$booking_id} completed successfully.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['doctor_error'] = $e->getMessage();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

$doctor_name = "Doctor #".$doctor_id;
$doctor_speciality = "Doctor";
$doctor_initial = "D";

try {
    $stmtDoc = $pdo->prepare("
        SELECT 
            CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS full_name,
            d.speciality
        FROM \"user\" u
        JOIN doctor d ON d.user_id = u.user_id
        WHERE u.user_id = :id
        LIMIT 1
    ");
    $stmtDoc->execute([':id'=>$doctor_id]);
    $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $doctor_name = trim($doc['full_name']) ?: $doctor_name;
        $doctor_speciality = $doc['speciality'] ?: $doctor_speciality;
        $doctor_initial = mb_strtoupper(mb_substr($doctor_name, 0, 1));
    }
} catch(Exception $e) {}

$requests = [];
$activeVisits = [];
$history = [];

$wallet = [
    'available_balance'=>0,
    'total_earned'=>0,
    'total_visits'=>0
];

$doctor_rating = null;
$doctor_rating_count = 0;

try {
    $stmtWallet = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN wallet_processed = TRUE THEN payment_total * 0.85 ELSE 0 END), 0) AS available_balance,
            COALESCE(SUM(CASE WHEN wallet_processed = TRUE THEN payment_total ELSE 0 END), 0) AS total_earned,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) AS total_visits
        FROM booking
        WHERE provider_id = :doctor_id
          AND status = 'completed'
    ");
    $stmtWallet->execute([':doctor_id'=>$doctor_id]);
    $walletRow = $stmtWallet->fetch(PDO::FETCH_ASSOC);
    if ($walletRow) $wallet = $walletRow;

    $stmtRating = $pdo->prepare("
        SELECT ROUND(AVG(rating)::numeric, 1) AS avg_rating, COUNT(rating) AS rating_count
        FROM booking
        WHERE provider_id = :doctor_id
          AND rating IS NOT NULL
          AND status = 'completed'
    ");
    $stmtRating->execute([':doctor_id'=>$doctor_id]);
    $ratingRow = $stmtRating->fetch(PDO::FETCH_ASSOC);

    if ($ratingRow) {
        $doctor_rating = $ratingRow['avg_rating'];
        $doctor_rating_count = (int)($ratingRow['rating_count'] ?? 0);
    }

    $stmtReq = $pdo->prepare("
        SELECT
            b.booking_id,
            b.address,
            b.destination,
            b.payment_total,
            b.payment_method,
            b.status,
            ".($hasPaymentStatus ? "b.payment_status," : "NULL AS payment_status,")."
            b.date,
            b.booking_time,
            b.service_time,
            b.patient_id,
            CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS patient_name,
            p.phone AS patient_phone
        FROM booking b
        LEFT JOIN \"user\" u ON u.user_id = b.patient_id
        LEFT JOIN patient p ON p.user_id = b.patient_id
        WHERE b.provider_id = :doctor_id
          AND (b.status = 'pending' OR b.status IS NULL)
        ORDER BY b.booking_id DESC
        LIMIT 50
    ");
    $stmtReq->execute([':doctor_id'=>$doctor_id]);
    $requests = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

    $stmtActive = $pdo->prepare("
        SELECT
            b.booking_id,
            b.address,
            b.destination,
            b.payment_total,
            b.payment_method,
            b.status,
            ".($hasPaymentStatus ? "b.payment_status," : "NULL AS payment_status,")."
            b.date,
            b.booking_time,
            b.service_time,
            CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS patient_name,
            p.phone AS patient_phone
        FROM booking b
        LEFT JOIN \"user\" u ON u.user_id = b.patient_id
        LEFT JOIN patient p ON p.user_id = b.patient_id
        WHERE b.provider_id = :doctor_id
          AND b.status IN ('accepted','arrived','in_trip')
        ORDER BY b.booking_id DESC
        LIMIT 50
    ");
    $stmtActive->execute([':doctor_id'=>$doctor_id]);
    $activeVisits = $stmtActive->fetchAll(PDO::FETCH_ASSOC);

    $stmtHistory = $pdo->prepare("
        SELECT
            booking_id,
            address,
            destination,
            payment_total,
            payment_method,
            date,
            service_time,
            rating,
            status
        FROM booking
        WHERE provider_id = :doctor_id
          AND status = 'completed'
        ORDER BY booking_id DESC
        LIMIT 10
    ");
    $stmtHistory->execute([':doctor_id'=>$doctor_id]);
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    $error = "Error loading data: ".$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>RafiQ Doctor Portal</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
:root{
  --bg:#f4f7fb;
  --text:#1d2440;
  --muted:#7a84a3;
  --primary:#5b58eb;
  --primary-2:#4744cf;
  --card:#ffffff;
  --line:#e8edf6;
  --shadow:0 18px 40px rgba(31, 41, 86, 0.09);
  --shadow-sm:0 10px 24px rgba(31, 41, 86, 0.06);
  --ok:#1f9d5a;
  --bad:#c0392b;
  --call:#16a34a;
  --call-2:#15803d;
  --container:1200px;
  --radius:26px;
  --gold:#d5a72c;
}
*{box-sizing:border-box}
body{
  margin:0;
  font-family:"Nunito",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:
    radial-gradient(circle at top left, rgba(91,88,235,.08), transparent 28%),
    linear-gradient(180deg,#f7f9fd 0%, #f2f5fb 100%);
  color:var(--text);
}
.container{width:min(var(--container), calc(100% - 32px)); margin:0 auto;}

.topbar{
  position:sticky; top:0; z-index:50;
  background:rgba(255,255,255,0.84);
  backdrop-filter:blur(16px);
  border-bottom:1px solid rgba(232,237,246,.95);
}
.topbar-inner{
  display:flex; justify-content:space-between; align-items:center; gap:16px; padding:16px 0;
}
.brand{display:flex; align-items:center; gap:14px; flex-wrap:wrap;}
.logo{
  width:48px; height:48px; border-radius:16px; display:grid; place-items:center;
  background:linear-gradient(135deg,#5b58eb,#7d6eff); color:#fff; font-weight:900;
  box-shadow:0 14px 30px rgba(91,88,235,.28);
}
.brand-name{font-size:25px; font-weight:900; color:#2c2d55;}
.brand-name span{color:var(--gold)}
.top-right{display:flex; align-items:center; gap:10px; flex-wrap:wrap;}
.pill{
  display:inline-flex; align-items:center; gap:8px; padding:10px 14px;
  background:#fff; border:1px solid var(--line); border-radius:999px; font-weight:900;
  color:#2f3558; box-shadow:var(--shadow-sm); text-decoration:none;
}
.dot{
  width:10px; height:10px; border-radius:999px; background:var(--ok);
  box-shadow:0 0 0 6px rgba(31,157,90,.12);
}
.hero{padding:28px 0 10px}
.hero-box{
  background:linear-gradient(135deg,#ffffff 0%, #f8faff 65%, #f4f4ff 100%);
  border:1px solid var(--line); border-radius:32px; box-shadow:var(--shadow);
  padding:26px; position:relative; overflow:hidden;
}
.hero-top{
  display:flex; justify-content:space-between; gap:20px; align-items:flex-start; flex-wrap:wrap;
}
.hero h1{margin:0; font-size:34px; line-height:1.1; color:#25284b;}
.hero p{margin:10px 0 0; color:var(--muted); font-weight:800; max-width:720px;}
.profile-box{
  min-width:260px; background:#fff; border:1px solid var(--line); border-radius:22px;
  padding:16px; box-shadow:var(--shadow-sm);
}
.profile-top{display:flex; gap:12px; align-items:center;}
.avatar{
  width:56px; height:56px; border-radius:18px; display:grid; place-items:center;
  background:linear-gradient(135deg,#5b58eb,#7868ff); color:#fff; font-size:20px; font-weight:900;
}
.hello{font-size:14px; color:var(--muted); font-weight:800; margin:0;}
.driver-name{font-size:18px; font-weight:900; color:#25284b; margin:2px 0 0;}
.alerts{margin-top:16px; display:flex; flex-direction:column; gap:10px;}
.alert{
  border-radius:16px; padding:13px 15px; font-weight:800; border:1px solid var(--line);
  box-shadow:var(--shadow-sm); background:#fff;
}
.alert.ok{border-color: rgba(31,157,90,0.20); background:#f4fcf7; color:#16663d;}
.alert.bad{border-color: rgba(192,57,43,0.20); background:#fff5f4; color:#8d2b21;}
.hero-actions{margin-top:18px; display:flex; gap:12px; flex-wrap:wrap;}
.kpi-grid{
  display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin:20px 0 6px;
}
.kpi{
  padding:20px; border-radius:24px; border:1px solid var(--line);
  background:linear-gradient(180deg,#fff,#fafcff); box-shadow:var(--shadow);
}
.kpi-link{
  text-decoration:none; color:inherit; cursor:pointer;
  transition:transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}
.kpi-link:hover{
  transform:translateY(-4px);
  background:linear-gradient(180deg,#f6f5ff,#eef0ff);
  border-color:rgba(91,88,235,.26);
  box-shadow:0 20px 34px rgba(91,88,235,.13);
}
.kpi-link:hover .k, .kpi-link:hover .s{color:#4f46e5;}
.kpi .k{font-size:12px; color:var(--muted); font-weight:900; text-transform:uppercase; letter-spacing:.5px;}
.kpi .v{margin-top:8px; font-size:30px; font-weight:900; color:#26264b;}
.kpi .s{margin-top:6px; font-size:12px; color:#6e7694; font-weight:800;}
.grid{
  display:grid; grid-template-columns:1.35fr 1fr; gap:20px; padding:18px 0 40px; align-items:start;
}
.card{
  background:var(--card); border:1px solid rgba(232,237,246,.95); border-radius:var(--radius);
  box-shadow:var(--shadow); padding:18px;
}
.card-head{display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;}
.card h2{margin:0; font-size:20px; font-weight:900; color:#2d315d;}
.sub{margin-top:8px; color:var(--muted); font-weight:800; font-size:13px;}
.list{margin-top:14px; display:flex; flex-direction:column; gap:14px;}
.item{
  border:1px solid rgba(232,237,246,.95); border-radius:22px; padding:15px; display:flex;
  gap:14px; align-items:flex-start; background:linear-gradient(180deg,#fff,#fcfcff); transition:.18s ease;
}
.item:hover{transform:translateY(-2px); box-shadow:0 14px 26px rgba(38,45,90,.06);}
.item-left{
  width:60px; min-width:60px; height:60px; border-radius:18px; background:#f2f5ff;
  display:grid; place-items:center; font-weight:900; color:#3c3b59; border:1px solid rgba(236,236,245,.9);
}
.item-main{flex:1; min-width:0;}
.title{display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;}
.title b{font-size:15px; font-weight:900; color:#2a2a46;}
.badge{
  font-size:12px; font-weight:900; padding:7px 10px; border-radius:999px; border:1px solid var(--line);
  background:#fff; color:#2a2a46;
}
.badge.pending{border-color: rgba(212,155,0,0.35); color:#8a6700; background:#fff9e9;}
.badge.accepted{border-color: rgba(31,157,90,0.35); color:#137043; background:#eefaf3;}
.badge.arrived{border-color: rgba(46,154,223,0.35); color:#0c6ea8; background:#eef8ff;}
.badge.in_trip{border-color: rgba(75,74,116,0.35); color:#3f3e66; background:#f3f1ff;}
.badge.completed{border-color: rgba(31,157,90,0.35); color:#137043; background:#eefaf3;}
.badge.cash{border-color: rgba(25,135,84,0.28); color:#146c43; background:#eefaf3;}
.badge.visa{border-color: rgba(13,110,253,0.25); color:#0b5ed7; background:#eef4ff;}
.meta{
  margin-top:8px; display:flex; flex-wrap:wrap; gap:10px 14px; color:#5a5a7a; font-weight:800; font-size:13px;
}
.tiny{
  width:6px; height:6px; border-radius:99px; background:#bdbdd6; display:inline-block; margin-right:6px;
}
.trip-price{margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;}
.price-chip{
  padding:8px 10px; border-radius:999px; background:#f6f7fd; border:1px solid var(--line);
  font-size:12px; font-weight:900; color:#3a3a5e;
}
.actions{display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;}
.btn{
  display:inline-flex; align-items:center; justify-content:center; height:42px; padding:0 15px;
  border-radius:14px; border:1px solid var(--line); background:#fff; font-weight:900; color:#2a2a46;
  cursor:pointer; transition:.15s; text-decoration:none; font-family:inherit;
}
.btn:hover{transform:translateY(-1px); box-shadow:0 8px 16px rgba(38,45,90,.06);}
.btn.primary{
  background:linear-gradient(135deg,var(--primary),#716dff); border-color:transparent; color:#fff;
  box-shadow:0 12px 22px rgba(91,88,235,.22);
}
.btn.primary:hover{background:linear-gradient(135deg,var(--primary-2),#6863ff);}
.btn.danger{border-color: rgba(192,57,43,0.25); color:#a13024; background:#fff7f6;}
.btn.call{
  background:linear-gradient(135deg,var(--call),var(--call-2)); border-color:transparent; color:#fff;
  box-shadow:0 12px 22px rgba(22,163,74,.18);
}
.btn.logout{background:#fff6f6; border-color:rgba(192,57,43,.18); color:#922c23;}
.phone-chip{
  padding:8px 12px; border-radius:999px; background:#eefaf3; border:1px solid rgba(22,163,74,0.16);
  font-size:12px; font-weight:900; color:#166534;
}
.rating-stars{display:inline-flex; gap:4px; align-items:center;}
.rating-stars .star{font-size:16px; line-height:1; color:#d7dbe7;}
.rating-stars .star.filled{color:#f5b301;}
.empty{
  padding:14px; color:var(--muted); font-weight:800; border:1px dashed rgba(232,237,246,.95);
  border-radius:18px; background:#fff;
}
.right-stack{display:flex; flex-direction:column; gap:20px;}
.footer{
  padding:24px 0 40px; border-top:1px solid rgba(232,237,246,.9); color:#5f6283; font-weight:800;
  font-size:12px; text-align:center;
}
@media (max-width:1100px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}
@media (max-width:980px){
  .grid{grid-template-columns:1fr;}
  .kpi-grid{grid-template-columns:1fr;}
  .title{flex-direction:column; align-items:flex-start;}
  .topbar-inner{flex-direction:column; align-items:flex-start;}
  .hero-top{flex-direction:column;}
  .profile-box{width:100%;}
}
</style>

<script>
setInterval(() => {
  fetch(window.location.href, { cache: "no-store" })
    .then(r => r.text())
    .then(html => {
      const doc = new DOMParser().parseFromString(html, "text/html");
      ["requests_list","active_list","history_list","wallet_kpis"].forEach(id => {
        const src = doc.getElementById(id);
        const dst = document.getElementById(id);
        if(src && dst) dst.innerHTML = src.innerHTML;
      });
    })
    .catch(() => {});
}, 5000);
</script>
</head>

<body>

<?php include '../general/nav_prov.php'; ?>

<header class="topbar">
  <div class="container">
    <div class="topbar-inner">
      <div class="brand">
        <div class="logo">R</div>
        <div class="brand-name">Rafi<span>Q</span></div>
        <span class="pill">Doctor Portal</span>
      </div>

      <div class="top-right">
        <div class="pill"><span class="dot"></span> Live</div>
        <div class="pill">ID #<?= h($doctor_id) ?></div>
        <form method="post" style="margin:0;">
          <input type="hidden" name="action" value="logout">
          <button class="btn logout" type="submit">Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>

<main class="container">
  <section class="hero">
    <div class="hero-box">
      <div class="hero-top">
        <div>
          <h1>Hello, Dr. <?= h($doctor_name) ?> 👋</h1>
          <p>Manage patient requests, update visit status, mark visits as completed, and track your wallet after commission.</p>
        </div>

        <div class="profile-box">
          <div class="profile-top">
            <div class="avatar"><?= h($doctor_initial) ?></div>
            <div>
              <p class="hello">Welcome back</p>
              <div class="driver-name"><?= h($doctor_speciality) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="alerts">
        <?php if ($success): ?>
          <div class="alert ok">✅ <?= h($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert bad">⛔ <?= h($error) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="kpi-grid" id="wallet_kpis">
      <div class="kpi">
        <div class="k">Wallet Balance</div>
        <div class="v"><?= h(money($wallet['available_balance'] ?? 0)) ?></div>
        <div class="s">Net amount after 15% commission</div>
      </div>

      <div class="kpi">
        <div class="k">Total Earned</div>
        <div class="v"><?= h(money($wallet['total_earned'] ?? 0)) ?></div>
        <div class="s">Gross total of completed visits</div>
      </div>

      <div class="kpi kpi-link">
        <div class="k">Completed Visits</div>
        <div class="v"><?= h((int)($wallet['total_visits'] ?? 0)) ?></div>
        <div class="s">All completed doctor bookings</div>
      </div>

      <div class="kpi">
        <div class="k">Doctor Rating</div>
        <div class="v"><?= $doctor_rating !== null ? h($doctor_rating) : '—' ?></div>
        <div class="s"><?= h($doctor_rating_count) ?> rating<?= $doctor_rating_count == 1 ? '' : 's' ?></div>
      </div>
    </div>
  </section>

  <section class="grid">
    <div class="card">
      <div class="card-head">
        <div>
          <h2>Patient Requests</h2>
          <div class="sub">Accept or reject new patient bookings.</div>
        </div>
        <span class="badge pending"><?= count($requests) ?> Pending</span>
      </div>

      <div class="list" id="requests_list">
        <?php if (!$requests): ?>
          <div class="empty">No patient requests right now.</div>
        <?php else: ?>
          <?php foreach ($requests as $r): ?>
            <?php
              $gross = (float)($r['payment_total'] ?? 0);
              $fee = calc_platform_fee($gross);
              $net = calc_doctor_net($gross);
              $pm = strtolower(trim((string)($r['payment_method'] ?? 'cash')));
              if (!in_array($pm, ['cash','visa'], true)) $pm = 'cash';
            ?>
            <div class="item">
              <div class="item-left">#<?= h($r['booking_id']) ?></div>
              <div class="item-main">
                <div class="title">
                  <b><?= h(trim($r['patient_name'] ?? '') ?: 'Patient') ?></b>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <span class="badge <?= h($pm) ?>"><?= h(strtoupper($pm)) ?></span>
                    <span class="badge pending"><?= h(status_label($r['status'] ?? 'pending')) ?></span>
                  </div>
                </div>

                <div class="meta">
                  <span><span class="tiny"></span>Address: <?= h($r['address'] ?? '—') ?></span>
                  <?php if (!empty($r['destination'])): ?>
                    <span><span class="tiny"></span>Destination: <?= h($r['destination']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($r['date'])): ?>
                    <span><span class="tiny"></span>Date: <?= h($r['date']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($r['booking_time'])): ?>
                    <span><span class="tiny"></span>Time: <?= h(substr((string)$r['booking_time'],0,5)) ?></span>
                  <?php endif; ?>
                </div>

                <div class="trip-price">
                  <span class="price-chip">Gross: <?= h(money($gross)) ?> EGP</span>
                  <span class="price-chip">App Fee: <?= h(money($fee)) ?> EGP</span>
                  <span class="price-chip">Doctor Net: <?= h(money($net)) ?> EGP</span>
                </div>

                <div class="actions">
                  <form method="post">
                    <input type="hidden" name="booking_id" value="<?= h($r['booking_id']) ?>">
                    <button class="btn call" name="action" value="accept">Accept</button>
                  </form>

                  <form method="post">
                    <input type="hidden" name="booking_id" value="<?= h($r['booking_id']) ?>">
                    <button class="btn danger" name="action" value="reject">Reject</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="right-stack">
      <div class="card">
        <div class="card-head">
          <div>
            <h2>Active Visits</h2>
            <div class="sub">Update visit status exactly like driver flow.</div>
          </div>
        </div>

        <div class="list" id="active_list">
          <?php if (!$activeVisits): ?>
            <div class="empty">No active visits right now.</div>
          <?php else: ?>
            <?php foreach ($activeVisits as $a): ?>
              <?php
                $gross = (float)($a['payment_total'] ?? 0);
                $net = calc_doctor_net($gross);
                $statusClass = strtolower((string)($a['status'] ?? 'accepted'));
              ?>
              <div class="item">
                <div class="item-left">#<?= h($a['booking_id']) ?></div>
                <div class="item-main">
                  <div class="title">
                    <b><?= h(trim($a['patient_name'] ?? '') ?: 'Patient') ?></b>
                    <span class="badge <?= h($statusClass) ?>"><?= h(status_label($a['status'])) ?></span>
                  </div>

                  <div class="meta">
                    <span><span class="tiny"></span><?= h($a['address'] ?? '—') ?></span>
                    <?php if (!empty($a['date'])): ?>
                      <span><span class="tiny"></span><?= h($a['date']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($a['booking_time'])): ?>
                      <span><span class="tiny"></span><?= h(substr((string)$a['booking_time'],0,5)) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="trip-price">
                    <span class="price-chip">Net: <?= h(money($net)) ?> EGP</span>
                    <?php if (!empty($a['patient_phone'])): ?>
                      <span class="phone-chip">Phone: <?= h($a['patient_phone']) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="actions">
                    <?php if (($a['status'] ?? '') === 'accepted'): ?>
                      <form method="post">
                        <input type="hidden" name="booking_id" value="<?= h($a['booking_id']) ?>">
                        <button class="btn primary" name="action" value="arrived">Mark Arrived</button>
                      </form>
                    <?php endif; ?>

                    <?php if (in_array(($a['status'] ?? ''), ['accepted','arrived'], true)): ?>
                      <form method="post">
                        <input type="hidden" name="booking_id" value="<?= h($a['booking_id']) ?>">
                        <button class="btn primary" name="action" value="start">Start Visit</button>
                      </form>
                    <?php endif; ?>

                    <?php if (($a['status'] ?? '') === 'in_trip'): ?>
                      <form method="post">
                        <input type="hidden" name="booking_id" value="<?= h($a['booking_id']) ?>">
                        <button class="btn call" name="action" value="complete">Mark Completed</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-head">
          <div>
            <h2>Completed History</h2>
            <div class="sub">Latest completed visits.</div>
          </div>
        </div>

        <div class="list" id="history_list">
          <?php if (!$history): ?>
            <div class="empty">No completed visits yet.</div>
          <?php else: ?>
            <?php foreach ($history as $hrow): ?>
              <?php
                $gross = (float)($hrow['payment_total'] ?? 0);
                $net = calc_doctor_net($gross);
              ?>
              <div class="item">
                <div class="item-left">#<?= h($hrow['booking_id']) ?></div>
                <div class="item-main">
                  <div class="title">
                    <b>Completed Visit</b>
                    <span class="badge completed">Completed</span>
                  </div>

                  <div class="meta">
                    <span><span class="tiny"></span><?= h($hrow['address'] ?? '—') ?></span>
                    <?php if (!empty($hrow['date'])): ?>
                      <span><span class="tiny"></span><?= h($hrow['date']) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="trip-price">
                    <span class="price-chip">Gross: <?= h(money($gross)) ?> EGP</span>
                    <span class="price-chip">Doctor Net: <?= h(money($net)) ?> EGP</span>
                  </div>

                  <?php if (!empty($hrow['rating'])): ?>
                    <div style="margin-top:10px;">
                      <?= render_stars_html($hrow['rating']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include '../general/footer.php'; ?>

</body>
</html>