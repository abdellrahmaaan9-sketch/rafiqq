<?php
session_start();
$_doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_dir  = str_replace('\\', '/', dirname(__DIR__));
$_rel  = ltrim(str_replace($_doc, '', $_dir), '/');
$_base = '/' . $_rel;

$role         = $_SESSION['role'] ?? '';
$providerType = $_SESSION['provider_type'] ?? '';

$back_link    = "$_base/general/login.php";
$home_link    = "$_base/general/login.php";
$bookings_link= "$_base/general/login.php";
$profile_link = "$_base/general/login.php";

if ($role === 'patient') {
    $home_link     = "$_base/patient/patient_homepage.php";
    $back_link     = $home_link;
    $bookings_link = "$_base/patient/my_bookings.php";
    $profile_link  = "$_base/patient/patient_profile.php";
} elseif ($role === 'provider') {
    $pm = [
        'doctor'      => "$_base/providers/doctor/doctor_homepage.php",
        'interpreter' => "$_base/providers/interpreter/int_homepage.php",
        'driver'      => "$_base/providers/driver/driver_portal.php",
        'caregiver'   => "$_base/providers/caregiver/caregiver_home.php"
    ];
    $home_link  = $pm[$providerType] ?? $back_link;
    $back_link  = $home_link;
    $profile_link = $pm[$providerType] ?? $back_link;
}

$vc_links = json_encode([
    'home'          => $home_link,
    'bookings'      => $bookings_link,
    'profile'       => $profile_link,
    'sign_language' => "$_base/general/sign_language.php",
    'ocr'           => "$_base/general/ocr_reader.php",
    'voice'         => "$_base/general/voice_companion.php",
    'logout'        => "$_base/general/logout.php",
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rafiq | AI Voice Companion</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* ── Reset & Tokens ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#1e2040;--indigo:#3730a3;--accent:#4f46e5;--a2:#7c3aed;
  --light:#eef2ff;--green:#16a34a;--red:#dc2626;--amber:#d97706;
  --bg:#f5f4ff;--card:#fff;--text:#1e2040;--muted:#6366a0;
  --border:rgba(79,70,229,.13);--sh:0 4px 20px rgba(79,70,229,.08);
  --sh-lg:0 16px 48px rgba(79,70,229,.14);--mono:'JetBrains Mono',monospace;
}
html{scroll-behavior:smooth}
body{font-family:"Nunito",system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
body.hc-mode{filter:contrast(2) grayscale(.15)}
:focus-visible{outline:2.5px solid var(--accent);outline-offset:3px;border-radius:6px}

/* ── Layout ── */
.wrap{max-width:980px;margin:0 auto;padding:0 24px 72px}

/* ── Hero ── */
.hero{
  background:linear-gradient(135deg,#0f0c29 0%,#302b63 50%,#4f46e5 100%);
  margin:0 -24px;padding:38px 52px 46px;
  color:#fff;position:relative;overflow:hidden;
  border-radius:0 0 40px 40px
}
.hero-orb{position:absolute;border-radius:50%;pointer-events:none}
.hero-orb-1{width:360px;height:360px;top:-150px;right:-100px;background:rgba(255,255,255,.04)}
.hero-orb-2{width:190px;height:190px;bottom:-70px;left:4%;background:rgba(255,255,255,.03)}
.hero-inner{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap}
.hero-text{flex:1;min-width:240px}
.hero-back{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 16px;border-radius:12px;
  border:1.5px solid rgba(255,255,255,.3);
  background:rgba(255,255,255,.1);color:#fff;
  font-size:13px;font-weight:800;text-decoration:none;
  margin-bottom:18px;transition:background .15s,transform .12s;
  backdrop-filter:blur(8px)
}
.hero-back:hover{background:rgba(255,255,255,.18);transform:translateX(-2px)}
.hero-eyebrow{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,.12);backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.2);
  border-radius:99px;padding:5px 14px;
  font-size:12px;font-weight:800;letter-spacing:.06em;
  text-transform:uppercase;margin-bottom:14px
}
.hero h1{font-size:clamp(24px,4vw,38px);font-weight:900;line-height:1.18;margin-bottom:10px}
.hero-desc{font-size:15px;opacity:.78;max-width:520px;line-height:1.65;font-weight:600}
.hero-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}
.hero-badge{
  display:flex;align-items:center;gap:5px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
  border-radius:8px;padding:5px 12px;
  font-size:12px;font-weight:700;color:rgba(255,255,255,.88)
}

