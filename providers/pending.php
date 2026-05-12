<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../general/login.php");
    exit();
}

require __DIR__ . '/../pgdb/db.php';

/* ── AJAX status-check endpoint ── */
if (isset($_GET['action']) && $_GET['action'] === 'check_status') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("SELECT status FROM provider WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => (int)$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $status = $row ? strtolower(trim((string)$row['status'])) : 'pending';

        $redirectMap = [
            'doctor'      => 'doctor/doctor_homepage.php',
            'driver'      => 'driver/driver_portal.php',
            'caregiver'   => 'caregiver/caregiver_homepage.php',
            'interpreter' => 'interpreter/int_homepage.php',
        ];
        $type     = strtolower(trim((string)($_SESSION['provider_type'] ?? '')));
        $redirect = $redirectMap[$type] ?? 'doctor/doctor_homepage.php';

        echo json_encode(['status' => $status, 'redirect' => $redirect]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'pending', 'redirect' => '']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rafiq - Application Under Review</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#353b69,#6470d2);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}
.card {
    background: #fff;
    width: 480px;
    max-width: 95%;
    border-radius: 30px;
    padding: 50px 44px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
.logo img {
    width: 140px;
    margin-bottom: 30px;
}
.icon {
    font-size: 64px;
    margin-bottom: 20px;
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.08); }
}
h2 {
    color: #2B2C41;
    font-size: 22px;
    margin-bottom: 14px;
}
p {
    color: #64748b;
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 10px;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0f0ff;
    color: #4a4aaa;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 18px;
    border-radius: 99px;
    margin: 18px 0 28px;
    border: 1px solid #c0c0f0;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6470d2;
    animation: blink 1.2s ease-in-out infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.2; }
}
.logout-btn {
    margin-top: 10px;
    display: inline-block;
    padding: 12px 32px;
    border-radius: 30px;
    background: linear-gradient(135deg,#353b69,#6470d2);
    color: white;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}
.logout-btn:hover { opacity: 0.88; }

/* rejection state */
.rejected-box {
    display: none;
    margin-top: 18px;
    padding: 16px;
    border-radius: 18px;
    background: #fff3f3;
    border: 1px solid #fca5a5;
    color: #8d2727;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.6;
}

/* live status indicator */
.poll-status {
    margin-top: 14px;
    font-size: 12px;
    color: #94a3b8;
    font-style: italic;
}
</style>
</head>
<body>

<div class="card">
    <div class="logo">
        <img src="../pictures/rafiq_logo.png" alt="Rafiq Logo">
    </div>

    <div class="icon" id="statusIcon">⏳</div>

    <h2 id="statusTitle">Your application is under review</h2>

    <div class="status-badge" id="statusBadge">
        <div class="status-dot"></div>
        <span id="statusText">Pending Approval</span>
    </div>

    <p>Thank you for signing up as a provider on Rafiq.</p>
    <p>Our admin team is currently reviewing your application and documents. You will be able to access your dashboard once your account is approved.</p>
    <p style="margin-top:14px;font-size:13px;color:#94a3b8">If you have any questions, please contact support.</p>

    <div class="rejected-box" id="rejectedBox">
        ❌ Unfortunately, your application was not approved. Please contact our support team for more information.
    </div>

    <p class="poll-status" id="pollStatus">Checking for updates...</p>

    <a href="../general/logout.php" class="logout-btn">Sign out</a>
</div>

<script>
(function() {
    let pollInterval = 5000;
    let attempts = 0;

    function checkStatus() {
        attempts++;
        fetch('pending.php?action=check_status', { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                const s = (data.status || '').toLowerCase();

                if (s === 'accepted' && data.redirect) {
                    document.getElementById('statusIcon').textContent = '✅';
                    document.getElementById('statusTitle').textContent = 'Approved! Redirecting you...';
                    document.getElementById('statusText').textContent = 'Accepted';
                    document.getElementById('statusBadge').style.background = '#f0fbf5';
                    document.getElementById('statusBadge').style.color = '#12643e';
                    document.getElementById('statusBadge').style.borderColor = '#a7f3d0';
                    document.getElementById('pollStatus').textContent = 'Redirecting to your dashboard…';
                    setTimeout(() => { window.location.href = data.redirect; }, 1200);
                    return;
                }

                if (s === 'rejected') {
                    document.getElementById('statusIcon').textContent = '❌';
                    document.getElementById('statusTitle').textContent = 'Application not approved';
                    document.getElementById('statusText').textContent = 'Rejected';
                    document.getElementById('statusBadge').style.background = '#fff3f3';
                    document.getElementById('statusBadge').style.color = '#8d2727';
                    document.getElementById('statusBadge').style.borderColor = '#fca5a5';
                    document.getElementById('rejectedBox').style.display = 'block';
                    document.getElementById('pollStatus').style.display = 'none';
                    return;
                }

                // still pending — keep polling, slow down after 20 attempts
                if (attempts > 20) pollInterval = 15000;
                document.getElementById('pollStatus').textContent = 'Still waiting for admin review…';
                setTimeout(checkStatus, pollInterval);
            })
            .catch(() => {
                setTimeout(checkStatus, pollInterval);
            });
    }

    // start polling after 3s
    setTimeout(checkStatus, 3000);
})();
</script>

</body>
</html>
