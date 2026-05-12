<?php
session_start();
require __DIR__ . '/../pgdb/db.php';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['user_id'])) {
    header("Location: ../general/login.php");
    exit;
}

$products = [
    'Smart Beeping Glasses'    => ['price' => 1200, 'img' => '../pictures/glasses.jpeg', 'desc' => 'Smart glasses that help users detect nearby obstacles with gentle sound alerts for safer movement.'],
    'Emergency Alert Bracelet' => ['price' => 850,  'img' => '../pictures/watch.jpeg',   'desc' => 'A smart bracelet that sends an emergency alert with important details quickly when needed.'],
];

$productName = $_GET['product'] ?? '';
if (!isset($products[$productName])) {
    header("Location: patient_homepage.php");
    exit;
}
$product = $products[$productName];

/* Load patient profile for auto-fill */
$patientData = ['full_name' => '', 'phone' => '', 'address' => ''];
try {
    $stmt = $pdo->prepare("
        SELECT CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS full_name,
               pt.phone, pt.address
        FROM \"user\" u
        LEFT JOIN patient pt ON pt.user_id = u.user_id
        WHERE u.user_id = :uid LIMIT 1
    ");
    $stmt->execute([':uid' => (int)$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $patientData = $row;
} catch (Exception $e) {}

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $fullName  = trim((string)($_POST['full_name'] ?? ''));
    $phone     = trim((string)($_POST['phone'] ?? ''));
    $address   = trim((string)($_POST['address'] ?? ''));
    $payMethod = trim((string)($_POST['payment'] ?? 'cash'));
    $cardHolder = trim((string)($_POST['card_holder'] ?? ''));
    $cardNumber = trim((string)($_POST['card_number'] ?? ''));
    $expiry     = trim((string)($_POST['expiry'] ?? ''));
    $cvv        = trim((string)($_POST['cvv'] ?? ''));

    if ($fullName === '') { $error = 'Please enter your full name.'; }
    elseif ($phone === '') { $error = 'Please enter your phone number.'; }
    elseif ($address === '') { $error = 'Please enter your delivery address.'; }
    elseif ($payMethod === 'visa') {
        $digits = preg_replace('/\D+/', '', $cardNumber);
        if ($cardHolder === '') { $error = 'Please enter the card holder name.'; }
        elseif (strlen($digits) < 12) { $error = 'Please enter a valid card number.'; }
        elseif (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiry)) { $error = 'Please enter a valid expiry (MM/YY).'; }
        elseif (!preg_match('/^[0-9]{3,4}$/', $cvv)) { $error = 'Please enter a valid CVV.'; }
    }

    if ($error === '') {
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buy <?= h($productName) ?> — Rafiq</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{
    --navy:#292b4a; --purple:#353b69; --accent:#6470d2;
    --green:#168653; --green-bg:#eefbf4;
    --red:#b53535; --red-bg:#fff3f3;
    --shadow:0 24px 60px rgba(41,43,74,.14);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:'Nunito',system-ui,sans-serif;
    background:
        radial-gradient(circle at 10% 0%,rgba(100,112,210,.16),transparent 30%),
        linear-gradient(180deg,#f5f6ff,#f2f4fa);
    min-height:100vh;color:#23243a;
}
.wrap{width:min(600px,calc(100% - 32px));margin:0 auto;padding:32px 0 56px}

/* hero */
.prod-hero{
    border-radius:28px;overflow:hidden;margin-bottom:18px;
    background:linear-gradient(135deg,#353b69 0%,#6470d2 100%);
    box-shadow:0 20px 50px rgba(53,59,105,.28);
    display:flex;gap:22px;align-items:center;padding:28px;
}
.prod-img{
    width:110px;height:110px;flex-shrink:0;
    border-radius:20px;background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.2);
    object-fit:contain;padding:12px;
}
.prod-info-title{font-size:22px;font-weight:900;color:#fff;margin-bottom:6px}
.prod-info-desc{font-size:13px;color:rgba(255,255,255,.82);line-height:1.7;margin-bottom:12px}
.prod-price{
    display:inline-block;background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.24);color:#fff;
    font-size:22px;font-weight:900;padding:8px 18px;border-radius:12px;
}

/* panels */
.panel{
    background:#fff;border-radius:26px;
    border:1px solid rgba(100,112,210,.12);
    box-shadow:var(--shadow);padding:24px;margin-bottom:16px;
}
.panel-title{font-size:17px;font-weight:900;color:#20213b;margin-bottom:16px;
    display:flex;align-items:center;gap:8px}
.panel-title-icon{
    width:34px;height:34px;border-radius:12px;
    background:#eef0ff;color:#6470d2;
    display:grid;place-items:center;font-size:16px;flex-shrink:0;
}

/* fields */
.field{margin-bottom:14px}
.field label{display:block;font-size:12px;font-weight:900;color:#4b4e68;
    text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
.input{
    width:100%;border:1.5px solid rgba(100,112,210,.2);
    border-radius:14px;padding:13px 14px;
    font:inherit;font-weight:700;color:#23243a;
    background:#f8f8ff;outline:none;transition:.18s;
}
.input:focus{border-color:#6470d2;background:#fff;box-shadow:0 0 0 4px rgba(100,112,210,.1)}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}

/* payment methods */
.method-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.method-opt{position:relative}
.method-opt input{position:absolute;opacity:0;width:0;height:0}
.method-label{
    display:flex;flex-direction:column;align-items:center;gap:8px;
    padding:18px 14px;border-radius:16px;
    border:2px solid rgba(100,112,210,.14);background:#f8f8ff;
    cursor:pointer;transition:all .18s;font-size:13px;font-weight:800;color:#4a4e6a;
}
.method-label i{font-size:24px}
.method-opt input:checked + .method-label{
    border-color:#6470d2;background:#eef0ff;color:#353b69;
}
.method-label:hover{border-color:#a5b4fc;background:#f5f7ff}

/* card block */
.card-block{display:none;animation:rise .22s ease both}
.card-block.show{display:block}
@keyframes rise{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* fake card */
.fake-card{
    margin-bottom:16px;border-radius:22px;padding:18px;color:#fff;
    min-height:180px;display:flex;flex-direction:column;justify-content:space-between;
    background:linear-gradient(135deg,#17182d 0%,#2c315f 50%,#3f426f 100%);
    box-shadow:0 20px 44px rgba(23,24,45,.26);position:relative;overflow:hidden;
}
.fake-card:before{content:"";position:absolute;inset:0;
    background:linear-gradient(120deg,rgba(255,255,255,.08),transparent 45%);pointer-events:none}
.fc-chip{width:44px;height:32px;border-radius:8px;
    background:linear-gradient(135deg,#d4c8ff,#fff 50%,#9a8fd4)}
.fc-row{position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start}
.fc-brand{font-size:13px;font-weight:900;letter-spacing:1px}
.fc-number{position:relative;z-index:2;font-size:20px;letter-spacing:2px;font-weight:900}
.fc-bottom{position:relative;z-index:2;display:flex;justify-content:space-between}
.fc-cap{font-size:9px;font-weight:900;opacity:.65;letter-spacing:.8px;text-transform:uppercase;margin-bottom:3px}
.fc-val{font-size:13px;font-weight:900}

/* submit */
.submit-btn{
    width:100%;height:54px;border:none;border-radius:18px;
    background:linear-gradient(135deg,#353b69,#6470d2);
    color:#fff;font-size:15px;font-weight:900;font-family:inherit;
    cursor:pointer;box-shadow:0 16px 36px rgba(53,59,105,.26);
    transition:.2s ease;display:flex;align-items:center;justify-content:center;gap:10px;
}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 22px 44px rgba(53,59,105,.32)}

/* alerts */
.alert{border-radius:16px;padding:14px 16px;font-weight:800;font-size:14px;margin-bottom:14px}
.alert.err{background:var(--red-bg);color:var(--red);border:1px solid rgba(181,53,53,.18)}

/* success */
.success-box{text-align:center;padding:48px 24px}
.success-icon{font-size:64px;margin-bottom:18px;animation:pop .5s cubic-bezier(.22,.68,0,1.6) both}
@keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
.success-box h2{font-size:24px;font-weight:900;color:#20213b;margin-bottom:8px}
.success-box p{color:#6b7188;font-size:15px;line-height:1.7;margin-bottom:24px}
.back-btn{
    display:inline-flex;align-items:center;gap:8px;padding:13px 24px;
    border-radius:14px;background:#eef0ff;color:#4b4f83;
    font-weight:800;font-size:14px;text-decoration:none;transition:background .18s;
}
.back-btn:hover{background:#e0e6ff}

@media(max-width:520px){
    .prod-hero{flex-direction:column;text-align:center}
    .row2,.method-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<?php include '../general/nav_patient.php'; ?>

<div class="wrap">

<?php if ($success): ?>
<div class="panel">
    <div class="success-box">
        <div class="success-icon">🎉</div>
        <h2>Order Placed!</h2>
        <p>Thank you for your order. Your <strong><?= h($productName) ?></strong> will be delivered soon.<br>Our team will contact you to confirm.</p>
        <a class="back-btn" href="patient_homepage.php"><i class="fa-solid fa-house"></i> Back to Home</a>
    </div>
</div>

<?php else: ?>

<!-- Product hero -->
<div class="prod-hero">
    <img class="prod-img" src="<?= h($product['img']) ?>" alt="<?= h($productName) ?>">
    <div>
        <div class="prod-info-title"><?= h($productName) ?></div>
        <div class="prod-info-desc"><?= h($product['desc']) ?></div>
        <div class="prod-price"><?= number_format($product['price']) ?> EGP</div>
    </div>
</div>

<form method="POST" novalidate>

<?php if ($error): ?>
<div class="alert err"><i class="fa-solid fa-circle-exclamation"></i> <?= h($error) ?></div>
<?php endif; ?>

<!-- Delivery info -->
<div class="panel">
    <div class="panel-title"><div class="panel-title-icon">📦</div> Delivery Information</div>

    <div class="field">
        <label>Full Name</label>
        <input class="input" type="text" name="full_name"
               value="<?= h($_POST['full_name'] ?? trim((string)$patientData['full_name'])) ?>"
               placeholder="Your full name" required>
    </div>
    <div class="field">
        <label>Phone Number</label>
        <input class="input" type="tel" name="phone"
               value="<?= h($_POST['phone'] ?? trim((string)$patientData['phone'])) ?>"
               placeholder="01XXXXXXXXX" required>
    </div>
    <div class="field">
        <label>Delivery Address</label>
        <input class="input" type="text" name="address"
               value="<?= h($_POST['address'] ?? trim((string)$patientData['address'])) ?>"
               placeholder="Your full delivery address" required>
    </div>
</div>

<!-- Payment method -->
<div class="panel">
    <div class="panel-title"><div class="panel-title-icon">💳</div> Payment Method</div>

    <div class="method-grid">
        <div class="method-opt">
            <input type="radio" name="payment" id="pay-cash" value="cash"
                   <?= (($_POST['payment'] ?? 'cash') === 'cash') ? 'checked' : '' ?>>
            <label class="method-label" for="pay-cash">
                <i class="fa-solid fa-money-bill-wave" style="color:#168653"></i>
                Cash on Delivery
            </label>
        </div>
        <div class="method-opt">
            <input type="radio" name="payment" id="pay-visa" value="visa"
                   <?= (($_POST['payment'] ?? '') === 'visa') ? 'checked' : '' ?>>
            <label class="method-label" for="pay-visa">
                <i class="fa-brands fa-cc-visa" style="color:#3147c7"></i>
                Visa / Card
            </label>
        </div>
    </div>

    <div class="card-block" id="cardBlock">
        <!-- Live card preview -->
        <div class="fake-card">
            <div class="fc-row">
                <div class="fc-chip"></div>
                <div class="fc-brand" id="fcBrand">CARD</div>
            </div>
            <div class="fc-number" id="fcNumber">•••• •••• •••• ••••</div>
            <div class="fc-bottom">
                <div><div class="fc-cap">Card Holder</div><div class="fc-val" id="fcHolder">YOUR NAME</div></div>
                <div><div class="fc-cap">Expiry</div><div class="fc-val" id="fcExpiry">MM/YY</div></div>
            </div>
        </div>

        <div class="field">
            <label>Card Holder Name</label>
            <input class="input" type="text" name="card_holder" id="card_holder"
                   value="<?= h($_POST['card_holder'] ?? '') ?>" placeholder="Name on card">
        </div>
        <div class="field">
            <label>Card Number</label>
            <input class="input" type="text" name="card_number" id="card_number"
                   value="<?= h($_POST['card_number'] ?? '') ?>"
                   placeholder="1234 5678 9012 3456" maxlength="23" inputmode="numeric">
        </div>
        <div class="row2">
            <div class="field">
                <label>Expiry</label>
                <input class="input" type="text" name="expiry" id="expiry"
                       value="<?= h($_POST['expiry'] ?? '') ?>" placeholder="MM/YY" maxlength="5">
            </div>
            <div class="field">
                <label>CVV</label>
                <input class="input" type="password" name="cvv" id="cvv"
                       value="<?= h($_POST['cvv'] ?? '') ?>" placeholder="123" maxlength="4">
            </div>
        </div>
    </div>
</div>

<button class="submit-btn" type="submit" name="confirm">
    <i class="fa-solid fa-bag-shopping"></i>
    Confirm Order — <?= number_format($product['price']) ?> EGP
</button>
</form>

<?php endif; ?>

</div>

<?php include '../general/footer.php'; ?>

<script>
(function(){
    const cashRadio = document.getElementById('pay-cash');
    const visaRadio = document.getElementById('pay-visa');
    const cardBlock = document.getElementById('cardBlock');
    const cardNumber= document.getElementById('card_number');
    const cardHolder= document.getElementById('card_holder');
    const expiry    = document.getElementById('expiry');
    const fcNumber  = document.getElementById('fcNumber');
    const fcHolder  = document.getElementById('fcHolder');
    const fcExpiry  = document.getElementById('fcExpiry');
    const fcBrand   = document.getElementById('fcBrand');

    function toggleCard(){
        cardBlock && cardBlock.classList.toggle('show', visaRadio && visaRadio.checked);
    }
    cashRadio && cashRadio.addEventListener('change', toggleCard);
    visaRadio && visaRadio.addEventListener('change', toggleCard);
    toggleCard();

    function detectBrand(num){
        const d = (num||'').replace(/\D+/g,'');
        if(/^4/.test(d)) return 'VISA';
        if(/^(5[1-5]|2[2-7])/.test(d)) return 'MASTERCARD';
        if(/^3[47]/.test(d)) return 'AMEX';
        return 'CARD';
    }
    function fmtCard(v){
        return (v||'').replace(/\D+/g,'').substring(0,19).replace(/(.{4})/g,'$1 ').trim();
    }
    function fmtExpiry(v){
        const d=(v||'').replace(/\D+/g,'').substring(0,4);
        return d.length<=2 ? d : d.slice(0,2)+'/'+d.slice(2);
    }

    cardNumber && cardNumber.addEventListener('input', function(){
        this.value = fmtCard(this.value);
        if(fcNumber) fcNumber.textContent = this.value || '•••• •••• •••• ••••';
        if(fcBrand) fcBrand.textContent = detectBrand(this.value);
    });
    cardHolder && cardHolder.addEventListener('input', function(){
        if(fcHolder) fcHolder.textContent = (this.value||'YOUR NAME').toUpperCase();
    });
    expiry && expiry.addEventListener('input', function(){
        this.value = fmtExpiry(this.value);
        if(fcExpiry) fcExpiry.textContent = this.value || 'MM/YY';
    });
    if(cardNumber) cardNumber.dispatchEvent(new Event('input'));
    if(cardHolder) cardHolder.dispatchEvent(new Event('input'));
    if(expiry) expiry.dispatchEvent(new Event('input'));
})();
</script>
</body>
</html>
