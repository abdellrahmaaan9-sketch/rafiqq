<?php
session_start();
$_doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_dir  = str_replace('\\', '/', dirname(__DIR__));
$_rel  = ltrim(str_replace($_doc, '', $_dir), '/');
$_base = '/' . $_rel;

$role = $_SESSION['role'] ?? '';
$pt   = $_SESSION['provider_type'] ?? '';

$home_link     = "$_base/general/login.php";
$bookings_link = "$_base/general/login.php";
$profile_link  = "$_base/general/login.php";
$back_link     = "$_base/general/login.php";

if ($role === 'patient') {
    $home_link     = "$_base/patient/patient_homepage.php";
    $bookings_link = "$_base/patient/my_bookings.php";
    $profile_link  = "$_base/patient/patient_profile.php";
    $back_link     = $home_link;
} elseif ($role === 'provider') {
    $pm = ['doctor'=>"$_base/providers/doctor/doctor_homepage.php",'interpreter'=>"$_base/providers/interpreter/int_homepage.php",'driver'=>"$_base/providers/driver/driver_portal.php",'caregiver'=>"$_base/providers/caregiver/caregiver_home.php"];
    $home_link    = $pm[$pt] ?? $home_link;
    $back_link    = $home_link;
    $profile_link = $pm[$pt] ?? $profile_link;
}

