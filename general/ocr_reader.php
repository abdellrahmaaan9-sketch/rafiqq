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
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* ── Reset & Tokens ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#1e2040;--teal:#0d7a6e;--accent:#0f9586;--a2:#1ab5a3;
  --light:#e8faf8;--green:#16a34a;--red:#dc2626;--amber:#d97706;
  --bg:#f2f9f8;--card:#fff;--text:#1a2a28;--muted:#5a706d;
  --border:rgba(13,122,110,.13);--sh:0 4px 20px rgba(13,122,110,.08);
  --sh-lg:0 16px 48px rgba(13,122,110,.14);--mono:'JetBrains Mono',monospace;
}
html{scroll-behavior:smooth}
body{font-family:"Nunito",system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
:focus-visible{outline:2.5px solid var(--accent);outline-offset:3px;border-radius:6px}

/* ── Loading overlay ── */
#ocrLoadOverlay{
  position:fixed;inset:0;z-index:9000;
  background:rgba(10,40,36,.9);backdrop-filter:blur(10px);
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;
  color:#fff;transition:opacity .4s
}
#ocrLoadOverlay.hidden{opacity:0;pointer-events:none}
.ocr-spinner{
  width:52px;height:52px;
  border:3px solid rgba(255,255,255,.15);
  border-top-color:#0f9586;
  border-radius:50%;animation:spin .7s linear infinite
}
@keyframes spin{to{transform:rotate(360deg)}}
.ocr-load-icon{font-size:42px;margin-bottom:4px}
.ocr-load-title{font-size:20px;font-weight:900;letter-spacing:-.01em}
.ocr-load-sub{font-size:13px;opacity:.65;font-weight:600}
.ocr-load-progress{
  width:180px;height:4px;
  background:rgba(255,255,255,.12);
  border-radius:99px;overflow:hidden;margin-top:4px
}
.ocr-load-fill{
  height:100%;width:0%;background:var(--accent);
  border-radius:99px;transition:width .3s ease
}

/* ── Layout ── */
.wrap{max-width:1180px;margin:0 auto;padding:0 24px 72px}

/* ── Hero ── */
.hero{
  background:linear-gradient(135deg,#0a2826 0%,#0d4a42 50%,#0f9586 100%);
  margin:0 -24px;padding:38px 52px 46px;
  color:#fff;position:relative;overflow:hidden;
  border-radius:0 0 40px 40px
}
.hero-orb{position:absolute;border-radius:50%;pointer-events:none}
.hero-orb-1{width:360px;height:360px;top:-140px;right:-90px;background:rgba(255,255,255,.04)}
.hero-orb-2{width:190px;height:190px;bottom:-70px;left:4%;background:rgba(255,255,255,.03)}
.hero-orb-3{width:110px;height:110px;top:22%;right:30%;background:rgba(255,255,255,.025)}
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
.hero-desc{font-size:15px;opacity:.78;max-width:500px;line-height:1.65;font-weight:600}
.hero-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}
.hero-badge{
  display:flex;align-items:center;gap:5px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
  border-radius:8px;padding:5px 12px;
  font-size:12px;font-weight:700;color:rgba(255,255,255,.88)
}
.hero-widget{
  background:rgba(255,255,255,.1);backdrop-filter:blur(16px);
  border:1px solid rgba(255,255,255,.2);
  border-radius:24px;padding:24px 28px;
  min-width:200px;text-align:center
}
.hero-widget-icon{font-size:42px;margin-bottom:8px}
.hero-widget-label{font-size:12px;opacity:.7;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.hero-widget-val{font-size:28px;font-weight:900;margin:2px 0}
.hero-widget-sub{font-size:11px;opacity:.55;font-weight:700}

/* ── Sections ── */
.section-title{
  font-size:18px;font-weight:900;color:var(--navy);
  margin-bottom:16px;display:flex;align-items:center;gap:10px
}
.section-title i{color:var(--accent);font-size:16px}

/* ── Input Panel ── */
.input-panel{
  display:grid;grid-template-columns:1fr 1fr;gap:20px;
  margin-top:32px
}
@media(max-width:800px){.input-panel{grid-template-columns:1fr}}

.input-card{
  background:var(--card);border-radius:22px;
  border:1px solid var(--border);box-shadow:var(--sh);
  padding:26px 28px;
}
.input-card-title{
  font-size:14px;font-weight:900;color:var(--navy);
  display:flex;align-items:center;gap:8px;margin-bottom:16px;
  text-transform:uppercase;letter-spacing:.04em
}
.input-card-title i{
  width:32px;height:32px;border-radius:10px;
  background:var(--light);color:var(--accent);
  display:flex;align-items:center;justify-content:center;font-size:14px
}

/* Camera */
.camera-container{
  position:relative;width:100%;
  border-radius:16px;overflow:hidden;
  background:#0a1a18;aspect-ratio:4/3;
}
#cameraFeed{
  width:100%;height:100%;object-fit:cover;
  display:block;transform:scaleX(-1)
}
.camera-off-msg{
  position:absolute;inset:0;display:flex;flex-direction:column;
  align-items:center;justify-content:center;gap:10px;
  color:rgba(255,255,255,.45);font-size:14px;font-weight:700
}
.camera-off-msg i{font-size:32px;opacity:.4}
.camera-capture-btn{
  position:absolute;bottom:12px;left:50%;transform:translateX(-50%);
  background:rgba(255,255,255,.15);backdrop-filter:blur(8px);
  border:2px solid rgba(255,255,255,.35);
  color:#fff;border-radius:50%;
  width:52px;height:52px;
  display:none;align-items:center;justify-content:center;
  font-size:20px;cursor:pointer;transition:background .18s,transform .12s
}
.camera-capture-btn.visible{display:flex}
.camera-capture-btn:hover{background:rgba(255,255,255,.3);transform:translateX(-50%) scale(1.08)}

