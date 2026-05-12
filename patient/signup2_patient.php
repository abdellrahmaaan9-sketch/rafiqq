<?php
session_start();
require __DIR__ . '/../pgdb/db.php';

// للتطوير فقط — شيله في الإنتاج
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['signup']) || !is_array($_SESSION['signup'])) {
    header("Location: signup_patient.php");
    exit();
}

$error = "";

if (isset($_POST['finish'])) {

    $address    = trim($_POST['address'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $disability = trim($_POST['disability'] ?? '');

    $month = (int)($_POST['month'] ?? 0);
    $day   = (int)($_POST['day'] ?? 0);
    $year  = (int)($_POST['year'] ?? 0);

    $signup = $_SESSION['signup'];

    // تأكد إن بيانات التسجيل الأساسية موجودة
    if (
        empty($signup['fname']) ||
        empty($signup['lname']) ||
        empty($signup['email']) ||
        empty($signup['password']) ||
        empty($signup['role'])
    ) {
        $error = "Signup session is incomplete. Please sign up again.";
    }

    // Address validation
    elseif (empty($address)) {
        $error = "Address is required.";
    }

    // Gender validation
    elseif (!in_array($gender, ['male', 'female'], true)) {
        $error = "Please select a valid gender.";
    }

    // Phone validation: exactly 11 digits
    elseif (!preg_match('/^[0-9]{11}$/', $phone)) {
        $error = "Phone number must be exactly 11 digits.";
    }

    // Egyptian phone validation
    elseif (!preg_match('/^01[0125][0-9]{8}$/', $phone)) {
        $error = "Enter a valid Egyptian phone number.";
    }

    // Disability validation
    elseif (
        !in_array($disability, [
            'Physical disability',
            'Visual impairment',
            'Hearing impairment',
            'Intellectual disability'
        ], true)
    ) {
        $error = "Please select a valid disability type.";
    }

    // Date validation
    elseif (!checkdate($month, $day, $year)) {
        $error = "Invalid date of birth.";
    }

    else {
        $dob = sprintf('%04d-%02d-%02d', $year, $month, $day);

        try {
            $pdo->beginTransaction();

            // Insert into user table
            $stmt1 = $pdo->prepare('
                INSERT INTO public."user" (first_name, last_name, email, password, role)
                VALUES (:first_name, :last_name, :email, :password, :role)
                RETURNING user_id
            ');

            $stmt1->execute([
                ':first_name' => $signup['fname'],
                ':last_name'  => $signup['lname'],
                ':email'      => $signup['email'],
                ':password'   => $signup['password'], // hashed password
                ':role'       => $signup['role']
            ]);

            $user_id = $stmt1->fetchColumn();

            if (!$user_id) {
                throw new Exception("Failed to create user account.");
            }

            // Insert into patient table
            $stmt2 = $pdo->prepare('
                INSERT INTO public.patient (user_id, disability, phone, address, gender, dob)
                VALUES (:user_id, :disability, :phone, :address, :gender, :dob)
            ');

            $stmt2->execute([
                ':user_id'    => $user_id,
                ':disability' => $disability,
                ':phone'      => $phone,
                ':address'    => $address,
                ':gender'     => $gender,
                ':dob'        => $dob
            ]);

            $pdo->commit();

            unset($_SESSION['signup']);
            $_SESSION['user_id'] = $user_id;

            header("Location: terms.php");
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // للتطوير: اعرض الخطأ الحقيقي
            $error = $e->getMessage();

            // للإنتاج استخدم السطر ده بدل اللي فوق:
            // $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rafiq - Patient Details</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
html, body{
    margin:0;
    padding:0;
    height:100%;
    overflow:hidden;
    font-family:'Segoe UI', sans-serif;
}

body{
    background:#4B4A73;
    display:flex;
    justify-content:center;
    align-items:center;
}

.card{
    background:#FFFFFF;
    width:600px;
    max-width:95%;
    max-height:95vh;
    padding:35px 55px;
    border-radius:35px;
    box-sizing:border-box;
    overflow-y:auto;
}

.logo{
    text-align:center;
    margin-bottom:2px;
}

.logo img{
    width:170px;
    max-width:100%;
}

.subtitle{
    text-align:center;
    color:#2B2C41;
    font-size:18px;
    margin-bottom:15px;
}

.steps{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:18px;
    margin-bottom:25px;
}

.circle{
    width:38px;
    height:38px;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    font-weight:600;
    border:1.5px solid #3E3D63;
    background:#FFFFFF;
    color:#3E3D63;
}

.circle.active{
    background:#CFE6FF;
}

.line{
    width:80px;
    height:2px;
    background:#3E3D63;
}

form{
    display:flex;
    flex-direction:column;
    gap:18px;
}

.row{
    display:flex;
    gap:20px;
}

.field{
    flex:1;
    display:flex;
    flex-direction:column;
}

label{
    font-size:16px;
    color:#3E3D63;
    margin-bottom:5px;
    font-weight:500;
}

.input-group{
    position:relative;
}

.input-group i{
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color:#8C8FB1;
    font-size:15px;
}

input, select{
    width:100%;
    height:48px;
    padding:0 16px 0 45px;
    border-radius:18px;
    border:1.5px solid #3E3D63;
    font-size:15px;
    background:#FFFFFF;
    box-sizing:border-box;
    outline:none;
    color:#2B2C41;
}

select{
    appearance:none;
    background:#FFFFFF;
}

.no-icon{
    padding-left:16px;
}

input::placeholder{
    color:#A3A6C3;
}

input:focus, select:focus{
    border-color:#4B4A73;
}

.buttons{
    display:flex;
    justify-content:space-between;
    gap:15px;
    margin-top:8px;
}

button{
    width:180px;
    height:55px;
    border:none;
    border-radius:30px;
    background:#4B4A73;
    color:white;
    font-weight:600;
    font-size:16px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#37365A;
}

.error{
    background:#FFE8E8;
    color:#B00020;
    border:1px solid #F3B5B5;
    padding:12px 15px;
    border-radius:14px;
    margin-bottom:15px;
    font-size:14px;
}

@media (max-width: 768px){
    .card{
        padding:25px 20px;
        border-radius:20px;
    }

    .row{
        flex-direction:column;
        gap:15px;
    }

    .buttons{
        flex-direction:column;
        align-items:center;
    }

    button{
        width:100%;
    }

    .line{
        width:40px;
    }
}
</style>
</head>

<body>
<div class="card">

    <div class="logo">
        <img src="../pictures/rafiq_logo.png" alt="Rafiq Logo">
    </div>

    <div class="subtitle">
        Tell us about yourself.
    </div>

    <div class="steps">
        <div class="circle">1</div>
        <div class="line"></div>
        <div class="circle active">2</div>
    </div>

    <h3 style="color:#3E3D63; margin-bottom:8px;">Almost done!</h3>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="row">
            <div class="field">
                <label>Address</label>
                <div class="input-group">
                    <i class="fa-solid fa-location-dot"></i>
                    <input
                        type="text"
                        name="address"
                        placeholder="Enter your address"
                        value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                        required
                    >
                </div>
            </div>

            <div class="field">
                <label>Gender</label>
                <div class="input-group">
                    <i class="fa-regular fa-user"></i>
                    <select name="gender" required>
                        <option value="">Select gender</option>
                        <option value="male" <?php echo (($_POST['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>male</option>
                        <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>female</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="field">
            <label>Phone number</label>
            <div class="input-group">
                <i class="fa-solid fa-phone"></i>
                <input
                    type="tel"
                    name="phone"
                    placeholder="01XXXXXXXXX"
                    maxlength="11"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                    required
                >
            </div>
        </div>

        <div class="field">
            <label>Date of birth</label>
            <div class="row">
                <select name="month" class="no-icon" required>
                    <option value="">MM</option>
                    <?php for ($m = 1; $m <= 12; $m++): 
                        $value = str_pad($m, 2, '0', STR_PAD_LEFT);
                    ?>
                        <option value="<?php echo $value; ?>" <?php echo (($_POST['month'] ?? '') === $value) ? 'selected' : ''; ?>>
                            <?php echo $m; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select name="day" class="no-icon" required>
                    <option value="">DD</option>
                    <?php for ($d = 1; $d <= 31; $d++): 
                        $value = str_pad($d, 2, '0', STR_PAD_LEFT);
                    ?>
                        <option value="<?php echo $value; ?>" <?php echo (($_POST['day'] ?? '') === $value) ? 'selected' : ''; ?>>
                            <?php echo $d; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select name="year" class="no-icon" required>
                    <option value="">YYYY</option>
                    <?php for ($y = date("Y"); $y >= 1950; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo (($_POST['year'] ?? '') == $y) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label>Disability type</label>
            <select name="disability" class="no-icon" required>
                <option value="">Select disability type</option>
                <option value="Physical disability" <?php echo (($_POST['disability'] ?? '') === 'Physical disability') ? 'selected' : ''; ?>>Physical disability</option>
                <option value="Visual impairment" <?php echo (($_POST['disability'] ?? '') === 'Visual impairment') ? 'selected' : ''; ?>>Visual impairment</option>
                <option value="Hearing impairment" <?php echo (($_POST['disability'] ?? '') === 'Hearing impairment') ? 'selected' : ''; ?>>Hearing impairment</option>
                <option value="Intellectual disability" <?php echo (($_POST['disability'] ?? '') === 'Intellectual disability') ? 'selected' : ''; ?>>Intellectual disability</option>
            </select>
        </div>

        <div class="buttons">
            <button type="button" onclick="window.location.href='signup_patient.php'">Previous</button>
            <button type="submit" name="finish">Finish</button>
        </div>

    </form>
</div>
</body>
</html>