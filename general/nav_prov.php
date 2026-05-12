<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['provider_type'])) {
    header("Location: ../../general/login.php");
    exit();
}

$providerType = $_SESSION['provider_type'];

$home_link    = '../../general/login.php';
$profile_link = '../../general/login.php';

if ($providerType === 'doctor') {
    $home_link    = '../../providers/doctor/doctor_homepage.php';
    $profile_link = '../../providers/doctor/doctor_profile.php';
} elseif ($providerType === 'interpreter') {
    $home_link    = '../../providers/interpreter/int_homepage.php';
    $profile_link = '../../providers/interpreter/int_homepage.php';
} elseif ($providerType === 'driver') {
    $home_link    = '../../providers/driver/driver_portal.php';
    $profile_link = '../../providers/driver/driver_portal.php';
} elseif ($providerType === 'caregiver') {
    $home_link    = '../../providers/caregiver/caregiver_homepage.php';
    $profile_link = '../../providers/caregiver/caregiver_profile.php';
}
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
.provider-navbar {
    position: sticky;
    top: 0;
    z-index: 1000;
    width: 100%;
    height: 68px;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 36px;
    box-sizing: border-box;
    border-bottom: 1px solid rgba(36,39,66,0.08);
    box-shadow: 0 2px 16px rgba(36,39,66,0.06);
    transition: box-shadow 0.2s;
}
.pnav-left {
    display: flex;
    align-items: center;
    gap: 32px;
}
.pnav-left img {
    height: 40px;
    cursor: pointer;
}
.pnav-links {
    display: flex;
    gap: 28px;
}
.pnav-links a {
    text-decoration: none;
    font-size: 15px;
    font-family: 'Segoe UI', sans-serif;
    color: #2B2C41;
    font-weight: 600;
    padding: 6px 2px;
    border-bottom: 2px solid transparent;
    transition: color 0.18s, border-color 0.18s;
}
.pnav-links a:hover {
    color: #4b4f83;
    border-bottom-color: #4b4f83;
}
.pnav-right {
    display: flex;
    align-items: center;
    gap: 20px;
}
.pnav-right a {
    text-decoration: none;
    font-size: 15px;
    font-family: 'Segoe UI', sans-serif;
    color: #2B2C41;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color 0.18s;
}
.pnav-right a:hover { color: #4b4f83; }
.pnav-right i { font-size: 20px; }
.pnav-logout { color: #b53535 !important; }
.pnav-logout:hover { color: #8b1a1a !important; }
.pnav-provider-chip {
    background: #eef2ff;
    color: #4b4f83;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 99px;
    text-transform: capitalize;
    letter-spacing: 0.03em;
}
.pnav-sl-pill {
    display: flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
    color: #5a3e8a;
    background: #f3eeff;
    border: 1.5px solid rgba(140,90,210,.20);
    padding: 7px 15px;
    border-radius: 12px;
    transition: background .18s, border-color .18s, color .18s;
}
.pnav-sl-pill:hover {
    background: #ebe0ff;
    border-color: rgba(140,90,210,.40);
    color: #3b1f6a;
}
.pnav-ocr-pill {
    display: flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
    color: #0d6e64;
    background: #e8faf8;
    border: 1.5px solid rgba(13,122,110,.20);
    padding: 7px 15px;
    border-radius: 12px;
    transition: background .18s, border-color .18s, color .18s;
}
.pnav-ocr-pill:hover {
    background: #d0f4f0;
    border-color: rgba(13,122,110,.40);
    color: #074f48;
}
.pnav-vc-pill {
    display: flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
    color: #3730a3;
    background: #eef2ff;
    border: 1.5px solid rgba(79,70,229,.20);
    padding: 7px 15px;
    border-radius: 12px;
    transition: background .18s, border-color .18s, color .18s;
}
.pnav-vc-pill:hover {
    background: #e0e7ff;
    border-color: rgba(79,70,229,.40);
    color: #1e1b7a;
}
</style>

<nav class="provider-navbar">
    <div class="pnav-left">
        <a href="<?= $home_link ?>">
            <img src="../../pictures/rafiq_logo.png" alt="Rafiq Logo">
        </a>
        <div class="pnav-links">
            <a href="<?= $home_link ?>">Home</a>
        </div>
    </div>

    <div class="pnav-right">
        <a class="pnav-sl-pill" href="../../general/sign_language.php">
            <i class="fa-solid fa-hands"></i> Sign Language AI
        </a>
        <a class="pnav-ocr-pill" href="../../general/ocr_reader.php">
            <i class="fa-solid fa-eye"></i> OCR Reader
        </a>
        <a class="pnav-vc-pill" href="../../general/voice_companion.php">
            <i class="fa-solid fa-microphone-lines"></i> Voice AI
        </a>
        <span class="pnav-provider-chip"><?= htmlspecialchars(ucfirst($providerType)) ?></span>
        <a href="<?= $profile_link ?>"><i class="fa-regular fa-user"></i></a>
        <a href="../../general/logout.php" class="pnav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</nav>
