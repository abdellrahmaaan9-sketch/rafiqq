<?php
$conn = pg_connect("host=localhost port=5432 dbname=rafiq user=postgres password=123456789");
$result = pg_query($conn, "SELECT * FROM caregiver WHERE id = 1");
$user = pg_fetch_assoc($result);

if (!$user) {
    $user = ['first_name' => 'Farida', 'last_name' => 'Shaarawy', 'email' => 'farida@gmail.com', 'phone' => '01156644220', 'national_id' => '29808121234567', 'dob' => '2004-05-07', 'address' => 'El sheikh zayed', 'gender' => 'Female', 'shift_preference' => 'Morning', 'profile_pic' => '', 'cv_path' => ''];
}
$image_src = !empty($user['profile_pic']) ? "../pictures/" . $user['profile_pic'] : "../pictures/caregiver_default.jpeg";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Caregiver Profile - Rafiq</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --dark-navy: #32324e; --light-purple: #b1b1ff; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #fff; }

        /* --- Navbar Styling --- */
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 8%; 
        }
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 40px; 
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 35px;
        }

        .nav-links { 
            display: flex; 
            gap: 35px; 
            align-items: center; 
            color: var(--dark-navy); 
        }
        
        /* جعل الخط عريض (Bold) كما في الـ Prototype */
        .nav-links span { 
            cursor: pointer; 
            font-weight: 800; /* تغيير السمك ليكون Bold واضح */
            font-size: 16px;
        }

        .logo-img { height: 50px; }

        /* --- Main Content --- */
        .main-wrapper { display: flex; justify-content: center; padding: 40px 20px; }
        .dark-card { background-color: var(--dark-navy); width: 1050px; border-radius: 40px; padding: 60px; display: flex; align-items: center; gap: 60px; color: white; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .profile-sidebar { flex: 1; display: flex; justify-content: center; }
        .avatar-container { position: relative; width: 280px; height: 280px; }
        .avatar-container img { width: 100%; height: 100%; border-radius: 50%; border: 5px solid #fff; object-fit: cover; background: #fff; }
        .pencil-icon { position: absolute; bottom: 15px; right: 15px; background: var(--light-purple); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--dark-navy); cursor: pointer; border: 3px solid var(--dark-navy); }
        
        .profile-form { flex: 2; display: grid; grid-template-columns: 1fr 1fr; gap: 20px 40px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        label { font-weight: 600; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="date"], select { padding: 12px 15px; border-radius: 12px; border: none; font-size: 14px; outline: none; width: 100%; box-sizing: border-box; }
        
        .cv-box { background: white; border-radius: 12px; padding: 10px; display: flex; flex-direction: column; gap: 5px; }
        .cv-box input { color: #333; }
        .saved-file { color: #2e7d32; font-size: 11px; font-weight: bold; background: #e8f5e9; padding: 4px 8px; border-radius: 6px; display: inline-block; margin-top: 5px; }

        .footer-actions { grid-column: span 2; display: flex; justify-content: center; gap: 25px; margin-top: 30px; }
        .btn { padding: 12px 45px; border-radius: 12px; border: none; font-weight: bold; cursor: pointer; font-size: 15px; transition: 0.3s; }
        .btn-save { background: #fff; color: var(--dark-navy); }
        .btn-logout { background: #fff; color: #cc0000; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        
        footer { text-align: center; padding: 40px; margin-top: 40px; border-top: 1px solid #eee; }
        .footer-links { display: flex; justify-content: center; gap: 30px; margin-top: 15px; }
        .footer-links a { text-decoration: none; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <img src="../pictures/rafiq_logo.png" class="logo-img">
            <div class="nav-links">
                <span>Home</span>
                <span>Services</span>
            </div>
        </div>

        <div class="nav-right">
            <div class="nav-links">
                <span>Map</span>
            </div>
            <i class="fa-regular fa-user" style="font-size: 24px; color: var(--dark-navy); margin-left: 20px;"></i>
        </div>
    </header>

    <div class="main-wrapper">
        <form action="update_caregiver.php" method="POST" enctype="multipart/form-data" class="dark-card">
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <img src="<?php echo $image_src; ?>">
                    <label for="pic-upload" class="pencil-icon"><i class="fa-solid fa-pencil"></i></label>
                    <input type="file" id="pic-upload" name="pic" hidden>
                </div>
            </div>
            <div class="profile-form">
                <div class="input-group"><label>First Name</label><input type="text" name="fname" value="<?php echo $user['first_name']; ?>"></div>
                <div class="input-group"><label>Date of Birth</label><input type="date" name="dob" value="<?php echo $user['dob']; ?>"></div>
                <div class="input-group"><label>Last Name</label><input type="text" name="lname" value="<?php echo $user['last_name']; ?>"></div>
                <div class="input-group"><label>Address</label><input type="text" name="address" value="<?php echo $user['address']; ?>"></div>
                <div class="input-group"><label>Email</label><input type="email" name="email" value="<?php echo $user['email']; ?>"></div>
                <div class="input-group">
                    <label>Gender</label>
                    <div style="display:flex; gap:20px;">
                        <label><input type="radio" name="gender" value="Male" <?php if($user['gender']=='Male') echo 'checked'; ?>> Male</label>
                        <label><input type="radio" name="gender" value="Female" <?php if($user['gender']=='Female') echo 'checked'; ?>> Female</label>
                    </div>
                </div>
                <div class="input-group"><label>Phone Number</label><input type="text" name="phone" value="<?php echo $user['phone']; ?>"></div>
                <div class="input-group"><label>Shift Preference</label>
                    <select name="shift">
                        <option value="Morning" <?php if($user['shift_preference']=='Morning') echo 'selected'; ?>>Morning</option>
                        <option value="Night" <?php if($user['shift_preference']=='Night') echo 'selected'; ?>>Night</option>
                    </select>
                </div>
                <div class="input-group"><label>National ID</label><input type="text" name="nid" value="<?php echo $user['national_id']; ?>"></div>
                <div class="input-group">
                    <label>CV</label>
                    <div class="cv-box">
                        <input type="file" name="cv">
                        <?php if(!empty($user['cv_path'])): ?>
                            <div class="saved-file"><i class="fa-solid fa-circle-check"></i> Saved: <?php echo $user['cv_path']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-actions">
                    <button type="submit" class="btn btn-save">Save Changes</button>
                    <a href="logout.php" class="btn btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </form>
    </div>

    <footer>
        <img src="../pictures/rafiq_logo.png" height="30">
        <div class="footer-links"><a href="#">Home</a> <a href="#">Contact</a> <a href="#">FAQs</a> <a href="#">Our Story</a></div>
    </footer>
</body>
</html>