/* ── Orb Animation ── */
.voice-orb-wrap{
  display:flex;align-items:center;justify-content:center;
  width:130px;height:130px;flex-shrink:0
}
.voice-orb{
  width:100px;height:100px;border-radius:50%;
  background:rgba(255,255,255,.12);
  border:2px solid rgba(255,255,255,.25);
  display:flex;align-items:center;justify-content:center;
  position:relative;cursor:pointer;
  transition:transform .2s
}
.voice-orb i{font-size:32px;color:#fff;transition:opacity .2s}
.voice-orb::before,.voice-orb::after{
  content:'';position:absolute;inset:-10px;
  border-radius:50%;border:1.5px solid rgba(255,255,255,.15);
  animation:orb-ring 2.5s ease-in-out infinite;
}
.voice-orb::after{inset:-20px;animation-delay:-.8s}
.voice-orb.listening::before,.voice-orb.listening::after{
  border-color:rgba(255,255,255,.4);
  animation:orb-ring-active 1s ease-in-out infinite
}
@keyframes orb-ring{
  0%,100%{transform:scale(1);opacity:.4}
  50%{transform:scale(1.06);opacity:.8}
}
@keyframes orb-ring-active{
  0%,100%{transform:scale(1);opacity:.6}
  50%{transform:scale(1.14);opacity:1}
}
.voice-orb.listening{
  background:rgba(255,255,255,.2);
  box-shadow:0 0 40px rgba(255,255,255,.25),0 0 80px rgba(79,70,229,.5);
  animation:orb-pulse .8s ease-in-out infinite
}
@keyframes orb-pulse{
  0%,100%{transform:scale(1)}
  50%{transform:scale(1.05)}
}
.voice-orb.speaking{
  background:rgba(255,200,0,.15);
  box-shadow:0 0 40px rgba(255,200,0,.2),0 0 80px rgba(255,150,0,.3)
}

/* ── Main card ── */
.vc-main{
  background:var(--card);border-radius:28px;
  border:1px solid var(--border);box-shadow:var(--sh-lg);
  margin-top:28px;overflow:hidden
}

/* ── Status bar ── */
.vc-status{
  display:flex;align-items:center;gap:12px;
  padding:16px 26px;border-bottom:1px solid rgba(79,70,229,.07);
  background:linear-gradient(90deg,rgba(79,70,229,.03),transparent)
}
.vc-status-dot{
  width:10px;height:10px;border-radius:50%;
  background:#d1d5db;flex-shrink:0;
  transition:background .3s,box-shadow .3s
}
.vc-status-dot.idle{}
.vc-status-dot.listening{
  background:#22c55e;
  box-shadow:0 0 0 3px rgba(34,197,94,.2);
  animation:dot-pulse 1s infinite
}
.vc-status-dot.speaking{background:#f59e0b}
.vc-status-dot.error{background:#ef4444}
@keyframes dot-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.3)}}
.vc-status-label{font-size:13px;font-weight:800;color:var(--muted)}
.vc-lang-chip{
  margin-left:auto;font-size:11px;font-weight:700;
  color:var(--accent);background:var(--light);
  border-radius:8px;padding:3px 10px
}