.cam-btns{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}

/* Upload area */
.upload-zone{
  border:2px dashed rgba(13,122,110,.25);
  border-radius:16px;padding:36px 20px;
  text-align:center;cursor:pointer;
  transition:border-color .18s,background .18s;
  background:transparent;position:relative
}
.upload-zone:hover,.upload-zone.drag-over{
  border-color:var(--accent);background:rgba(13,122,110,.04)
}
.upload-zone input{
  position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%
}
.upload-zone-icon{font-size:36px;color:var(--accent);opacity:.6;margin-bottom:10px}
.upload-zone-text{font-size:14px;font-weight:700;color:var(--muted)}
.upload-zone-sub{font-size:12px;color:var(--muted);opacity:.7;margin-top:4px;font-weight:600}
.upload-preview{
  width:100%;max-height:240px;object-fit:contain;
  border-radius:12px;margin-top:14px;display:none
}
.upload-preview.visible{display:block}
.upload-file-name{
  font-size:12px;font-weight:700;color:var(--muted);
  margin-top:8px;display:none
}
.upload-file-name.visible{display:block}

/* ── Action Buttons ── */
.action-row{
  display:flex;gap:10px;flex-wrap:wrap;
  margin-top:20px
}
.btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:11px 20px;border-radius:13px;
  font-size:14px;font-weight:800;font-family:inherit;
  cursor:pointer;border:none;transition:all .18s;text-decoration:none
}
.btn-primary{
  background:linear-gradient(135deg,var(--teal),var(--accent));
  color:#fff;box-shadow:0 4px 16px rgba(13,122,110,.25)
}
.btn-primary:hover{box-shadow:0 6px 24px rgba(13,122,110,.38);transform:translateY(-1px)}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}
.btn-secondary{
  background:#f0f9f8;color:var(--teal);
  border:1.5px solid rgba(13,122,110,.2)
}
.btn-secondary:hover{background:#e0f4f2;border-color:rgba(13,122,110,.4)}
.btn-secondary:disabled{opacity:.5;cursor:not-allowed}
.btn-danger{background:#fef2f2;color:#dc2626;border:1.5px solid rgba(220,38,38,.18)}
.btn-danger:hover{background:#fee2e2;border-color:rgba(220,38,38,.35)}
.btn-sm{padding:8px 14px;font-size:12px;border-radius:10px}
.btn-cam-start{background:linear-gradient(135deg,#047857,#059669);color:#fff;box-shadow:0 4px 14px rgba(5,150,105,.25)}
.btn-cam-start:hover{box-shadow:0 6px 20px rgba(5,150,105,.38);transform:translateY(-1px)}
.btn-cam-stop{background:#fef2f2;color:#dc2626;border:1.5px solid rgba(220,38,38,.18)}
.btn-cam-stop:hover{background:#fee2e2}

/* ── OCR Progress ── */
.ocr-progress-wrap{
  display:none;margin-top:20px;
  background:var(--card);border-radius:16px;
  border:1px solid var(--border);padding:20px 24px
}
.ocr-progress-wrap.visible{display:block}
.ocr-progress-label{
  font-size:13px;font-weight:800;color:var(--navy);
  display:flex;justify-content:space-between;margin-bottom:8px
}
.ocr-bar{
  width:100%;height:8px;background:var(--light);
  border-radius:99px;overflow:hidden
}
.ocr-bar-fill{
  height:100%;width:0%;
  background:linear-gradient(90deg,var(--teal),var(--a2));
  border-radius:99px;transition:width .25s ease
}
.ocr-status{font-size:12px;font-weight:700;color:var(--muted);margin-top:6px}

/* ── Result Panel ── */
.result-panel{
  background:var(--card);border-radius:22px;
  border:1px solid var(--border);box-shadow:var(--sh);
  margin-top:24px;overflow:hidden
}
.result-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:18px 24px;border-bottom:1px solid rgba(13,122,110,.08)
}
.result-header-left{
  display:flex;align-items:center;gap:10px;
  font-size:15px;font-weight:900;color:var(--navy)
}
.result-header-left i{color:var(--accent)}
.result-word-count{
  font-size:12px;font-weight:700;
  color:var(--muted);background:var(--light);
  border-radius:8px;padding:3px 10px
}
.result-actions{display:flex;gap:6px;align-items:center}
#resultText{
  padding:22px 26px;
  font-family:var(--mono);font-size:14px;
  line-height:1.8;color:var(--text);
  min-height:180px;max-height:420px;
  overflow-y:auto;white-space:pre-wrap;
  border:none;outline:none;resize:none;
  width:100%;background:transparent;
  font-weight:400
}
#resultText:empty::before{
  content:'Extracted text will appear here...';
  color:var(--muted);opacity:.5;font-style:italic
}
.result-placeholder{
  display:flex;flex-direction:column;align-items:center;
  justify-content:center;gap:12px;padding:48px 24px;
  color:var(--muted);opacity:.5;text-align:center
}
.result-placeholder i{font-size:40px}
.result-placeholder p{font-size:14px;font-weight:700}

/* ── TTS Controls ── */
.tts-panel{
  background:var(--card);border-radius:22px;
  border:1px solid var(--border);box-shadow:var(--sh);
  padding:22px 26px;margin-top:20px
}
.tts-panel-title{
  font-size:14px;font-weight:900;color:var(--navy);
  display:flex;align-items:center;gap:8px;margin-bottom:16px;
  text-transform:uppercase;letter-spacing:.04em
}
.tts-panel-title i{color:var(--accent)}
.tts-controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.tts-speed-wrap{
  display:flex;align-items:center;gap:10px;
  margin-top:14px;flex-wrap:wrap
}
.tts-speed-label{font-size:13px;font-weight:700;color:var(--muted)}
.tts-speed-slider{
  -webkit-appearance:none;appearance:none;
  width:160px;height:5px;
  background:var(--light);border-radius:99px;outline:none;cursor:pointer
}
.tts-speed-slider::-webkit-slider-thumb{
  -webkit-appearance:none;width:18px;height:18px;
  background:var(--accent);border-radius:50%;cursor:pointer;
  box-shadow:0 2px 8px rgba(13,122,110,.3)
}
.tts-speaking-indicator{
  display:none;align-items:center;gap:7px;
  font-size:13px;font-weight:800;color:var(--accent);
  padding:6px 14px;background:var(--light);border-radius:10px
}
.tts-speaking-indicator.visible{display:flex}
.tts-bars{display:flex;align-items:center;gap:2px;height:16px}
.tts-bar{
  width:3px;border-radius:2px;background:var(--accent);
  animation:tts-pulse 0.8s ease-in-out infinite
}
.tts-bar:nth-child(1){animation-delay:0s;height:6px}
.tts-bar:nth-child(2){animation-delay:.15s;height:14px}
.tts-bar:nth-child(3){animation-delay:.3s;height:9px}
.tts-bar:nth-child(4){animation-delay:.45s;height:13px}
@keyframes tts-pulse{
  0%,100%{transform:scaleY(.5);opacity:.6}
  50%{transform:scaleY(1.2);opacity:1}
}

/* ── Tips / Usage panel ── */
.tips-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
  gap:14px;margin-top:32px
}
.tip-card{
  background:var(--card);border-radius:16px;
  border:1px solid var(--border);
  padding:18px 18px 16px;
  display:flex;gap:14px
}
.tip-icon{
  width:38px;height:38px;border-radius:12px;flex-shrink:0;
  background:var(--light);color:var(--accent);
  display:flex;align-items:center;justify-content:center;font-size:16px
}
.tip-content p{font-size:13px;font-weight:800;color:var(--navy);margin-bottom:3px}
.tip-content span{font-size:12px;color:var(--muted);font-weight:600;line-height:1.5}

/* ── Copy toast ── */
#copyToast{
  position:fixed;bottom:28px;right:28px;z-index:8000;
  background:var(--navy);color:#fff;
  font-size:13px;font-weight:800;
  padding:11px 20px;border-radius:12px;
  box-shadow:0 8px 28px rgba(0,0,0,.22);
  opacity:0;transform:translateY(8px);
  transition:opacity .25s,transform .25s;pointer-events:none
}
#copyToast.show{opacity:1;transform:translateY(0)}

/* ── Captured Preview Modal ── */
#captureModal{
  display:none;position:fixed;inset:0;z-index:7000;
  background:rgba(0,0,0,.65);backdrop-filter:blur(6px);
  align-items:center;justify-content:center
}
#captureModal.open{display:flex}
.capture-modal-inner{
  background:#fff;border-radius:24px;
  padding:28px;max-width:480px;width:90%;
  box-shadow:0 28px 72px rgba(0,0,0,.3)
}
.capture-modal-title{
  font-size:16px;font-weight:900;color:var(--navy);
  margin-bottom:16px;display:flex;align-items:center;gap:8px
}
#capturePreviewImg{
  width:100%;border-radius:14px;object-fit:contain;max-height:280px
}
.capture-modal-btns{display:flex;gap:10px;margin-top:18px;flex-wrap:wrap}
</style>
</head>
<body>

