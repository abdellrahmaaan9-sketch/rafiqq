<?php
session_start();
$_doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_dir  = str_replace('\\', '/', dirname(__DIR__));
$_rel  = ltrim(str_replace($_doc, '', $_dir), '/');
$_base = '/' . $_rel;

$back_link = "$_base/general/login.php";
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'patient') $back_link = "$_base/patient/patient_homepage.php";
    elseif ($_SESSION['role'] === 'provider') {
        $map = [
            'doctor'      => "$_base/providers/doctor/doctor_homepage.php",
            'interpreter' => "$_base/providers/interpreter/int_homepage.php",
            'driver'      => "$_base/providers/driver/driver_portal.php",
            'caregiver'   => "$_base/providers/caregiver/caregiver_home.php"
        ];
        $back_link = $map[$_SESSION['provider_type'] ?? ''] ?? $back_link;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rafiq | Smart OCR Reader</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════
   RESET & DESIGN TOKENS  (identical to rest of Rafiq site)
══════════════════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#1e2040;--purple:#353b69;--accent:#6470d2;--a2:#494788;
  --light:#eef0ff;--green:#16a34a;--red:#dc2626;--amber:#d97706;
  --bg:#f4f5fb;--card:#fff;--text:#1e2040;--muted:#6b7080;
  --border:rgba(100,112,210,.13);
  --sh:0 4px 20px rgba(30,32,64,.08);
  --sh-lg:0 16px 48px rgba(30,32,64,.13);
  --mono:'JetBrains Mono',monospace;
}
html{scroll-behavior:smooth}
body{font-family:"Nunito",system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
:focus-visible{outline:2.5px solid var(--accent);outline-offset:3px;border-radius:6px}

/* ── LOADING OVERLAY ── */
#ocrInit{
  position:fixed;inset:0;z-index:9000;
  background:rgba(20,22,50,.92);backdrop-filter:blur(10px);
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;
  color:#fff;transition:opacity .5s
}
#ocrInit.hidden{opacity:0;pointer-events:none}
.init-icon{width:70px;height:70px;border-radius:22px;
  background:linear-gradient(135deg,var(--accent),var(--a2));
  display:flex;align-items:center;justify-content:center;font-size:30px;
  box-shadow:0 12px 40px rgba(100,112,210,.45);
  animation:initFloat 2s ease-in-out infinite}
@keyframes initFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.init-title{font-size:20px;font-weight:900;letter-spacing:-.3px}
.init-sub{font-size:13px;font-weight:700;opacity:.6;margin-top:-6px}
.init-bar{width:200px;height:5px;background:rgba(255,255,255,.12);border-radius:99px;overflow:hidden}
.init-fill{height:100%;width:0;background:linear-gradient(90deg,var(--accent),var(--a2));
  border-radius:99px;transition:width .3s ease}
.init-step{font-size:12px;font-weight:700;opacity:.55;min-height:18px}

/* ── LAYOUT ── */
.wrap{max-width:1220px;margin:0 auto;padding:0 24px 80px}

