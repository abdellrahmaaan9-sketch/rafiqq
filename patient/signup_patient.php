<?php
session_start();
require __DIR__ . '/../pgdb/db.php'; // adjust path if needed

// Handle form submission
if(isset($_POST['continue'])){

    $fname   = trim($_POST['fname']);
    $lname   = trim($_POST['lname']);
    $email   = trim($_POST['email']);
    $pass    = $_POST['pass'];
    $confirm = $_POST['confirm'];

    if($pass !== $confirm){
        $error = "Passwords do not match!";
    } else {

        $_SESSION['signup'] = [
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email,
            'password' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'patient'
        ];

        header("Location: signup2_patient.php");
        exit();
    }
}

// =============================
// AJAX EMAIL CHECK
// =============================
if(isset($_GET['check_email'])){

    $email = trim($_GET['check_email']);

    $check = pg_query_params(
        $conn,
        "SELECT user_id FROM \"user\" WHERE email = $1 LIMIT 1",
        array($email)
    );

    if(pg_num_rows($check) > 0){
        echo "taken";
    } else {
        echo "available";
    }

    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Rafiq - Patient Sign Up</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* ===== BODY ===== */
body{
    margin:0;
    padding:0;
    font-family:'Segoe UI', sans-serif;
    background:#404066;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    overflow:hidden;
}

/* ===== CARD ===== */
.card{
    background:#FFFFFF;
    width:430px;
    padding:35px 40px;
    border-radius:30px;
}

/* ===== LOGO ===== */
.logo{
    text-align:center;
    margin-bottom:10px;
}
.logo img{
    width:140px;
}

/* ===== TEXT ===== */
.subtitle{
    text-align:center;
    color:#2B2C41;
    font-size:15px;
    line-height:1.4;
}
.subtitle span{
    display:block;
    margin-top:5px;
    font-weight:500;
}

/* ===== STEPS ===== */
.steps{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:15px;
    margin:20px 0 25px;
}
.circle{
    width:32px;
    height:32px;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    font-weight:600;
    border:1.5px solid #2B2C41;
    color:#2B2C41;
    background:#FFFFFF;
}
.circle.active{
    background:#D2EBFF;
}
.line{
    width:55px;
    height:2px;
    background:#2B2C41;
}

/* ===== FORM ===== */
form{
    display:flex;
    flex-direction:column;
    gap:15px;
}
.row{
    display:flex;
    gap:15px;
}
.field{
    flex:1;
    display:flex;
    flex-direction:column;
}

/* ===== LABEL ===== */
label{
    font-size:14px;
    color:#2B2C41;
    margin-bottom:5px;
}

/* ===== INPUT GROUP ===== */
.input-group{
    position:relative;
}
.input-group i{
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    color:#9AA0B4;
    font-size:14px;
}
input{
    width:100%;
    height:42px;
    padding:0 12px 0 38px;
    border-radius:12px;
    border:1.5px solid #2B2C41;
    font-size:14px;
    background:#FFFFFF;
    box-sizing:border-box;
}
input::placeholder{
    color:#B0B3C7;
}
input:focus{
    outline:none;
    border-color:#404066;
}

/* ===== BUTTON ===== */
button{
    margin-top:15px;
    height:45px;
    border:none;
    border-radius:30px;
    background:#404066;
    color:white;
    font-weight:600;
    font-size:15px;
    cursor:pointer;
    transition:0.3s;
}
button:hover{
    background:#2B2C41;
}

/* ===== ERROR ===== */
.error{
    background:#B53535;
    color:white;
    padding:10px;
    border-radius:12px;
    text-align:center;
    margin-bottom:10px;
    font-size:14px;
}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <img src="../pictures/rafiq_logo.png" alt="Rafiq Logo">
    </div>
    <div class="subtitle">
        Let us know how we can assist you on your journey.
        <span class="bold">Tell us about yourself.</span>
    </div>
    <div class="steps">
        <div class="circle active">1</div>
        <div class="line"></div>
        <div class="circle">2</div>
    </div>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST" id="signupForm">

        <div class="row">
            <div class="field">
                <label>First name</label>
                <div class="input-group">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="fname" placeholder="First name" required>
                </div>
            </div>
            <div class="field">
                <label>Last name</label>
                <div class="input-group">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="lname" placeholder="Last name" required>
                </div>
            </div>
        </div>

        <div>
            <label>Email</label>
            <div class="input-group">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="example@gmail.com" required>
            </div>
        </div>

        <div>
            <label>Password</label>
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="pass" placeholder="Must have at least 8 characters" required>
            </div>
        </div>

        <div>
            <label>Confirm password</label>
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="confirm" placeholder="Re-enter your password" required>
            </div>
        </div>

        <button type="submit" name="continue">Continue</button>

    </form>
</div>

<script>
// Check email on blur
document.getElementById('email').addEventListener('blur', function(){
    const emailField = this;
    const email = emailField.value.trim();
    if(email === '') return;

    fetch(`signup_patient.php?check_email=${encodeURIComponent(email)}`)
        .then(res => res.text())
        .then(status => {
            if(status === 'taken'){
                alert('Email already registered! Please use a different email.');
                emailField.value = '';
                emailField.focus();
            }
        });
});
</script>
</body>
</html>