<!-- Loading Overlay -->
<div id="ocrLoadOverlay">
  <div class="ocr-load-icon">
    <div class="ocr-spinner"></div>
  </div>
  <div class="ocr-load-title">Smart OCR Reader</div>
  <div class="ocr-load-sub">Loading Tesseract.js engine...</div>
  <div class="ocr-load-progress"><div class="ocr-load-fill" id="loadFill"></div></div>
</div>

<!-- Copy toast -->
<div id="copyToast"><i class="fa-solid fa-check"></i> Copied to clipboard!</div>

<!-- Capture preview modal -->
<div id="captureModal">
  <div class="capture-modal-inner">
    <div class="capture-modal-title"><i class="fa-solid fa-camera-retro" style="color:var(--accent)"></i>Captured Image</div>
    <img id="capturePreviewImg" src="" alt="Captured">
    <div class="capture-modal-btns">
      <button class="btn btn-primary" id="captureUseBtn"><i class="fa-solid fa-scan"></i> Run OCR on this</button>
      <button class="btn btn-secondary" id="captureRetryBtn"><i class="fa-solid fa-rotate-left"></i> Retake</button>
      <button class="btn btn-danger" id="captureCloseBtn"><i class="fa-solid fa-xmark"></i> Cancel</button>
    </div>
  </div>