/* ── HERO ── */
.hero{
  background:linear-gradient(135deg,var(--navy) 0%,#2d1b69 50%,var(--accent) 100%);
  margin:0 -24px;padding:38px 52px 46px;
  color:#fff;position:relative;overflow:hidden;border-radius:0 0 40px 40px
}
.hero-orb{position:absolute;border-radius:50%;pointer-events:none}
.hero-orb-1{width:380px;height:380px;top:-150px;right:-100px;background:rgba(255,255,255,.04)}
.hero-orb-2{width:200px;height:200px;bottom:-80px;left:3%;background:rgba(255,255,255,.03)}
.hero-orb-3{width:120px;height:120px;top:20%;right:28%;background:rgba(255,255,255,.025)}
.hero-inner{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:28px;flex-wrap:wrap}
.hero-text{flex:1;min-width:240px}
.hero-back{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 16px;border-radius:12px;
  border:1.5px solid rgba(255,255,255,.3);background:rgba(255,255,255,.1);
  color:#fff;font-size:13px;font-weight:800;text-decoration:none;
  margin-bottom:18px;transition:background .15s,transform .12s;backdrop-filter:blur(8px)
}
.hero-back:hover{background:rgba(255,255,255,.2);transform:translateX(-2px)}
.hero-badge{
  display:inline-flex;align-items:center;gap:8px;
  padding:6px 14px;border-radius:999px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
  font-size:10.5px;font-weight:900;letter-spacing:.07em;
  text-transform:uppercase;margin-bottom:14px
}
.hero h1{font-size:clamp(24px,3.8vw,42px);font-weight:900;letter-spacing:-.8px;line-height:1.06;margin-bottom:12px}
.hero h1 span{background:linear-gradient(90deg,#c4caff,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero p{font-size:14px;font-weight:600;color:rgba(255,255,255,.78);line-height:1.75;max-width:520px}
.hero-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.hero-tag{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 12px;border-radius:8px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);
  font-size:11.5px;font-weight:800;color:rgba(255,255,255,.85)
}
.hero-widget{
  background:rgba(255,255,255,.1);backdrop-filter:blur(16px);
  border:1.5px solid rgba(255,255,255,.2);border-radius:24px;
  padding:26px 30px;text-align:center;min-width:160px;flex-shrink:0
}
.hero-widget-icon{font-size:36px;margin-bottom:8px;opacity:.9}
.hero-widget-num{font-size:34px;font-weight:900;line-height:1}
.hero-widget-label{font-size:11px;font-weight:800;opacity:.6;text-transform:uppercase;letter-spacing:.06em;margin-top:4px}

/* ── CARD SYSTEM (matches site-wide) ── */
.card{background:var(--card);border:1px solid var(--border);border-radius:24px;box-shadow:var(--sh);overflow:hidden}
.card-head{padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.card-head-icon{width:40px;height:40px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:17px;background:var(--light);flex-shrink:0;color:var(--accent)}
.card-head-title{font-size:15px;font-weight:900;color:var(--navy)}
.card-head-sub{font-size:11.5px;font-weight:700;color:var(--muted);margin-top:1px}
.card-body{padding:20px 22px}

/* ── BUTTONS (matches site-wide) ── */
.btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:11px 20px;border-radius:13px;
  font-size:13.5px;font-weight:800;font-family:inherit;
  cursor:pointer;border:none;transition:all .18s;text-decoration:none;
  user-select:none
}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--a2));color:#fff;box-shadow:0 4px 16px rgba(100,112,210,.25)}
.btn-primary:hover{box-shadow:0 6px 24px rgba(100,112,210,.4);transform:translateY(-1px)}
.btn-primary:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
.btn-secondary{background:var(--light);color:var(--accent);border:1.5px solid var(--border)}
.btn-secondary:hover:not(:disabled){background:#e4e7ff;border-color:rgba(100,112,210,.3)}
.btn-secondary:disabled{opacity:.45;cursor:not-allowed}
.btn-danger{background:#fef2f2;color:var(--red);border:1.5px solid rgba(220,38,38,.16)}
.btn-danger:hover:not(:disabled){background:#fee2e2;border-color:rgba(220,38,38,.3)}
.btn-danger:disabled{opacity:.45;cursor:not-allowed}
.btn-sm{padding:8px 14px;font-size:12px;border-radius:10px}
.btn-green{background:linear-gradient(135deg,#15803d,#16a34a);color:#fff;box-shadow:0 4px 14px rgba(22,163,74,.25)}
.btn-green:hover{box-shadow:0 6px 22px rgba(22,163,74,.38);transform:translateY(-1px)}
.btn-green:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}

/* ── INPUT GRID ── */
.input-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:28px}
@media(max-width:820px){.input-grid{grid-template-columns:1fr}}

/* ── CAMERA VIEWPORT ── */
.cam-viewport{
  position:relative;width:100%;aspect-ratio:4/3;
  background:#06070f;border-radius:18px;overflow:hidden;margin-bottom:14px;
  box-shadow:inset 0 0 0 1.5px rgba(100,112,210,.2)
}
#camVideo{width:100%;height:100%;object-fit:cover;display:block}
.cam-off{
  position:absolute;inset:0;display:flex;flex-direction:column;
  align-items:center;justify-content:center;gap:10px;
  color:rgba(255,255,255,.35);font-weight:800
}
.cam-off i{font-size:44px;opacity:.2}
.cam-off span{font-size:13px}
/* corner decorations */
.cam-corner{position:absolute;z-index:3;width:18px;height:18px;border-color:rgba(100,112,210,.38);border-style:solid;pointer-events:none}
.cam-corner.tl{top:10px;left:10px;border-width:2px 0 0 2px;border-radius:4px 0 0 0}
.cam-corner.tr{top:10px;right:10px;border-width:2px 2px 0 0;border-radius:0 4px 0 0}
.cam-corner.bl{bottom:10px;left:10px;border-width:0 0 2px 2px;border-radius:0 0 0 4px}
.cam-corner.br{bottom:10px;right:10px;border-width:0 2px 2px 0;border-radius:0 0 4px 0}
.cam-corner.live{border-color:rgba(52,211,153,.65)!important}
/* capture flash */
.cam-flash{position:absolute;inset:0;background:#fff;opacity:0;pointer-events:none;z-index:9;transition:opacity .08s}
.cam-flash.pop{opacity:.7}
/* live scan line (shown during OCR) */
.scan-line{
  position:absolute;left:0;right:0;height:3px;z-index:6;
  background:linear-gradient(90deg,transparent 0%,var(--accent) 30%,rgba(100,112,210,.9) 50%,var(--accent) 70%,transparent 100%);
  box-shadow:0 0 18px rgba(100,112,210,.7),0 0 6px rgba(255,255,255,.4);
  display:none;animation:scanMove 1.6s ease-in-out infinite
}
.scan-line.active{display:block}
@keyframes scanMove{0%{top:0;opacity:.8}50%{opacity:1}100%{top:calc(100% - 3px);opacity:.8}}

/* cam buttons */
.cam-btns{display:flex;gap:8px;flex-wrap:wrap}
.btn-cam{background:linear-gradient(135deg,#15803d,#16a34a);color:#fff;box-shadow:0 4px 12px rgba(22,163,74,.25)}
.btn-cam:hover{box-shadow:0 6px 18px rgba(22,163,74,.38);transform:translateY(-1px)}
.btn-cam-stop{background:#fef2f2;color:var(--red);border:1.5px solid rgba(220,38,38,.18)}
.btn-cam-stop:hover{background:#fee2e2}

/* ── UPLOAD ZONE ── */
.upload-zone{
  border:2px dashed rgba(100,112,210,.25);border-radius:16px;
  padding:36px 20px;text-align:center;cursor:pointer;
  transition:border-color .2s,background .2s;
  position:relative;background:transparent;
  min-height:180px;display:flex;flex-direction:column;
  align-items:center;justify-content:center;gap:8px
}
.upload-zone:hover,.upload-zone.over{border-color:var(--accent);background:rgba(100,112,210,.04)}
.upload-zone input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-zone-icon{font-size:38px;color:var(--accent);opacity:.55}
.upload-zone-title{font-size:14px;font-weight:800;color:var(--navy)}
.upload-zone-sub{font-size:11.5px;font-weight:700;color:var(--muted)}
.upload-zone-paste{font-size:11px;font-weight:700;color:var(--accent);opacity:.7;margin-top:4px}
/* preview inside upload zone */
.upload-preview-wrap{
  width:100%;border-radius:12px;overflow:hidden;
  max-height:220px;margin-top:14px;display:none;position:relative
}
.upload-preview-wrap.show{display:block}
.upload-preview-wrap img{width:100%;max-height:220px;object-fit:contain;display:block}
.upload-preview-tag{
  position:absolute;top:8px;left:8px;
  background:rgba(30,32,64,.75);backdrop-filter:blur(6px);
  color:#fff;font-size:10px;font-weight:800;
  padding:3px 9px;border-radius:6px
}

/* ── OCR PROGRESS PANEL ── */
.ocr-progress{
  background:var(--card);border:1px solid var(--border);
  border-radius:20px;box-shadow:var(--sh);
  padding:22px 24px;margin-top:20px;
  display:none
}
.ocr-progress.show{display:block}
.ocr-progress-title{
  font-size:13px;font-weight:900;color:var(--navy);
  display:flex;align-items:center;gap:8px;margin-bottom:14px;
  text-transform:uppercase;letter-spacing:.04em
}
.ocr-bar{height:7px;background:var(--light);border-radius:99px;overflow:hidden;margin-bottom:14px}
.ocr-bar-fill{
  height:100%;width:0;
  background:linear-gradient(90deg,var(--accent),var(--a2));
  border-radius:99px;transition:width .35s ease
}
/* steps list */
.ocr-steps{display:flex;flex-direction:column;gap:7px}
.ocr-step{
  display:flex;align-items:center;gap:10px;
  font-size:13px;font-weight:700;color:var(--muted);
  opacity:.4;transition:opacity .3s,color .3s
}
.ocr-step.active{opacity:1;color:var(--navy)}
.ocr-step.done{opacity:.65;color:var(--green)}
.ocr-step-icon{
  width:24px;height:24px;border-radius:7px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:11px;
  background:var(--light);color:var(--muted);transition:background .3s,color .3s
}
.ocr-step.active .ocr-step-icon{background:rgba(100,112,210,.15);color:var(--accent)}
.ocr-step.done .ocr-step-icon{background:rgba(22,163,74,.12);color:var(--green)}
.spin-sm{animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── RESULT PANEL ── */
.result-panel{
  background:var(--card);border:1px solid var(--border);
  border-radius:24px;box-shadow:var(--sh);
  margin-top:20px;overflow:hidden
}
.result-head{
  padding:16px 22px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;flex-wrap:wrap
}
.result-head-left{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.result-head-left i{color:var(--accent);font-size:16px}
.result-head-title{font-size:15px;font-weight:900;color:var(--navy)}
.result-stats{display:flex;gap:8px;flex-wrap:wrap}
.result-stat{
  font-size:11px;font-weight:800;
  background:var(--light);color:var(--accent);
  border-radius:7px;padding:3px 10px
}
.result-stat.rtl-tag{background:rgba(217,119,6,.1);color:var(--amber)}
.result-actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
/* empty state */
.result-empty{
  display:flex;flex-direction:column;align-items:center;
  justify-content:center;gap:14px;padding:56px 24px;
  color:var(--muted)
}
.result-empty-icon{
  width:72px;height:72px;border-radius:22px;
  background:var(--light);color:var(--accent);
  display:flex;align-items:center;justify-content:center;font-size:28px;
  opacity:.6
}
.result-empty p{font-size:14px;font-weight:700;opacity:.6;text-align:center}
/* text output */
#resultBox{
  padding:22px 26px;
  font-family:var(--mono);font-size:14px;line-height:1.9;
  color:var(--text);min-height:160px;max-height:440px;
  overflow-y:auto;white-space:pre-wrap;word-break:break-word;
  display:none;border:none;outline:none;
  resize:none;width:100%;background:transparent
}
#resultBox[dir="rtl"]{text-align:right;font-size:15px}

/* ── TTS PANEL ── */
.tts-panel{
  background:var(--card);border:1px solid var(--border);
  border-radius:24px;box-shadow:var(--sh);
  padding:22px 24px;margin-top:20px
}
.tts-panel-title{
  font-size:13px;font-weight:900;color:var(--navy);
  display:flex;align-items:center;gap:8px;margin-bottom:14px;
  text-transform:uppercase;letter-spacing:.04em
}
.tts-panel-title i{color:var(--accent)}
.tts-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.tts-speed-row{
  display:flex;align-items:center;gap:10px;margin-top:14px;flex-wrap:wrap
}
.tts-speed-label{font-size:13px;font-weight:700;color:var(--muted);display:flex;align-items:center;gap:6px}
.speed-range{
  -webkit-appearance:none;appearance:none;
  width:140px;height:5px;background:var(--light);
  border-radius:99px;outline:none;cursor:pointer
}
.speed-range::-webkit-slider-thumb{
  -webkit-appearance:none;width:18px;height:18px;
  background:var(--accent);border-radius:50%;cursor:pointer;
  box-shadow:0 2px 8px rgba(100,112,210,.3)
}
.tts-reading-indicator{
  display:none;align-items:center;gap:8px;
  font-size:13px;font-weight:800;color:var(--accent);
  background:var(--light);border-radius:10px;padding:7px 14px
}
.tts-reading-indicator.show{display:flex}
.tts-waves{display:flex;align-items:center;gap:2px;height:16px}
.tts-wave{
  width:3px;border-radius:2px;background:var(--accent);
  animation:wave .7s ease-in-out infinite
}
.tts-wave:nth-child(1){animation-delay:0s;height:6px}
.tts-wave:nth-child(2){animation-delay:.12s;height:13px}
.tts-wave:nth-child(3){animation-delay:.24s;height:8px}
.tts-wave:nth-child(4){animation-delay:.36s;height:14px}
.tts-wave:nth-child(5){animation-delay:.48s;height:5px}
@keyframes wave{0%,100%{transform:scaleY(.5);opacity:.6}50%{transform:scaleY(1.2);opacity:1}}
.lang-detected{
  font-size:11px;font-weight:800;padding:3px 10px;border-radius:7px;
  background:rgba(100,112,210,.12);color:var(--accent);margin-left:4px
}

/* ── TIPS GRID ── */
.tips-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;margin-top:28px}
.tip-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:16px;padding:16px 18px;display:flex;gap:13px
}
.tip-icon{
  width:36px;height:36px;border-radius:11px;flex-shrink:0;
  background:var(--light);color:var(--accent);
  display:flex;align-items:center;justify-content:center;font-size:15px
}
.tip-title{font-size:13px;font-weight:800;color:var(--navy);margin-bottom:3px}
.tip-desc{font-size:11.5px;font-weight:600;color:var(--muted);line-height:1.55}

/* ── SECTION TITLE ── */
.section-title{
  font-size:17px;font-weight:900;color:var(--navy);
  margin:36px 0 16px;display:flex;align-items:center;gap:10px
}
.section-title i{color:var(--accent);font-size:15px}

/* ── TOAST ── */
#toast{
  position:fixed;bottom:28px;right:28px;z-index:8000;
  background:var(--navy);color:#fff;font-size:13px;font-weight:800;
  padding:12px 20px;border-radius:13px;
  box-shadow:0 8px 30px rgba(0,0,0,.2);
  display:flex;align-items:center;gap:8px;
  opacity:0;transform:translateY(8px);
  transition:opacity .25s,transform .25s;pointer-events:none
}
#toast.show{opacity:1;transform:translateY(0)}

/* ── PASTE HINT TOOLTIP ── */
.paste-highlight{
  border-color:var(--accent)!important;
  background:rgba(100,112,210,.07)!important;
  animation:pastePop .4s ease
}
@keyframes pastePop{0%{transform:scale(1)}50%{transform:scale(1.02)}100%{transform:scale(1)}}

/* ── RESPONSIVE ── */
@media(max-width:600px){
  .hero{padding:28px 22px 36px}
  .result-actions{gap:4px}
  .result-actions .btn-sm{padding:7px 10px;font-size:11px}
}
</style>
</head>
<body>

<!-- ═══════════ LOADING OVERLAY ═══════════ -->
<div id="ocrInit">
  <div class="init-icon"><i class="fa-solid fa-eye"></i></div>
  <div class="init-title">Smart OCR Reader</div>
  <div class="init-sub">Loading AI language engine...</div>
  <div class="init-bar"><div class="init-fill" id="initFill"></div></div>
  <div class="init-step" id="initStep">Initializing Tesseract.js...</div>
</div>

<!-- Toast -->
<div id="toast"><i class="fa-solid fa-check"></i> <span id="toastMsg">Copied!</span></div>

<!-- ═══════════ MAIN WRAP ═══════════ -->
<div class="wrap">

<!-- ─── HERO ─── -->
<div class="hero">
  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>
  <div class="hero-orb hero-orb-3"></div>
  <div class="hero-inner">
    <div class="hero-text">
      <a href="<?= htmlspecialchars($back_link) ?>" class="hero-back">
        <i class="fa-solid fa-arrow-left"></i> Back
      </a>
      <div class="hero-badge"><i class="fa-solid fa-eye"></i> Accessibility Feature</div>
      <h1>Smart <span>OCR Reader</span></h1>
      <p>Point your camera at any text — medicine labels, menus, signs, books — and Rafiq reads it aloud instantly. Supports Arabic and English.</p>
      <div class="hero-tags">
        <span class="hero-tag"><i class="fa-solid fa-brain"></i> Tesseract AI</span>
        <span class="hero-tag"><i class="fa-solid fa-language"></i> Arabic + English</span>
        <span class="hero-tag"><i class="fa-solid fa-volume-high"></i> Text-to-Speech</span>
        <span class="hero-tag"><i class="fa-solid fa-lock"></i> 100% Private</span>
      </div>
    </div>
    <div class="hero-widget">
      <div class="hero-widget-icon"><i class="fa-solid fa-file-lines"></i></div>
      <div class="hero-widget-num" id="heroWords">0</div>
      <div class="hero-widget-label">Words Read</div>
    </div>
  </div>
</div>

<!-- ─── INPUT GRID ─── -->
<div class="input-grid">

  <!-- CAMERA CARD -->
  <div class="card">
    <div class="card-head">
      <div class="card-head-icon"><i class="fa-solid fa-camera"></i></div>
      <div>
        <div class="card-head-title">Camera Capture</div>
        <div class="card-head-sub">Point at any text and capture</div>
      </div>
    </div>
    <div class="card-body">
      <div class="cam-viewport" id="camViewport">
        <video id="camVideo" autoplay muted playsinline></video>
        <div class="cam-corner tl" id="ccTL"></div>
        <div class="cam-corner tr" id="ccTR"></div>
        <div class="cam-corner bl" id="ccBL"></div>
        <div class="cam-corner br" id="ccBR"></div>
        <div class="scan-line" id="scanLine"></div>
        <div class="cam-flash" id="camFlash"></div>
        <div class="cam-off" id="camOff">
          <i class="fa-solid fa-camera-slash"></i>
          <span>Camera is off</span>
        </div>
      </div>
      <canvas id="camCanvas" style="display:none"></canvas>
      <div class="cam-btns">
        <button class="btn btn-cam btn-sm" id="startCamBtn">
          <i class="fa-solid fa-video"></i> Start Camera
        </button>
        <button class="btn btn-cam-stop btn-sm" id="stopCamBtn" style="display:none">
          <i class="fa-solid fa-video-slash"></i> Stop
        </button>
        <button class="btn btn-primary btn-sm" id="captureScanBtn" style="display:none" disabled>
          <i class="fa-solid fa-scan"></i> Capture & Scan
        </button>
      </div>
    </div>
  </div>

  <!-- UPLOAD CARD -->
  <div class="card">
    <div class="card-head">
      <div class="card-head-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
      <div>
        <div class="card-head-title">Upload Image</div>
        <div class="card-head-sub">JPG, PNG, WEBP, BMP — any document or label</div>
      </div>
    </div>
    <div class="card-body">
      <label class="upload-zone" id="uploadZone">
        <input type="file" id="fileInput" accept="image/*">
        <div class="upload-zone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <div class="upload-zone-title">Drop image here or click to browse</div>
        <div class="upload-zone-sub">Medicine labels, menus, signs, documents</div>
        <div class="upload-zone-paste"><i class="fa-solid fa-clipboard"></i> You can also paste an image (Ctrl+V)</div>
        <div class="upload-preview-wrap" id="previewWrap">
          <img id="previewImg" src="" alt="Preview">
          <span class="upload-preview-tag" id="previewTag"></span>
        </div>
      </label>
      <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" id="scanUploadBtn" disabled>
          <i class="fa-solid fa-magnifying-glass"></i> Scan Image
        </button>
        <button class="btn btn-secondary btn-sm" id="clearUploadBtn" style="display:none">
          <i class="fa-solid fa-xmark"></i> Clear
        </button>
      </div>
    </div>
  </div>

</div><!-- /input-grid -->

<!-- ─── OCR PROGRESS ─── -->
<div class="ocr-progress" id="ocrProgress">
  <div class="ocr-progress-title"><i class="fa-solid fa-microchip"></i> Processing</div>
  <div class="ocr-bar"><div class="ocr-bar-fill" id="ocrBarFill"></div></div>
  <div class="ocr-steps" id="ocrSteps">
    <div class="ocr-step" id="step1">
      <div class="ocr-step-icon" id="step1ico"><i class="fa-solid fa-image"></i></div>
      Preparing image...
    </div>
    <div class="ocr-step" id="step2">
      <div class="ocr-step-icon" id="step2ico"><i class="fa-solid fa-sliders"></i></div>
      Enhancing readability...
    </div>
    <div class="ocr-step" id="step3">
      <div class="ocr-step-icon" id="step3ico"><i class="fa-solid fa-language"></i></div>
      Scanning Arabic &amp; English text...
    </div>
    <div class="ocr-step" id="step4">
      <div class="ocr-step-icon" id="step4ico"><i class="fa-solid fa-align-left"></i></div>
      Extracting &amp; formatting text...
    </div>
    <div class="ocr-step" id="step5">
      <div class="ocr-step-icon" id="step5ico"><i class="fa-solid fa-check"></i></div>
      Done!
    </div>
  </div>
</div>

<!-- ─── RESULT PANEL ─── -->
<div class="result-panel">
  <div class="result-head">
    <div class="result-head-left">
      <i class="fa-solid fa-file-lines"></i>
      <span class="result-head-title">Extracted Text</span>
      <div class="result-stats">
        <span class="result-stat" id="wordStat">0 words</span>
        <span class="result-stat" id="charStat">0 chars</span>
        <span class="result-stat rtl-tag" id="langTag" style="display:none"></span>
      </div>
    </div>
    <div class="result-actions">
      <button class="btn btn-secondary btn-sm" id="copyBtn" disabled>
        <i class="fa-regular fa-copy"></i> Copy
      </button>
      <button class="btn btn-secondary btn-sm" id="downloadBtn" disabled>
        <i class="fa-solid fa-download"></i> Download
      </button>
      <button class="btn btn-danger btn-sm" id="clearResultBtn" disabled>
        <i class="fa-solid fa-trash-can"></i> Clear
      </button>
    </div>
  </div>
  <!-- empty state -->
  <div class="result-empty" id="resultEmpty">
    <div class="result-empty-icon"><i class="fa-solid fa-file-magnifying-glass"></i></div>
    <p>Upload an image or capture from camera<br>to extract text here</p>
  </div>
  <!-- text output -->
  <textarea id="resultBox" spellcheck="false" aria-label="Extracted text" dir="ltr"></textarea>
</div>

<!-- ─── TTS PANEL ─── -->
<div class="tts-panel">
  <div class="tts-panel-title"><i class="fa-solid fa-volume-high"></i> Text-to-Speech</div>
  <div class="tts-row">
    <button class="btn btn-primary" id="readBtn" disabled>
      <i class="fa-solid fa-play" id="readIcon"></i> Read Aloud
    </button>
    <button class="btn btn-secondary" id="stopReadBtn" disabled>
      <i class="fa-solid fa-stop"></i> Stop
    </button>
    <div class="tts-reading-indicator" id="ttsIndicator">
      <div class="tts-waves">
        <div class="tts-wave"></div><div class="tts-wave"></div>
        <div class="tts-wave"></div><div class="tts-wave"></div>
        <div class="tts-wave"></div>
      </div>
      Reading aloud
      <span class="lang-detected" id="ttsLangChip"></span>
    </div>
  </div>
  <div class="tts-speed-row">
    <span class="tts-speed-label"><i class="fa-solid fa-gauge-simple"></i> Speed:</span>
    <input type="range" class="speed-range" id="speedRange" min="0.5" max="2" step="0.1" value="0.9">
    <span class="tts-speed-label" id="speedLabel">0.9×</span>
  </div>
</div>

<!-- ─── TIPS ─── -->
<div class="section-title"><i class="fa-solid fa-lightbulb"></i> Tips for Best Results</div>
<div class="tips-grid">
  <div class="tip-card">
    <div class="tip-icon"><i class="fa-solid fa-sun"></i></div>
    <div><div class="tip-title">Good Lighting</div><div class="tip-desc">Ensure text is well-lit. Avoid glare and shadows across the text.</div></div>
  </div>
  <div class="tip-card">
    <div class="tip-icon"><i class="fa-solid fa-arrows-to-eye"></i></div>
    <div><div class="tip-title">Hold Steady</div><div class="tip-desc">Keep document flat and camera parallel to the page for a sharp capture.</div></div>
  </div>
  <div class="tip-card">
    <div class="tip-icon"><i class="fa-solid fa-text-height"></i></div>
    <div><div class="tip-title">Larger Text = Better</div><div class="tip-desc">14pt+ printed fonts give best accuracy. Very small print may be harder to read.</div></div>
  </div>
  <div class="tip-card">
    <div class="tip-icon"><i class="fa-solid fa-crop-simple"></i></div>
    <div><div class="tip-title">Crop Tightly</div><div class="tip-desc">Upload cropped images with just the text area for higher extraction accuracy.</div></div>
  </div>
  <div class="tip-card">
    <div class="tip-icon"><i class="fa-solid fa-language"></i></div>
    <div><div class="tip-title">Arabic + English</div><div class="tip-desc">Mixed Arabic and English text is supported. Language direction auto-detects.</div></div>
  </div>
  <div class="tip-card">
    <div class="tip-icon"><i class="fa-solid fa-clipboard"></i></div>
    <div><div class="tip-title">Paste from Clipboard</div><div class="tip-desc">Screenshot any text on screen, then press Ctrl+V on this page to scan it.</div></div>
  </div>
</div>

</div><!-- /wrap -->

<!-- Tesseract.js v5 -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.0.4/dist/tesseract.min.js"></script>
<script>
'use strict';

/* ══════════════════════════════════════════════════════
   STATE
══════════════════════════════════════════════════════ */
let worker        = null;
let workerReady   = false;
let cameraStream  = null;
let currentBlob   = null;   // the image blob/url that will be OCR'd
let sessionWords  = 0;
let isSpeaking    = false;

/* ══════════════════════════════════════════════════════
   DOM REFS
══════════════════════════════════════════════════════ */
const $  = id => document.getElementById(id);
const el = {
  initOverlay  : $('ocrInit'),
  initFill     : $('initFill'),
  initStep     : $('initStep'),
  // camera
  camVideo     : $('camVideo'),
  camCanvas    : $('camCanvas'),
  camOff       : $('camOff'),
  scanLine     : $('scanLine'),
  camFlash     : $('camFlash'),
  startCamBtn  : $('startCamBtn'),
  stopCamBtn   : $('stopCamBtn'),
  captureScanBtn: $('captureScanBtn'),
  ccTL:$('ccTL'),ccTR:$('ccTR'),ccBL:$('ccBL'),ccBR:$('ccBR'),
  // upload
  fileInput    : $('fileInput'),
  uploadZone   : $('uploadZone'),
  previewWrap  : $('previewWrap'),
  previewImg   : $('previewImg'),
  previewTag   : $('previewTag'),
  scanUploadBtn: $('scanUploadBtn'),
  clearUploadBtn:$('clearUploadBtn'),
  // progress
  ocrProgress  : $('ocrProgress'),
  ocrBarFill   : $('ocrBarFill'),
  // steps
  step1:$('step1'),step2:$('step2'),step3:$('step3'),step4:$('step4'),step5:$('step5'),
  step1ico:$('step1ico'),step2ico:$('step2ico'),step3ico:$('step3ico'),step4ico:$('step4ico'),step5ico:$('step5ico'),
  // result
  resultEmpty  : $('resultEmpty'),
  resultBox    : $('resultBox'),
  wordStat     : $('wordStat'),
  charStat     : $('charStat'),
  langTag      : $('langTag'),
  copyBtn      : $('copyBtn'),
  downloadBtn  : $('downloadBtn'),
  clearResultBtn:$('clearResultBtn'),
  heroWords    : $('heroWords'),
  // tts
  readBtn      : $('readBtn'),
  readIcon     : $('readIcon'),
  stopReadBtn  : $('stopReadBtn'),
  ttsIndicator : $('ttsIndicator'),
  ttsLangChip  : $('ttsLangChip'),
  speedRange   : $('speedRange'),
  speedLabel   : $('speedLabel'),
  toast        : $('toast'),
  toastMsg     : $('toastMsg'),
};

/* ══════════════════════════════════════════════════════
   TESSERACT INIT
══════════════════════════════════════════════════════ */
async function initWorker() {
  try {
    let pct = 0;
    const tick = setInterval(() => {
      pct = Math.min(pct + (Math.random() * 14), 80);
      el.initFill.style.width = pct + '%';
    }, 220);

    const steps = [
      'Loading Tesseract core...',
      'Loading Arabic language data...',
      'Loading English language data...',
      'Initialising OCR engine...',
    ];
    let si = 0;
    const stepTick = setInterval(() => {
      if (si < steps.length) { el.initStep.textContent = steps[si++]; }
    }, 900);

    worker = await Tesseract.createWorker(['eng', 'ara'], 1, {
      logger: m => {
        if (m.status === 'loading tesseract core')        el.initFill.style.width = '20%';
        if (m.status === 'loading language traineddata')  el.initFill.style.width = '50%';
        if (m.status === 'initializing tesseract')        el.initFill.style.width = '80%';
      }
    });

    clearInterval(tick);
    clearInterval(stepTick);
    el.initFill.style.width = '100%';
    el.initStep.textContent = 'Ready!';
    await delay(380);

    workerReady = true;
    el.initOverlay.classList.add('hidden');
  } catch (e) {
    console.error('Tesseract init error:', e);
    el.initStep.textContent = 'Failed to load — please refresh.';
  }
}

/* ══════════════════════════════════════════════════════
   IMAGE PREPROCESSING PIPELINE
══════════════════════════════════════════════════════ */
/**
 * Takes a source (HTMLImageElement | HTMLVideoElement | HTMLCanvasElement | Blob | string URL)
 * Returns a preprocessed canvas optimised for OCR.
 * Also corrects horizontal mirror if `mirror` is true.
 */
async function preprocess(source, mirror = false) {
  // 1. Draw source onto a temp canvas
  const tmp = document.createElement('canvas');
  let sw, sh;

  if (source instanceof HTMLVideoElement) {
    sw = source.videoWidth  || 640;
    sh = source.videoHeight || 480;
  } else if (source instanceof HTMLCanvasElement) {
    sw = source.width; sh = source.height;
  } else {
    // Blob / URL — load into Image
    const img = await loadImage(source);
    sw = img.naturalWidth; sh = img.naturalHeight;
    source = img;
  }

  // 2. Upscale small images (helps Tesseract a lot)
  const TARGET = 1600;
  const scaleFactor = sw < TARGET ? Math.min(4, TARGET / Math.max(sw, sh)) : 1;
  tmp.width  = Math.round(sw * scaleFactor);
  tmp.height = Math.round(sh * scaleFactor);

  const ctx = tmp.getContext('2d', { willReadFrequently: true });
  ctx.imageSmoothingEnabled = true;
  ctx.imageSmoothingQuality = 'high';

  if (mirror) {
    // Correct mirror: flip horizontally before drawing
    ctx.translate(tmp.width, 0);
    ctx.scale(-1, 1);
  }
  ctx.drawImage(source, 0, 0, tmp.width, tmp.height);

  // 3. Grayscale + auto-levels (contrast stretch)
  const id = ctx.getImageData(0, 0, tmp.width, tmp.height);
  const d  = id.data;

  // Grayscale
  for (let i = 0; i < d.length; i += 4) {
    const g = Math.round(0.299 * d[i] + 0.587 * d[i+1] + 0.114 * d[i+2]);
    d[i] = d[i+1] = d[i+2] = g;
  }

  // Auto-levels (stretch histogram to 0-255)
  let mn = 255, mx = 0;
  for (let i = 0; i < d.length; i += 4) {
    if (d[i] < mn) mn = d[i];
    if (d[i] > mx) mx = d[i];
  }
  const range = Math.max(1, mx - mn);
  for (let i = 0; i < d.length; i += 4) {
    const v = Math.round(((d[i] - mn) / range) * 255);
    const clamped = v < 0 ? 0 : v > 255 ? 255 : v;
    d[i] = d[i+1] = d[i+2] = clamped;
  }
  ctx.putImageData(id, 0, 0);

  // 4. Sharpening convolution (3×3 unsharp)
  return applySharpening(tmp);
}

function applySharpening(src) {
  const out = document.createElement('canvas');
  out.width  = src.width;
  out.height = src.height;
  const ctx  = out.getContext('2d', { willReadFrequently: true });
  ctx.drawImage(src, 0, 0);

  const srcData = ctx.getImageData(0, 0, out.width, out.height);
  const dstData = ctx.createImageData(out.width, out.height);
  // Sharpen kernel: [0,-1,0,-1,5,-1,0,-1,0]
  const K = [0,-1,0,-1,5,-1,0,-1,0];
  const W = out.width, H = out.height;
  const s = srcData.data, d = dstData.data;

  for (let y = 0; y < H; y++) {
    for (let x = 0; x < W; x++) {
      let v = 0;
      for (let ky = -1; ky <= 1; ky++) {
        for (let kx = -1; kx <= 1; kx++) {
          const nx = x + kx, ny = y + ky;
          if (nx >= 0 && nx < W && ny >= 0 && ny < H) {
            v += s[(ny * W + nx) * 4] * K[(ky+1)*3 + (kx+1)];
          }
        }
      }
      const idx = (y * W + x) * 4;
      const clamped = v < 0 ? 0 : v > 255 ? 255 : v;
      d[idx] = d[idx+1] = d[idx+2] = clamped;
      d[idx+3] = 255;
    }
  }
  ctx.putImageData(dstData, 0, 0);
  return out;
}

function loadImage(src) {
  return new Promise((res, rej) => {
    const img = new Image();
    img.onload  = () => res(img);
    img.onerror = rej;
    img.src = src instanceof Blob ? URL.createObjectURL(src) : src;
  });
}

/* ══════════════════════════════════════════════════════
   CAMERA
══════════════════════════════════════════════════════ */
el.startCamBtn.addEventListener('click', startCamera);
el.stopCamBtn.addEventListener('click', stopCamera);
el.captureScanBtn.addEventListener('click', captureAndScan);

async function startCamera() {
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } }
    }).catch(() => navigator.mediaDevices.getUserMedia({ video: true }));

    el.camVideo.srcObject = cameraStream;
    el.camVideo.style.display = 'block';
    // CSS mirror so user sees natural orientation; we UN-mirror during capture
    el.camVideo.style.transform = 'scaleX(-1)';
    el.camOff.style.display = 'none';
    el.startCamBtn.style.display = 'none';
    el.stopCamBtn.style.display  = 'inline-flex';
    el.captureScanBtn.style.display = 'inline-flex';
    el.captureScanBtn.disabled = false;
    [el.ccTL,el.ccTR,el.ccBL,el.ccBR].forEach(c => c.classList.add('live'));
  } catch (e) {
    showToast('Camera access denied or unavailable', true);
  }
}

function stopCamera() {
  if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
  el.camVideo.srcObject = null;
  el.camVideo.style.display = 'none';
  el.camOff.style.display = 'flex';
  el.startCamBtn.style.display = 'inline-flex';
  el.stopCamBtn.style.display  = 'none';
  el.captureScanBtn.style.display = 'none';
  [el.ccTL,el.ccTR,el.ccBL,el.ccBR].forEach(c => c.classList.remove('live'));
}

async function captureAndScan() {
  if (!workerReady) return;
  // Flash effect
  el.camFlash.classList.add('pop');
  setTimeout(() => el.camFlash.classList.remove('pop'), 200);

  const v = el.camVideo;
  const w = v.videoWidth  || 640;
  const h = v.videoHeight || 480;
  el.camCanvas.width  = w;
  el.camCanvas.height = h;
  const ctx = el.camCanvas.getContext('2d');
  // Draw WITHOUT CSS mirror — camera stream is horizontally flipped by CSS
  // so we need to un-flip here: translate+scale to get real-world orientation
  ctx.translate(w, 0);
  ctx.scale(-1, 1);
  ctx.drawImage(v, 0, 0, w, h);
  ctx.setTransform(1,0,0,1,0,0); // reset

  el.camCanvas.toBlob(async blob => {
    currentBlob = blob;
    await runOCR(blob, false); // already un-mirrored above
  }, 'image/jpeg', 0.95);
}

/* ══════════════════════════════════════════════════════
   FILE UPLOAD / DRAG+DROP / CLIPBOARD PASTE
══════════════════════════════════════════════════════ */
el.fileInput.addEventListener('change', e => {
  if (e.target.files[0]) handleFile(e.target.files[0]);
});

el.uploadZone.addEventListener('dragover',  e => { e.preventDefault(); el.uploadZone.classList.add('over'); });
el.uploadZone.addEventListener('dragleave', ()  => el.uploadZone.classList.remove('over'));
el.uploadZone.addEventListener('drop', e => {
  e.preventDefault();
  el.uploadZone.classList.remove('over');
  const f = e.dataTransfer.files[0];
  if (f && f.type.startsWith('image/')) handleFile(f);
});

// Clipboard paste (Ctrl+V anywhere on page)
document.addEventListener('paste', e => {
  const items = e.clipboardData?.items;
  if (!items) return;
  for (const item of items) {
    if (item.type.startsWith('image/')) {
      const f = item.getAsFile();
      if (f) {
        el.uploadZone.classList.add('paste-highlight');
        setTimeout(() => el.uploadZone.classList.remove('paste-highlight'), 600);
        handleFile(f);
        showToast('Image pasted from clipboard!');
      }
      break;
    }
  }
});

function handleFile(file) {
  if (!file.type.startsWith('image/')) { showToast('Please select an image file', true); return; }
  const url = URL.createObjectURL(file);
  el.previewImg.src = url;
  el.previewWrap.classList.add('show');
  el.previewTag.textContent = file.name.length > 22 ? file.name.slice(0,20)+'…' : file.name;
  el.scanUploadBtn.disabled = false;
  el.clearUploadBtn.style.display = 'inline-flex';
  currentBlob = file;
}

el.scanUploadBtn.addEventListener('click', () => {
  if (!currentBlob || !workerReady) return;
  runOCR(currentBlob, false);
});

el.clearUploadBtn.addEventListener('click', () => {
  el.previewImg.src = '';
  el.previewWrap.classList.remove('show');
  el.scanUploadBtn.disabled = true;
  el.clearUploadBtn.style.display = 'none';
  el.fileInput.value = '';
  currentBlob = null;
});

/* ══════════════════════════════════════════════════════
   OCR PIPELINE
══════════════════════════════════════════════════════ */
async function runOCR(source, isMirrored = false) {
  if (!workerReady) return;

  // Show scan line
  el.scanLine.classList.add('active');
  // Show progress panel
  showProgress(true);
  setStep(1);

  try {
    await delay(350); // Step 1: Preparing image
    setStep(2);

    // Step 2: Preprocess
    const processed = await preprocess(source, isMirrored);
    await delay(300);
    setStep(3);

    // Step 3+4: OCR with Tesseract
    setBar(45);
    let progressInterval = setInterval(() => {
      const cur = parseFloat(el.ocrBarFill.style.width) || 45;
      if (cur < 88) el.ocrBarFill.style.width = (cur + Math.random() * 8) + '%';
    }, 400);

    const result = await worker.recognize(processed);
    clearInterval(progressInterval);

    await delay(200);
    setStep(4);
    setBar(96);
    await delay(300);
    setStep(5);
    setBar(100);

    const text = (result.data.text || '').trim();
    await delay(400);
    showResult(text);

  } catch (e) {
    console.error('OCR error:', e);
    showToast('OCR failed — please try again', true);
  } finally {
    el.scanLine.classList.remove('active');
    setTimeout(() => showProgress(false), 600);
  }
}

/* ── Step helpers ── */
const stepEls = [null, el.step1, el.step2, el.step3, el.step4, el.step5];
const icoEls  = [null, el.step1ico, el.step2ico, el.step3ico, el.step4ico, el.step5ico];
const spinIco = '<i class="fa-solid fa-circle-notch spin-sm"></i>';
const doneIco = '<i class="fa-solid fa-check"></i>';

function setStep(n) {
  for (let i = 1; i <= 5; i++) {
    const s = stepEls[i], ic = icoEls[i];
    s.classList.remove('active','done');
    if (i < n) { s.classList.add('done'); ic.innerHTML = doneIco; }
    else if (i === n) { s.classList.add('active'); ic.innerHTML = spinIco; }
  }
  setBar(n === 1 ? 10 : n === 2 ? 25 : n === 3 ? 45 : n === 4 ? 80 : 100);
}

function setBar(pct) { el.ocrBarFill.style.width = pct + '%'; }
function showProgress(v) { el.ocrProgress.classList.toggle('show', v); }

/* ══════════════════════════════════════════════════════
   RESULT DISPLAY
══════════════════════════════════════════════════════ */
function showResult(text) {
  el.resultEmpty.style.display = 'none';
  el.resultBox.style.display   = 'block';
  el.resultBox.value = text;

  const words = text ? text.trim().split(/\s+/).filter(Boolean).length : 0;
  const chars = text.length;

  el.wordStat.textContent = words + ' words';
  el.charStat.textContent = chars + ' chars';

  // Detect language / direction
  const isArabic = hasArabic(text);
  if (isArabic) {
    el.resultBox.dir = 'rtl';
    el.langTag.textContent = 'Arabic / RTL';
    el.langTag.style.display = 'inline-flex';
  } else {
    el.resultBox.dir = 'ltr';
    el.langTag.style.display = 'none';
  }

  sessionWords += words;
  el.heroWords.textContent = sessionWords;

  const hasText = text.length > 0;
  el.copyBtn.disabled       = !hasText;
  el.downloadBtn.disabled   = !hasText;
  el.clearResultBtn.disabled= !hasText;
  el.readBtn.disabled       = !hasText;
}

el.clearResultBtn.addEventListener('click', () => {
  el.resultBox.value = '';
  el.resultBox.style.display = 'none';
  el.resultEmpty.style.display = 'flex';
  el.wordStat.textContent = '0 words';
  el.charStat.textContent = '0 chars';
  el.langTag.style.display = 'none';
  el.copyBtn.disabled = el.downloadBtn.disabled = el.clearResultBtn.disabled = el.readBtn.disabled = true;
  stopSpeech();
});

/* ══════════════════════════════════════════════════════
   COPY & DOWNLOAD
══════════════════════════════════════════════════════ */
el.copyBtn.addEventListener('click', () => {
  const t = el.resultBox.value;
  if (!t) return;
  navigator.clipboard.writeText(t).then(() => showToast('Copied to clipboard!')).catch(() => {
    el.resultBox.select();
    document.execCommand('copy');
    showToast('Copied!');
  });
});

el.downloadBtn.addEventListener('click', () => {
  const t = el.resultBox.value;
  if (!t) return;
  const blob = new Blob([t], { type: 'text/plain;charset=utf-8' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'rafiq-ocr-' + Date.now() + '.txt';
  a.click();
  URL.revokeObjectURL(url);
  showToast('Downloaded!');
});

/* ══════════════════════════════════════════════════════
   TEXT-TO-SPEECH
══════════════════════════════════════════════════════ */
el.speedRange.addEventListener('input', () => {
  el.speedLabel.textContent = parseFloat(el.speedRange.value).toFixed(1) + '×';
});

el.readBtn.addEventListener('click', () => {
  const text = el.resultBox.value.trim();
  if (!text) return;
  stopSpeech();

  const arabic = hasArabic(text);
  const utter  = new SpeechSynthesisUtterance(text);
  utter.rate   = parseFloat(el.speedRange.value);
  utter.pitch  = 1.0;
  utter.lang   = arabic ? 'ar-SA' : 'en-US';

  // Try to pick the best matching voice
  const voices = window.speechSynthesis.getVoices();
  const voice  = voices.find(v => arabic ? v.lang.startsWith('ar') : v.lang.startsWith('en-US'))
              || voices.find(v => arabic ? v.lang.startsWith('ar') : v.lang.startsWith('en'));
  if (voice) utter.voice = voice;

  utter.onstart = () => {
    isSpeaking = true;
    el.ttsIndicator.classList.add('show');
    el.ttsLangChip.textContent = arabic ? 'Arabic' : 'English';
    el.stopReadBtn.disabled = false;
    el.readIcon.className = 'fa-solid fa-volume-high';
  };
  utter.onend = utter.onerror = () => {
    isSpeaking = false;
    el.ttsIndicator.classList.remove('show');
    el.stopReadBtn.disabled = true;
    el.readIcon.className = 'fa-solid fa-play';
  };

  window.speechSynthesis.speak(utter);
});

el.stopReadBtn.addEventListener('click', stopSpeech);

function stopSpeech() {
  window.speechSynthesis.cancel();
  isSpeaking = false;
  el.ttsIndicator.classList.remove('show');
  el.stopReadBtn.disabled = true;
  el.readIcon.className = 'fa-solid fa-play';
}

/* ══════════════════════════════════════════════════════
   ARABIC DETECTION
══════════════════════════════════════════════════════ */
function hasArabic(text) {
  return /[؀-ۿݐ-ݿࢠ-ࣿﭐ-﷿ﹰ-﻿]/.test(text);
}

/* ══════════════════════════════════════════════════════
   TOAST
══════════════════════════════════════════════════════ */
let toastTimer = null;
function showToast(msg, error = false) {
  el.toastMsg.textContent = msg;
  el.toast.style.background = error ? '#dc2626' : 'var(--navy)';
  el.toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.toast.classList.remove('show'), 2400);
}

/* ══════════════════════════════════════════════════════
   UTILS
══════════════════════════════════════════════════════ */
function delay(ms) { return new Promise(r => setTimeout(r, ms)); }

/* ══════════════════════════════════════════════════════
   CLEANUP ON LEAVE
══════════════════════════════════════════════════════ */
window.addEventListener('beforeunload', () => {
  stopSpeech();
  stopCamera();
  if (worker) worker.terminate();
});

/* ══════════════════════════════════════════════════════
   BOOT
══════════════════════════════════════════════════════ */
initWorker();

// Pre-load voices for TTS
if (window.speechSynthesis) {
  window.speechSynthesis.getVoices();
  window.speechSynthesis.addEventListener('voiceschanged', () => window.speechSynthesis.getVoices());
}
</script>
</body>
</html>