$vc_links = json_encode([
    'home'     => $home_link,
    'bookings' => $bookings_link,
    'profile'  => $profile_link,
    'sl'       => "$_base/general/sign_language.php",
    'ocr'      => "$_base/general/ocr_reader.php",
    'voice'    => "$_base/general/voice_companion.php",
    'map'      => "$_base/patient/map.php",
    'logout'   => "$_base/general/logout.php",
], JSON_UNESCAPED_UNICODE);
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
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#1e2040;--purple:#353b69;--accent:#6470d2;--a2:#494788;
  --light:#eef0ff;--green:#16a34a;--red:#dc2626;--amber:#d97706;
  --bg:#f4f5fb;--card:#fff;--text:#1e2040;--muted:#6b7080;
  --border:rgba(100,112,210,.13);--sh:0 4px 20px rgba(30,32,64,.08);
  --sh-lg:0 16px 48px rgba(30,32,64,.13);--mono:'JetBrains Mono',monospace;
}
html{scroll-behavior:smooth}
body{font-family:"Nunito",system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
body.hc{filter:contrast(1.9) grayscale(.1)}
:focus-visible{outline:2.5px solid var(--accent);outline-offset:3px;border-radius:6px}

.wrap{max-width:1000px;margin:0 auto;padding:0 24px 80px}

/* ── Hero ── */
.hero{background:linear-gradient(135deg,var(--navy) 0%,#2d1b69 50%,var(--accent) 100%);margin:0 -24px;padding:38px 52px 46px;color:#fff;position:relative;overflow:hidden;border-radius:0 0 40px 40px}
.hero-orb{position:absolute;border-radius:50%;pointer-events:none}
.hero-orb-1{width:380px;height:380px;top:-150px;right:-100px;background:rgba(255,255,255,.04)}
.hero-orb-2{width:200px;height:200px;bottom:-80px;left:3%;background:rgba(255,255,255,.03)}
.hero-inner{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap}
.hero-text{flex:1;min-width:240px}
.hero-back{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:12px;border:1.5px solid rgba(255,255,255,.3);background:rgba(255,255,255,.1);color:#fff;font-size:13px;font-weight:800;text-decoration:none;margin-bottom:18px;transition:background .15s,transform .12s;backdrop-filter:blur(8px)}
.hero-back:hover{background:rgba(255,255,255,.2);transform:translateX(-2px)}
.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);font-size:10.5px;font-weight:900;letter-spacing:.07em;text-transform:uppercase;margin-bottom:14px}
.hero h1{font-size:clamp(24px,3.8vw,42px);font-weight:900;letter-spacing:-.8px;line-height:1.06;margin-bottom:12px}
.hero h1 span{background:linear-gradient(90deg,#c4caff,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero p{font-size:14px;font-weight:600;color:rgba(255,255,255,.78);line-height:1.75;max-width:520px}
.hero-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.hero-tag{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);font-size:11.5px;font-weight:800;color:rgba(255,255,255,.88)}

/* ── Orb ── */
.orb-wrap{display:flex;flex-direction:column;align-items:center;gap:10px;flex-shrink:0}
.voice-orb{width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;position:relative;cursor:pointer;transition:box-shadow .3s,background .3s}
.voice-orb i{font-size:32px;color:#fff}
.voice-orb::before,.voice-orb::after{content:'';position:absolute;inset:-12px;border-radius:50%;border:1.5px solid rgba(255,255,255,.15);animation:orbRing 2.5s ease-in-out infinite}
.voice-orb::after{inset:-22px;animation-delay:-.8s}
.voice-orb.listening{background:rgba(255,255,255,.22);box-shadow:0 0 40px rgba(255,255,255,.22),0 0 80px rgba(100,112,210,.5);animation:orbPulse .85s ease-in-out infinite}
.voice-orb.listening::before,.voice-orb.listening::after{border-color:rgba(255,255,255,.45);animation:orbRingFast 1s ease-in-out infinite}
.voice-orb.speaking{background:rgba(255,200,50,.15);box-shadow:0 0 40px rgba(255,200,0,.2)}
@keyframes orbRing{0%,100%{transform:scale(1);opacity:.4}50%{transform:scale(1.06);opacity:.8}}
@keyframes orbRingFast{0%,100%{transform:scale(1);opacity:.6}50%{transform:scale(1.14);opacity:1}}
@keyframes orbPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}
.orb-hint{font-size:11px;font-weight:800;color:rgba(255,255,255,.55);text-align:center}

/* ── Cards / Buttons (site-wide match) ── */
.card{background:var(--card);border:1px solid var(--border);border-radius:24px;box-shadow:var(--sh);overflow:hidden;margin-top:20px}
.card-head{padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.card-head-icon{width:40px;height:40px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:17px;background:var(--light);flex-shrink:0;color:var(--accent)}
.card-head-title{font-size:15px;font-weight:900;color:var(--navy)}
.card-head-sub{font-size:11.5px;font-weight:700;color:var(--muted);margin-top:1px}
.card-body{padding:20px 22px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:11px 20px;border-radius:13px;font-size:13.5px;font-weight:800;font-family:inherit;cursor:pointer;border:none;transition:all .18s;text-decoration:none;user-select:none}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--a2));color:#fff;box-shadow:0 4px 16px rgba(100,112,210,.25)}
.btn-primary:hover{box-shadow:0 6px 24px rgba(100,112,210,.4);transform:translateY(-1px)}
.btn-primary:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
.btn-secondary{background:var(--light);color:var(--accent);border:1.5px solid var(--border)}
.btn-secondary:hover{background:#e4e7ff;border-color:rgba(100,112,210,.3)}
.btn-danger{background:#fef2f2;color:var(--red);border:1.5px solid rgba(220,38,38,.16)}
.btn-danger:hover{background:#fee2e2}
.btn-sm{padding:8px 14px;font-size:12px;border-radius:10px}
.btn-mic{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;box-shadow:0 4px 16px rgba(220,38,38,.25)}
.btn-mic:hover{box-shadow:0 6px 22px rgba(220,38,38,.38);transform:translateY(-1px)}
.btn-mic.active{background:linear-gradient(135deg,#15803d,#16a34a);box-shadow:0 4px 16px rgba(22,163,74,.3);animation:micPulse 1.2s ease-in-out infinite}
@keyframes micPulse{0%,100%{box-shadow:0 4px 16px rgba(22,163,74,.3)}50%{box-shadow:0 6px 28px rgba(22,163,74,.55)}}

/* ── Status bar ── */
.status-bar{display:flex;align-items:center;gap:10px;padding:12px 18px;background:var(--bg);border-radius:14px;border:1px solid var(--border);margin-bottom:14px}
.status-dot{width:10px;height:10px;border-radius:50%;background:#94a3b8;flex-shrink:0;transition:background .3s}
.status-dot.idle{}
.status-dot.listening{background:#22c55e;animation:dotBlink 1s infinite}
.status-dot.processing{background:var(--amber);animation:dotBlink .6s infinite}
.status-dot.speaking{background:var(--accent)}
.status-dot.executed{background:var(--green)}
.status-dot.error{background:var(--red)}
@keyframes dotBlink{0%,100%{opacity:1}50%{opacity:.2}}
.status-label{font-size:13px;font-weight:800;color:var(--muted);flex:1}
.lang-chip{margin-left:auto;font-size:11px;font-weight:700;background:var(--light);color:var(--accent);border-radius:7px;padding:3px 10px}

/* ── Chat ── */
.chat-box{min-height:300px;max-height:420px;overflow-y:auto;padding:14px 18px;display:flex;flex-direction:column;gap:10px;scroll-behavior:smooth}
.msg{display:flex;gap:9px;align-items:flex-end;max-width:88%}
.msg.user{flex-direction:row-reverse;align-self:flex-end}
.msg.assistant{align-self:flex-start}
.msg-avatar{width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:13px}
.msg.user .msg-avatar{background:var(--light);color:var(--accent)}
.msg.assistant .msg-avatar{background:linear-gradient(135deg,var(--accent),var(--a2));color:#fff}
.msg-bubble{padding:10px 15px;border-radius:16px;font-size:14px;font-weight:700;line-height:1.55;max-width:100%}
.msg.user .msg-bubble{background:var(--light);color:var(--navy);border-bottom-right-radius:4px}
.msg.assistant .msg-bubble{background:linear-gradient(135deg,var(--accent),var(--a2));color:#fff;border-bottom-left-radius:4px}
.msg.assistant .msg-bubble.system{background:#fef9c3;color:#78350f}

/* ── Input row ── */
.input-row{display:flex;gap:8px;padding:14px 18px;border-top:1px solid var(--border)}
#textInput{flex:1;padding:11px 16px;border:1.5px solid var(--border);border-radius:13px;font-size:14px;font-weight:700;font-family:inherit;color:var(--text);background:var(--bg);outline:none;transition:border-color .18s}
#textInput:focus{border-color:var(--accent);background:#fff}
#textInput::placeholder{color:var(--muted);opacity:.6}
.send-btn{width:44px;height:44px;border-radius:12px;background:var(--accent);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:background .18s,transform .15s}
.send-btn:hover{background:var(--a2);transform:scale(1.06)}

/* ── Controls ── */
.controls-row{display:flex;gap:8px;padding:14px 18px 18px;flex-wrap:wrap;border-top:1px solid var(--border)}

/* ── Language selector ── */
.lang-select-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:14px 18px;border-top:1px solid var(--border);background:rgba(100,112,210,.02)}
.lang-select-lbl{font-size:13px;font-weight:800;color:var(--navy)}
.lang-sel{padding:7px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-weight:700;font-family:inherit;color:var(--navy);background:var(--card);cursor:pointer;outline:none}
.lang-sel:focus{border-color:var(--accent)}

/* ── Transcript ── */
.transcript-box{background:var(--bg);border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:13px;font-weight:700;color:var(--muted);min-height:36px;border:1px solid var(--border);font-family:var(--mono)}
.transcript-label{font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}

/* ── Command log ── */
.cmd-log{display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto}
.cmd-log-item{display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;padding:10px 14px;border-radius:12px;border:1px solid var(--border);background:var(--bg)}
.cli-said{font-size:13px;font-weight:700;color:var(--navy)}
.cli-intent{font-size:11px;font-weight:800;padding:3px 9px;border-radius:7px;background:var(--light);color:var(--accent)}
.cli-result{font-size:11px;font-weight:800;padding:3px 9px;border-radius:7px}
.cli-result.ok{background:rgba(22,163,74,.1);color:var(--green)}
.cli-result.fail{background:rgba(220,38,38,.1);color:var(--red)}
.cmd-log-empty{font-size:13px;font-weight:700;color:var(--muted);opacity:.55;text-align:center;padding:24px}

/* ── Quick commands ── */
.cmd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px}
.cmd-chip{background:var(--card);border:1.5px solid var(--border);border-radius:13px;padding:11px 14px;cursor:pointer;display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:800;color:var(--navy);transition:background .18s,border-color .18s,transform .12s;user-select:none}
.cmd-chip:hover{background:var(--light);border-color:rgba(100,112,210,.3);transform:translateY(-1px)}
.cmd-chip-icon{font-size:16px;color:var(--accent)}
.cmd-chip-en{font-size:11px;font-weight:700;color:var(--muted)}
.cmd-chip-ar{font-size:11px;font-weight:700;color:var(--amber);direction:rtl}

/* ── a11y settings ── */
.a11y-row{display:flex;align-items:center;justify-content:space-between;padding:13px 0;border-bottom:1px solid rgba(100,112,210,.07)}
.a11y-row:last-child{border-bottom:none}
.a11y-label{display:flex;align-items:center;gap:9px;font-size:14px;font-weight:700;color:var(--navy)}
.a11y-label i{color:var(--muted);width:16px}
.toggle-cb{width:38px;height:22px;border-radius:99px;border:none;cursor:pointer;position:relative;background:#d1d5db;transition:background .2s;flex-shrink:0}
.toggle-cb.on{background:var(--accent)}
.toggle-cb::after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .2s;box-shadow:0 1px 4px rgba(0,0,0,.2)}
.toggle-cb.on::after{transform:translateX(16px)}
.font-row{display:flex;align-items:center;gap:6px}
.font-btn{width:30px;height:30px;border:1.5px solid var(--border);border-radius:9px;background:var(--light);color:var(--accent);font-size:13px;font-weight:900;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s}
.font-btn:hover{background:#e4e7ff}
.font-val{font-size:12px;font-weight:800;color:var(--muted);min-width:30px;text-align:center;font-family:var(--mono)}

/* ── No-support banner ── */
.no-support{background:#fef3c7;border:1.5px solid rgba(245,158,11,.25);border-radius:14px;padding:14px 18px;font-size:13px;font-weight:700;color:#78350f;display:none;align-items:center;gap:10px;margin-top:16px}
.no-support i{font-size:18px;color:#d97706}
.no-support.show{display:flex}

/* ── Toast ── */
#toast{position:fixed;bottom:28px;right:28px;z-index:8000;background:var(--navy);color:#fff;font-size:13px;font-weight:800;padding:12px 20px;border-radius:13px;box-shadow:0 8px 30px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px;opacity:0;transform:translateY(8px);transition:opacity .25s,transform .25s;pointer-events:none}
#toast.show{opacity:1;transform:translateY(0)}
@media(max-width:600px){.hero{padding:28px 22px 36px}}
</style>
</head>
<body>

<div id="toast"><i class="fa-solid fa-check"></i> <span id="toastMsg">Done</span></div>

<div class="wrap">

<!-- Hero -->
<div class="hero">
  <div class="hero-orb hero-orb-1"></div><div class="hero-orb hero-orb-2"></div>
  <div class="hero-inner">
    <div class="hero-text">
      <a href="<?= htmlspecialchars($back_link) ?>" class="hero-back"><i class="fa-solid fa-arrow-left"></i> Back</a>
      <div class="hero-badge"><i class="fa-solid fa-microphone-lines"></i> Accessibility Feature</div>
      <h1>AI <span>Voice Companion</span></h1>
      <p>Navigate Rafiq with your voice — in English, Arabic, or Franco Arabic. Say a command and I'll take you there.</p>
      <div class="hero-tags">
        <span class="hero-tag"><i class="fa-solid fa-microphone"></i> Voice Commands</span>
        <span class="hero-tag"><i class="fa-solid fa-language"></i> English + Arabic + Franco</span>
        <span class="hero-tag"><i class="fa-solid fa-volume-high"></i> Voice Responses</span>
        <span class="hero-tag"><i class="fa-solid fa-universal-access"></i> Hands-Free</span>
      </div>
    </div>
    <div class="orb-wrap">
      <div class="voice-orb" id="voiceOrb"><i class="fa-solid fa-microphone" id="orbIco"></i></div>
      <div class="orb-hint">Click to speak</div>
    </div>
  </div>
</div>

<!-- No support banner -->
<div class="no-support" id="noSupport">
  <i class="fa-solid fa-triangle-exclamation"></i>
  <span>Your browser does not support the Web Speech API. Please use Google Chrome or Microsoft Edge for voice input.</span>
</div>

<!-- Main card: chat -->
<div class="card" style="margin-top:28px">
  <div class="card-head">
    <div class="card-head-icon"><i class="fa-solid fa-comments"></i></div>
    <div><div class="card-head-title">Voice Chat</div><div class="card-head-sub">Speak or type a command</div></div>
  </div>

  <!-- Language selector -->
  <div class="lang-select-row">
    <span class="lang-select-lbl"><i class="fa-solid fa-language"></i> Recognition language:</span>
    <select class="lang-sel" id="langSel">
      <option value="en-US" selected>English (US)</option>
      <option value="ar-EG">Arabic — Egyptian (ar-EG)</option>
      <option value="ar-SA">Arabic — Standard (ar-SA)</option>
      <option value="auto">Auto-detect</option>
    </select>
    <span style="font-size:12px;font-weight:700;color:var(--muted)">Current: <span id="langChip" style="color:var(--accent)">English</span></span>
  </div>

  <!-- Status bar -->
  <div style="padding:0 18px 0">
    <div class="status-bar" id="statusBar">
      <div class="status-dot idle" id="statusDot"></div>
      <span class="status-label" id="statusLabel">Ready — click microphone or type a command</span>
      <span class="lang-chip" id="statusLangChip">EN</span>
    </div>
  </div>

  <!-- Live transcript -->
  <div style="padding:0 18px 12px">
    <div class="transcript-label">What I heard:</div>
    <div class="transcript-box" id="transcriptBox">—</div>
  </div>

  <!-- Chat messages -->
  <div class="chat-box" id="chatBox"></div>

  <!-- Text input -->
  <div class="input-row">
    <input type="text" id="textInput" placeholder="Type a command or question..." autocomplete="off">
    <button class="send-btn" id="sendBtn" title="Send"><i class="fa-solid fa-paper-plane"></i></button>
  </div>

  <!-- Controls -->
  <div class="controls-row">
    <button class="btn btn-mic" id="micBtn"><i class="fa-solid fa-microphone" id="micIco"></i> <span id="micLbl">Start Listening</span></button>
    <button class="btn btn-secondary" id="stopSpeakBtn"><i class="fa-solid fa-stop"></i> Stop Speaking</button>
    <button class="btn btn-secondary" id="clearChatBtn"><i class="fa-solid fa-broom"></i> Clear Chat</button>
  </div>
</div>

<!-- Quick commands -->
<div class="card">
  <div class="card-head">
    <div class="card-head-icon"><i class="fa-solid fa-bolt"></i></div>
    <div><div class="card-head-title">Quick Commands</div><div class="card-head-sub">Click any command to run it</div></div>
  </div>
  <div class="card-body">
    <div class="cmd-grid" id="cmdGrid"></div>
  </div>
</div>

<!-- Command log -->
<div class="card">
  <div class="card-head">
    <div class="card-head-icon"><i class="fa-solid fa-list-check"></i></div>
    <div><div class="card-head-title">Command Log</div><div class="card-head-sub">What you said, intent detected, action taken</div></div>
  </div>
  <div class="card-body">
    <div class="cmd-log" id="cmdLog">
      <div class="cmd-log-empty">No commands yet</div>
    </div>
  </div>
</div>

<!-- Accessibility settings -->
<div class="card">
  <div class="card-head">
    <div class="card-head-icon"><i class="fa-solid fa-universal-access"></i></div>
    <div><div class="card-head-title">Accessibility Settings</div><div class="card-head-sub">Also controllable by voice</div></div>
  </div>
  <div class="card-body">
    <div class="a11y-row">
      <div class="a11y-label"><i class="fa-solid fa-circle-half-stroke"></i> High Contrast Mode</div>
      <button class="toggle-cb" id="hcToggle" aria-label="High contrast"></button>
    </div>
    <div class="a11y-row">
      <div class="a11y-label"><i class="fa-solid fa-text-height"></i> Font Size</div>
      <div class="font-row">
        <button class="font-btn" id="fontDec">−</button>
        <span class="font-val" id="fontVal">16px</span>
        <button class="font-btn" id="fontInc">+</button>
      </div>
    </div>
    <div class="a11y-row">
      <div class="a11y-label"><i class="fa-solid fa-volume-high"></i> Speak Responses Aloud</div>
      <button class="toggle-cb on" id="ttsToggle" aria-label="TTS"></button>
    </div>
  </div>
</div>

</div>

<script>
'use strict';

/* ══════════════════════════════════════════════
   CONFIG — PHP-injected links
══════════════════════════════════════════════ */
const LINKS = <?= $vc_links ?>;

/* ══════════════════════════════════════════════
   INTENT DATABASE
   Three keys per intent: en, ar, franco
   All entries are KEYWORDS (partial match OK)
══════════════════════════════════════════════ */
const INTENTS = [
  {
    id: 'go_home',
    icon: 'fa-house',
    label: 'Go Home',
    en:     ['go home','open home','home page','homepage','main page','go to home'],
    ar:     ['الرئيسية','الصفحة الرئيسية','الهوم','افتح الرئيسية','ارجع للرئيسية','روح الرئيسية'],
    franco: ['el home','el raesiya','eftah home','eftah el home','el raisia','roo7 home'],
    action: () => navigate(LINKS.home),
    response: { en: 'Opening the home page.', ar: 'جاري فتح الصفحة الرئيسية.' }
  },
  {
    id: 'open_bookings',
    icon: 'fa-calendar-check',
    label: 'My Bookings',
    en:     ['my bookings','open bookings','bookings','my appointments','open appointments'],
    ar:     ['حجوزاتي','افتح الحجوزات','الحجوزات','مواعيدي'],
    franco: ['7agozaty','bookings','el 7agozat','mawa3edy','eftah bookings'],
    action: () => navigate(LINKS.bookings),
    response: { en: 'Opening your bookings.', ar: 'جاري فتح الحجوزات.' }
  },
  {
    id: 'open_profile',
    icon: 'fa-circle-user',
    label: 'My Profile',
    en:     ['my profile','open profile','profile page','account'],
    ar:     ['ملفي الشخصي','الملف الشخصي','افتح البروفايل','حسابي'],
    franco: ['profile','el profile','7esaby','eftah profile','profaili'],
    action: () => navigate(LINKS.profile),
    response: { en: 'Opening your profile.', ar: 'جاري فتح الملف الشخصي.' }
  },
  {
    id: 'open_map',
    icon: 'fa-map',
    label: 'Open Map',
    en:     ['open map','go to map','show map','map'],
    ar:     ['افتح الخريطة','الخريطة','خريطة'],
    franco: ['el map','eftah el map','eftah map','el kharita'],
    action: () => navigate(LINKS.map),
    response: { en: 'Opening the map.', ar: 'جاري فتح الخريطة.' }
  },
  {
    id: 'open_sign_language',
    icon: 'fa-hands',
    label: 'Sign Language AI',
    en:     ['sign language','sign language ai','open sign language','asl','hand signs','open asl'],
    ar:     ['لغة الإشارة','افتح لغة الإشارة','اشارة','فتح لغة الاشارة'],
    franco: ['sign language','lughet el ishara','eftah sign','eftah sign language','el isharah'],
    action: () => navigate(LINKS.sl),
    response: { en: 'Opening Sign Language AI.', ar: 'جاري فتح مساعد لغة الإشارة.' }
  },
  {
    id: 'open_ocr',
    icon: 'fa-eye',
    label: 'OCR Reader',
    en:     ['ocr','ocr reader','open ocr','smart reader','read image','scan text','text reader'],
    ar:     ['قارئ النصوص','افتح قارئ النصوص','ocr','اقرأ صورة','مسح النص'],
    franco: ['ocr','eftah ocr','el ocr','reader','el qare2','kare2 nosoos'],
    action: () => navigate(LINKS.ocr),
    response: { en: 'Opening the Smart OCR Reader.', ar: 'جاري فتح قارئ النصوص.' }
  },
  {
    id: 'open_voice',
    icon: 'fa-microphone',
    label: 'Voice Companion',
    en:     ['voice companion','open voice','voice ai'],
    ar:     ['المساعد الصوتي','افتح المساعد الصوتي','مساعد صوتي'],
    franco: ['voice','el voice','mosa3ed soty','eftah voice'],
    action: () => { addBubble("You're already on the Voice Companion page!", 'assistant'); return null; },
    response: { en: "You're already here!", ar: 'أنت بالفعل على هذه الصفحة!' }
  },
  {
    id: 'high_contrast_on',
    icon: 'fa-circle-half-stroke',
    label: 'High Contrast ON',
    en:     ['high contrast','turn on high contrast','enable high contrast','contrast on','dark mode'],
    ar:     ['شغل التباين العالي','تباين عالي','وضع التباين','فعل التباين'],
    franco: ['high contrast','sha8al contrast','sha8el contrast','eftah contrast','3aly contrast'],
    action: () => setHighContrast(true),
    response: { en: 'High contrast enabled.', ar: 'تم تفعيل التباين العالي.' }
  },
  {
    id: 'high_contrast_off',
    icon: 'fa-sun',
    label: 'High Contrast OFF',
    en:     ['turn off high contrast','disable high contrast','contrast off','normal mode'],
    ar:     ['اقفل التباين','وقف التباين','ألغي التباين'],
    franco: ['e2fel contrast','wa2af contrast','2efl high contrast','normal mode'],
    action: () => setHighContrast(false),
    response: { en: 'High contrast disabled.', ar: 'تم إيقاف التباين العالي.' }
  },
  {
    id: 'font_increase',
    icon: 'fa-text-height',
    label: 'Bigger Text',
    en:     ['increase text','bigger text','larger text','increase font','zoom in text','make bigger','font up','text up'],
    ar:     ['كبر الخط','كبر الكلام','زود حجم الخط','خط أكبر'],
    franco: ['kbr el font','kaber font','kbar el kalam','zawed font','font akbar','text akbar'],
    action: () => changeFontSize(2),
    response: { en: 'Text size increased.', ar: 'تم تكبير الخط.' }
  },
  {
    id: 'font_decrease',
    icon: 'fa-text-height',
    label: 'Smaller Text',
    en:     ['decrease text','smaller text','reduce font','font down','text down','make smaller','zoom out text'],
    ar:     ['صغر الخط','قلل حجم الخط','خط أصغر'],
    franco: ['soghar el font','so8ar font','so8har kalam','qalel font','font as8ar'],
    action: () => changeFontSize(-2),
    response: { en: 'Text size decreased.', ar: 'تم تصغير الخط.' }
  },
  {
    id: 'font_reset',
    icon: 'fa-rotate',
    label: 'Reset Text Size',
    en:     ['reset text','reset font','default font','normal text'],
    ar:     ['رجع الخط الطبيعي','الخط الافتراضي'],
    franco: ['reset font','raga3 font','default'],
    action: () => { fontSize=16; applyFont(); },
    response: { en: 'Font size reset to default.', ar: 'تم إعادة الخط للوضع الطبيعي.' }
  },
  {
    id: 'read_page',
    icon: 'fa-volume-high',
    label: 'Read Page',
    en:     ['read page','read this page','read aloud','speak page','read screen'],
    ar:     ['اقرأ الصفحة','اقرأ هذه الصفحة','قراءة الصفحة','اقرأ الشاشة'],
    franco: ['e2ra el page','e2ra el saf7a','e2ra','read el page','kalam el page'],
    action: () => readPageContent(),
    response: { en: 'Reading the page aloud.', ar: 'جاري قراءة الصفحة.' }
  },
  {
    id: 'stop_reading',
    icon: 'fa-stop',
    label: 'Stop Reading',
    en:     ['stop reading','stop speaking','stop talking','quiet','silence','shut up'],
    ar:     ['وقف القراءة','اسكت','وقف الكلام','اصمت'],
    franco: ['wa2af','stop','wa2af el kalam','oske t','wa2af e2ra'],
    action: () => { window.speechSynthesis.cancel(); },
    response: { en: 'Stopped.', ar: 'توقف.', speak: false }
  },
  {
    id: 'logout',
    icon: 'fa-right-from-bracket',
    label: 'Logout',
    en:     ['logout','log out','sign out','exit'],
    ar:     ['تسجيل الخروج','اخرج','اطلع'],
    franco: ['logout','te5rog','et2al3','ta5rog','5rog'],
    action: () => navigate(LINKS.logout),
    response: { en: 'Logging out. Goodbye!', ar: 'جاري تسجيل الخروج. مع السلامة!' }
  },
  {
    id: 'help',
    icon: 'fa-circle-question',
    label: 'Help',
    en:     ['help','what can you do','what commands','commands','options','guide'],
    ar:     ['ساعدني','مساعدة','إيه اللي تقدر تعمله','اوامر'],
    franco: ['sa3edny','mosa3da','help','eh el awamir','commands'],
    action: () => showHelp(),
    response: null
  },
  {
    id: 'greeting',
    icon: 'fa-hand-wave',
    label: 'Greeting',
    en:     ['hello','hi','hey','good morning','good evening','good afternoon','salam'],
    ar:     ['السلام عليكم','مرحبا','اهلا','صباح الخير','مساء الخير'],
    franco: ['salam','ahlan','marhaba','alo','sabah el kheir','masa el kheir'],
    action: () => showGreeting(),
    response: null
  },
];

/* ══════════════════════════════════════════════
   TEXT NORMALISATION
══════════════════════════════════════════════ */
function normalize(text) {
  let t = (text || '').toLowerCase();
  // Remove Arabic diacritics
  t = t.replace(/[ً-ٰٟـ]/g, '');
  // Normalise Arabic letters (أ إ آ → ا, ة → ه, ى → ي)
  t = t.replace(/[أإآٱ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
  // Franco/common substitutions
  t = t.replace(/3/g, 'ع').replace(/7/g, 'ح').replace(/2/g, 'ء').replace(/5/g, 'خ').replace(/8/g, 'غ').replace(/9/g, 'ق').replace(/0/g, 'ض');
  // Remove punctuation, collapse spaces
  t = t.replace(/[^؀-ۿa-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
  return t;
}

function matchIntent(raw) {
  const norm = normalize(raw);
  // Check all intents, return highest scoring match
  let best = null, bestScore = 0;
  for(const intent of INTENTS) {
    for(const lang of ['en','ar','franco']) {
      for(const kw of (intent[lang]||[])) {
        const nkw = normalize(kw);
        if(norm.includes(nkw) || nkw.includes(norm)) {
          const score = nkw.length; // longer match = more specific
          if(score > bestScore) { bestScore = score; best = intent; }
        }
      }
    }
  }
  return best;
}

/* ══════════════════════════════════════════════
   STATE
══════════════════════════════════════════════ */
let recognizing = false;
let recognition = null;
let ttsEnabled  = true;
let hcOn        = false;
let fontSize    = 16;
const hasSR     = !!(window.SpeechRecognition || window.webkitSpeechRecognition);

/* ══════════════════════════════════════════════
   DOM
══════════════════════════════════════════════ */
const $ = id => document.getElementById(id);
const D = {
  voiceOrb:$('voiceOrb'), orbIco:$('orbIco'),
  statusDot:$('statusDot'), statusLabel:$('statusLabel'), statusLangChip:$('statusLangChip'),
  chatBox:$('chatBox'), textInput:$('textInput'), sendBtn:$('sendBtn'),
  micBtn:$('micBtn'), micIco:$('micIco'), micLbl:$('micLbl'),
  stopSpeakBtn:$('stopSpeakBtn'), clearChatBtn:$('clearChatBtn'),
  langSel:$('langSel'), langChip:$('langChip'),
  transcriptBox:$('transcriptBox'),
  cmdGrid:$('cmdGrid'), cmdLog:$('cmdLog'),
  hcToggle:$('hcToggle'), ttsToggle:$('ttsToggle'),
  fontDec:$('fontDec'), fontInc:$('fontInc'), fontVal:$('fontVal'),
  noSupport:$('noSupport'),
  toast:$('toast'), toastMsg:$('toastMsg'),
};

/* ══════════════════════════════════════════════
   QUICK COMMAND CHIPS
══════════════════════════════════════════════ */
const QUICK = [
  { icon:'fa-house',        en:'Open Home',         ar:'افتح الرئيسية',    phrase:'go home' },
  { icon:'fa-calendar',     en:'My Bookings',        ar:'حجوزاتي',         phrase:'open bookings' },
  { icon:'fa-map',          en:'Open Map',           ar:'افتح الخريطة',    phrase:'open map' },
  { icon:'fa-hands',        en:'Sign Language AI',   ar:'لغة الإشارة',     phrase:'open sign language' },
  { icon:'fa-eye',          en:'OCR Reader',         ar:'قارئ النصوص',     phrase:'open ocr' },
  { icon:'fa-circle-half-stroke', en:'High Contrast', ar:'تباين عالي',    phrase:'high contrast' },
  { icon:'fa-text-height',  en:'Bigger Text',        ar:'كبر الخط',        phrase:'increase text' },
  { icon:'fa-text-height',  en:'Smaller Text',       ar:'صغر الخط',        phrase:'decrease text' },
  { icon:'fa-volume-high',  en:'Read Page',          ar:'اقرأ الصفحة',     phrase:'read page' },
  { icon:'fa-stop',         en:'Stop Speaking',      ar:'وقف الكلام',      phrase:'stop reading' },
  { icon:'fa-circle-question', en:'Help',            ar:'ساعدني',          phrase:'help' },
  { icon:'fa-right-from-bracket', en:'Logout',       ar:'اخرج',            phrase:'logout' },
];

QUICK.forEach(q => {
  const chip = document.createElement('div');
  chip.className = 'cmd-chip';
  chip.innerHTML = `<div class="cmd-chip-icon"><i class="fa-solid ${q.icon}"></i></div>
    <div>${q.en}</div><div class="cmd-chip-ar">${q.ar}</div>`;
  chip.addEventListener('click', () => processCommand(q.phrase, 'click'));
  chip.addEventListener('keydown', e => { if(e.key==='Enter'||e.key===' ') processCommand(q.phrase,'click'); });
  chip.setAttribute('tabindex','0');
  D.cmdGrid.appendChild(chip);
});

/* ══════════════════════════════════════════════
   COMMAND PROCESSOR
══════════════════════════════════════════════ */
function processCommand(raw, source) {
  const text = raw.trim();
  if(!text) return;

  if(source !== 'assistant') addBubble(text, 'user');

  const intent = matchIntent(text);

  setStatus('processing', 'Processing command...');

  setTimeout(() => {
    if(!intent) {
      logCommand(text, 'unknown', 'fail');
      const reply = "Sorry, I didn't understand. Try saying: open home, open OCR reader, high contrast, increase text size, read page, or help.";
      addBubble(reply, 'assistant');
      speak(reply, 'en');
      setStatus('error', 'Command not understood');
      return;
    }

    logCommand(text, intent.id, 'ok');

    // Determine response language
    const arabic = hasArabic(text);
    const respLang = arabic ? 'ar' : 'en';
    const resp = intent.response;

    let replyText = '';
    if(resp) {
      replyText = resp[respLang] || resp.en || '';
    }

    // Execute action
    let result = null;
    if(typeof intent.action === 'function') result = intent.action();

    // Respond
    if(replyText) {
      if(result !== null || resp?.speak !== false) {
        addBubble(replyText, 'assistant');
        if(resp?.speak !== false) speak(replyText, respLang);
      }
    }

    setStatus('executed', 'Command executed: ' + intent.id.replace(/_/g,' '));
    setTimeout(() => setStatus('idle', 'Ready'), 2500);
  }, 180);
}

/* ── special actions ── */
function showHelp() {
  const msg = `Commands I understand:

🏠 Navigation: "open home", "my bookings", "open map", "open OCR reader", "sign language", "logout"

✨ Accessibility: "high contrast", "increase text size", "decrease text size", "read page", "stop reading"

🌍 Languages: You can speak English, Arabic (عربي), or Franco Arabic (مثلاً: eftah el home, kbr el font, sha8al contrast)

🎙️ Tip: Click the microphone button or use the quick command chips below.`;
  addBubble(msg, 'assistant');
  speak('You can navigate by saying: open home, open OCR reader, my bookings, sign language AI, high contrast, increase text, or read page. I also understand Arabic and Franco Arabic commands.', 'en');
  setStatus('executed','Help shown');
  setTimeout(() => setStatus('idle','Ready'), 3000);
}

function showGreeting() {
  const hr = new Date().getHours();
  const g  = hr<12 ? 'Good morning' : hr<18 ? 'Good afternoon' : 'Good evening';
  const msg = `${g}! I'm Rafiq Voice Companion. I understand English, Arabic, and Franco Arabic. Say "help" to see all commands.`;
  addBubble(msg, 'assistant');
  speak(msg, 'en');
  setStatus('executed','Greeting');
  setTimeout(() => setStatus('idle','Ready'), 3000);
}

function readPageContent() {
  const txt = document.body.innerText.replace(/\s+/g,' ').trim().slice(0,1000);
  speak(txt, 'en');
}

/* ══════════════════════════════════════════════
   NAVIGATION
══════════════════════════════════════════════ */
function navigate(url) {
  if(!url || url === window.location.href) return;
  // Use window.location.href — works with PHP routing
  setTimeout(() => { window.location.href = url; }, 700);
}

/* ══════════════════════════════════════════════
   SPEECH RECOGNITION
══════════════════════════════════════════════ */
if(!hasSR) D.noSupport.classList.add('show');

D.langSel.addEventListener('change', () => {
  const labels = {'en-US':'English','ar-EG':'Arabic (EG)','ar-SA':'Arabic','auto':'Auto'};
  D.langChip.textContent = labels[D.langSel.value] || D.langSel.value;
  D.statusLangChip.textContent = D.langSel.value.toUpperCase().slice(0,2);
  if(recognizing) { stopListening(); startListening(); }
});

D.micBtn.addEventListener('click',   () => recognizing ? stopListening() : startListening());
D.voiceOrb.addEventListener('click', () => recognizing ? stopListening() : startListening());

function setupRec() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if(!SR) return null;
  const r = new SR();
  const sel = D.langSel.value;
  r.lang = sel === 'auto' ? '' : sel;
  r.continuous = false;
  r.interimResults = true;
  r.maxAlternatives = 3;

  r.onstart = () => {
    recognizing = true;
    setStatus('listening','Listening...');
    D.micBtn.classList.add('active');
    D.micIco.className = 'fa-solid fa-stop';
    D.micLbl.textContent = 'Stop Listening';
    D.voiceOrb.classList.add('listening');
    D.orbIco.className = 'fa-solid fa-microphone';
    D.transcriptBox.textContent = '...';
  };

  r.onresult = e => {
    let interim = '', final = '';
    for(let i = e.resultIndex; i < e.results.length; i++) {
      if(e.results[i].isFinal) final += e.results[i][0].transcript;
      else interim += e.results[i][0].transcript;
    }
    D.transcriptBox.textContent = interim || final || '...';
    if(final.trim()) processCommand(final.trim(), 'voice');
  };

  r.onerror = e => {
    if(e.error === 'no-speech') {
      addBubble("I didn't hear anything. Please try again.", 'assistant', true);
    } else if(e.error === 'not-allowed') {
      D.noSupport.classList.add('show');
      D.noSupport.querySelector('span').textContent = 'Microphone permission denied. Please allow microphone access in browser settings.';
    }
    stopListening();
  };

  r.onend = () => stopListening();
  return r;
}

function startListening() {
  if(!hasSR) { alert('Voice not supported. Please use Chrome or Edge.'); return; }
  recognition = setupRec();
  if(recognition) recognition.start();
}

function stopListening() {
  recognizing = false;
  if(recognition) { try { recognition.stop(); } catch(e){} }
  setStatus('idle','Ready');
  D.micBtn.classList.remove('active');
  D.micIco.className = 'fa-solid fa-microphone';
  D.micLbl.textContent = 'Start Listening';
  D.voiceOrb.classList.remove('listening');
  D.orbIco.className = 'fa-solid fa-microphone';
}

/* ══════════════════════════════════════════════
   TEXT INPUT
══════════════════════════════════════════════ */
D.sendBtn.addEventListener('click', sendText);
D.textInput.addEventListener('keydown', e => { if(e.key === 'Enter') sendText(); });

function sendText() {
  const val = D.textInput.value.trim();
  if(!val) return;
  D.textInput.value = '';
  D.transcriptBox.textContent = val;
  processCommand(val, 'text');
}

/* ══════════════════════════════════════════════
   STATUS
══════════════════════════════════════════════ */
function setStatus(state, label) {
  D.statusDot.className = 'status-dot ' + state;
  D.statusLabel.textContent = label;
}

/* ══════════════════════════════════════════════
   CHAT BUBBLES
══════════════════════════════════════════════ */
function addBubble(text, role, system=false) {
  const wrap = document.createElement('div');
  wrap.className = 'msg ' + role;
  const av = document.createElement('div');
  av.className = 'msg-avatar';
  av.innerHTML = role==='user' ? '<i class="fa-solid fa-user"></i>' : '<i class="fa-solid fa-robot"></i>';
  const bbl = document.createElement('div');
  bbl.className = 'msg-bubble' + (system?' system':'');
  bbl.textContent = text;
  if(hasArabic(text)) { bbl.dir='rtl'; bbl.style.textAlign='right'; }
  wrap.appendChild(av); wrap.appendChild(bbl);
  D.chatBox.appendChild(wrap);
  D.chatBox.scrollTop = D.chatBox.scrollHeight;
}

D.clearChatBtn.addEventListener('click', () => { D.chatBox.innerHTML = ''; addGreeting(); });

/* ══════════════════════════════════════════════
   TTS
══════════════════════════════════════════════ */
D.stopSpeakBtn.addEventListener('click', () => { window.speechSynthesis.cancel(); D.voiceOrb.classList.remove('speaking'); });
D.ttsToggle.addEventListener('click', () => { ttsEnabled=!ttsEnabled; D.ttsToggle.classList.toggle('on',ttsEnabled); });

function speak(text, langHint='en') {
  if(!ttsEnabled) return;
  window.speechSynthesis.cancel();
  const arabic = hasArabic(text) || langHint==='ar';
  const utter  = new SpeechSynthesisUtterance(text);
  utter.lang   = arabic ? 'ar-SA' : 'en-US';
  utter.rate   = 0.92;
  const voices = window.speechSynthesis.getVoices();
  const voice  = voices.find(v => arabic ? v.lang.startsWith('ar') : v.lang.startsWith('en-US'))
              || voices.find(v => arabic ? v.lang.startsWith('ar') : v.lang.startsWith('en'));
  if(voice) utter.voice = voice;
  utter.onstart = () => { D.voiceOrb.classList.add('speaking'); D.orbIco.className='fa-solid fa-volume-high'; };
  utter.onend   = () => { D.voiceOrb.classList.remove('speaking'); D.orbIco.className='fa-solid fa-microphone'; };
  window.speechSynthesis.speak(utter);
}

/* ══════════════════════════════════════════════
   COMMAND LOG
══════════════════════════════════════════════ */
let logCount = 0;
function logCommand(said, intent, result) {
  if(logCount === 0) D.cmdLog.innerHTML = '';
  logCount++;
  const item = document.createElement('div');
  item.className = 'cmd-log-item';
  item.innerHTML = `<span class="cli-said">"${escHtml(said)}"</span>
    <span class="cli-intent">${escHtml(intent.replace(/_/g,' '))}</span>
    <span class="cli-result ${result}">${result==='ok'?'✓ Executed':'✗ Unknown'}</span>`;
  D.cmdLog.insertBefore(item, D.cmdLog.firstChild);
  if(logCount > 20) D.cmdLog.removeChild(D.cmdLog.lastChild);
}

/* ══════════════════════════════════════════════
   ACCESSIBILITY
══════════════════════════════════════════════ */
D.hcToggle.addEventListener('click', () => setHighContrast(!hcOn));
D.fontInc.addEventListener('click', () => changeFontSize(2));
D.fontDec.addEventListener('click', () => changeFontSize(-2));

function setHighContrast(on) {
  hcOn = on;
  document.body.classList.toggle('hc', hcOn);
  D.hcToggle.classList.toggle('on', hcOn);
}

function changeFontSize(delta) {
  fontSize = Math.max(12, Math.min(28, fontSize + delta));
  applyFont();
}

function applyFont() {
  document.documentElement.style.fontSize = fontSize+'px';
  D.fontVal.textContent = fontSize+'px';
}

/* ══════════════════════════════════════════════
   UTILS
══════════════════════════════════════════════ */
function hasArabic(t) { return /[؀-ۿ]/.test(t); }
function escHtml(t) { return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

let toastT=null;
function showToast(msg) {
  D.toastMsg.textContent=msg; D.toast.classList.add('show');
  clearTimeout(toastT); toastT=setTimeout(()=>D.toast.classList.remove('show'),2400);
}

/* ══════════════════════════════════════════════
   GREETING ON LOAD
══════════════════════════════════════════════ */
function addGreeting() {
  const hr = new Date().getHours();
  const g  = hr<12?'Good morning':hr<18?'Good afternoon':'Good evening';
  addBubble(`${g}! I'm Rafiq Voice Companion. I understand English, Arabic (عربي), and Franco Arabic. Click the microphone or type a command. Say "help" to see everything I can do.`, 'assistant');
}

/* ══════════════════════════════════════════════
   BOOT
══════════════════════════════════════════════ */
addGreeting();
speak("Rafiq Voice Companion is ready. Say a command or press the microphone. Say help for all available commands.", 'en');

if(window.speechSynthesis) {
  window.speechSynthesis.getVoices();
  window.speechSynthesis.addEventListener('voiceschanged', () => window.speechSynthesis.getVoices());
}

setStatus('idle','Ready');

window.addEventListener('beforeunload', () => {
  window.speechSynthesis.cancel();
  if(recognition) { try { recognition.stop(); } catch(e){} }
});
</script>
</body>
</html>
