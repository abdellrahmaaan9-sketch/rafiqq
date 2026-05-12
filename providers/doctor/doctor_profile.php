<?php
// 1. الاتصال بقاعدة البيانات
$conn = pg_connect("host=localhost port=5432 dbname=rafiq user=postgres password=123456789");
if(!$conn) { die("Connection failed: " . pg_last_error()); }

// 2. جلب بيانات الطبيب (بافتراض الـ ID هو 1)
$result = pg_query($conn, "SELECT * FROM doctor WHERE id = 1");
$doctor = pg_fetch_assoc($result);

// لو ملقاش بيانات في الداتابيز، بيعرض البيانات دي كاحتياط
if (!$doctor) {
    $doctor = [
        'first_name' => 'Sara', 'last_name' => 'Mohamed', 'email' => 'sara@gmail.com', 
        'phone' => '01156644220', 'national_id' => '29808121234567', 'dob' => '2004-05-07', 
        'address' => 'El Sheikh Zayed', 'gender' => 'Female', 'speciality' => 'Physical Medicine & Rehabilitation',
        'profile_pic' => 'doctor.jpeg',
        'cv_path' => '',
        'license_path' => ''
    ];
}

// تحديد مسار الصورة الشخصية
$image_src = !empty($doctor['profile_pic']) ? "../pictures/" . $doctor['profile_pic'] : "../pictures/doctor.jpeg";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - Rafiq</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --dark-navy: #32324e; --light-purple: #b1b1ff; --text-gray: #666; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #fff; color: #333; }

        /* --- تحديث الـ Navbar --- */
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 8%; 
            background: #fff; 
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
        
        /* جعل الخط عريض كما في الـ Prototype */
        .nav-links span, .nav-links a { 
            text-decoration: none;
            color: var(--dark-navy);
            cursor: pointer; 
            font-weight: 800; 
            font-size: 16px;
        }

        .logo-img { height: 50px; }

        /* --- المحتوى الرئيسي --- */
        .main-wrapper { display: flex; justify-content: center; padding: 40px 20px; background-color: #fff; }
        .dark-card { 
            background-color: var(--dark-navy); 
            width: 100%; max-width: 1100px; 
            border-radius: 40px; padding: 60px; 
            display: flex; align-items: center; gap: 60px; color: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .profile-sidebar { flex: 1; display: flex; justify-content: center; }
        .avatar-holder { position: relative; width: 280px; height: 280px; }
        .avatar-holder img { width: 100%; height: 100%; border-radius: 50%; border: 5px solid #fff; object-fit: cover; background: #fff; }
        .pencil-btn { position: absolute; bottom: 15px; right: 15px; background: var(--light-purple); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--dark-navy); cursor: pointer; border: 3px solid var(--dark-navy); }
        
        .profile-content { flex: 2; }
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px 40px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        label { color: #fff; font-weight: 600; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="date"] { padding: 12px 15px; border-radius: 12px; border: none; font-size: 14px; outline: none; }
        
        .radio-group { display: flex; gap: 20px; align-items: center; margin-top: 5px; }
        .radio-group label { font-weight: 400; font-size: 13px; display: flex; align-items: center; gap: 5px; color: white; }
        
        .file-input-wrapper { background: #fff; border-radius: 12px; padding: 10px; display: flex; align-items: center; }
        .file-input-wrapper input { color: #333; font-size: 12px; }
        .file-status { color: #b1b1ff; font-size: 11px; font-weight: bold; margin-top: 5px; display: block; }
        
        .action-bar { margin-top: 40px; display: flex; justify-content: center; gap: 25px; }
        .btn { padding: 12px 45px; border-radius: 12px; border: none; font-weight: bold; cursor: pointer; font-size: 15px; transition: 0.3s; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .save-btn { background: #fff; color: var(--dark-navy); }
        .logout-btn { background: #fff; color: #cc0000; }

        /* --- Footer --- */
        footer { margin-top: 50px; padding: 40px; border-top: 1px solid #eee; text-align: center; }
        .footer-logo { height: 35px; margin-bottom: 20px; }
        .footer-links { display: flex; justify-content: center; gap: 30px; margin-bottom: 15px; }
        .footer-links a { text-decoration: none; color: #666; font-size: 14px; font-weight: 500; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <img src="../pictures/rafiq_logo.png" class="logo-img" alt="Logo">
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

    <main class="main-wrapper">
        <form action="update_doctor.php" method="POST" enctype="multipart/form-data" class="dark-card">
            <div class="profile-sidebar">
                <div class="avatar-holder">
                    <img src="<?php echo $image_src; ?>" alt="Doctor">
                    <label for="img-upload" class="pencil-btn"><i class="fa-solid fa-pencil"></i></label>
                    <input type="file" name="profile_pic" id="img-upload" hidden>
                </div>
            </div>

            <div class="profile-content">
                <div class="grid-layout">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="fname" value="<?php echo htmlspecialchars($doctor['first_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo $doctor['dob']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lname" value="<?php echo htmlspecialchars($doctor['last_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($doctor['address']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="radio-group">
                            <label><input type="radio" name="gender" value="Male" <?php if($doctor['gender'] == 'Male') echo 'checked'; ?>> Male</label>
                            <label><input type="radio" name="gender" value="Female" <?php if($doctor['gender'] == 'Female') echo 'checked'; ?>> Female</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Speciality</label>
                        <input type="text" name="speciality" value="<?php echo htmlspecialchars($doctor['speciality']); ?>">
                    </div>
                    <div class="form-group">
                        <label>National ID</label>
                        <input type="text" name="nid" value="<?php echo htmlspecialchars($doctor['national_id']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>CV</label>
                        <div class="file-input-wrapper"><input type="file" name="cv"></div>
                        <?php if(!empty($doctor['cv_path'])): ?>
                            <span class="file-status">✅ Current: <?php echo htmlspecialchars(basename($doctor['cv_path'])); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Medical License</label>
                        <div class="file-input-wrapper"><input type="file" name="license"></div>
                        <?php if(!empty($doctor['license_path'])): ?>
                            <span class="file-status">✅ Current: <?php echo htmlspecialchars(basename($doctor['license_path'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="action-bar">
                    <button type="submit" class="btn save-btn">Save Changes</button>
                    <a href="logout.php" class="btn logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </form>
    </main>

    <footer>
        <img src="../pictures/rafiq_logo.png" class="footer-logo" alt="Logo">
        <div class="footer-links">
            <a href="#">Home</a> 
            <a href="#">Contact</a> 
            <a href="#">FAQs</a>
            <a href="#">Our Story</a>
        </div>
    </footer>
</body>
</html>