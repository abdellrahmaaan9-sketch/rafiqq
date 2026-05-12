<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$admins = [
    'admin1@gmail.com' => ['password' => '123456789', 'name' => 'Admin 1'],
    'admin2@gmail.com' => ['password' => '123456789', 'name' => 'Admin 2'],
    'admin3@gmail.com' => ['password' => '123456789', 'name' => 'Admin 3'],
];

$error = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = $_POST['pass'];

    if (isset($admins[$email]) && $admins[$email]['password'] === $pass) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $email;
        $_SESSION['admin_name'] = $admins[$email]['name'];
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rafiq — Admin Login</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:'Segoe UI',Arial,sans-serif;
    background:linear-gradient(135deg,#1e1b4b,#312e81);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}
.card{
    background:#fff;
    border-radius:28px;
    width:420px;
    max-width:100%;
    padding:48px 44px;
    box-shadow:0 32px 80px rgba(10,8,40,.35);
}
.logo{
    text-align:center;
    margin-bottom:8px;
}
.logo img{
    width:130px;
}
.admin-chip{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    background:#eef2ff;
    color:#4338ca;
    font-size:12px;
    font-weight:800;
    padding:5px 14px;
    border-radius:99px;
    width:fit-content;
    margin:0 auto 28px;
    letter-spacing:.04em;
    text-transform:uppercase;
}
.admin-chip .dot{
    width:7px;height:7px;border-radius:50%;background:#4f46e5;
}
h2{
    text-align:center;
    font-size:22px;
    font-weight:900;
    color:#1e1b4b;
    margin-bottom:6px;
}
.sub{
    text-align:center;
    font-size:13px;
    color:#94a3b8;
    margin-bottom:32px;
}
.form-group{
    display:flex;
    flex-direction:column;
    gap:6px;
    margin-bottom:18px;
}
label{
    font-size:12px;
    font-weight:800;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.06em;
}
input{
    padding:12px 16px;
    border-radius:12px;
    border:1.5px solid #f1f5f9;
    font-size:14px;
    color:#1e1b4b;
    outline:none;
    font-family:inherit;
    transition:border-color .15s;
    background:#f8fafc;
}
input:focus{
    border-color:#a5b4fc;
    background:#fff;
}
.btn{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:none;
    background:linear-gradient(135deg,#4f46e5,#312e81);
    color:#fff;
    font-size:15px;
    font-weight:800;
    cursor:pointer;
    font-family:inherit;
    margin-top:8px;
    transition:opacity .15s;
}
.btn:hover{opacity:.9}
.error{
    background:#fef2f2;
    color:#991b1b;
    border:1px solid #fecaca;
    border-radius:12px;
    padding:11px 16px;
    font-size:13px;
    font-weight:600;
    text-align:center;
    margin-bottom:18px;
}
.back-link{
    display:block;
    text-align:center;
    margin-top:22px;
    font-size:13px;
    color:#94a3b8;
    text-decoration:none;
}
.back-link:hover{color:#4f46e5;}
</style>
</head>
<body>

<div class="card">
    <div class="logo">
        <img src="../pictures/rafiq_logo.png" alt="Rafiq">
    </div>

    <div class="admin-chip">
        <div class="dot"></div>
        Admin Panel
    </div>

    <h2>Welcome back</h2>
    <p class="sub">Sign in to manage the Rafiq platform</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="admin@gmail.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="pass" placeholder="Enter password" required>
        </div>
        <button class="btn" type="submit" name="login">Sign in</button>
    </form>

    <a class="back-link" href="../general/login.php">← Back to main login</a>
</div>

</body>
</html>
