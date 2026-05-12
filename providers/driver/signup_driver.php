<?php
session_start();

if (
    !isset($_SESSION['step1']) ||
    !isset($_SESSION['step2']) ||
    !isset($_SESSION['provider_type']) ||
    $_SESSION['provider_type'] !== 'driver'
) {
    header("Location: ../signup2_provider.php");
    exit();
}

$conn = pg_connect("host=localhost dbname=rafiq user=postgres password=123456789");
if (!$conn) {
    die("Database connection failed.");
}

$error = "";

if (isset($_POST['finish'])) {

    if (!isset($_FILES['driving_license']) || $_FILES['driving_license']['error'] !== 0) {
        $error = "Please upload your driving license.";
    } else {

        $fname = trim($_SESSION['step1']['fname'] ?? '');
        $lname = trim($_SESSION['step1']['lname'] ?? '');
        $email = trim($_SESSION['step1']['email'] ?? '');
        $pass  = $_SESSION['step1']['pass'] ?? '';

        $address     = trim($_SESSION['step2']['address'] ?? '');
        $gender      = trim($_SESSION['step2']['gender'] ?? '');
        $phone       = trim($_SESSION['step2']['phone'] ?? '');
        $national_id = trim($_SESSION['step2']['national_id'] ?? '');
        $dob         = trim($_SESSION['step2']['dob'] ?? '');
        $cv_path     = trim($_SESSION['step2']['cv'] ?? '');

        if ($fname === '' || $lname === '' || $email === '' || $pass === '') {
            $error = "Missing account data.";
        } elseif ($address === '' || $gender === '' || $phone === '' || $national_id === '' || $dob === '') {
            $error = "Missing personal data.";
        } else {

            pg_query($conn, "BEGIN");

            $insert_user = pg_query_params(
                $conn,
                'INSERT INTO "user" (first_name, last_name, email, password, role)
                 VALUES ($1, $2, $3, $4, $5)
                 RETURNING user_id',
                array($fname, $lname, $email, $pass, 'provider')
            );

            if (!$insert_user) {
                pg_query($conn, "ROLLBACK");
                die("User insert failed: " . pg_last_error($conn));
            }

            $row = pg_fetch_assoc($insert_user);
            $user_id = (int)$row['user_id'];

            if (!is_dir("../../uploads")) {
                mkdir("../../uploads", 0777, true);
            }

            $license_name = uniqid("license_", true) . "_" . basename($_FILES['driving_license']['name']);
            $license_path = "../../uploads/" . $license_name;

            if (!move_uploaded_file($_FILES['driving_license']['tmp_name'], $license_path)) {
                pg_query($conn, "ROLLBACK");
                die("Failed to upload driving license.");
            }

            $insert_provider = pg_query_params(
                $conn,
                'INSERT INTO provider (user_id, national_id, gender, dob, address, phone, cv, status)
                 OVERRIDING SYSTEM VALUE
                 VALUES ($1, $2, $3, $4, $5, $6, $7, \'pending\')',
                array($user_id, $national_id, $gender, $dob, $address, $phone, $cv_path)
            );

            if (!$insert_provider) {
                pg_query($conn, "ROLLBACK");
                die("Provider insert failed: " . pg_last_error($conn));
            }

            $insert_driver = pg_query_params(
                $conn,
                'INSERT INTO driver (user_id, driving_license)
                 OVERRIDING SYSTEM VALUE
                 VALUES ($1, $2)',
                array($user_id, $license_path)
            );

            if (!$insert_driver) {
                pg_query($conn, "ROLLBACK");
                die("Driver insert failed: " . pg_last_error($conn));
            }

            pg_query($conn, "COMMIT");

            $_SESSION['user_id'] = $user_id;
            $_SESSION['driver_id'] = $user_id;
            $_SESSION['driver_name'] = $fname . ' ' . $lname;
            $_SESSION['role'] = 'provider';

            unset($_SESSION['step1'], $_SESSION['step2'], $_SESSION['provider_type']);

            header("Location: ../pending.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rafiq - Driver Sign Up</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#404066;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    padding:20px;
    box-sizing:border-box;
}
.card{
    background:white;
    width:440px;
    max-width:100%;
    padding:40px 38px;
    border-radius:30px;
    box-shadow:0 15px 35px rgba(0,0,0,0.15);
    box-sizing:border-box;
}
.logo,.subtitle,.steps{text-align:center;}
.logo img{width:135px;margin-bottom:10px;}
.subtitle{color:#2B2C41;font-size:14px;margin-bottom:28px;line-height:1.5;}
.steps{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:12px;
    margin-bottom:25px;
}
.circle{
    width:32px;
    height:32px;
    border-radius:50%;
    border:1.6px solid #2B2C41;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:14px;
    background:white;
    color:#2B2C41;
    font-weight:600;
}
.circle.active{background:#D2EBFF;}
.line{width:50px;height:2px;background:#2B2C41;}
h3{
    font-size:22px;
    margin:10px 0 8px;
    color:#2B2C41;
    text-align:center;
}
.question{
    font-size:15px;
    color:#2B2C41;
    margin-bottom:5px;
    font-weight:600;
}
.small{
    font-size:13px;
    color:#777;
    margin-bottom:20px;
}
.file-box{
    width:100%;
    border-radius:14px;
    border:1.5px solid #2B2C41;
    padding:12px;
    margin-bottom:28px;
    background:white;
    box-sizing:border-box;
}
.buttons{
    display:flex;
    justify-content:space-between;
    gap:12px;
}
button{
    width:50%;
    height:45px;
    border:none;
    border-radius:28px;
    background:#2B2C41;
    color:white;
    font-weight:600;
    font-size:14px;
    cursor:pointer;
    transition:0.2s;
}
button:hover{background:#404066;}
.error{
    background:#B53535;
    color:white;
    padding:10px;
    border-radius:12px;
    margin-bottom:15px;
    font-size:13px;
    text-align:center;
}
</style>
</head>
<body>

<div class="card">
    <div class="logo">
        <img src="../../pictures/rafiq_logo.png" alt="Rafiq Logo">
    </div>

    <div class="subtitle">
        Join Rafiq and provide transportation services.<br>
        Tell us about yourself.
    </div>

    <div class="steps">
        <div class="circle">1</div>
        <div class="line"></div>
        <div class="circle">2</div>
        <div class="line"></div>
        <div class="circle active">3</div>
    </div>

    <h3>Almost done!</h3>

    <div class="question">Driving License</div>
    <div class="small">Upload a clear copy of your license</div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="file-box">
            <input type="file" name="driving_license" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>

        <div class="buttons">
            <button type="button" onclick="history.back()">Previous</button>
            <button type="submit" name="finish">Finish</button>
        </div>
    </form>
</div>

</body>
</html>