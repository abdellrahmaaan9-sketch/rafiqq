<?php
session_start();

if(!isset($_SESSION['step1']) || !isset($_SESSION['provider_type'])){
    header("Location: signupprov.php");
    exit();
}

$conn = pg_connect("host=localhost dbname=rafiq user=postgres password=123456789");
if(!$conn){
    die("Database connection failed.");
}

if(isset($_POST['continue'])){

    $address = trim($_POST['address']);
    $gender  = strtolower($_POST['gender']);
    $phone   = trim($_POST['phone']);
    $national_id = trim($_POST['national_id']);
    $dob = $_POST['year']."-".$_POST['month']."-".$_POST['day'];

if(empty($address) || empty($gender) || empty($phone) || empty($national_id)){
        $error = "All fields required.";
    }
    elseif(!isset($_FILES['cv']) || $_FILES['cv']['error'] !== 0){
        $error = "Please upload your CV.";
    }
    else {

        // ✅ CREATE UPLOADS FOLDER IF NOT EXISTS
        if(!is_dir("../uploads")){
            mkdir("../uploads", 0777, true);
        }

        // ✅ MOVE CV FILE NOW (IMPORTANT)
        $cv_name = uniqid() . "_" . basename($_FILES['cv']['name']);
        $cv_path = "../uploads/" . $cv_name;

        if(!move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)){
            die("Failed to upload CV.");
        }

        // ✅ SAVE ONLY PATH IN SESSION
        $_SESSION['step2'] = [
            'address'     => $address,
            'gender'      => $gender,
            'phone'       => $phone,
            'national_id' => $national_id,
            'dob'         => $dob,
            'cv'          => $cv_path
        ];

        if($_SESSION['provider_type'] === 'doctor'){
            header("Location: doctor/signup_doctor.php");
        } 
        elseif($_SESSION['provider_type'] === 'interpreter') {
            header("Location: interpreter/signup_interpreter.php");
        }
        elseif($_SESSION['provider_type'] === 'caregiver'){
           header("Location: caregiver/signup_caregiver.php"); 
        }
        elseif($_SESSION['provider_type'] === 'driver'){
           header("Location: driver/signup_driver.php"); 
        }
        else{
            header("Location: signup2_provider.php");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Rafiq - Provider Details</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ===== CSS UNCHANGED ===== */
html, body{
    margin:0;
    padding:0;
    height:100%;
    font-family:'Segoe UI', sans-serif;
    overflow:hidden; /* remove scroll */
}

body{
    background:#4B4A73;
    display:flex;
    justify-content:center;
    align-items:center;
}

/* CARD */
.card{
    background:#F4F4F7;
    width:650px;
    max-height:90vh;   /* prevent overflow */
    padding:25px 55px; /* slightly reduced */
    border-radius:40px;
    box-sizing:border-box;
}

/* LOGO */
.logo{
    text-align:center;
    margin-bottom:3px;
}

.logo img{
    width:190px;
}

/* SUBTITLE */
.subtitle{
    text-align:center;
    color:#2B2C41;
    font-size:19px;
    margin-bottom:10px;
}

/* STEPS */
.steps{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:15px;
    margin-bottom:10px;
}

.circle{
    width:40px;
    height:40px;
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
    width:90px;
    height:2px;
    background:#3E3D63;
}
h3{
    margin-bottom: 5px;
}

/* FORM */
form{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.row{
    display:flex;
    gap:20px;
    align-items:flex-end;
}


.field{
    flex:1;
    display:flex;
    flex-direction:column;
}

label{
    font-size:17px;
    color:#3E3D63;
    margin-bottom:4px;
    font-weight:600;
}

/* INPUT STYLE */
.input-group{
    position:relative;
}

.input-group i{
    position:absolute;
    left:18px;
    top:50%;
    transform:translateY(-50%);
    color:#8C8FB1;
    font-size:15px;
}

input, select{
    width:100%;
    height:45px;
    padding:0 18px 0 48px;
    border-radius:22px;
    border:1.5px solid #3E3D63;
    font-size:15px;
    background:#FFFFFF;
    box-sizing:border-box;
    outline:none;
    color:#2B2C41;
}

.no-icon{
    padding-left:18px;
}

input::placeholder{
    color:#A3A6C3;
}

input:focus, select:focus{
    border-color:#4B4A73;
}

/* FILE INPUT */
input[type="file"]{
    padding:12px 18px;
    border-radius:22px;
    background:#FFFFFF;
    border:1.5px solid #3E3D63;
    height:auto;
}

/* BUTTONS */
.buttons{
    display:flex;
    justify-content:space-between;
    margin-top:8px;
}

button{
    width:200px;
    height:58px;
    border:none;
    border-radius:35px;
    background:#4B4A73;
    color:white;
    font-weight:600;
    font-size:17px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#37365A;
}

</style>
</head>

<body>
<div class="card">

    <div class="logo">
        <img src="../pictures/rafiq_logo.png">
    </div>

    <div class="subtitle">
        Join Rafiq and provide support to users.
    </div>

    <!-- 3 STEPS -->
    <div class="steps">
        <div class="circle">1</div>
        <div class="line"></div>
        <div class="circle active">2</div>
        <div class="line"></div>
        <div class="circle">3</div>
    </div>

    <h3 style="color:#3E3D63;">Almost done!</h3>

    <form method="POST" enctype="multipart/form-data">

        <!-- Address + Gender -->
        <div class="row">
            <div class="field">
                <label>Address</label>
                <div class="input-group">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="address" placeholder="." required>
                </div>
            </div>

            <div class="field">
                <label>Gender</label>
                <div class="input-group">
                    <i class="fa-regular fa-user"></i>
                        <select name="gender" required>
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Phone -->
            <div class="field">
                <label>Phone number</label>
                <div class="input-group">
                    <i class="fa-solid fa-phone"></i>
                    <input type="text" name="phone" placeholder="01XXXXXXXXX" maxlength="11" required>
                </div>
            </div>

            <!-- National ID -->
            <div class="field">
                <label>National ID</label>
                <div class="input-group">
                    <i class="fa-regular fa-id-card"></i>
                    <input type="text" name="national_id" placeholder="Enter 14 numbers" maxlength="14" required>
                </div>
            </div>
        </div>


        <!-- DOB -->
        <div class="field">
            <label>Date of birth</label>
            <div class="row">
                <select name="month" class="no-icon" required>
                    <option value="">MM</option>
                    <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
                </select>
                <select name="day" class="no-icon" required>
                    <option value="">DD</option>
                    <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
                </select>
                <select name="year" class="no-icon" required>
                    <option value="">YYYY</option>
                    <?php for($y=date("Y");$y>=1950;$y--) echo "<option>$y</option>"; ?>
                </select>
            </div>
        </div>

        <!-- CV -->
        <div class="field">
            <label>Upload your CV</label>
            <input type="file" name="cv" class="no-icon">
        </div>

        <!-- Buttons -->
        <div class="buttons">
            <button type="button" onclick="window.location.href='signup_provider.php'">Previous</button>
            <button type="submit" name="continue">Continue</button>
        </div>

    </form>
</div>
</body>
</html>
