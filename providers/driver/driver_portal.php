<?php
session_start();
require __DIR__ . '/../../pgdb/db.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 2); }
function calc_platform_fee(float $total): float { return round($total * 0.15, 2); }
function calc_driver_net(float $total): float { return round($total * 0.85, 2); }

function payment_method_safe($value): string {
    $v = strtolower(trim((string)$value));
    return in_array($v, ['cash', 'visa'], true) ? $v : 'cash';
}

function status_label($status): string {
    $map = [
        'pending'   => 'Pending',
        'accepted'  => 'Accepted',
        'arrived'   => 'Arrived',
        'in_trip'   => 'In Trip',
        'completed' => 'Completed',
        'declined'  => 'Declined',
        'cancelled' => 'Cancelled',
    ];
    $s = strtolower(trim((string)$status));
    return $map[$s] ?? ucfirst($s);
}

function normalize_phone_for_tel(?string $phone): string {
    $phone = trim((string)$phone);
    if ($phone === '') return '';
    return preg_replace('/[^0-9\+]/', '', $phone);
}

function get_session_driver_id(): int {
    if (!empty($_SESSION['driver_id'])) return (int)$_SESSION['driver_id'];
    if (!empty($_SESSION['user_id']))   return (int)$_SESSION['user_id'];
    if (!empty($_SESSION['ID']))        return (int)$_SESSION['ID'];
    return 0;
}

function get_session_driver_name(): string {
    if (!empty($_SESSION['driver_name'])) return trim((string)$_SESSION['driver_name']);
    return '';
}