/* ── Chat Area ── */
.vc-chat{
  padding:22px 24px;min-height:320px;max-height:420px;
  overflow-y:auto;display:flex;flex-direction:column;gap:12px;
  scroll-behavior:smooth
}
.vc-msg{
  display:flex;gap:10px;align-items:flex-end;max-width:88%
}
.vc-msg.user{flex-direction:row-reverse;align-self:flex-end}
.vc-msg.assistant{align-self:flex-start}
.vc-avatar{
  width:34px;height:34px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:14px
}
.vc-msg.user .vc-avatar{background:var(--light);color:var(--accent)}
.vc-msg.assistant .vc-avatar{
  background:linear-gradient(135deg,var(--accent),var(--a2));
  color:#fff
}
.vc-bubble{
  padding:11px 16px;border-radius:16px;
  font-size:14px;font-weight:700;line-height:1.55;
  max-width:100%
}
.vc-msg.user .vc-bubble{
  background:var(--light);color:var(--navy);
  border-bottom-right-radius:4px
}
.vc-msg.assistant .vc-bubble{
  background:linear-gradient(135deg,var(--accent),var(--a2));
  color:#fff;
  border-bottom-left-radius:4px
}
.vc-bubble.system{
  background:#fef9c3;color:#78350f;
  border:1px solid rgba(250,204,21,.3)
}
.vc-typing{
  display:flex;align-items:center;gap:5px;
  padding:13px 16px
}
.vc-typing span{
  width:7px;height:7px;border-radius:50%;
  background:rgba(255,255,255,.5);
  animation:typing .9s ease-in-out infinite
}
.vc-typing span:nth-child(2){animation-delay:.2s}
.vc-typing span:nth-child(3){animation-delay:.4s}
@keyframes typing{0%,80%,100%{transform:scale(0.8);opacity:.5}40%{transform:scale(1.1);opacity:1}}

/* ── Input area ── */
.vc-input-area{
  border-top:1px solid rgba(79,70,229,.07);
  padding:16px 24px;display:flex;gap:10px;align-items:center
}
#vcTextInput{
  flex:1;padding:12px 18px;
  border:1.5px solid rgba(79,70,229,.2);
  border-radius:14px;font-size:14px;font-weight:700;
  font-family:inherit;color:var(--text);
  background:#fafafe;outline:none;
  transition:border-color .18s
}
#vcTextInput:focus{border-color:var(--accent);background:#fff}
#vcTextInput::placeholder{color:var(--muted);opacity:.6}
.vc-send-btn{
  width:44px;height:44px;border-radius:13px;border:none;cursor:pointer;
  background:var(--accent);color:#fff;font-size:16px;
  display:flex;align-items:center;justify-content:center;
  transition:transform .15s,background .18s
}
.vc-send-btn:hover{background:var(--indigo);transform:scale(1.06)}