</div>

<div class="wrap">

  <!-- Hero -->
  <div class="hero">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
    <div class="hero-inner">
      <div class="hero-text">
        <a href="<?= htmlspecialchars($back_link) ?>" class="hero-back">
          <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <div class="hero-eyebrow">
          <i class="fa-solid fa-eye"></i> Accessibility Feature
        </div>
        <h1>Smart OCR Reader</h1>
        <p class="hero-desc">
          Point your camera at any printed text — medicine labels, menus, signs, books, or documents — and let AI read it aloud for you instantly.
        </p>
        <div class="hero-badges">
          <span class="hero-badge"><i class="fa-solid fa-brain"></i> Tesseract.js AI</span>
          <span class="hero-badge"><i class="fa-solid fa-volume-high"></i> Text-to-Speech</span>
          <span class="hero-badge"><i class="fa-solid fa-lock"></i> 100% Private</span>
          <span class="hero-badge"><i class="fa-solid fa-wifi-slash"></i> Works Offline</span>
        </div>
      </div>
      <div class="hero-widget">
        <div class="hero-widget-icon"><i class="fa-solid fa-file-lines" style="font-size:42px;color:rgba(255,255,255,.8)"></i></div>
        <div class="hero-widget-label">Words Read</div>
        <div class="hero-widget-val" id="heroWordCount">0</div>
        <div class="hero-widget-sub">this session</div>
      </div>
    </div>
  </div>

  <!-- Input Panel -->
  <div class="input-panel">

    <!-- Camera card -->
    <div class="input-card">
      <div class="input-card-title">
        <i><i class="fa-solid fa-camera" style="font-size:14px"></i></i>
        Camera Capture
      </div>
      <div class="camera-container" id="cameraContainer">
        <video id="cameraFeed" autoplay muted playsinline></video>
        <div class="camera-off-msg" id="cameraOffMsg">
          <i class="fa-solid fa-camera-slash"></i>
          <span>Camera is off</span>
        </div>
        <button class="camera-capture-btn" id="captureBtn" title="Capture">
          <i class="fa-solid fa-camera"></i>
        </button>
      </div>
      <canvas id="captureCanvas" style="display:none"></canvas>
      <div class="cam-btns">
        <button class="btn btn-cam-start btn-sm" id="startCamBtn">
          <i class="fa-solid fa-video"></i> Start Camera
        </button>
        <button class="btn btn-cam-stop btn-sm" id="stopCamBtn" style="display:none">
          <i class="fa-solid fa-video-slash"></i> Stop Camera
        </button>
      </div>
    </div>

    <!-- Upload card -->
    <div class="input-card">
      <div class="input-card-title">
        <i><i class="fa-solid fa-upload" style="font-size:14px"></i></i>
        Upload Image
      </div>
      <label class="upload-zone" id="uploadZone">
        <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp">
        <div class="upload-zone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <div class="upload-zone-text">Drop an image here or click to browse</div>
        <div class="upload-zone-sub">JPG, PNG, WEBP, GIF, BMP — any document, label, or sign</div>
      </label>
      <img id="uploadPreview" class="upload-preview" src="" alt="Preview">
      <div class="upload-file-name" id="uploadFileName"></div>

      <div style="margin-top:16px">
        <button class="btn btn-primary" id="runOcrBtn" disabled>
          <i class="fa-solid fa-scan"></i> Extract Text
        </button>
      </div>
    </div>
  </div>

  <!-- OCR Progress -->
  <div class="ocr-progress-wrap" id="ocrProgressWrap">
    <div class="ocr-progress-label">
      <span id="ocrProgressLabel">Initializing...</span>
      <span id="ocrProgressPct">0%</span>
    </div>
    <div class="ocr-bar"><div class="ocr-bar-fill" id="ocrBarFill"></div></div>
    <div class="ocr-status" id="ocrStatusText">Starting OCR engine...</div>
  </div>

  <!-- Result Panel -->
  <div class="result-panel">
    <div class="result-header">
      <div class="result-header-left">
        <i class="fa-solid fa-file-lines"></i>
        Extracted Text
        <span class="result-word-count" id="resultWordCount">0 words</span>
      </div>
      <div class="result-actions">
        <button class="btn btn-secondary btn-sm" id="copyBtn" disabled>
          <i class="fa-regular fa-copy"></i> Copy
        </button>
        <button class="btn btn-danger btn-sm" id="clearBtn" disabled>
          <i class="fa-solid fa-trash-can"></i> Clear
        </button>
      </div>
    </div>
    <div id="resultPlaceholder" class="result-placeholder">
      <i class="fa-solid fa-file-magnifying-glass"></i>
      <p>Upload an image or capture from camera to extract text</p>
    </div>
    <textarea id="resultText" style="display:none" spellcheck="false" aria-label="Extracted OCR text"></textarea>
  </div>

  <!-- TTS Panel -->
  <div class="tts-panel">
    <div class="tts-panel-title">
      <i class="fa-solid fa-volume-high"></i>
      Text-to-Speech
    </div>
    <div class="tts-controls">
      <button class="btn btn-primary" id="readAloudBtn" disabled>
        <i class="fa-solid fa-play"></i> Read Aloud
      </button>
      <button class="btn btn-secondary" id="stopReadingBtn" disabled>
        <i class="fa-solid fa-stop"></i> Stop Reading
      </button>
      <div class="tts-speaking-indicator" id="ttsSpeakingIndicator">
        <div class="tts-bars">
          <div class="tts-bar"></div>
          <div class="tts-bar"></div>
          <div class="tts-bar"></div>
          <div class="tts-bar"></div>
        </div>
        Reading aloud...
      </div>
    </div>
    <div class="tts-speed-wrap">
      <span class="tts-speed-label"><i class="fa-solid fa-gauge"></i> Speed:</span>
      <input type="range" class="tts-speed-slider" id="ttsSpeed" min="0.5" max="2" step="0.1" value="0.9">
      <span class="tts-speed-label" id="ttsSpeedLabel">0.9x</span>
    </div>
  </div>

  <!-- Tips -->
  <div class="section-title" style="margin-top:40px">
    <i class="fa-solid fa-lightbulb"></i> Tips for Best Results
  </div>
  <div class="tips-grid">
    <div class="tip-card">
      <div class="tip-icon"><i class="fa-solid fa-sun"></i></div>
      <div class="tip-content">
        <p>Good Lighting</p>
        <span>Ensure the text is well-lit. Avoid glare or shadows across the text.</span>
      </div>
    </div>
    <div class="tip-card">
      <div class="tip-icon"><i class="fa-solid fa-arrows-to-eye"></i></div>
      <div class="tip-content">
        <p>Hold Steady</p>
        <span>Keep the document flat and the camera parallel to the page for sharp images.</span>
      </div>
    </div>
    <div class="tip-card">
      <div class="tip-icon"><i class="fa-solid fa-text-height"></i></div>
      <div class="tip-content">
        <p>Larger Text Works Best</p>
        <span>14px+ printed fonts give the highest accuracy. Very small print may be harder to read.</span>
      </div>
    </div>
    <div class="tip-card">
      <div class="tip-icon"><i class="fa-solid fa-crop-simple"></i></div>
      <div class="tip-content">
        <p>Crop Tightly</p>
        <span>Use the upload option to crop images around just the text area before running OCR.</span>
      </div>
    </div>
    <div class="tip-card">
      <div class="tip-icon"><i class="fa-solid fa-file-pdf"></i></div>
      <div class="tip-content">
        <p>Use Photos of Documents</p>
        <span>Take a clear photo of prescriptions, food labels, utility bills, or printed menus.</span>
      </div>
    </div>
    <div class="tip-card">
      <div class="tip-icon"><i class="fa-solid fa-language"></i></div>
      <div class="tip-content">
        <p>English Optimised</p>
        <span>This engine is optimised for English text. Arabic and mixed scripts may have lower accuracy.</span>
      </div>
    </div>
  </div>

