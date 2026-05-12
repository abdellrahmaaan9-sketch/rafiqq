<?php
session_start();
require __DIR__ . '/../../pgdb/db.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 2); }

function get_session_interpreter_id(): int {
    if (!empty($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (!empty($_SESSION['provider_id'])) return (int)$_SESSION['provider_id'];
    return 0;
}

function normalize_phone_for_tel(?string $phone): string {
    $phone = trim((string)$phone);
    if ($phone === '') return '';
    return preg_replace('/[^0-9\+]/', '', $phone);
}

function booking_status_label($status): string {
    $map = [
        'pending'    => 'Waiting',
        'accepted'   => 'Confirmed',
        'in_session' => 'In Session',
        'completed'  => 'Completed',
        'declined'   => 'Declined',
        'cancelled'  => 'Cancelled',
    ];
    $s = strtolower(trim((string)$status));
    return $map[$s] ?? ucfirst($s);
}

function payment_method_safe($value): string {
    $v = strtolower(trim((string)$value));
    return in_array($v, ['cash', 'visa'], true) ? $v : 'cash';
}

function fetch_interpreter_name(PDO $pdo, int $user_id): string {
    try {
        $stmt = $pdo->prepare('
            SELECT CONCAT(COALESCE(first_name, \'\'), \' \', COALESCE(last_name, \'\')) AS full_name
            FROM "user"
            WHERE user_id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $user_id]);
        $name = trim((string)$stmt->fetchColumn());
        if ($name !== '') return $name;
    } catch (Exception $e) {
    }
    return "Interpreter";
}

/* ---------------- AUTH ---------------- */
$interpreter_id = get_session_interpreter_id();

if ($interpreter_id <= 0) {
    header("Location: ../../general/login.php");
    exit;
}

$interpreter_name = fetch_interpreter_name($pdo, $interpreter_id);

$error = "";
$success = "";

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

/* ---------------- LOGOUT ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_unset();
    session_destroy();
    header("Location: ../../general/login.php");
    exit;
}

/* ---------------- ACTIONS ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'logout') {
    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        $_SESSION['flash_error'] = "Invalid booking.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        if ($action === 'accept') {
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'accepted'
                WHERE booking_id = :booking_id
                  AND provider_id = :provider_id
                  AND COALESCE(status, 'pending') = 'pending'
            ");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':provider_id' => $interpreter_id
            ]);

            $_SESSION['flash_success'] = $stmt->rowCount() > 0
                ? "The booking was accepted successfully."
                : "Could not accept the booking.";

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'decline') {
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'declined'
                WHERE booking_id = :booking_id
                  AND provider_id = :provider_id
                  AND COALESCE(status, 'pending') = 'pending'
            ");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':provider_id' => $interpreter_id
            ]);

            $_SESSION['flash_success'] = $stmt->rowCount() > 0
                ? "The booking was declined."
                : "Could not decline the booking.";

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'start') {
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'in_session'
                WHERE booking_id = :booking_id
                  AND provider_id = :provider_id
                  AND status = 'accepted'
            ");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':provider_id' => $interpreter_id
            ]);

            $_SESSION['flash_success'] = $stmt->rowCount() > 0
                ? "The session has started."
                : "Could not start the session.";

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'complete') {
            $stmt = $pdo->prepare("
                UPDATE booking
                SET status = 'completed'
                WHERE booking_id = :booking_id
                  AND provider_id = :provider_id
                  AND status IN ('accepted', 'in_session')
            ");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':provider_id' => $interpreter_id
            ]);

            $_SESSION['flash_success'] = $stmt->rowCount() > 0
                ? "The session was completed successfully."
                : "Could not complete the session.";

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['flash_error'] = $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* ---------------- FETCH DATA ---------------- */
$pendingBookings = [];
$activeBookings = [];
$history = [];

$stats = [
    'pending_count' => 0,
    'active_count' => 0,
    'completed_count' => 0,
    'total_earned' => 0,
];

try {
    $stmtStats = $pdo->prepare("
        SELECT
            COUNT(*) FILTER (WHERE COALESCE(status, 'pending') = 'pending') AS pending_count,
            COUNT(*) FILTER (WHERE status IN ('accepted', 'in_session')) AS active_count,
            COUNT(*) FILTER (WHERE status = 'completed') AS completed_count,
            COALESCE(SUM(payment_total) FILTER (WHERE status = 'completed'), 0) AS total_earned
        FROM booking
        WHERE provider_id = :provider_id
    ");
    $stmtStats->execute([':provider_id' => $interpreter_id]);
    $statsRow = $stmtStats->fetch(PDO::FETCH_ASSOC);
    if ($statsRow) {
        $stats = $statsRow;
    }

    $stmtPending = $pdo->prepare("
        SELECT
            booking_id,
            fullname,
            phone,
            email,
            date,
            booking_time,
            service_time,
            start_at,
            end_at,
            service_type,
            payment_total,
            payment_method,
            payment_status,
            status
        FROM booking
        WHERE provider_id = :provider_id
          AND COALESCE(status, 'pending') = 'pending'
        ORDER BY date ASC, booking_time ASC, booking_id DESC
        LIMIT 50
    ");
    $stmtPending->execute([':provider_id' => $interpreter_id]);
    $pendingBookings = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

    $stmtActive = $pdo->prepare("
        SELECT
            booking_id,
            fullname,
            phone,
            email,
            date,
            booking_time,
            service_time,
            start_at,
            end_at,
            service_type,
            payment_total,
            payment_method,
            payment_status,
            status
        FROM booking
        WHERE provider_id = :provider_id
          AND status IN ('accepted', 'in_session')
        ORDER BY date ASC, booking_time ASC, booking_id DESC
        LIMIT 50
    ");
    $stmtActive->execute([':provider_id' => $interpreter_id]);
    $activeBookings = $stmtActive->fetchAll(PDO::FETCH_ASSOC);

    $stmtHistory = $pdo->prepare("
        SELECT
            booking_id,
            fullname,
            date,
            booking_time,
            service_time,
            payment_total,
            payment_method,
            payment_status,
            status
        FROM booking
        WHERE provider_id = :provider_id
          AND status = 'completed'
        ORDER BY date DESC, booking_time DESC, booking_id DESC
        LIMIT 10
    ");
    $stmtHistory->execute([':provider_id' => $interpreter_id]);
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Error loading your bookings: " . $e->getMessage();
    $pendingBookings = [];
    $activeBookings = [];
    $history = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>RafiQ Interpreter Home</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
:root{
    --primary:#404066;
    --primary-dark:#2B2C41;
    --white:#FFFFFF;
    --danger:#B53535;
    --bg:#f6f7fb;
    --muted:#727793;
    --line:#e7e8f0;
    --shadow:0 14px 32px rgba(43,44,65,0.08);
    --shadow-soft:0 8px 18px rgba(43,44,65,0.05);
    --success:#2d8a57;
}

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:var(--bg);
    color:var(--primary-dark);
}

.container{
    width:min(1180px, calc(100% - 32px));
    margin:0 auto;
}

/* HERO */
.hero{
    padding:28px 0 12px;
}

.hero-box{
    background:var(--white);
    border:1px solid var(--line);
    border-radius:30px;
    box-shadow:var(--shadow);
    padding:34px 36px;
}

.hero h1{
    margin:0;
    color:var(--primary-dark);
    font-size:40px;
    line-height:1.08;
    font-weight:800;
}

.hero h1 span{
    display:block;
    margin-top:6px;
    color:var(--primary);
}

.hero p{
    margin:14px 0 0;
    max-width:760px;
    color:var(--muted);
    font-size:15px;
    line-height:1.7;
    font-weight:500;
}

.alerts{
    margin-top:18px;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.alert{
    border-radius:16px;
    padding:13px 15px;
    font-size:14px;
    font-weight:700;
    border:1px solid var(--line);
    background:#fff;
}

.alert.ok{
    background:#f3fbf6;
    border-color:rgba(45,138,87,.18);
    color:#17643c;
}

.alert.bad{
    background:#fff5f5;
    border-color:rgba(181,53,53,.18);
    color:#8c2626;
}

/* KPI */
.kpi-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
    margin-top:20px;
}

.kpi{
    background:#FFFFFF;
    border:1px solid var(--line);
    border-radius:24px;
    box-shadow:var(--shadow-soft);
    padding:20px;
}

.kpi .k{
    font-size:12px;
    font-weight:700;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.5px;
}

.kpi .v{
    margin-top:8px;
    font-size:30px;
    font-weight:800;
    color:var(--primary-dark);
}

.kpi .s{
    margin-top:6px;
    font-size:12px;
    color:var(--muted);
    font-weight:600;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:1.35fr 1fr;
    gap:20px;
    padding:18px 0 40px;
    align-items:start;
}

.right-stack{
    display:flex;
    flex-direction:column;
    gap:20px;
}

/* CARD */
.card{
    background:#FFFFFF;
    border:1px solid var(--line);
    border-radius:28px;
    box-shadow:var(--shadow);
    padding:22px;
}

.card h2{
    margin:0;
    font-size:24px;
    color:var(--primary-dark);
    font-weight:800;
}

.sub{
    margin-top:8px;
    color:var(--muted);
    font-size:14px;
    line-height:1.6;
    font-weight:500;
}

/* LIST */
.list{
    margin-top:16px;
    display:flex;
    flex-direction:column;
    gap:14px;
}

.item{
    background:#FFFFFF;
    border:1px solid var(--line);
    border-radius:22px;
    padding:16px;
    display:flex;
    gap:14px;
    align-items:flex-start;
    transition:.18s ease;
}

.item:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(43,44,65,.06);
}

.item-left{
    width:56px;
    min-width:56px;
    height:56px;
    border-radius:18px;
    background:#f3f4f9;
    border:1px solid #ececf4;
    display:grid;
    place-items:center;
    font-size:20px;
    color:var(--primary);
}

.item-main{
    flex:1;
    min-width:0;
}

.title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
}

.title b{
    font-size:17px;
    color:var(--primary-dark);
    font-weight:800;
}

.badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:7px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    border:1px solid var(--line);
    background:#fff;
    color:var(--primary-dark);
}

.badge.pending{
    background:#fff8e7;
    color:#8a6700;
    border-color:#f0ddb1;
}

.badge.accepted{
    background:#edf8f1;
    color:#16663d;
    border-color:#cfe9d9;
}

.badge.in_session{
    background:#eef2ff;
    color:#3550c7;
    border-color:#d9e0ff;
}

.badge.completed{
    background:#edf8f1;
    color:#16663d;
    border-color:#cfe9d9;
}

.badge.declined{
    background:#fff3f3;
    color:#8c2626;
    border-color:#efcaca;
}

.badge.cash{
    background:#eefaf3;
    color:#146c43;
    border-color:#d2ecdb;
}

.badge.visa{
    background:#eef3ff;
    color:#3550c7;
    border-color:#d9e2ff;
}

.meta{
    margin-top:10px;
    display:flex;
    flex-wrap:wrap;
    gap:10px 14px;
    color:#5d627d;
    font-size:13px;
    font-weight:600;
}

.tiny{
    width:6px;
    height:6px;
    border-radius:50%;
    background:#bdbdd6;
    display:inline-block;
    margin-right:6px;
}

.trip-price{
    margin-top:10px;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}

.price-chip{
    padding:8px 10px;
    border-radius:999px;
    background:#f6f7fd;
    border:1px solid var(--line);
    font-size:12px;
    font-weight:800;
    color:#3a3a5e;
}

.contact-chip{
    padding:8px 12px;
    border-radius:999px;
    background:#f3f5fb;
    border:1px solid #dfe4f2;
    font-size:12px;
    font-weight:800;
    color:#3e4a74;
}

.actions{
    margin-top:14px;
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

/* BUTTONS */
.btn{
    height:42px;
    padding:0 16px;
    border:none;
    border-radius:14px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:.18s ease;
}

.btn:hover{
    transform:translateY(-1px);
}

.btn.primary{
    background:#404066;
    color:#FFFFFF;
}

.btn.primary:hover{
    background:#2B2C41;
}

.btn.danger{
    background:#fff4f4;
    color:#B53535;
    border:1px solid #efcdcd;
}

.btn.danger:hover{
    background:#ffeaea;
}

.btn.call{
    background:#404066;
    color:#FFFFFF;
}

.btn.call:hover{
    background:#2B2C41;
}

.btn.mail{
    background:#2B2C41;
    color:#FFFFFF;
}

.btn.mail:hover{
    background:#1f2030;
}

.btn.neutral{
    background:#f4f5fa;
    color:#2B2C41;
    border:1px solid #e1e4ef;
}

.btn.neutral:hover{
    background:#eceff7;
}

.empty{
    padding:16px;
    border:1px dashed var(--line);
    border-radius:18px;
    background:#fff;
    color:var(--muted);
    font-size:14px;
    font-weight:600;
}

@media (max-width: 980px){
    .grid{
        grid-template-columns:1fr;
    }

    .kpi-grid{
        grid-template-columns:1fr;
    }

    .title{
        flex-direction:column;
        align-items:flex-start;
    }

    .hero h1{
        font-size:32px;
    }

    .hero-box{
        padding:26px 22px;
    }

    .card{
        padding:18px;
    }
}
</style>
</head>
<body>

<?php include '../../general/nav_prov.php'; ?>

<main class="container">
    <section class="hero">
        <div class="hero-box">
            <h1>
                Welcome back,
                <span><?= h($interpreter_name) ?></span>
            </h1>

            <p>
                Review new requests, manage your confirmed sessions, and keep track of your completed work in one place.
            </p>

            <div class="alerts">
                <?php if ($success): ?>
                    <div class="alert ok">✅ <?= h($success) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert bad">⛔ <?= h($error) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi">
                <div class="k">New Requests</div>
                <div class="v"><?= h((int)($stats['pending_count'] ?? 0)) ?></div>
                <div class="s">Waiting for your reply</div>
            </div>

            <div class="kpi">
                <div class="k">Upcoming Sessions</div>
                <div class="v"><?= h((int)($stats['active_count'] ?? 0)) ?></div>
                <div class="s">Confirmed or in progress</div>
            </div>

            <div class="kpi">
                <div class="k">Completed</div>
                <div class="v"><?= h((int)($stats['completed_count'] ?? 0)) ?></div>
                <div class="s">Finished sessions</div>
            </div>

            <div class="kpi">
                <div class="k">Total Earnings</div>
                <div class="v"><?= h(money($stats['total_earned'] ?? 0)) ?></div>
                <div class="s">Completed booking income</div>
            </div>
        </div>
    </section>

    <section class="grid">
        <div class="card">
            <h2>New Booking Requests</h2>
            <div class="sub">These patients are waiting for your confirmation.</div>

            <div class="list">
                <?php if (!$pendingBookings): ?>
                    <div class="empty">There are no new requests at the moment.</div>
                <?php else: ?>
                    <?php foreach ($pendingBookings as $b): ?>
                        <?php
                            $pm = payment_method_safe($b['payment_method'] ?? 'cash');
                            $patientPhoneRaw = (string)($b['phone'] ?? '');
                            $patientPhoneTel = normalize_phone_for_tel($patientPhoneRaw);
                            $patientEmail = trim((string)($b['email'] ?? ''));
                        ?>
                        <div class="item">
                            <div class="item-left">👤</div>
                            <div class="item-main">
                                <div class="title">
                                    <b><?= h($b['fullname'] ?: 'Patient') ?></b>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <span class="badge <?= h($pm) ?>"><?= h(strtoupper($pm)) ?></span>
                                        <span class="badge pending"><?= h(booking_status_label($b['status'] ?? 'pending')) ?></span>
                                    </div>
                                </div>

                                <div class="meta">
                                    <?php if (!empty($b['date'])): ?>
                                        <span><span class="tiny"></span><?= h($b['date']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($b['booking_time'])): ?>
                                        <span><span class="tiny"></span>Starts at <?= h(substr((string)$b['booking_time'], 0, 5)) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($b['service_time'])): ?>
                                        <span><span class="tiny"></span>Ends at <?= h(substr((string)$b['service_time'], 0, 5)) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($b['service_type'])): ?>
                                        <span><span class="tiny"></span><?= h($b['service_type']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="trip-price">
                                    <span class="price-chip">Session Fee: <?= h(money($b['payment_total'] ?? 0)) ?> EGP</span>
                                    <?php if ($patientPhoneRaw !== ''): ?>
                                        <span class="contact-chip">📞 <?= h($patientPhoneRaw) ?></span>
                                    <?php endif; ?>
                                    <?php if ($patientEmail !== ''): ?>
                                        <span class="contact-chip">✉ <?= h($patientEmail) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="actions">
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="action" value="accept">
                                        <input type="hidden" name="booking_id" value="<?= h($b['booking_id']) ?>">
                                        <button class="btn primary" type="submit">Accept</button>
                                    </form>

                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="action" value="decline">
                                        <input type="hidden" name="booking_id" value="<?= h($b['booking_id']) ?>">
                                        <button class="btn danger" type="submit">Decline</button>
                                    </form>

                                    <?php if ($patientPhoneTel !== ''): ?>
                                        <a class="btn call" href="tel:<?= h($patientPhoneTel) ?>">Call Patient</a>
                                    <?php endif; ?>

                                    <?php if ($patientEmail !== ''): ?>
                                        <a class="btn mail" href="mailto:<?= h($patientEmail) ?>">Email Patient</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="right-stack">
            <div class="card">
                <h2>Current Sessions</h2>
                <div class="sub">Your confirmed bookings and sessions in progress.</div>

                <div class="list">
                    <?php if (!$activeBookings): ?>
                        <div class="empty">You do not have any active sessions right now.</div>
                    <?php else: ?>
                        <?php foreach ($activeBookings as $b): ?>
                            <?php
                                $pm = payment_method_safe($b['payment_method'] ?? 'cash');
                                $st = strtolower((string)($b['status'] ?? ''));
                                $cls = $st === 'in_session' ? 'in_session' : 'accepted';
                                $patientPhoneRaw = (string)($b['phone'] ?? '');
                                $patientPhoneTel = normalize_phone_for_tel($patientPhoneRaw);
                                $patientEmail = trim((string)($b['email'] ?? ''));
                            ?>
                            <div class="item">
                                <div class="item-left">🗓</div>
                                <div class="item-main">
                                    <div class="title">
                                        <b><?= h($b['fullname'] ?: 'Patient') ?></b>
                                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                            <span class="badge <?= h($pm) ?>"><?= h(strtoupper($pm)) ?></span>
                                            <span class="badge <?= h($cls) ?>"><?= h(booking_status_label($b['status'] ?? 'accepted')) ?></span>
                                        </div>
                                    </div>

                                    <div class="meta">
                                        <?php if (!empty($b['date'])): ?>
                                            <span><span class="tiny"></span><?= h($b['date']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($b['booking_time'])): ?>
                                            <span><span class="tiny"></span>Starts at <?= h(substr((string)$b['booking_time'], 0, 5)) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($b['service_time'])): ?>
                                            <span><span class="tiny"></span>Ends at <?= h(substr((string)$b['service_time'], 0, 5)) ?></span>
                                        <?php endif; ?>
                                        <?php if ($patientPhoneRaw !== ''): ?>
                                            <span class="contact-chip">📞 <?= h($patientPhoneRaw) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="trip-price">
                                        <span class="price-chip">Session Fee: <?= h(money($b['payment_total'] ?? 0)) ?> EGP</span>
                                    </div>

                                    <div class="actions">
                                        <?php if ($patientPhoneTel !== ''): ?>
                                            <a class="btn call" href="tel:<?= h($patientPhoneTel) ?>">Call Patient</a>
                                        <?php endif; ?>

                                        <?php if ($patientEmail !== ''): ?>
                                            <a class="btn mail" href="mailto:<?= h($patientEmail) ?>">Email Patient</a>
                                        <?php endif; ?>

                                        <?php if (($b['status'] ?? '') === 'accepted'): ?>
                                            <form method="post" style="margin:0;">
                                                <input type="hidden" name="action" value="start">
                                                <input type="hidden" name="booking_id" value="<?= h($b['booking_id']) ?>">
                                                <button class="btn primary" type="submit">Begin Session</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (in_array(($b['status'] ?? ''), ['accepted', 'in_session'], true)): ?>
                                            <form method="post" style="margin:0;">
                                                <input type="hidden" name="action" value="complete">
                                                <input type="hidden" name="booking_id" value="<?= h($b['booking_id']) ?>">
                                                <button class="btn neutral" type="submit">Mark as Completed</button>
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
                <h2>Completed Sessions</h2>
                <div class="sub">Your most recent finished bookings.</div>

                <div class="list">
                    <?php if (!$history): ?>
                        <div class="empty">No completed sessions yet.</div>
                    <?php else: ?>
                        <?php foreach ($history as $b): ?>
                            <?php $pm = payment_method_safe($b['payment_method'] ?? 'cash'); ?>
                            <div class="item">
                                <div class="item-left">✓</div>
                                <div class="item-main">
                                    <div class="title">
                                        <b><?= h($b['fullname'] ?: 'Patient') ?></b>
                                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                            <span class="badge <?= h($pm) ?>"><?= h(strtoupper($pm)) ?></span>
                                            <span class="badge completed"><?= h(booking_status_label($b['status'] ?? 'completed')) ?></span>
                                        </div>
                                    </div>

                                    <div class="meta">
                                        <?php if (!empty($b['date'])): ?>
                                            <span><span class="tiny"></span><?= h($b['date']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($b['booking_time'])): ?>
                                            <span><span class="tiny"></span>Starts at <?= h(substr((string)$b['booking_time'], 0, 5)) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($b['service_time'])): ?>
                                            <span><span class="tiny"></span>Ends at <?= h(substr((string)$b['service_time'], 0, 5)) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="trip-price">
                                        <span class="price-chip">Session Fee: <?= h(money($b['payment_total'] ?? 0)) ?> EGP</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../../general/footer.php'; ?>

</body>
</html>