/* ── Control Buttons ── */
.vc-controls{
  display:flex;gap:10px;padding:16px 24px 22px;
  flex-wrap:wrap;border-top:1px solid rgba(79,70,229,.07)
}
.btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:11px 20px;border-radius:13px;
  font-size:14px;font-weight:800;font-family:inherit;
  cursor:pointer;border:none;transition:all .18s;text-decoration:none
}
.btn-primary{
  background:linear-gradient(135deg,var(--accent),var(--a2));
  color:#fff;box-shadow:0 4px 16px rgba(79,70,229,.25)
}
.btn-primary:hover{box-shadow:0 6px 24px rgba(79,70,229,.4);transform:translateY(-1px)}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}
.btn-secondary{background:var(--light);color:var(--accent);border:1.5px solid rgba(79,70,229,.18)}
.btn-secondary:hover{background:#e0e7ff;border-color:rgba(79,70,229,.38)}
.btn-mic{
  background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;
  box-shadow:0 4px 16px rgba(220,38,38,.25)
}
.btn-mic:hover{box-shadow:0 6px 22px rgba(220,38,38,.38);transform:translateY(-1px)}
.btn-mic.listening{
  background:linear-gradient(135deg,#16a34a,#22c55e);
  box-shadow:0 4px 16px rgba(22,163,74,.3);
  animation:btn-pulse 1.2s ease-in-out infinite
}
@keyframes btn-pulse{
  0%,100%{box-shadow:0 4px 16px rgba(22,163,74,.3)}
  50%{box-shadow:0 6px 28px rgba(22,163,74,.55)}
}

/* ── Command Quick-Grid ── */
.cmd-section{margin-top:28px}
.cmd-section-title{
  font-size:16px;font-weight:900;color:var(--navy);
  margin-bottom:14px;display:flex;align-items:center;gap:8px
}
.cmd-section-title i{color:var(--accent);font-size:14px}
.cmd-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
  gap:10px
}
.cmd-chip{
  background:var(--card);border:1.5px solid var(--border);
  border-radius:14px;padding:13px 16px;
  cursor:pointer;display:flex;align-items:center;gap:10px;
  font-size:13px;font-weight:800;color:var(--navy);
  transition:background .18s,border-color .18s,transform .12s;
  user-select:none
}
.cmd-chip:hover{
  background:var(--light);
  border-color:rgba(79,70,229,.35);
  transform:translateY(-1px)
}
.cmd-chip i{
  width:28px;height:28px;border-radius:9px;
  background:var(--light);color:var(--accent);
  display:flex;align-items:center;justify-content:center;
  font-size:12px;flex-shrink:0
}

/* ── Accessibility settings card ── */
.a11y-card{
  background:var(--card);border-radius:22px;
  border:1px solid var(--border);box-shadow:var(--sh);
  padding:24px 26px;margin-top:20px
}
.a11y-card-title{
  font-size:14px;font-weight:900;color:var(--navy);
  display:flex;align-items:center;gap:8px;margin-bottom:16px;
  text-transform:uppercase;letter-spacing:.04em
}
.a11y-card-title i{color:var(--accent)}
.a11y-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 0;border-bottom:1px solid rgba(79,70,229,.06)
}
.a11y-row:last-child{border-bottom:none}
.a11y-row-label{
  display:flex;align-items:center;gap:10px;
  font-size:14px;font-weight:700;color:var(--navy)
}
.a11y-row-label i{color:var(--muted);width:18px}
.a11y-row-controls{display:flex;gap:6px;align-items:center}
.a11y-toggle{
  width:44px;height:24px;border-radius:99px;
  border:none;cursor:pointer;position:relative;
  background:#d1d5db;transition:background .22s
}
.a11y-toggle.on{background:var(--accent)}
.a11y-toggle::after{
  content:'';position:absolute;
  top:3px;left:3px;
  width:18px;height:18px;border-radius:50%;
  background:#fff;transition:transform .22s;
  box-shadow:0 1px 4px rgba(0,0,0,.2)
}
.a11y-toggle.on::after{transform:translateX(20px)}
.font-ctrl{display:flex;align-items:center;gap:4px}
.font-btn{
  width:30px;height:30px;border:1.5px solid var(--border);
  border-radius:9px;background:var(--light);
  color:var(--accent);font-size:13px;font-weight:900;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:background .15s
}
.font-btn:hover{background:#e0e7ff}
.font-size-val{
  font-size:13px;font-weight:800;color:var(--muted);
  min-width:32px;text-align:center;font-family:var(--mono)
}

/* ── Browser support banner ── */
.support-banner{
  background:#fef3c7;border:1.5px solid rgba(245,158,11,.25);
  border-radius:14px;padding:14px 18px;
  font-size:13px;font-weight:700;color:#78350f;
  display:none;align-items:center;gap:10px;margin-top:16px
}
.support-banner i{font-size:18px;color:#d97706}
.support-banner.visible{display:flex}
</style>
</head>
<body>

<div class="wrap">

  <!-- Hero -->
  <div class="hero">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-inner">
      <div class="hero-text">
        <a href="<?= htmlspecialchars($back_link) ?>" class="hero-back">
          <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <div class="hero-eyebrow">
          <i class="fa-solid fa-microphone-lines"></i> Accessibility Feature
        </div>
        <h1>AI Voice Companion</h1>
        <p class="hero-desc">
          Navigate Rafiq entirely with your voice. Say a command, ask for help, control accessibility settings, and get spoken responses — no touch required.
        </p>
        <div class="hero-badges">
          <span class="hero-badge"><i class="fa-solid fa-microphone"></i> Voice Commands</span>
          <span class="hero-badge"><i class="fa-solid fa-volume-high"></i> Spoken Responses</span>
          <span class="hero-badge"><i class="fa-solid fa-universal-access"></i> Fully Accessible</span>
          <span class="hero-badge"><i class="fa-solid fa-lock"></i> On-Device AI</span>
        </div>
      </div>
      <div class="voice-orb-wrap">
        <div class="voice-orb" id="voiceOrb">
          <i class="fa-solid fa-microphone" id="orbIcon"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Browser support banner -->
  <div class="support-banner" id="supportBanner">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <span>Your browser does not fully support the Web Speech API. Voice input may not work. Please use Google Chrome or Microsoft Edge for the best experience.</span>
  </div>

  <!-- Main Card -->
  <div class="vc-main">

    <!-- Status bar -->
    <div class="vc-status">
      <div class="vc-status-dot idle" id="statusDot"></div>
      <span class="vc-status-label" id="statusLabel">Ready — click Speak or type a command</span>
      <span class="vc-lang-chip">EN — US</span>
    </div>

    <!-- Chat -->
    <div class="vc-chat" id="vcChat">
      <!-- Initial greeting injected by JS -->
    </div>

    <!-- Text input -->
    <div class="vc-input-area">
      <input type="text" id="vcTextInput" placeholder="Type a command or question..." autocomplete="off">
      <button class="vc-send-btn" id="vcSendBtn" title="Send"><i class="fa-solid fa-paper-plane"></i></button>
    </div>

    <!-- Controls -->
    <div class="vc-controls">
      <button class="btn btn-mic" id="micBtn">
        <i class="fa-solid fa-microphone" id="micIcon"></i>
        <span id="micLabel">Start Listening</span>
      </button>
      <button class="btn btn-secondary" id="stopSpeakBtn">
        <i class="fa-solid fa-stop"></i> Stop Speaking
      </button>
      <button class="btn btn-secondary" id="clearChatBtn">
        <i class="fa-solid fa-broom"></i> Clear Chat
      </button>
    </div>
  </div>

  <!-- Quick commands -->
  <div class="cmd-section">
    <div class="cmd-section-title">
      <i class="fa-solid fa-bolt"></i> Quick Voice Commands — click to run
    </div>
    <div class="cmd-grid" id="cmdGrid">
      <!-- injected by JS -->
    </div>
  </div>

  <!-- Accessibility Card -->
  <div class="a11y-card">
    <div class="a11y-card-title">
      <i class="fa-solid fa-universal-access"></i> Accessibility Settings
    </div>
    <div class="a11y-row">
      <div class="a11y-row-label"><i class="fa-solid fa-circle-half-stroke"></i> High Contrast Mode</div>
      <div class="a11y-row-controls">
        <button class="a11y-toggle" id="hcToggle" aria-label="Toggle High Contrast"></button>
      </div>
    </div>
    <div class="a11y-row">
      <div class="a11y-row-label"><i class="fa-solid fa-text-height"></i> Font Size</div>
      <div class="a11y-row-controls">
        <div class="font-ctrl">
          <button class="font-btn" id="fontDecBtn">−</button>
          <span class="font-size-val" id="fontSizeVal">16px</span>
          <button class="font-btn" id="fontIncBtn">+</button>
        </div>
      </div>
    </div>
    <div class="a11y-row">
      <div class="a11y-row-label"><i class="fa-solid fa-volume-high"></i> Read Responses Aloud</div>
      <div class="a11y-row-controls">
        <button class="a11y-toggle on" id="ttsToggle" aria-label="Toggle TTS"></button>
      </div>
    </div>
  </div>

</div><!-- /wrap -->

<script>
// ── Links injected from PHP ──
const LINKS = <?= $vc_links ?>;
const BASE  = '<?= $_base ?>';

// ── State ──
let recognizing = false;
let recognition = null;
let ttsEnabled  = true;
let hcOn        = false;
let fontSize    = 16;
const $ = id => document.getElementById(id);

// ── DOM refs ──
const voiceOrb    = $('voiceOrb');
const orbIcon     = $('orbIcon');
const statusDot   = $('statusDot');
const statusLabel = $('statusLabel');
const vcChat      = $('vcChat');
const vcTextInput = $('vcTextInput');
const vcSendBtn   = $('vcSendBtn');
const micBtn      = $('micBtn');
const micIcon     = $('micIcon');
const micLabel    = $('micLabel');
const stopSpeakBtn= $('stopSpeakBtn');
const clearChatBtn= $('clearChatBtn');
const cmdGrid     = $('cmdGrid');
const hcToggle    = $('hcToggle');
const ttsToggle   = $('ttsToggle');
const fontDecBtn  = $('fontDecBtn');
const fontIncBtn  = $('fontIncBtn');
const fontSizeVal = $('fontSizeVal');
const supportBanner=$('supportBanner');

// ── Quick commands ──
const COMMANDS = [
    { icon:'fa-house',          label:'Go Home',             phrase:'go home' },
    { icon:'fa-calendar-check', label:'My Bookings',         phrase:'open my bookings' },
    { icon:'fa-circle-user',    label:'My Profile',          phrase:'open profile' },
    { icon:'fa-hands',          label:'Sign Language AI',    phrase:'open sign language' },
    { icon:'fa-eye',            label:'OCR Reader',          phrase:'open ocr reader' },
    { icon:'fa-circle-half-stroke', label:'High Contrast',  phrase:'toggle high contrast' },
    { icon:'fa-text-height',    label:'Increase Text',       phrase:'increase text size' },
    { icon:'fa-text-height',    label:'Decrease Text',       phrase:'decrease text size' },
    { icon:'fa-volume-high',    label:'Read This Page',      phrase:'read page' },
    { icon:'fa-circle-question',label:'Help',                phrase:'help' },
    { icon:'fa-right-from-bracket', label:'Logout',         phrase:'logout' },
];

// ── Build quick commands ──
COMMANDS.forEach(cmd => {
    const chip = document.createElement('div');
    chip.className = 'cmd-chip';
    chip.setAttribute('role', 'button');
    chip.setAttribute('tabindex', '0');
    chip.innerHTML = `<i class="fa-solid ${cmd.icon}"></i> ${cmd.label}`;
    chip.addEventListener('click', () => handleCommand(cmd.phrase, 'user'));
    chip.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') handleCommand(cmd.phrase, 'user'); });
    cmdGrid.appendChild(chip);
});

// ── Command Router ──
function handleCommand(raw, source) {
    const text = raw.trim();
    if (!text) return;
    if (source === 'user') addMessage(text, 'user');
    const cmd = text.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim();

    // Navigation
    if (/\b(go\s*home|homepage|home\s*page)\b/.test(cmd)) {
        return respond("Navigating to the Home page now.", () => navigate(LINKS.home));
    }
    if (/\b(my\s*booking|booking|appointment|reservation)\b/.test(cmd)) {
        return respond("Opening your Bookings.", () => navigate(LINKS.bookings));
    }
    if (/\bprofile\b/.test(cmd)) {
        return respond("Opening your Profile.", () => navigate(LINKS.profile));
    }
    if (/\b(sign\s*language|sign\s*lang|asl|hand\s*sign)\b/.test(cmd)) {
        return respond("Opening the Sign Language AI Assistant.", () => navigate(LINKS.sign_language));
    }
    if (/\b(ocr|ocr\s*reader|smart\s*reader|read\s*image|scan\s*text)\b/.test(cmd)) {
        return respond("Opening the Smart OCR Reader.", () => navigate(LINKS.ocr));
    }
    if (/\b(voice|voice\s*companion)\b/.test(cmd) && source !== 'init') {
        return respond("You are already on the Voice Companion page.");
    }
    if (/\b(logout|log\s*out|sign\s*out)\b/.test(cmd)) {
        return respond("Logging you out now. Goodbye!", () => navigate(LINKS.logout));
    }
    if (/\b(service|services|find\s*help|get\s*help)\b/.test(cmd)) {
        return respond("Opening the Home page where you can find all services.", () => navigate(LINKS.home));
    }

    // Accessibility
    if (/\b(high\s*contrast|contrast|dark\s*mode)\b/.test(cmd)) {
        toggleHighContrast();
        return respond("High contrast mode has been " + (hcOn ? "enabled" : "disabled") + ".");
    }
    if (/\b(increase|bigger|larger|zoom\s*in)\b.*\b(text|font|size)\b/.test(cmd)
        || /\b(text|font|size)\b.*\b(increase|bigger|larger|up)\b/.test(cmd)) {
        changeFontSize(2);
        return respond("Font size increased to " + fontSize + " pixels.");
    }
    if (/\b(decrease|smaller|reduce|zoom\s*out)\b.*\b(text|font|size)\b/.test(cmd)
        || /\b(text|font|size)\b.*\b(decrease|smaller|reduce|down)\b/.test(cmd)) {
        changeFontSize(-2);
        return respond("Font size decreased to " + fontSize + " pixels.");
    }
    if (/\b(reset\s*(font|text)|default\s*(font|text|size))\b/.test(cmd)) {
        fontSize = 16; applyFontSize();
        return respond("Font size reset to 16 pixels.");
    }

    // TTS control
    if (/\b(read\s*(this\s*)?page|read\s*aloud|speak\s*page)\b/.test(cmd)) {
        const pageText = document.body.innerText.replace(/\s+/g,' ').trim().substring(0, 1200);
        return respond("Reading the page content aloud.", () => speakText(pageText));
    }
    if (/\b(stop\s*(reading|speak|talking)|quiet|silence|shut\s*up)\b/.test(cmd)) {
        window.speechSynthesis.cancel();
        return respond("Stopped.", null, false);
    }
    if (/\b(mute|turn\s*off.*voice|disable.*voice)\b/.test(cmd)) {
        ttsEnabled = false; ttsToggle.classList.remove('on');
        return respond("Voice responses muted.", null, false);
    }
    if (/\b(unmute|turn\s*on.*voice|enable.*voice)\b/.test(cmd)) {
        ttsEnabled = true; ttsToggle.classList.add('on');
        return respond("Voice responses enabled.");
    }

    // Help
    if (/\b(help|what\s*(can|commands)|available|options|commands)\b/.test(cmd)) {
        return respond(
            "Here are things you can say: Go home · My Bookings · My Profile · Sign Language AI · OCR Reader · " +
            "Toggle High Contrast · Increase or Decrease Text Size · Read Page · Logout. " +
            "You can also type any command in the text box below."
        );
    }

    // Greeting / small talk
    if (/\b(hello|hi|hey|good\s*(morning|afternoon|evening)|greet)\b/.test(cmd)) {
        return respond("Hello! I'm Rafiq Voice Companion. Say 'help' to hear available commands, or just tell me where you'd like to go.");
    }
    if (/\b(thank|thanks|thank\s*you)\b/.test(cmd)) {
        return respond("You're welcome! Is there anything else I can help you with?");
    }

    // Fallback
    respond(
        "Sorry, I didn't recognise that command. Say 'help' to hear available commands, or use the quick command buttons below."
    );
}

// ── Respond ──
function respond(text, afterFn, speak = true) {
    addMessage(text, 'assistant');
    if (speak && ttsEnabled) {
        speakText(text, afterFn);
    } else if (afterFn) {
        setTimeout(afterFn, 400);
    }
}

function speakText(text, afterFn) {
    window.speechSynthesis.cancel();
    const utter = new SpeechSynthesisUtterance(text);
    utter.rate  = 0.95;
    utter.pitch = 1.05;
    utter.lang  = 'en-US';
    utter.onstart = () => {
        statusDot.className = 'vc-status-dot speaking';
        statusLabel.textContent = 'Speaking...';
        voiceOrb.classList.add('speaking');
        orbIcon.className = 'fa-solid fa-volume-high';
    };
    utter.onend = utter.onerror = () => {
        statusDot.className = 'vc-status-dot idle';
        statusLabel.textContent = 'Ready';
        voiceOrb.classList.remove('speaking');
        orbIcon.className = 'fa-solid fa-microphone';
        if (afterFn) setTimeout(afterFn, 350);
    };
    window.speechSynthesis.speak(utter);
}

function navigate(url) {
    if (url && url !== window.location.href) window.location.href = url;
}

// ── Add message bubble ──
function addMessage(text, role) {
    const wrap = document.createElement('div');
    wrap.className = 'vc-msg ' + role;
    const avatar = document.createElement('div');
    avatar.className = 'vc-avatar';
    avatar.innerHTML = role === 'user'
        ? '<i class="fa-solid fa-user"></i>'
        : '<i class="fa-solid fa-robot"></i>';
    const bubble = document.createElement('div');
    bubble.className = 'vc-bubble';
    bubble.textContent = text;
    wrap.appendChild(avatar);
    wrap.appendChild(bubble);
    vcChat.appendChild(wrap);
    vcChat.scrollTop = vcChat.scrollHeight;
}

// ── Web Speech API ──
const hasSpeechAPI = ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window);
if (!hasSpeechAPI) supportBanner.classList.add('visible');

function setupRecognition() {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) return null;
    const r = new SR();
    r.lang = 'en-US';
    r.continuous = false;
    r.interimResults = true;
    r.maxAlternatives = 1;

    r.onstart = () => {
        recognizing = true;
        statusDot.className = 'vc-status-dot listening';
        statusLabel.textContent = 'Listening...';
        micBtn.classList.add('listening');
        micIcon.className = 'fa-solid fa-stop';
        micLabel.textContent = 'Stop Listening';
        voiceOrb.classList.add('listening');
        orbIcon.className = 'fa-solid fa-microphone';
    };

    r.onresult = e => {
        let interim = '';
        let final   = '';
        for (let i = e.resultIndex; i < e.results.length; i++) {
            if (e.results[i].isFinal) final += e.results[i][0].transcript;
            else interim += e.results[i][0].transcript;
        }
        if (interim) {
            statusLabel.textContent = 'Heard: "' + interim + '"';
        }
        if (final.trim()) {
            vcTextInput.value = '';
            handleCommand(final.trim(), 'user');
        }
    };

    r.onerror = e => {
        if (e.error === 'no-speech') {
            respond("I didn't catch anything. Please try again.", null, false);
        } else if (e.error === 'not-allowed') {
            supportBanner.classList.add('visible');
            supportBanner.querySelector('span').textContent =
                'Microphone permission was denied. Please allow microphone access in your browser settings.';
        }
        stopListening();
    };

    r.onend = () => stopListening();
    return r;
}