</div><!-- /wrap -->

<!-- Tesseract.js -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.0.4/dist/tesseract.min.js"></script>

<script>
// ── State ──
let ocrWorker   = null;
let workerReady = false;
let cameraStream= null;
let capturedImg = null;
let currentImg  = null;   // the image/blob/URL to run OCR on
let isSpeaking  = false;
let sessionWords= 0;

const $ = id => document.getElementById(id);

// ── DOM refs ──
const loadOverlay   = $('ocrLoadOverlay');
const loadFill      = $('loadFill');
const startCamBtn   = $('startCamBtn');
const stopCamBtn    = $('stopCamBtn');
const cameraFeed    = $('cameraFeed');
const cameraOffMsg  = $('cameraOffMsg');
const captureBtn    = $('captureBtn');
const captureCanvas = $('captureCanvas');
const fileInput     = $('fileInput');
const uploadZone    = $('uploadZone');
const uploadPreview = $('uploadPreview');
const uploadFileName= $('uploadFileName');
const runOcrBtn     = $('runOcrBtn');
const ocrProgressWrap=$('ocrProgressWrap');
const ocrBarFill    = $('ocrBarFill');
const ocrProgressLabel=$('ocrProgressLabel');
const ocrProgressPct=$('ocrProgressPct');
const ocrStatusText = $('ocrStatusText');
const resultPlaceholder=$('resultPlaceholder');
const resultText    = $('resultText');
const resultWordCount=$('resultWordCount');
const copyBtn       = $('copyBtn');
const clearBtn      = $('clearBtn');
const readAloudBtn  = $('readAloudBtn');
const stopReadingBtn= $('stopReadingBtn');
const ttsSpeaking   = $('ttsSpeakingIndicator');
const ttsSpeed      = $('ttsSpeed');
const ttsSpeedLabel = $('ttsSpeedLabel');
const heroWordCount = $('heroWordCount');
const captureModal  = $('captureModal');
const capturePreviewImg = $('capturePreviewImg');
const captureUseBtn = $('captureUseBtn');
const captureRetryBtn= $('captureRetryBtn');
const captureCloseBtn= $('captureCloseBtn');
const copyToast     = $('copyToast');