function ensure_provider(PDO $pdo, int $driver_id): void {
    $chk = $pdo->prepare("SELECT 1 FROM provider WHERE user_id = :id LIMIT 1");
    $chk->execute([':id' => $driver_id]);
    if ($chk->fetchColumn()) return;

    $ins = $pdo->prepare("
        INSERT INTO provider (user_id)
        OVERRIDING SYSTEM VALUE
        VALUES (:id)
    ");
    $ins->execute([':id' => $driver_id]);
}

function ensure_driver(PDO $pdo, int $driver_id): void {
    $chk = $pdo->prepare("SELECT 1 FROM driver WHERE user_id = :id LIMIT 1");
    $chk->execute([':id' => $driver_id]);
    if ($chk->fetchColumn()) return;

    $ins = $pdo->prepare("
        INSERT INTO driver (
            user_id,
            available_balance,
            company_due,
            total_earned,
            total_trips,
            updated_at
        )
        OVERRIDING SYSTEM VALUE
        VALUES (
            :id,
            0,
            0,
            0,
            0,
            CURRENT_TIMESTAMP
        )
    ");
    $ins->execute([':id' => $driver_id]);
}

function fetch_driver_name(PDO $pdo, int $driver_id): string {
    $sessionName = get_session_driver_name();
    if ($sessionName !== '') return $sessionName;

    try {
        $stmt = $pdo->prepare('
            SELECT CONCAT(COALESCE(first_name, \'\'), \' \', COALESCE(last_name, \'\')) AS full_name
            FROM "user"
            WHERE user_id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $driver_id]);
        $name = trim((string)$stmt->fetchColumn());
        if ($name !== '') return $name;
    } catch (Exception $e) {}

    return "Driver #{$driver_id}";
}

function has_col(PDO $pdo, string $table, string $col): bool {
    $q = $pdo->prepare("
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema = 'public'
        AND table_name = :t
        AND column_name = :c
      LIMIT 1
    ");
    $q->execute([':t' => $table, ':c' => $col]);
    return (bool)$q->fetchColumn();
}

function render_stars_html($rating): string {
    $rating = (float)$rating;
    $filled = (int)round($rating);
    $html = '<span class="rating-stars">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="star '.($i <= $filled ? 'filled' : '').'">★</span>';
    }
    $html .= '</span>';
    return $html;
}

$driver_id = get_session_driver_id();

if ($driver_id <= 0) {
    header("Location: ../../general/login.php");
    exit;
}

$driverCol = null;
if (has_col($pdo, 'booking', 'driver_id')) $driverCol = 'driver_id';
elseif (has_col($pdo, 'booking', 'provider_id')) $driverCol = 'provider_id';

if (!$driverCol) {
    die("Booking table must contain driver_id or provider_id.");
}

$hasServiceType = has_col($pdo, 'booking', 'service_type');
$serviceTypeFilter = $hasServiceType
    ? "AND (LOWER(b.service_type) = 'driver' OR b.service_type IS NULL)"
    : "";

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

$actions_disabled = false;

try {
    $pdo->beginTransaction();
    ensure_provider($pdo, $driver_id);
    ensure_driver($pdo, $driver_id);
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $actions_disabled = true;
    $error = "Driver initialization failed: " . $e->getMessage();
}

$driver_name = fetch_driver_name($pdo, $driver_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_unset();
    session_destroy();
    header("Location: ../../general/login.php");
    exit;
}

if (!isset($_SESSION['declined_requests'])) $_SESSION['declined_requests'] = [];
if (!isset($_SESSION['declined_requests'][$driver_id])) $_SESSION['declined_requests'][$driver_id] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'logout') {
    if ($actions_disabled) {
        $_SESSION['flash_error'] = "Driver is not registered correctly.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        $_SESSION['flash_error'] = "Invalid booking id.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        if ($action === 'accept') {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE booking
                SET {$driverCol} = :driver_id_set,
                    payment_status = 'accepted',
                    status = 'accepted'
                WHERE booking_id = :booking_id
                  AND {$driverCol} IS NULL
                  AND (payment_status IN ('pending','unpaid','cash_pending','paid') OR payment_status IS NULL)
                  AND (status = 'pending' OR status IS NULL)
            ");
            $stmt->execute([
                ':driver_id_set' => $driver_id,
                ':booking_id'    => $booking_id
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['declined_requests'][$driver_id] = array_values(array_diff(
                    $_SESSION['declined_requests'][$driver_id],
                    [$booking_id]
                ));
                $_SESSION['flash_success'] = "Trip #{$booking_id} accepted successfully.";
            } else {
                $_SESSION['flash_error'] = "Trip could not be accepted.";
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'decline') {
            if (!in_array($booking_id, $_SESSION['declined_requests'][$driver_id], true)) {
                $_SESSION['declined_requests'][$driver_id][] = $booking_id;
            }
            $_SESSION['flash_success'] = "Trip #{$booking_id} declined.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'arrived') {
            $stmt = $pdo->prepare("
                UPDATE booking
                SET payment_status = 'arrived',
                    status = 'arrived'
                WHERE booking_id = :booking_id
                  AND {$driverCol} = :driver_id
                  AND payment_status = 'accepted'
                  AND status = 'accepted'
            ");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':driver_id'  => $driver_id
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_success'] = "Trip #{$booking_id} marked as arrived.";
            } else {
                $_SESSION['flash_error'] = "Could not mark trip as arrived.";
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'start') {
            $stmt = $pdo->prepare("
                UPDATE booking
                SET payment_status = 'in_trip',
                    status = 'in_trip'
                WHERE booking_id = :booking_id
                  AND {$driverCol} = :driver_id
                  AND payment_status IN ('accepted','arrived')
                  AND status IN ('accepted','arrived')
            ");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':driver_id'  => $driver_id
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_success'] = "Trip #{$booking_id} started.";
            } else {
                $_SESSION['flash_error'] = "Could not start trip.";
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'complete') {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT booking_id, payment_total, payment_method, payment_status, status, wallet_processed
                FROM booking
                WHERE booking_id = :booking_id
                  AND {$driverCol} = :driver_id_check
                FOR UPDATE
            ");
            $stmt->execute([
                ':booking_id'      => $booking_id,
                ':driver_id_check' => $driver_id
            ]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$trip) throw new Exception("Trip not found.");
            if (($trip['payment_status'] ?? '') !== 'in_trip' || ($trip['status'] ?? '') !== 'in_trip') {
                throw new Exception("Only active trips can be completed.");
            }
            if ((bool)($trip['wallet_processed'] ?? false) === true) {
                throw new Exception("This trip was already processed.");
            }

            $gross = (float)($trip['payment_total'] ?? 0);
            $driverNet = calc_driver_net($gross);

            $upd = $pdo->prepare("
                UPDATE booking
                SET payment_status = 'completed',
                    status = 'completed',
                    wallet_processed = TRUE,
                    end_at = COALESCE(end_at, CURRENT_TIMESTAMP)
                WHERE booking_id = :booking_id
                  AND {$driverCol} = :driver_id_check2
                  AND payment_status = 'in_trip'
                  AND status = 'in_trip'
            ");
            $upd->execute([
                ':booking_id'       => $booking_id,
                ':driver_id_check2' => $driver_id
            ]);

            if ($upd->rowCount() < 1) {
                throw new Exception("Trip completion failed.");
            }

            $w = $pdo->prepare("
                UPDATE driver
                SET available_balance = COALESCE(available_balance, 0) + :driver_net,
                    total_earned = COALESCE(total_earned, 0) + :gross_total,
                    total_trips = COALESCE(total_trips, 0) + 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :driver_wallet_id
            ");
            $w->execute([
                ':driver_net'       => $driverNet,
                ':gross_total'      => $gross,
                ':driver_wallet_id' => $driver_id
            ]);

            $pdo->commit();
            $_SESSION['flash_success'] = "Trip #{$booking_id} completed successfully.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'rate_patient') {
            $rating = (int)($_POST['driver_patient_rating'] ?? 0);
            $comment = trim((string)($_POST['driver_patient_comment'] ?? ''));

            if ($rating < 1 || $rating > 5) {
                throw new Exception("Patient rating must be between 1 and 5.");
            }

            $stmt = $pdo->prepare("
                UPDATE booking
                SET driver_patient_rating = :rating,
                    driver_patient_comment = :comment
                WHERE booking_id = :booking_id
                  AND {$driverCol} = :driver_id
                  AND status = 'completed'
                  AND payment_status = 'completed'
                  AND driver_patient_rating IS NULL
            ");
            $stmt->execute([
                ':rating'     => $rating,
                ':comment'    => ($comment !== '' ? $comment : null),
                ':booking_id' => $booking_id,
                ':driver_id'  => $driver_id
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_success'] = "Patient rated successfully for trip #{$booking_id}.";
            } else {
                $_SESSION['flash_error'] = "This trip was already rated or could not be updated.";
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_error'] = $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$requests = [];
$activeTrips = [];
$history = [];
$wallet = [
    'available_balance' => 0,
    'total_earned' => 0,
    'total_trips' => 0,
];
$driver_rating = null;
$driver_rating_count = 0;

try {
    $stmtWallet = $pdo->prepare("
        SELECT available_balance, total_earned, total_trips
        FROM driver
        WHERE user_id = :driver_id
        LIMIT 1
    ");
    $stmtWallet->execute([':driver_id' => $driver_id]);
    $walletRow = $stmtWallet->fetch(PDO::FETCH_ASSOC);
    if ($walletRow) $wallet = $walletRow;

    $stmtDriverRating = $pdo->prepare("
        SELECT
            ROUND(AVG(rating)::numeric, 1) AS avg_rating,
            COUNT(rating) AS rating_count
        FROM booking
        WHERE {$driverCol} = :driver_id
          AND rating IS NOT NULL
          AND status = 'completed'
          AND payment_status = 'completed'
    ");
    $stmtDriverRating->execute([':driver_id' => $driver_id]);
    $driverRatingRow = $stmtDriverRating->fetch(PDO::FETCH_ASSOC);
    if ($driverRatingRow) {
        $driver_rating = $driverRatingRow['avg_rating'];
        $driver_rating_count = (int)($driverRatingRow['rating_count'] ?? 0);
    }

    $declined = $_SESSION['declined_requests'][$driver_id] ?? [];
    $declinedSql = "";
    $declinedParams = [];

    if ($declined) {
        $keys = [];
        foreach ($declined as $i => $bid) {
            $k = ":d{$i}";
            $keys[] = $k;
            $declinedParams[$k] = (int)$bid;
        }
        $declinedSql = " AND b.booking_id NOT IN (" . implode(",", $keys) . ") ";
    }

    $sqlRequests = "
      SELECT
          b.booking_id,
          b.address,
          b.destination,
          b.payment_status,
          b.status,
          b.payment_total,
          b.payment_method,
          b.pickup_lat,
          b.pickup_lng,
          b.dest_lat,
          b.dest_lng,
          b.date,
          b.booking_time,
          b.service_time,
          b.patient_id,
          CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS patient_name,
          ROUND(AVG(prev.driver_patient_rating)::numeric, 1) AS patient_avg_rating,
          COUNT(prev.driver_patient_rating) AS patient_rating_count
      FROM booking b
      LEFT JOIN \"user\" u ON u.user_id = b.patient_id
      LEFT JOIN booking prev
        ON prev.patient_id = b.patient_id
       AND prev.driver_patient_rating IS NOT NULL
       AND prev.status = 'completed'
       AND prev.payment_status = 'completed'
      WHERE b.{$driverCol} IS NULL
        AND (b.payment_status IN ('pending','unpaid','cash_pending','paid') OR b.payment_status IS NULL)
        AND (b.status = 'pending' OR b.status IS NULL)
        {$serviceTypeFilter}
        {$declinedSql}
      GROUP BY
          b.booking_id,
          b.address,
          b.destination,
          b.payment_status,
          b.status,
          b.payment_total,
          b.payment_method,
          b.pickup_lat,
          b.pickup_lng,
          b.dest_lat,
          b.dest_lng,
          b.date,
          b.booking_time,
          b.service_time,
          b.patient_id,
          u.first_name,
          u.last_name
      ORDER BY b.booking_id DESC
      LIMIT 50
    ";
    $stmtR = $pdo->prepare($sqlRequests);
    $stmtR->execute($declinedParams);
    $requests = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    $sqlActive = "
      SELECT
          b.booking_id,
          b.address,
          b.destination,
          b.payment_status,
          b.status,
          b.payment_total,
          b.payment_method,
          b.pickup_lat,
          b.pickup_lng,
          b.dest_lat,
          b.dest_lng,
          b.date,
          b.booking_time,
          b.service_time,
          CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS patient_name,
          p.phone AS patient_phone
      FROM booking b
      LEFT JOIN patient p ON p.user_id = b.patient_id
      LEFT JOIN \"user\" u ON u.user_id = b.patient_id
      WHERE b.{$driverCol} = :driver_id
        AND b.payment_status IN ('accepted','arrived','in_trip')
        AND b.status IN ('accepted','arrived','in_trip')
      ORDER BY b.booking_id DESC
      LIMIT 50
    ";
    $stmtA = $pdo->prepare($sqlActive);
    $stmtA->execute([':driver_id' => $driver_id]);
    $activeTrips = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    $sqlHistory = "
      SELECT
          b.booking_id,
          b.address,
          b.destination,
          b.payment_total,
          b.payment_method,
          b.date,
          b.service_time,
          b.rating,
          b.driver_patient_rating,
          b.driver_patient_comment
      FROM booking b
      WHERE b.{$driverCol} = :driver_id
        AND b.payment_status = 'completed'
        AND b.status = 'completed'
      ORDER BY COALESCE(b.end_at, b.start_at) DESC NULLS LAST, b.booking_id DESC
      LIMIT 10
    ";
    $stmtH = $pdo->prepare($sqlHistory);
    $stmtH->execute([':driver_id' => $driver_id]);
    $history = $stmtH->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Error loading data: " . $e->getMessage();
    $requests = [];
    $activeTrips = [];
    $history = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>RafiQ Driver Portal</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
  .badge.done{border-color: rgba(91,88,235,.24); color:#4f46e5; background:#f4f3ff;}

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
    cursor:pointer; transition:.15s; text-decoration:none;
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

  .rating-preview{
    margin-top:10px;
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .rating-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:#fff8e8;
    border:1px solid rgba(213,167,44,.25);
    color:#8a6700;
    font-size:12px;
    font-weight:900;
  }
  .rating-stars{
    display:inline-flex;
    gap:4px;
    align-items:center;
  }
  .rating-stars .star{
    font-size:16px;
    line-height:1;
    color:#d7dbe7;
  }
  .rating-stars .star.filled{
    color:#f5b301;
  }

  .star-rating-box{
    margin-top:14px;
    padding:14px;
    border-radius:18px;
    border:1px solid #ebe7ff;
    background:#f8f7ff;
  }
  .star-rating-box h4{
    margin:0 0 10px;
    font-size:14px;
    color:#4338ca;
  }
  .star-rating{
    display:flex;
    flex-direction:row-reverse;
    justify-content:flex-end;
    gap:8px;
    margin-bottom:12px;
  }
  .star-rating input{
    display:none;
  }
  .star-rating label{
    cursor:pointer;
    font-size:28px;
    color:#d8dceb;
    transition:.15s ease;
    line-height:1;
  }
  .star-rating label:hover,
  .star-rating label:hover ~ label,
  .star-rating input:checked ~ label{
    color:#f5b301;
    transform:scale(1.06);
  }

  .rate-input{
    width:100%;
    min-height:82px;
    border-radius:14px;
    border:1px solid var(--line);
    background:#fff;
    padding:12px 14px;
    font:inherit;
    resize:vertical;
    margin-bottom:10px;
  }
  .rated-note{
    margin-top:12px; font-weight:900; color:#4f46e5; background:#f5f3ff; border:1px solid #e9ddff;
    border-radius:14px; padding:10px 12px;
  }

  .empty{
    padding:14px; color:var(--muted); font-weight:800; border:1px dashed rgba(232,237,246,.95);
    border-radius:18px; background:#fff;
  }

  .right-stack{display:flex; flex-direction:column; gap:20px;}
  .footer{
    padding:24px 0 40px; border-top:1px solid rgba(232,237,246,.9); color:#5f6283; font-weight:800;
    font-size:12px; text-align:center;
  }

  @media (max-width: 1100px){
    .kpi-grid{grid-template-columns:repeat(2,1fr);}
  }
  @media (max-width: 980px){
    .grid{grid-template-columns:1fr;}
    .kpi-grid{grid-template-columns:1fr;}
    .title{flex-direction:column; align-items:flex-start;}
    .topbar-inner{flex-direction:column; align-items:flex-start;}
    .hero-top{flex-direction:column;}
    .profile-box{width:100%;}
  }

  .modal{
    position:fixed; inset:0; background:rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; z-index:999;
  }
  .modal.open{display:flex;}
  .modal-card{
    width:min(900px, calc(100% - 40px)); background:#fff; border-radius:22px;
    border:1px solid rgba(236,236,245,0.95); box-shadow:0 30px 60px rgba(0,0,0,0.14); overflow:hidden;
  }
  .modal-head{
    display:flex; align-items:center; justify-content:space-between; padding:12px 14px;
    border-bottom:1px solid rgba(236,236,245,0.95); font-weight:900;
  }
  #tripMap{height:520px;}
  @media (max-width: 980px){ #tripMap{height:420px;} }
</style>

<script>
setInterval(() => {
  fetch(window.location.href, { cache: "no-store" })
    .then(r => r.text())
    .then(html => {
      const doc = new DOMParser().parseFromString(html, "text/html");
      const ids = ["requests_list", "active_list", "history_list", "wallet_kpis"];
      ids.forEach(id => {
        const src = doc.getElementById(id);
        const dst = document.getElementById(id);
        if (src && dst) dst.innerHTML = src.innerHTML;
      });
    })
    .catch(() => {});
}, 5000);
</script>
</head>
<body>

<header class="topbar">
  <div class="container">
    <div class="topbar-inner">
      <div class="brand">
        <div class="logo">R</div>
        <div class="brand-name">Rafi<span>Q</span></div>
        <a class="pill" href="trips.php">Completed Trips</a>
      </div>

      <div class="top-right">
        <div class="pill"><span class="dot"></span> Live</div>
        <div class="pill">ID #<?= h($driver_id) ?></div>
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
          <h1>Hello, <?= h($driver_name) ?> 👋</h1>
          <p>Manage requests, update live trip status, track your wallet after commission, and open your full trips page anytime.</p>
        </div>

        <div class="profile-box">
          <div class="profile-top">
            <div class="avatar"><?= h(mb_strtoupper(mb_substr($driver_name, 0, 1))) ?></div>
            <div>
              <p class="hello">Welcome back</p>
              <div class="driver-name"><?= h($driver_name) ?></div>
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

      <div class="hero-actions">
        <a class="btn primary" href="trips.php">Open Trips Page</a>
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
        <div class="s">Gross total of completed trips</div>
      </div>

      <a class="kpi kpi-link" href="trips.php">
        <div class="k">Completed Trips</div>
        <div class="v"><?= h((int)($wallet['total_trips'] ?? 0)) ?></div>
        <div class="s">Hover me • click to open full trips page</div>
      </a>

      <div class="kpi">
        <div class="k">Driver Rating</div>
        <div class="v"><?= $driver_rating !== null ? h($driver_rating) : '—' ?></div>
        <div class="s"><?= h($driver_rating_count) ?> rating<?= $driver_rating_count == 1 ? '' : 's' ?></div>
      </div>
    </div>
  </section>

  <section class="grid">
    <div class="card">
      <h2>Available Requests</h2>
      <div class="sub">Patient average rating is shown before you accept.</div>

      <div class="list" id="requests_list">
        <?php if (!$requests): ?>
          <div class="empty">No requests available right now.</div>
        <?php else: ?>
          <?php foreach ($requests as $r): ?>
            <?php
              $gross = (float)($r['payment_total'] ?? 0);
              $fee = calc_platform_fee($gross);
              $net = calc_driver_net($gross);
              $pm = payment_method_safe($r['payment_method'] ?? 'cash');
            ?>
            <div class="item">
              <div class="item-left">#<?= h($r['booking_id']) ?></div>
              <div class="item-main">
                <div class="title">
                  <b>Pickup → Destination</b>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <span class="badge <?= h($pm) ?>"><?= h(strtoupper($pm)) ?></span>
                    <span class="badge pending"><?= h(status_label($r['status'] ?? 'pending')) ?></span>
                  </div>
                </div>

                <div class="meta">
                  <?php if (!empty(trim((string)($r['patient_name'] ?? '')))): ?>
                    <span><span class="tiny"></span>Patient: <?= h($r['patient_name']) ?></span>
                  <?php endif; ?>
                  <span><span class="tiny"></span><?= h($r['address'] ?? '—') ?></span>
                  <span><span class="tiny"></span><?= h($r['destination'] ?? '—') ?></span>
                  <?php if (!empty($r['date'])): ?>
                    <span><span class="tiny"></span><?= h($r['date']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($r['service_time'])): ?>
                    <span><span class="tiny"></span><?= h($r['service_time']) ?></span>
                  <?php endif; ?>
                </div>

                <?php if ($r['patient_avg_rating'] !== null): ?>
                  <div class="rating-preview">
                    <span class="rating-chip">
                      Patient Rating:
                      <?= render_stars_html($r['patient_avg_rating']) ?>
                      <span><?= h($r['patient_avg_rating']) ?>/5</span>
                      <span>(<?= h((int)$r['patient_rating_count']) ?>)</span>
                    </span>
                  </div>
                <?php else: ?>
                  <div class="rating-preview">
                    <span class="rating-chip">Patient Rating: No ratings yet</span>
                  </div>
                <?php endif; ?>

                <div class="trip-price">
                  <span class="price-chip">Trip Price: <?= h(money($gross)) ?></span>
                  <span class="price-chip">Your Net: <?= h(money($net)) ?></span>
                  <span class="price-chip">Commission: <?= h(money($fee)) ?></span>
                </div>

                <div class="actions">
                  <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="accept">
                    <input type="hidden" name="booking_id" value="<?= h($r['booking_id']) ?>">
                    <button class="btn primary" type="submit">Accept</button>
                  </form>

                  <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="decline">
                    <input type="hidden" name="booking_id" value="<?= h($r['booking_id']) ?>">
                    <button class="btn danger" type="submit">Decline</button>
                  </form>

                  <button class="btn" type="button"
                    onclick='openMap(
                      <?= (float)($r["pickup_lat"] ?? 0) ?>,
                      <?= (float)($r["pickup_lng"] ?? 0) ?>,
                      <?= (float)($r["dest_lat"] ?? 0) ?>,
                      <?= (float)($r["dest_lng"] ?? 0) ?>,
                      "<?= h($r["address"] ?? "") ?>",
                      "<?= h($r["destination"] ?? "") ?>"
                    )'>Map</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="right-stack">
      <div class="card">
        <h2>My Active Trips</h2>
        <div class="sub">Update each trip until it becomes completed.</div>

        <div class="list" id="active_list">
          <?php if (!$activeTrips): ?>
            <div class="empty">No active trips.</div>
          <?php else: ?>
            <?php foreach ($activeTrips as $t): ?>
              <?php
                $st = strtolower(trim((string)($t['status'] ?? 'accepted')));
                $cls = 'accepted';
                if ($st === 'arrived') $cls = 'arrived';
                if ($st === 'in_trip') $cls = 'in_trip';

                $gross = (float)($t['payment_total'] ?? 0);
                $net = calc_driver_net($gross);
                $pm = payment_method_safe($t['payment_method'] ?? 'cash');
                $patientPhoneRaw = (string)($t['patient_phone'] ?? '');
                $patientPhoneTel = normalize_phone_for_tel($patientPhoneRaw);
              ?>
              <div class="item">
                <div class="item-left">#<?= h($t['booking_id']) ?></div>
                <div class="item-main">
                  <div class="title">
                    <b>Trip Details</b>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                      <span class="badge <?= h($pm) ?>"><?= h(strtoupper($pm)) ?></span>
                      <span class="badge <?= h($cls) ?>"><?= h(status_label($st)) ?></span>
                    </div>
                  </div>

                  <div class="meta">
                    <?php if (!empty(trim((string)($t['patient_name'] ?? '')))): ?>
                      <span><span class="tiny"></span>Patient: <?= h($t['patient_name']) ?></span>
                    <?php endif; ?>
                    <span><span class="tiny"></span><?= h($t['address'] ?? '—') ?></span>
                    <span><span class="tiny"></span><?= h($t['destination'] ?? '—') ?></span>
                    <?php if ($patientPhoneRaw !== ''): ?>
                      <span class="phone-chip">📞 <?= h($patientPhoneRaw) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="trip-price">
                    <span class="price-chip">Trip Price: <?= h(money($gross)) ?></span>
                    <span class="price-chip">Your Net: <?= h(money($net)) ?></span>
                  </div>

                  <div class="actions">
                    <!-- Live Tracking Button -->
                    <a class="btn primary" href="driver_tracking.php?booking_id=<?= h($t['booking_id']) ?>"
                       style="background:linear-gradient(135deg,#10b981,#059669);display:inline-flex;align-items:center;gap:6px;text-decoration:none">
                      📍 Live Tracking
                    </a>

                    <button class="btn" type="button"
                      onclick='openMap(
                        <?= (float)($t["pickup_lat"] ?? 0) ?>,
                        <?= (float)($t["pickup_lng"] ?? 0) ?>,
                        <?= (float)($t["dest_lat"] ?? 0) ?>,
                        <?= (float)($t["dest_lng"] ?? 0) ?>,
                        "<?= h($t["address"] ?? "") ?>",
                        "<?= h($t["destination"] ?? "") ?>"
                      )'>Map</button>

                    <?php if ($patientPhoneTel !== ''): ?>
                      <a class="btn call" href="tel:<?= h($patientPhoneTel) ?>">📞 Call Patient</a>
                    <?php endif; ?>

                    <?php if ($st === 'accepted'): ?>
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="arrived">
                        <input type="hidden" name="booking_id" value="<?= h($t['booking_id']) ?>">
                        <button class="btn" type="submit">Arrived</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($st === 'accepted' || $st === 'arrived'): ?>
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="start">
                        <input type="hidden" name="booking_id" value="<?= h($t['booking_id']) ?>">
                        <button class="btn primary" type="submit">Start Trip</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($st === 'in_trip'): ?>
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="complete">
                        <input type="hidden" name="booking_id" value="<?= h($t['booking_id']) ?>">
                        <button class="btn primary" type="submit">Complete</button>
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
            <h2>Recent Completed Trips</h2>
            <div class="sub">Important details only.</div>
          </div>
          <a class="btn primary" href="trips.php">View All</a>
        </div>

        <div class="list" id="history_list">
          <?php if (!$history): ?>
            <div class="empty">No completed trips yet.</div>
          <?php else: ?>
            <?php foreach ($history as $hrow): ?>
              <?php
                $gross = (float)($hrow['payment_total'] ?? 0);
                $net = calc_driver_net($gross);
                $pm = payment_method_safe($hrow['payment_method'] ?? 'cash');
              ?>
              <div class="item">
                <div class="item-left">#<?= h($hrow['booking_id']) ?></div>
                <div class="item-main">
                  <div class="title">
                    <b>Completed Trip</b>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                      <span class="badge <?= h($pm) ?>"><?= h(strtoupper($pm)) ?></span>
                      <span class="badge completed">Completed</span>
                      <?php if ($hrow['rating'] !== null): ?>
                        <span class="badge done">Your Rating: <?= h($hrow['rating']) ?>/5</span>
                      <?php endif; ?>
                      <?php if ($hrow['driver_patient_rating'] !== null): ?>
                        <span class="badge done">You rated patient: <?= h($hrow['driver_patient_rating']) ?>/5</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="meta">
                    <span><span class="tiny"></span><?= h($hrow['address'] ?? '—') ?></span>
                    <span><span class="tiny"></span><?= h($hrow['destination'] ?? '—') ?></span>
                    <?php if (!empty($hrow['date'])): ?>
                      <span><span class="tiny"></span><?= h($hrow['date']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($hrow['service_time'])): ?>
                      <span><span class="tiny"></span><?= h($hrow['service_time']) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="trip-price">
                    <span class="price-chip">Trip Price: <?= h(money($gross)) ?></span>
                    <span class="price-chip">Your Net: <?= h(money($net)) ?></span>
                  </div>

                  <?php if ($hrow['driver_patient_rating'] === null): ?>
                    <div class="star-rating-box">
                      <h4>Rate the patient</h4>
                      <form method="post">
                        <input type="hidden" name="action" value="rate_patient">
                        <input type="hidden" name="booking_id" value="<?= h($hrow['booking_id']) ?>">

                        <div class="star-rating">
                          <input type="radio" id="star5_<?= h($hrow['booking_id']) ?>" name="driver_patient_rating" value="5" required>
                          <label for="star5_<?= h($hrow['booking_id']) ?>">★</label>

                          <input type="radio" id="star4_<?= h($hrow['booking_id']) ?>" name="driver_patient_rating" value="4">
                          <label for="star4_<?= h($hrow['booking_id']) ?>">★</label>

                          <input type="radio" id="star3_<?= h($hrow['booking_id']) ?>" name="driver_patient_rating" value="3">
                          <label for="star3_<?= h($hrow['booking_id']) ?>">★</label>

                          <input type="radio" id="star2_<?= h($hrow['booking_id']) ?>" name="driver_patient_rating" value="2">
                          <label for="star2_<?= h($hrow['booking_id']) ?>">★</label>

                          <input type="radio" id="star1_<?= h($hrow['booking_id']) ?>" name="driver_patient_rating" value="1">
                          <label for="star1_<?= h($hrow['booking_id']) ?>">★</label>
                        </div>

                        <textarea class="rate-input" name="driver_patient_comment" placeholder="Optional note about the patient"></textarea>
                        <button class="btn primary" type="submit">Submit Rating</button>
                      </form>
                    </div>
                  <?php elseif (!empty(trim((string)$hrow['driver_patient_comment']))): ?>
                    <div class="rated-note">Your note: <?= h($hrow['driver_patient_comment']) ?></div>
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

<div class="footer">© <?= date("Y") ?> RafiQ Driver Portal</div>

<div class="modal" id="mapModal" onclick="closeMap()">
  <div class="modal-card" onclick="event.stopPropagation()">
    <div class="modal-head">
      <div>Trip Map</div>
      <button class="btn" type="button" onclick="closeMap()">Close</button>
    </div>
    <div id="tripMap"></div>
  </div>
</div>

<script>
let mapInstance = null;
let markersLayer = null;

function closeMap(){
  document.getElementById('mapModal').classList.remove('open');
}

function openMap(pLat, pLng, dLat, dLng, pText, dText){
  document.getElementById('mapModal').classList.add('open');

  setTimeout(() => {
    const fallbackLat = 29.976;
    const fallbackLng = 30.949;
    const startLat = (pLat && !isNaN(pLat)) ? pLat : fallbackLat;
    const startLng = (pLng && !isNaN(pLng)) ? pLng : fallbackLng;

    if (!mapInstance) {
      mapInstance = L.map('tripMap').setView([startLat, startLng], 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
      }).addTo(mapInstance);
      markersLayer = L.layerGroup().addTo(mapInstance);
    }

    markersLayer.clearLayers();

    if (pLat && pLng) {
      L.marker([pLat, pLng]).addTo(markersLayer).bindPopup("Pickup<br>" + (pText || "")).openPopup();
    }

    if (dLat && dLng) {
      L.marker([dLat, dLng]).addTo(markersLayer).bindPopup("Destination<br>" + (dText || ""));
    }

    if (pLat && pLng && dLat && dLng) {
      L.polyline([[pLat, pLng], [dLat, dLng]]).addTo(markersLayer);
      mapInstance.fitBounds(L.latLngBounds([[pLat, pLng], [dLat, dLng]]), { padding: [30, 30] });
    } else if (pLat && pLng) {
      mapInstance.setView([pLat, pLng], 14);
    } else {
      mapInstance.setView([fallbackLat, fallbackLng], 12);
    }

    mapInstance.invalidateSize();
  }, 120);
}
</script>

</body>
</html>