function startListening() {
    if (!hasSpeechAPI) { alert('Voice input is not supported in this browser. Please use Chrome or Edge.'); return; }
    if (recognizing) { stopListening(); return; }
    recognition = setupRecognition();
    if (recognition) recognition.start();
}

function stopListening() {
    recognizing = false;
    if (recognition) { try { recognition.stop(); } catch(e){} }
    statusDot.className = 'vc-status-dot idle';
    statusLabel.textContent = 'Ready';
    micBtn.classList.remove('listening');
    micIcon.className = 'fa-solid fa-microphone';
    micLabel.textContent = 'Start Listening';
    voiceOrb.classList.remove('listening');
    orbIcon.className = 'fa-solid fa-microphone';
}

micBtn.addEventListener('click', startListening);
voiceOrb.addEventListener('click', () => { if (!recognizing) startListening(); else stopListening(); });

stopSpeakBtn.addEventListener('click', () => {
    window.speechSynthesis.cancel();
    statusDot.className = 'vc-status-dot idle';
    statusLabel.textContent = 'Ready';
    voiceOrb.classList.remove('speaking');
    orbIcon.className = 'fa-solid fa-microphone';
});

clearChatBtn.addEventListener('click', () => {
    vcChat.innerHTML = '';
    addGreeting();
});