// ── Init Tesseract Worker ──
async function initWorker() {
    try {
        // Simulate progress while loading
        let pct = 0;
        const fakeProgress = setInterval(() => {
            pct = Math.min(pct + Math.random() * 12, 85);
            loadFill.style.width = pct + '%';
        }, 200);

        ocrWorker = await Tesseract.createWorker('eng', 1, {
            logger: m => {
                if (m.status === 'loading tesseract core') loadFill.style.width = '20%';
                if (m.status === 'loading language traineddata') loadFill.style.width = '50%';
                if (m.status === 'initializing tesseract') loadFill.style.width = '75%';
                if (m.status === 'initialized tesseract') loadFill.style.width = '95%';
            }
        });

        clearInterval(fakeProgress);
        loadFill.style.width = '100%';
        await new Promise(r => setTimeout(r, 350));

        workerReady = true;
        loadOverlay.classList.add('hidden');
    } catch (e) {
        console.error('Tesseract init failed:', e);
        loadOverlay.querySelector('.ocr-load-sub').textContent = 'Failed to load. Please refresh.';
    }
}

// ── Camera ──
startCamBtn.addEventListener('click', async () => {
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } }
        });
        cameraFeed.srcObject = cameraStream;
        cameraFeed.style.display = 'block';
        cameraOffMsg.style.display = 'none';
        captureBtn.classList.add('visible');
        startCamBtn.style.display = 'none';
        stopCamBtn.style.display = 'inline-flex';
    } catch (e) {
        // Try without environment constraint
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
            cameraFeed.srcObject = cameraStream;
            cameraFeed.style.display = 'block';
            cameraOffMsg.style.display = 'none';
            captureBtn.classList.add('visible');
            startCamBtn.style.display = 'none';
            stopCamBtn.style.display = 'inline-flex';
        } catch (e2) {
            alert('Camera access denied or unavailable.');
        }
    }
});

