<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/
$host = "localhost";
$db   = "rafiq";
$user = "postgres";
$pass = "123456789";

try {
    $pdo = new PDO(
        "pgsql:host=$host;dbname=$db",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| Check booking session
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["booking_id"]) || empty($_SESSION["booking_id"])) {
    die("No booking found. Please book a service first.");
}

$booking_id = (int)$_SESSION["booking_id"];

/*
|--------------------------------------------------------------------------
| Load booking data
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM booking
    WHERE booking_id = :id
    LIMIT 1
");
$stmt->execute([":id" => $booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Handle payment submit
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $payment_method = $_POST["payment_method"] ?? "";

    $allowedMethods = ["Card", "Cash"];
    if (!in_array($payment_method, $allowedMethods, true)) {
        $error = "Please select a valid payment method.";
    } else {
        // Better business logic:
        // Cash = Pending
        // Card = Paid (for demo only)
        $payment_status = ($payment_method === "Cash") ? "Pending" : "Paid";

        try {
            $update = $pdo->prepare("
                UPDATE booking
                SET payment_method = :payment_method,
                    payment_status = :payment_status
                WHERE booking_id = :booking_id
            ");

            $update->execute([
                ":payment_method" => $payment_method,
                ":payment_status" => $payment_status,
                ":booking_id"     => $booking_id
            ]);

            $success = ($payment_method === "Cash")
                ? "Booking confirmed. Payment status is Pending (Cash)."
                : "Payment completed successfully.";

            // Optional: clear session booking after success
            // unset($_SESSION["booking_id"]);

            header("refresh:2;url=doc_service.php");
        } catch (Exception $e) {
            $error = "Failed to update payment. " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment | Rafiq</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
    --primary:#4b4a7d;
    --primary-dark:#39385f;
    --bg:#f6f7fc;
    --card:#ffffff;
    --text:#2d2d44;
    --muted:#7a7c92;
    --line:#eaecf4;
    --success:#1f9d55;
    --success-bg:#e9f9ef;
    --danger:#d93025;
    --danger-bg:#fff1f1;
    --shadow:0 14px 35px rgba(52,56,109,.10);
}

*{box-sizing:border-box}

body{
    margin:0;
    font-family:'Poppins',sans-serif;
    background:linear-gradient(180deg,#fbfcff 0%, #f3f5fb 100%);
    color:var(--text);
}

.page{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:30px 16px;
}

.wrapper{
    width:min(1100px, 100%);
    display:grid;
    grid-template-columns:1fr 420px;
    gap:24px;
}

.left-card,
.right-card{
    background:var(--card);
    border-radius:28px;
    box-shadow:var(--shadow);
    border:1px solid var(--line);
}

.left-card{
    padding:34px;
}

.right-card{
    padding:28px;
    align-self:start;
}

.top-badge{
    display:inline-block;
    padding:8px 14px;
    border-radius:999px;
    background:#f1f2fb;
    color:var(--primary);
    font-size:12px;
    font-weight:700;
    margin-bottom:16px;
}

h1{
    margin:0 0 10px;
    font-size:34px;
    color:#262640;
    line-height:1.15;
}

.subtext{
    margin:0 0 28px;
    color:var(--muted);
    font-size:14px;
    line-height:1.8;
}

.alert{
    padding:14px 16px;
    border-radius:14px;
    margin-bottom:18px;
    font-size:14px;
    font-weight:600;
}

.alert.error{
    background:var(--danger-bg);
    color:var(--danger);
    border:1px solid #ffd1d1;
}

.alert.success{
    background:var(--success-bg);
    color:var(--success);
    border:1px solid #ccefd8;
}

.method-title{
    font-size:22px;
    font-weight:800;
    margin-bottom:14px;
    color:#2c2d46;
}

.method-subtitle{
    color:var(--muted);
    font-size:14px;
    margin-bottom:22px;
}

.methods{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-bottom:22px;
}

.method-card{
    position:relative;
    border:2px solid #e6e8f2;
    border-radius:20px;
    padding:20px;
    cursor:pointer;
    transition:.2s;
    background:#fff;
}

.method-card:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(0,0,0,.06);
}

.method-card.active{
    border-color:var(--primary);
    background:#f6f5ff;
    box-shadow:0 0 0 4px rgba(75,74,125,.08);
}

.method-card input{
    position:absolute;
    opacity:0;
    pointer-events:none;
}

.method-name{
    font-size:17px;
    font-weight:700;
    margin-bottom:6px;
    color:#2f3150;
}

.method-desc{
    font-size:13px;
    color:var(--muted);
    line-height:1.6;
}

.card-fields{
    display:none;
    margin-top:10px;
    padding:20px;
    border-radius:18px;
    background:#fafbff;
    border:1px solid #ebedf6;
}

.field{
    margin-bottom:14px;
}

.field:last-child{
    margin-bottom:0;
}

.field label{
    display:block;
    margin-bottom:8px;
    font-size:13px;
    font-weight:700;
    color:#55576f;
}

.field input{
    width:100%;
    padding:14px 15px;
    border:1px solid #dde2ef;
    border-radius:14px;
    outline:none;
    font-size:14px;
    font-family:'Poppins',sans-serif;
}

.field input:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 4px rgba(75,74,125,.08);
}

.row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.note{
    margin-top:12px;
    color:var(--muted);
    font-size:12px;
    line-height:1.7;
}

.submit-btn{
    width:100%;
    margin-top:24px;
    border:none;
    border-radius:18px;
    padding:17px;
    background:linear-gradient(135deg,#4b4a7d,#3b3a63);
    color:white;
    font-size:16px;
    font-weight:800;
    cursor:pointer;
    box-shadow:0 14px 25px rgba(75,74,125,.22);
}

.submit-btn:hover{
    transform:translateY(-1px);
}

.summary-title{
    font-size:20px;
    font-weight:800;
    margin:0 0 18px;
    color:#2c2d46;
}

.summary-box{
    background:#f8f9fe;
    border:1px solid #eceef7;
    border-radius:20px;
    padding:18px;
    margin-bottom:14px;
}

.summary-label{
    font-size:12px;
    font-weight:700;
    color:var(--muted);
    text-transform:uppercase;
    margin-bottom:6px;
}

.summary-value{
    font-size:15px;
    font-weight:600;
    color:#2f3150;
    line-height:1.7;
}

.total-box{
    margin-top:20px;
    background:linear-gradient(135deg,#4b4a7d,#5d5ab0);
    color:#fff;
    border-radius:22px;
    padding:22px;
}

.total-label{
    font-size:13px;
    opacity:.88;
    margin-bottom:8px;
    font-weight:600;
}

.total-amount{
    font-size:34px;
    font-weight:800;
}

.back-link{
    display:inline-block;
    margin-top:18px;
    text-decoration:none;
    color:var(--primary);
    font-weight:700;
    font-size:14px;
}

@media (max-width: 900px){
    .wrapper{
        grid-template-columns:1fr;
    }

    .methods,
    .row{
        grid-template-columns:1fr;
    }

    .left-card,
    .right-card{
        padding:22px;
    }

    h1{
        font-size:28px;
    }
}
</style>
</head>
<body>
<?php include '../general/nav_patient.php'; ?>

<div class="page">
    <div class="wrapper">

        <div class="left-card">
            <div class="top-badge">Secure Checkout</div>
            <h1>Complete your payment</h1>
            <p class="subtext">
                Review your booking details and choose how you would like to pay.
            </p>

            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= e($success) ?> Redirecting...</div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="method-title">Payment Method</div>
                <div class="method-subtitle">
                    Select one of the available payment options below.
                </div>

                <div class="methods">
                    <label class="method-card" onclick="selectMethod(this, 'Card')">
                        <input type="radio" name="payment_method" value="Card" required>
                        <div class="method-name">💳 Credit / Debit Card</div>
                        <div class="method-desc">Pay now using your card.</div>
                    </label>

                    <label class="method-card" onclick="selectMethod(this, 'Cash')">
                        <input type="radio" name="payment_method" value="Cash" required>
                        <div class="method-name">💵 Cash</div>
                        <div class="method-desc">Pay later in cash.</div>
                    </label>
                </div>

                <div class="card-fields" id="cardFields">
                    <div class="field">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>

                    <div class="row">
                        <div class="field">
                            <label for="expiry">Expiry Date</label>
                            <input type="text" id="expiry" placeholder="MM/YY" maxlength="5">
                        </div>

                        <div class="field">
                            <label for="cvc">CVC</label>
                            <input type="text" id="cvc" placeholder="123" maxlength="4">
                        </div>
                    </div>

                    <div class="note">
                        Demo form only. For real payments, connect a secure payment gateway and never store raw card data in your database.
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    Confirm Payment
                </button>
            </form>
        </div>

        <div class="right-card">
            <h2 class="summary-title">Booking Summary</h2>

            <div class="summary-box">
                <div class="summary-label">Patient Name</div>
                <div class="summary-value"><?= e($booking["fullname"] ?? "") ?></div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Booking Date</div>
                <div class="summary-value"><?= e($booking["date"] ?? "") ?></div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Time</div>
                <div class="summary-value">
                    <?= e($booking["booking_time"] ?? "") ?> → <?= e($booking["service_time"] ?? "") ?>
                </div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Address</div>
                <div class="summary-value"><?= e($booking["address"] ?? "") ?></div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Destination</div>
                <div class="summary-value"><?= e($booking["destination"] ?? "") ?></div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Doctor ID</div>
                <div class="summary-value"><?= e($booking["provider_id"] ?? "") ?></div>
            </div>

            <div class="total-box">
                <div class="total-label">Total Payment</div>
                <div class="total-amount">EGP <?= e($booking["payment_total"] ?? 0) ?></div>
            </div>

            <a href="doc_service.php" class="back-link">← Back to booking</a>
        </div>

    </div>
</div>

<script>
function selectMethod(element, type){
    document.querySelectorAll('.method-card').forEach(card => card.classList.remove('active'));
    element.classList.add('active');

    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;

    const cardFields = document.getElementById('cardFields');
    if(type === 'Card'){
        cardFields.style.display = 'block';
    } else {
        cardFields.style.display = 'none';
    }
}

document.getElementById('card_number').addEventListener('input', function(e){
    let value = e.target.value.replace(/\D/g, '').substring(0, 16);
    value = value.replace(/(.{4})/g, '$1 ').trim();
    e.target.value = value;
});

document.getElementById('expiry').addEventListener('input', function(e){
    let value = e.target.value.replace(/\D/g, '').substring(0, 4);
    if(value.length >= 3){
        value = value.substring(0,2) + '/' + value.substring(2);
    }
    e.target.value = value;
});

document.getElementById('cvc').addEventListener('input', function(e){
    e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
});
</script>

<?php include '../general/footer.php'; ?>

</body>
</html>