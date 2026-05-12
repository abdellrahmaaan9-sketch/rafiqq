<?php
session_start();
require __DIR__ . '/../pgdb/db.php';

function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function only_digits($value){
    return preg_replace('/\D+/', '', (string)$value);
}

/*
|--------------------------------------------------------------------------
| Get current logged-in patient/user id from session
|--------------------------------------------------------------------------
*/
$patient_id = 0;

if (isset($_SESSION['patient_id']) && (int)$_SESSION['patient_id'] > 0) {
    $patient_id = (int)$_SESSION['patient_id'];
} elseif (isset($_SESSION['ID']) && (int)$_SESSION['ID'] > 0) {
    $patient_id = (int)$_SESSION['ID'];
} elseif (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    $patient_id = (int)$_SESSION['user_id'];
}

$booking_id = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

if ($patient_id <= 0) {
    die("User not found in session.");
}

if ($booking_id <= 0) {
    die("Bad request: booking_id missing.");
}

/*
|--------------------------------------------------------------------------
| Step 1: fetch booking by booking_id only
| So we can know if the booking exists at all
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        booking_id,
        patient_id,
        payment_total,
        payment_method,
        payment_state,
        payment_status,
        card_last4,
        card_brand,
        card_holder,
        paid_at
    FROM booking
    WHERE booking_id = :b
    LIMIT 1
");
$stmt->execute([
    ':b' => $booking_id
]);

$bk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bk) {
    die("Booking not found.");
}

/*
|--------------------------------------------------------------------------
| Step 2: verify booking belongs to logged in user
|--------------------------------------------------------------------------
*/
if ((int)$bk['patient_id'] !== $patient_id) {
    die("This booking does not belong to the current user.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $card_holder = trim($_POST['card_holder'] ?? '');
    $card_number = only_digits($_POST['card_number'] ?? '');
    $expiry      = trim($_POST['expiry'] ?? '');
    $cvv         = trim($_POST['cvv'] ?? '');

    if ($action === 'success') {

        if ($card_holder === '') {
            $error = "Please enter card holder name.";
        } elseif (strlen($card_number) < 12 || strlen($card_number) > 19) {
            $error = "Please enter a valid card number.";
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry)) {
            $error = "Please enter expiry date in MM/YY format.";
        } elseif (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
            $error = "Please enter a valid CVV.";
        } else {
            $last4 = substr($card_number, -4);

            // detect card brand simply
            $brand = 'Card';
            if (preg_match('/^4/', $card_number)) {
                $brand = 'Visa';
            } elseif (preg_match('/^5[1-5]/', $card_number)) {
                $brand = 'Mastercard';
            }

            $up = $pdo->prepare("
                UPDATE booking
                SET 
                    payment_method = 'visa',
                    payment_state = 'paid',
                    payment_status = 'success',
                    card_last4 = :last4,
                    card_brand = :brand,
                    card_holder = :holder,
                    paid_at = NOW()
                WHERE booking_id = :b
                  AND patient_id = :p
            ");

            $up->execute([
                ':last4'  => $last4,
                ':brand'  => $brand,
                ':holder' => $card_holder,
                ':b'      => $booking_id,
                ':p'      => $patient_id
            ]);

            header("Location: request_driver.php?sent=1&booking_id=" . urlencode($booking_id) . "&pm=visa");
            exit;
        }
    }

    if ($action === 'fail') {
        $last4 = strlen($card_number) >= 4 ? substr($card_number, -4) : null;

        $up = $pdo->prepare("
            UPDATE booking
            SET 
                payment_method = 'visa',
                payment_state = 'payment_failed',
                payment_status = 'failed',
                card_last4 = :last4,
                card_brand = 'Visa',
                card_holder = :holder
            WHERE booking_id = :b
              AND patient_id = :p
        ");

        $up->execute([
            ':last4'  => $last4,
            ':holder' => $card_holder !== '' ? $card_holder : null,
            ':b'      => $booking_id,
            ':p'      => $patient_id
        ]);

        $error = "Payment could not be completed. Please try again.";

        // reload booking after update
        $stmt->execute([
            ':b' => $booking_id
        ]);
        $bk = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$total = (float)($bk['payment_total'] ?? 0);
$currentLast4 = $bk['card_last4'] ?? '';
$currentHolder = $bk['card_holder'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Card Payment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f4f6fb;
            --panel:#ffffff;
            --text:#1d2340;
            --muted:#7c849c;
            --line:#e7ebf4;
            --primary:#4b4a74;
            --primary-2:#3e3d63;
            --soft:#f8f9fd;
            --danger:#b53535;
            --shadow:0 20px 50px rgba(30, 37, 74, .10);
            --shadow-soft:0 10px 24px rgba(30, 37, 74, .08);
        }

        *{box-sizing:border-box}

        body{
            margin:0;
            min-height:100vh;
            font-family:"Nunito",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
            color:var(--text);
            background:
                radial-gradient(circle at top left, rgba(75,74,116,.08), transparent 22%),
                radial-gradient(circle at bottom right, rgba(72,96,255,.06), transparent 22%),
                var(--bg);
        }

        .page{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:28px;
        }

        .payment-shell{
            width:min(1100px, 100%);
            background:var(--panel);
            border:1px solid var(--line);
            border-radius:32px;
            box-shadow:var(--shadow);
            overflow:hidden;
            display:grid;
            grid-template-columns:460px 1fr;
        }

        .left-side{
            position:relative;
            padding:30px;
            background:
                linear-gradient(180deg, #f8f9ff 0%, #f4f7ff 100%);
            border-right:1px solid var(--line);
        }

        .left-side::before{
            content:"";
            position:absolute;
            top:-80px;
            right:-80px;
            width:220px;
            height:220px;
            border-radius:50%;
            background:radial-gradient(circle, rgba(75,74,116,.10), transparent 65%);
        }

        .logo-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            position:relative;
            z-index:1;
        }

        .brand{
            display:inline-flex;
            align-items:center;
            gap:10px;
            padding:10px 14px;
            border-radius:999px;
            background:#fff;
            border:1px solid #edf0f7;
            box-shadow:var(--shadow-soft);
            font-weight:900;
            font-size:13px;
            color:#2b3154;
        }

        .brand-dot{
            width:10px;
            height:10px;
            border-radius:50%;
            background:linear-gradient(135deg, #4b4a74, #6a70d6);
        }

        .secure-badge{
            font-size:12px;
            font-weight:900;
            color:#5a627f;
            background:#eef2fb;
            border:1px solid #e2e7f3;
            padding:9px 12px;
            border-radius:999px;
        }

        .left-copy{
            position:relative;
            z-index:1;
            margin-top:28px;
        }

        .left-copy h1{
            margin:0;
            font-size:34px;
            line-height:1.05;
            font-weight:900;
            color:#202644;
        }

        .left-copy p{
            margin:10px 0 0;
            color:var(--muted);
            font-weight:700;
            line-height:1.7;
            max-width:340px;
        }

        .card-stage{
            position:relative;
            z-index:1;
            margin-top:28px;
        }

        .payment-card{
            position:relative;
            border-radius:30px;
            min-height:255px;
            padding:24px;
            overflow:hidden;
            color:#fff;
            background:
                radial-gradient(circle at 15% 20%, rgba(255,255,255,.16), transparent 18%),
                radial-gradient(circle at 85% 80%, rgba(255,255,255,.10), transparent 20%),
                linear-gradient(135deg, #21398d 0%, #3650bd 48%, #5b59a6 100%);
            box-shadow:0 24px 45px rgba(37,63,163,.24);
        }

        .payment-card::before{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(120deg, rgba(255,255,255,.12), transparent 32%, rgba(255,255,255,.04) 70%, transparent 100%);
            pointer-events:none;
        }

        .card-head{
            position:relative;
            z-index:1;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:10px;
        }

        .chip{
            width:58px;
            height:42px;
            border-radius:12px;
            background:linear-gradient(135deg, #f7d983, #cf9f2c);
            box-shadow:inset 0 1px 1px rgba(255,255,255,.22);
        }

        .contactless{
            margin-top:10px;
            display:inline-flex;
            align-items:center;
            gap:7px;
            padding:7px 10px;
            border-radius:999px;
            background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.16);
            font-size:11px;
            font-weight:900;
        }

        .visa{
            font-size:25px;
            font-weight:900;
            letter-spacing:1.6px;
            opacity:.98;
        }

        .card-number{
            position:relative;
            z-index:1;
            margin:38px 0 28px;
            font-size:30px;
            font-weight:900;
            letter-spacing:2.6px;
        }

        .card-foot{
            position:relative;
            z-index:1;
            display:grid;
            grid-template-columns:1fr auto;
            gap:12px;
            align-items:end;
        }

        .mini-label{
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:1px;
            color:rgba(255,255,255,.72);
            font-weight:800;
            margin-bottom:6px;
        }

        .mini-value{
            font-size:15px;
            font-weight:900;
            color:#fff;
        }

        .right-side{
            padding:32px;
            background:#fff;
        }

        .header-row{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:16px;
            margin-bottom:22px;
        }

        .header-row h2{
            margin:0;
            font-size:30px;
            font-weight:900;
            color:#202644;
        }

        .subtext{
            margin:7px 0 0;
            color:var(--muted);
            font-weight:700;
        }

        .amount-box{
            min-width:180px;
            text-align:right;
            background:var(--soft);
            border:1px solid var(--line);
            border-radius:22px;
            padding:14px 16px;
        }

        .amount-box small{
            display:block;
            margin-bottom:5px;
            color:var(--muted);
            font-weight:800;
        }

        .amount-box strong{
            font-size:25px;
            font-weight:900;
            color:#202644;
        }

        .error-box{
            margin-bottom:16px;
            padding:13px 14px;
            border-radius:16px;
            background:#fdecec;
            color:var(--danger);
            border:1px solid #f0bcbc;
            font-weight:900;
        }

        .section-box{
            background:#fbfcff;
            border:1px solid var(--line);
            border-radius:24px;
            padding:18px;
        }

        .section-title{
            margin:0 0 14px;
            font-size:16px;
            font-weight:900;
            color:#293055;
        }

        .field{
            margin-bottom:14px;
        }

        .field:last-child{
            margin-bottom:0;
        }

        label{
            display:block;
            margin:0 0 8px;
            font-weight:900;
            color:#252a48;
        }

        .input-wrap{
            position:relative;
        }

        .input-icon{
            position:absolute;
            left:14px;
            top:50%;
            transform:translateY(-50%);
            font-size:15px;
            opacity:.55;
            pointer-events:none;
        }

        input{
            width:100%;
            height:54px;
            border-radius:18px;
            border:1.5px solid #d8ddef;
            background:#fff;
            padding:0 16px;
            font-size:15px;
            font-weight:800;
            color:#202644;
            outline:none;
            transition:.18s ease;
        }

        .has-icon input{
            padding-left:44px;
        }

        input:focus{
            border-color:#4b4a74;
            box-shadow:0 0 0 4px rgba(75,74,116,.08);
        }

        .two-col{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:14px;
        }

        .summary{
            margin-top:16px;
            background:var(--soft);
            border:1px solid var(--line);
            border-radius:22px;
            padding:14px 16px;
        }

        .summary-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:10px 0;
            font-weight:800;
            color:#2c3154;
        }

        .summary-row + .summary-row{
            border-top:1px dashed #dde3f0;
        }

        .summary-val{
            font-weight:900;
            color:#202644;
        }

        .btns{
            margin-top:18px;
        }

        .btn{
            width:100%;
            height:56px;
            border:none;
            border-radius:18px;
            font-size:15px;
            font-weight:900;
            cursor:pointer;
            transition:.18s ease;
        }

        .btn:hover{
            transform:translateY(-1px);
        }

        .btn-primary{
            color:#fff;
            background:linear-gradient(135deg, #4b4a74, #3e3d63);
            box-shadow:0 16px 28px rgba(75,74,116,.18);
        }

        .btn-secondary{
            margin-top:10px;
            color:#252949;
            background:#f2f4fb;
            border:1px solid #e0e5f2;
        }

        @media (max-width: 980px){
            .payment-shell{
                grid-template-columns:1fr;
            }

            .left-side{
                border-right:none;
                border-bottom:1px solid var(--line);
            }

            .two-col{
                grid-template-columns:1fr;
            }

            .header-row{
                flex-direction:column;
                align-items:flex-start;
            }

            .amount-box{
                min-width:auto;
                text-align:left;
                width:100%;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="payment-shell">
        <div class="left-side">
            <div class="logo-row">
                <div class="brand">
                    <span class="brand-dot"></span>
                    <span>RafiQ Payment</span>
                </div>
                <div class="secure-badge">Secure</div>
            </div>

            <div class="left-copy">
                <h1>Complete your payment</h1>
                <p>Enter your card details to confirm your booking payment.</p>
            </div>

            <div class="card-stage">
                <div class="payment-card">
                    <div class="card-head">
                        <div>
                            <div class="chip"></div>
                            <div class="contactless">◔ Contactless</div>
                        </div>
                        <div class="visa">VISA</div>
                    </div>

                    <div class="card-number" id="previewNumber">
                        •••• •••• •••• <?= h($currentLast4 !== '' ? $currentLast4 : '0000') ?>
                    </div>

                    <div class="card-foot">
                        <div>
                            <div class="mini-label">Card Holder</div>
                            <div class="mini-value" id="previewHolder"><?= h($currentHolder !== '' ? strtoupper($currentHolder) : 'YOUR NAME') ?></div>
                        </div>
                        <div>
                            <div class="mini-label">Type</div>
                            <div class="mini-value">Visa</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-side">
            <div class="header-row">
                <div>
                    <h2>Card Payment</h2>
                    <div class="subtext">Booking #<?= h($booking_id) ?></div>
                </div>

                <div class="amount-box">
                    <small>Total amount</small>
                    <strong><?= h(number_format($total, 2)) ?> EGP</strong>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error-box"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <input type="hidden" name="booking_id" value="<?= h($booking_id) ?>">

                <div class="section-box">
                    <div class="section-title">Payment details</div>

                    <div class="field">
                        <label>Card holder name</label>
                        <div class="input-wrap has-icon">
                            <span class="input-icon">👤</span>
                            <input type="text" name="card_holder" id="card_holder" placeholder="Name on card" value="<?= h($currentHolder) ?>" maxlength="100" required>
                        </div>
                    </div>

                    <div class="field">
                        <label>Card number</label>
                        <div class="input-wrap has-icon">
                            <span class="input-icon">💳</span>
                            <input type="text" name="card_number" id="card_number" placeholder="4111 1111 1111 1111" maxlength="23" inputmode="numeric" required>
                        </div>
                    </div>

                    <div class="two-col">
                        <div class="field">
                            <label>Expiry date</label>
                            <input type="text" name="expiry" id="expiry" placeholder="MM/YY" maxlength="5" required>
                        </div>

                        <div class="field">
                            <label>CVV</label>
                            <input type="password" name="cvv" id="cvv" placeholder="***" maxlength="4" inputmode="numeric" required>
                        </div>
                    </div>
                </div>

                <div class="summary">
                    <div class="summary-row">
                        <span>Payment method</span>
                        <span class="summary-val">Visa</span>
                    </div>
                    <div class="summary-row">
                        <span>Saved card details</span>
                        <span class="summary-val">Last 4 digits only</span>
                    </div>
                    <div class="summary-row">
                        <span>Status after payment</span>
                        <span class="summary-val">Paid</span>
                    </div>
                </div>

                <div class="btns">
                    <button class="btn btn-primary" type="submit" name="action" value="success">Confirm payment</button>
                    <button class="btn btn-secondary" type="submit" name="action" value="fail">Try again later</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const cardNumber = document.getElementById('card_number');
    const cardHolder = document.getElementById('card_holder');
    const expiry = document.getElementById('expiry');
    const cvv = document.getElementById('cvv');
    const previewNumber = document.getElementById('previewNumber');
    const previewHolder = document.getElementById('previewHolder');

    function formatCardNumber(value){
        return value.replace(/\D+/g, '').slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 ').trim();
    }

    cardNumber.addEventListener('input', function(){
        this.value = formatCardNumber(this.value);
        const digits = this.value.replace(/\D+/g, '');
        const last4 = digits.length >= 4 ? digits.slice(-4) : '0000';
        previewNumber.textContent = '•••• •••• •••• ' + last4;
    });

    cardHolder.addEventListener('input', function(){
        previewHolder.textContent = (this.value.trim() || 'YOUR NAME').toUpperCase();
    });

    expiry.addEventListener('input', function(){
        let v = this.value.replace(/\D+/g, '').slice(0, 4);
        if (v.length >= 3) {
            v = v.slice(0, 2) + '/' + v.slice(2);
        }
        this.value = v;
    });

    cvv.addEventListener('input', function(){
        this.value = this.value.replace(/\D+/g, '').slice(0, 4);
    });
</script>
</body>
</html>