stopCamBtn.addEventListener('click', () => {
    if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
    cameraFeed.srcObject = null;
    cameraFeed.style.display = 'none';
    cameraOffMsg.style.display = 'flex';
    captureBtn.classList.remove('visible');
    startCamBtn.style.display = 'inline-flex';
    stopCamBtn.style.display = 'none';
});

captureBtn.addEventListener('click', () => {
    const w = cameraFeed.videoWidth || 640;
    const h = cameraFeed.videoHeight || 480;
    captureCanvas.width  = w;
    captureCanvas.height = h;
    const ctx = captureCanvas.getContext('2d');
    // mirror flip to undo CSS transform:scaleX(-1)
    ctx.translate(w, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(cameraFeed, 0, 0, w, h);
    captureCanvas.toBlob(blob => {
        capturedImg = blob;
        capturePreviewImg.src = URL.createObjectURL(blob);
        captureModal.classList.add('open');
    }, 'image/jpeg', 0.95);
});

captureUseBtn.addEventListener('click', () => {
    currentImg = capturedImg;
    captureModal.classList.remove('open');
    runOcr();
});
captureRetryBtn.addEventListener('click', () => { captureModal.classList.remove('open'); });
captureCloseBtn.addEventListener('click', () => { captureModal.classList.remove('open'); });

// ── File Upload ──
fileInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    handleUploadedFile(file);
});

function handleUploadedFile(file) {
    if (!file.type.startsWith('image/')) { alert('Please select an image file.'); return; }
    const url = URL.createObjectURL(file);
    uploadPreview.src = url;
    uploadPreview.classList.add('visible');
    uploadFileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    uploadFileName.classList.add('visible');
    currentImg = url;
    runOcrBtn.disabled = false;
}

// Drag & drop
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) handleUploadedFile(file);
});

runOcrBtn.addEventListener('click', runOcr);

// ── Run OCR ──
async function runOcr() {
    if (!workerReady || !currentImg) return;
    runOcrBtn.disabled = true;
    ocrProgressWrap.classList.add('visible');
    setProgress(0, 'Sending image to OCR engine...', 'Initializing...');

    try {
        const result = await ocrWorker.recognize(currentImg, {}, {
            // Progress callback via events
        });

        // Animate completion
        setProgress(100, 'Done!', 'Text extraction complete');
        await new Promise(r => setTimeout(r, 400));

        const text = result.data.text.trim();
        showResult(text);
    } catch (e) {
        console.error('OCR error:', e);
        setProgress(0, 'Error', 'OCR failed. Please try again.');
        setTimeout(() => ocrProgressWrap.classList.remove('visible'), 2500);
    } finally {
        runOcrBtn.disabled = (currentImg === null);
        // Fake progress for user feedback since Tesseract.js v5 progress is inside worker
        animateProgress();
    }
}

