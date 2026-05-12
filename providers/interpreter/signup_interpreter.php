<?php
session_start();

if(!isset($_SESSION['step1']) || !isset($_SESSION['step2']) || $_SESSION['provider_type'] !== 'interpreter'){
    header("Location: ../signup2_provider.php");
    exit();
}

$conn = pg_connect("host=localhost dbname=rafiq user=postgres password=123456789");
if(!$conn){
    die("Database connection failed.");
}

if(isset($_POST['finish'])){

    $languages = trim($_POST['languages']);

    if(empty($languages)){
        $error = "Please select your language.";
    }
    else {

        $fname = $_SESSION['step1']['fname'];
        $lname = $_SESSION['step1']['lname'];
        $email = $_SESSION['step1']['email'];
        $pass  = $_SESSION['step1']['pass'];

        $address     = $_SESSION['step2']['address'];
        $gender      = $_SESSION['step2']['gender'];
        $phone       = $_SESSION['step2']['phone'];
        $national_id = $_SESSION['step2']['national_id'];
        $dob         = $_SESSION['step2']['dob'];
        $cv_path     = $_SESSION['step2']['cv'];

        // Insert into user table
        $insert_user = pg_query_params(
            $conn,
            'INSERT INTO "user" (first_name,last_name,email,password,role)
             VALUES ($1,$2,$3,$4,$5) RETURNING user_id',
            array($fname,$lname,$email,$pass,'provider')
        );

        if(!$insert_user){
            die("User insert failed: " . pg_last_error($conn));
        }

        $row = pg_fetch_assoc($insert_user);
        $user_id = $row['user_id'];

        // Insert into provider table
        $insert_provider = pg_query_params(
            $conn,
            'INSERT INTO provider
            (user_id,national_id,gender,dob,address,phone,cv,status)
             VALUES ($1,$2,$3,$4,$5,$6,$7,\'pending\')',
            array($user_id,$national_id,$gender,$dob,$address,$phone,$cv_path)
        );

        if(!$insert_provider){
            die("Provider insert failed: " . pg_last_error($conn));
        }

        // Insert into interpreter table
        $insert_interpreter = pg_query_params(
            $conn,
            'INSERT INTO interpreter 
            (user_id,languages)
             VALUES ($1,$2)',
            array($user_id,$languages)
        );

        if(!$insert_interpreter){
            die("Interpreter insert failed: " . pg_last_error($conn));
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = 'provider';

        unset($_SESSION['step1'], $_SESSION['step2']);

        header("Location: ../pending.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Rafiq - Interpreter Sign Up</title>

<style>
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#404066;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:white;
    width:440px;
    padding:40px 38px;
    border-radius:30px;
}

.logo,
.subtitle,
.steps{
    text-align:center;
}

.logo img{
    width:135px;
    margin-bottom:10px;
}

.subtitle{
    color:#2B2C41;
    font-size:14px;
    margin-bottom:28px;
    line-height:1.5;
}

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
}

.circle.active{
    background:#D2EBFF;
}

.line{
    width:50px;
    height:2px;
    background:#2B2C41;
}

h3{
    font-size:22px;
    margin:10px 0 8px;
    color:#2B2C41;
}

.question{
    font-size:15px;
    color:#2B22C41;
    margin-bottom:5px;
}

.small{
    font-size:13px;
    color:#777;
    margin-bottom:20px;
}

select{
    width:100%;
    height:46px;
    border-radius:12px;
    border:1.5px solid #2B2C41;
    padding:0 14px;
    font-size:14px;
    margin-bottom:28px;
    background:white;
}

.buttons{
    display:flex;
    justify-content:space-between;
}

button{
    width:47%;
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

button:hover{
    background:#404066;
}

.error{
    background:#B53535;
    color:white;
    padding:10px;
    border-radius:12px;
    margin-bottom:15px;
    font-size:13px;
}
</style>
</head>

<body>

<div class="card">

    <div class="logo">
        <img src="../../pictures/rafiq_logo.png" alt="Rafiq Logo">
    </div>

    <div class="subtitle">
        Join Rafiq and provide support to users.<br>
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

    <div class="question">What languages do you speak?</div>
    <div class="small">Please select your main language</div>

    <?php if(isset($error)) echo "<div class='error'>".htmlspecialchars($error)."</div>"; ?>

    <form method="POST">

        <select name="languages" required>
            <option value="">Select language</option>
            <option>Arabic</option>
            <option>English</option>
            <option>French</option>
            <option>German</option>
            <option>Spanish</option>
            <option>Italian</option>
        </select>

        <div class="buttons">
            <button type="button" onclick="history.back()">Previous</button>
            <button type="submit" name="finish">Finish</button>
        </div>

    </form>

</div>

</body>
</html>