// ── Text input ──
vcTextInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') { vcSendBtn.click(); }
});
vcSendBtn.addEventListener('click', () => {
    const val = vcTextInput.value.trim();
    if (!val) return;
    vcTextInput.value = '';
    handleCommand(val, 'user');
});

// ── Accessibility controls ──
hcToggle.addEventListener('click', () => toggleHighContrast());
ttsToggle.addEventListener('click', () => {
    ttsEnabled = !ttsEnabled;
    ttsToggle.classList.toggle('on', ttsEnabled);
});
fontDecBtn.addEventListener('click', () => changeFontSize(-2));
fontIncBtn.addEventListener('click', () => changeFontSize(2));

function toggleHighContrast() {
    hcOn = !hcOn;
    document.body.classList.toggle('hc-mode', hcOn);
    hcToggle.classList.toggle('on', hcOn);
}

function changeFontSize(delta) {
    fontSize = Math.min(26, Math.max(12, fontSize + delta));
    applyFontSize();
}

function applyFontSize() {
    document.documentElement.style.fontSize = fontSize + 'px';
    fontSizeVal.textContent = fontSize + 'px';
}

// ── Greeting ──
function addGreeting() {
    const hour = new Date().getHours();
    const timeGreet = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    addMessage(
        timeGreet + "! I'm Rafiq Voice Companion. I can help you navigate the platform, " +
        "control accessibility settings, and more — entirely hands-free. " +
        "Click the microphone button or say a command to get started. " +
        "Say 'help' to hear all available commands.",
        'assistant'
    );
}

// ── Init ──
addGreeting();
speakText(
    "Rafiq Voice Companion is ready. Say a command or press the microphone button to begin. Say help for a list of commands.",
    null
);

window.addEventListener('beforeunload', () => {
    window.speechSynthesis.cancel();
    if (recognition) { try { recognition.stop(); } catch(e){} }
});
</script>
</body>
</html>