let progressInterval = null;
function animateProgress() {
    let p = 5;
    clearInterval(progressInterval);
    progressInterval = setInterval(() => {
        p = Math.min(p + Math.random() * 18, 92);
        ocrBarFill.style.width = p + '%';
        ocrProgressPct.textContent = Math.round(p) + '%';
        const labels = ['Analyzing layout...', 'Detecting characters...', 'Recognizing text...', 'Applying corrections...'];
        ocrProgressLabel.textContent = labels[Math.floor(p / 25)] || 'Processing...';
    }, 300);
    setTimeout(() => clearInterval(progressInterval), 5000);
}

function setProgress(pct, label, status) {
    clearInterval(progressInterval);
    ocrBarFill.style.width = pct + '%';
    ocrProgressPct.textContent = pct + '%';
    ocrProgressLabel.textContent = label;
    ocrStatusText.textContent = status;
}

function showResult(text) {
    ocrProgressWrap.classList.remove('visible');
    resultPlaceholder.style.display = 'none';
    resultText.style.display = 'block';
    resultText.value = text;

    const words = text ? text.trim().split(/\s+/).filter(Boolean).length : 0;
    resultWordCount.textContent = words + ' word' + (words !== 1 ? 's' : '');
    sessionWords += words;
    heroWordCount.textContent = sessionWords;

    const hasText = text.length > 0;
    copyBtn.disabled = !hasText;
    clearBtn.disabled = !hasText;
    readAloudBtn.disabled = !hasText;
}

// ── Copy ──
copyBtn.addEventListener('click', () => {
    const text = resultText.value;
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => showToast()).catch(() => {
        resultText.select();
        document.execCommand('copy');
        showToast();
    });
});

function showToast() {
    copyToast.classList.add('show');
    setTimeout(() => copyToast.classList.remove('show'), 2200);
}

// ── Clear ──
clearBtn.addEventListener('click', () => {
    resultText.value = '';
    resultText.style.display = 'none';
    resultPlaceholder.style.display = 'flex';
    resultWordCount.textContent = '0 words';
    copyBtn.disabled = true;
    clearBtn.disabled = true;
    readAloudBtn.disabled = true;
    stopSpeech();
    // Also clear uploaded image
    uploadPreview.src = '';
    uploadPreview.classList.remove('visible');
    uploadFileName.classList.remove('visible');
    currentImg = null;
    runOcrBtn.disabled = true;
    ocrProgressWrap.classList.remove('visible');
});

// ── TTS ──
ttsSpeed.addEventListener('input', () => {
    ttsSpeedLabel.textContent = parseFloat(ttsSpeed.value).toFixed(1) + 'x';
});

readAloudBtn.addEventListener('click', () => {
    const text = resultText.value.trim();
    if (!text) return;
    stopSpeech();
    const utter = new SpeechSynthesisUtterance(text);
    utter.rate  = parseFloat(ttsSpeed.value);
    utter.pitch = 1.0;
    utter.lang  = 'en-US';
    utter.onstart = () => {
        isSpeaking = true;
        ttsSpeaking.classList.add('visible');
        stopReadingBtn.disabled = false;
        readAloudBtn.querySelector('i').className = 'fa-solid fa-volume-high';
    };
    utter.onend = utter.onerror = () => {
        isSpeaking = false;
        ttsSpeaking.classList.remove('visible');
        readAloudBtn.querySelector('i').className = 'fa-solid fa-play';
    };
    window.speechSynthesis.speak(utter);
});

stopReadingBtn.addEventListener('click', stopSpeech);

function stopSpeech() {
    window.speechSynthesis.cancel();
    isSpeaking = false;
    ttsSpeaking.classList.remove('visible');
    stopReadingBtn.disabled = true;
    if (readAloudBtn.querySelector('i'))
        readAloudBtn.querySelector('i').className = 'fa-solid fa-play';
}

// ── Init ──
initWorker();

window.addEventListener('beforeunload', () => {
    stopSpeech();
    if (cameraStream) cameraStream.getTracks().forEach(t => t.stop());
    if (ocrWorker) ocrWorker.terminate();
});
</script>
</body>
</html>
