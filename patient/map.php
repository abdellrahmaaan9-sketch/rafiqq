<?php
session_start();
require __DIR__ . '/../pgdb/db.php';

/* Detect actual lat/lng column names in the place table */
function detectLatLngCols(PDO $pdo): array {
    $q = $pdo->query("
        SELECT column_name FROM information_schema.columns
        WHERE table_schema='public' AND table_name='place'
    ");
    $cols = array_column($q->fetchAll(PDO::FETCH_ASSOC), 'column_name');
    $latCol = in_array('latitude',  $cols) ? 'latitude'  : (in_array('lat', $cols) ? 'lat' : null);
    $lngCol = in_array('longitude', $cols) ? 'longitude' : (in_array('lng', $cols) ? 'lng' : (in_array('lon', $cols) ? 'lon' : null));
    return [$latCol, $lngCol, $cols];
}

try {
    [$latCol, $lngCol, $allCols] = detectLatLngCols($pdo);
    $stmt = $pdo->query("SELECT * FROM place");
    $rawPlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Normalise every row so JS always gets latitude/longitude keys */
    $places = array_map(function($row) use ($latCol, $lngCol) {
        $row['latitude']  = $latCol  ? ($row[$latCol]  ?? null) : null;
        $row['longitude'] = $lngCol  ? ($row[$lngCol]  ?? null) : null;
        return $row;
    }, $rawPlaces);
} catch (Exception $e) {
    $places = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rafiq | Accessible Places</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>
:root{
    --primary:#404066;
    --primary-2:#5f62b3;
    --primary-dark:#2B2C41;
    --bg:#f6f8ff;
    --card:#ffffff;
    --text:#23263a;
    --muted:#70778d;
    --border:#e7eaf3;
    --chip:#f1f4ff;
    --success:#1fa971;
    --shadow:0 12px 30px rgba(43,44,65,0.10);
    --radius:22px;
}

*{
    box-sizing:border-box;
}

html, body{
    margin:0;
    padding:0;
}

body{
    font-family:'Segoe UI',sans-serif;
    background:
        radial-gradient(circle at top left, #edf1ff 0%, #f7f9ff 40%, #f6f8ff 100%);
    color:var(--text);
}

.page-wrap{
    max-width:1450px;
    margin:0 auto;
    padding:24px;
}

/* HERO */
.hero{
    background:linear-gradient(135deg,#404066 0%,#54558c 45%,#6e6bff 100%);
    color:#fff;
    border-radius:28px;
    padding:34px 28px;
    box-shadow:var(--shadow);
    margin-top:18px;
    position:relative;
    overflow:hidden;
}

.hero::before,
.hero::after{
    content:"";
    position:absolute;
    border-radius:50%;
    background:rgba(255,255,255,0.08);
    pointer-events:none;
}

.hero::before{
    width:220px;
    height:220px;
    top:-70px;
    right:-50px;
}

.hero::after{
    width:160px;
    height:160px;
    bottom:-60px;
    left:-30px;
}

.hero-content{
    position:relative;
    z-index:2;
}

.hero h2{
    margin:0 0 10px;
    font-size:34px;
    font-weight:800;
    letter-spacing:-0.4px;
}

.hero p{
    margin:0;
    max-width:760px;
    line-height:1.7;
    color:rgba(255,255,255,0.92);
    font-size:15px;
}

.hero-badges{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:18px;
}

.hero-badge{
    padding:8px 12px;
    border-radius:999px;
    background:rgba(255,255,255,0.14);
    border:1px solid rgba(255,255,255,0.18);
    font-size:13px;
    font-weight:600;
}

/* TOP CONTROLS */
.top-grid{
    display:grid;
    grid-template-columns:1.5fr 1fr;
    gap:18px;
    margin-top:20px;
}

.panel{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:24px;
    padding:18px;
    box-shadow:var(--shadow);
}

.panel-title{
    margin:0 0 14px;
    color:var(--primary-dark);
    font-size:16px;
    font-weight:800;
}

.search-row{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}

.search-box{
    flex:1;
    min-width:240px;
    position:relative;
}

.search-icon{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    font-size:17px;
    color:#8a90a6;
}

.search-input{
    width:100%;
    padding:14px 16px 14px 44px;
    border-radius:14px;
    border:1px solid var(--border);
    outline:none;
    font-size:15px;
    background:#fff;
    transition:0.2s ease;
}

.search-input:focus{
    border-color:#9ea4ff;
    box-shadow:0 0 0 4px rgba(108,99,255,0.10);
}

.filters{
    margin-top:14px;
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

.filter-btn,
.action-btn,
.select-control,
.view-btn{
    border:none;
    outline:none;
    font-family:inherit;
    transition:0.2s ease;
}

.filter-btn{
    padding:10px 14px;
    border-radius:999px;
    background:#fff;
    border:1px solid #dbe0ee;
    color:var(--primary-dark);
    cursor:pointer;
    font-size:14px;
    font-weight:700;
}

.filter-btn:hover{
    background:#f7f8ff;
    transform:translateY(-1px);
}

.filter-btn.active{
    background:var(--primary);
    border-color:var(--primary);
    color:white;
    box-shadow:0 10px 18px rgba(64,64,102,0.14);
}

.action-btn{
    padding:13px 16px;
    border-radius:14px;
    cursor:pointer;
    font-size:14px;
    font-weight:700;
}

.action-btn.primary{
    background:var(--primary);
    color:white;
}

.action-btn.primary:hover{
    background:#343455;
}

.action-btn.soft{
    background:#eef1ff;
    color:var(--primary-dark);
    border:1px solid #dbe2ff;
}

.action-btn.soft:hover{
    background:#e7ebff;
}

.toolbar{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
}

.select-control{
    min-width:190px;
    padding:13px 14px;
    border-radius:14px;
    border:1px solid var(--border);
    background:#fff;
    cursor:pointer;
    color:var(--text);
    font-size:14px;
}

.view-switch{
    display:flex;
    gap:8px;
    padding:5px;
    border-radius:14px;
    background:#f5f7fd;
    border:1px solid var(--border);
}

.view-btn{
    padding:9px 14px;
    border-radius:10px;
    cursor:pointer;
    background:transparent;
    color:var(--muted);
    font-size:13px;
    font-weight:800;
}

.view-btn.active{
    background:white;
    color:var(--primary-dark);
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

/* MAIN */
.main-layout{
    display:grid;
    grid-template-columns:1.35fr 0.95fr;
    gap:18px;
    margin-top:20px;
    align-items:start;
}

.map-panel,
.sidebar-panel{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:24px;
    box-shadow:var(--shadow);
    overflow:hidden;
}

.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    padding:18px 18px 14px;
    border-bottom:1px solid #eef1f7;
    flex-wrap:wrap;
    background:#fff;
}

.panel-head h3{
    margin:0;
    color:var(--primary-dark);
    font-size:18px;
    font-weight:800;
}

.panel-sub{
    font-size:13px;
    color:var(--muted);
}

#map{
    height:640px;
    width:100%;
}

/* SIDEBAR WITH INTERNAL SCROLL */
.sidebar-panel{
    height:640px;
    display:flex;
    flex-direction:column;
}

.list-info{
    padding:14px 18px 0;
    color:var(--muted);
    font-size:13px;
    flex:0 0 auto;
}

#cards{
    flex:1 1 auto;
    min-height:0;
    overflow-y:auto;
    overflow-x:hidden;
    padding:16px 18px 18px;
    scrollbar-width:thin;
    scrollbar-color:#cfd5ea transparent;
}

#cards::-webkit-scrollbar{
    width:8px;
}

#cards::-webkit-scrollbar-track{
    background:transparent;
}

#cards::-webkit-scrollbar-thumb{
    background:#d4d9ec;
    border-radius:999px;
}

.cards.list-view{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.cards.grid-view{
    display:grid;
    grid-template-columns:1fr;
    gap:14px;
}

.place-card{
    background:linear-gradient(180deg, #ffffff 0%, #fcfcff 100%);
    border:1px solid #ebedf5;
    border-radius:20px;
    padding:16px;
    cursor:pointer;
    transition:0.22s ease;
}

.place-card:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 24px rgba(43,44,65,0.10);
    border-color:#d8ddef;
}

.place-top{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    margin-bottom:10px;
}

.place-name{
    font-size:17px;
    font-weight:800;
    color:var(--primary-dark);
    margin-bottom:6px;
    line-height:1.35;
}

.place-meta{
    font-size:13px;
    color:var(--muted);
    line-height:1.6;
}

.place-badges{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:12px;
    margin-bottom:14px;
}

.badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 10px;
    border-radius:999px;
    background:var(--chip);
    color:#39406e;
    border:1px solid #dde3ff;
    font-size:12px;
    font-weight:700;
}

.badge.distance{
    background:#ebfbf5;
    color:#11835a;
    border-color:#cceedd;
}

.place-footer{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}

.rank{
    font-size:12px;
    color:#98a0b5;
    font-weight:700;
}

.card-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.small-btn{
    padding:9px 12px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-size:12px;
    font-weight:800;
    transition:0.2s;
}

.small-btn.light{
    background:#edf1ff;
    color:var(--primary-dark);
}

.small-btn.light:hover{
    background:#e4e9ff;
}

.small-btn.primary{
    background:var(--primary);
    color:#fff;
}

.small-btn.primary:hover{
    background:#343455;
}

.empty-state{
    text-align:center;
    color:#777e95;
    padding:42px 16px;
    background:#fafbff;
    border:1px dashed #d7dced;
    border-radius:18px;
}

/* LEAFLET */
.leaflet-popup-content-wrapper{
    border-radius:18px;
    box-shadow:0 12px 28px rgba(0,0,0,0.12);
}

.popup-title{
    font-size:16px;
    font-weight:800;
    color:var(--primary-dark);
    margin-bottom:6px;
}

.popup-address{
    font-size:13px;
    color:var(--muted);
    line-height:1.6;
    margin-bottom:10px;
}

.popup-badges{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
}

.popup-badge{
    display:inline-flex;
    align-items:center;
    padding:5px 8px;
    border-radius:999px;
    background:var(--chip);
    color:#39406e;
    border:1px solid #dde3ff;
    font-size:11px;
    font-weight:700;
}

@media (max-width: 1180px){
    .main-layout{
        grid-template-columns:1fr;
    }

    #map,
    .sidebar-panel{
        height:560px;
    }
}

@media (max-width: 980px){
    .top-grid{
        grid-template-columns:1fr;
    }
}

@media (max-width: 720px){
    .page-wrap{
        padding:14px;
    }

    .hero{
        padding:24px 18px;
        border-radius:22px;
    }

    .hero h2{
        font-size:28px;
    }

    .search-row,
    .toolbar{
        flex-direction:column;
        align-items:stretch;
    }

    .action-btn,
    .select-control{
        width:100%;
    }

    #map,
    .sidebar-panel{
        height:460px;
    }
}
</style>
</head>

<body>

<?php include '../general/nav_patient.php'; ?>

<div class="page-wrap">

    <section class="hero">
        <div class="hero-content">
            <h2>Find Accessible Places</h2>
            <p>
                Discover nearby places with accessibility features like elevators, ramps, toilets, and parking.
                Search faster, filter smarter, and explore everything on an interactive map.
            </p>

            <div class="hero-badges">
                <span class="hero-badge">♿ Accessibility First</span>
                <span class="hero-badge">📍 Nearby Results</span>
                <span class="hero-badge">🗺️ Interactive Map</span>
                <span class="hero-badge">⚡ Better Experience</span>
            </div>
        </div>
    </section>

    <section class="top-grid">
        <div class="panel">
            <h3 class="panel-title">Search & Filters</h3>

            <div class="search-row">
                <div class="search-box">
                    <span class="search-icon">🔎</span>
                    <input
                        type="text"
                        id="searchInput"
                        class="search-input"
                        placeholder="Search by place name or address..."
                    >
                </div>

                <button id="locateBtn" class="action-btn primary" type="button">Use My Location</button>
                <button id="resetBtn" class="action-btn soft" type="button">Reset</button>
            </div>

            <div class="filters">
                <button class="filter-btn" data-filter="elevator" type="button">🛗 Elevator</button>
                <button class="filter-btn" data-filter="ramp" type="button">♿ Ramp</button>
                <button class="filter-btn" data-filter="toilet" type="button">🚻 Toilet</button>
                <button class="filter-btn" data-filter="parking" type="button">🅿️ Parking</button>
            </div>
        </div>

        <div class="panel">
            <h3 class="panel-title">Display Options</h3>

            <div class="toolbar">
                <select id="sortSelect" class="select-control">
                    <option value="default">Sort: Default</option>
                    <option value="name_asc">Sort: Name A → Z</option>
                    <option value="name_desc">Sort: Name Z → A</option>
                    <option value="nearest">Sort: Nearest First</option>
                    <option value="farthest">Sort: Farthest First</option>
                </select>

                <div class="view-switch">
                    <button id="listViewBtn" class="view-btn active" type="button">List</button>
                    <button id="gridViewBtn" class="view-btn" type="button">Grid</button>
                </div>
            </div>
        </div>
    </section>

    <section class="main-layout">
        <div class="map-panel">
            <div class="panel-head">
                <div>
                    <h3>Map View</h3>
                    <div class="panel-sub">Interactive location browsing</div>
                </div>
            </div>
            <div id="map"></div>
        </div>

        <div class="sidebar-panel">
            <div class="panel-head">
                <div>
                    <h3>Places (<span id="count">0</span>)</h3>
                    <div class="panel-sub">Scrollable list inside the panel</div>
                </div>
            </div>

            <div class="list-info" id="resultInfo">Showing all available places.</div>
            <div id="cards" class="cards list-view"></div>
        </div>
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const PLACES = <?= json_encode($places, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

const EGYPT_CENTER = [26.8206, 30.8025];
const EGYPT_ZOOM = 6;

const map = L.map('map').setView(EGYPT_CENTER, EGYPT_ZOOM);

L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO'
}).addTo(map);

function makePlaceIcon(color = '#6470d2') {
    return L.divIcon({
        className: '',
        html: `<div style="
            width:18px;height:18px;border-radius:50%;
            background:${color};border:3px solid #fff;
            box-shadow:0 3px 10px rgba(100,112,210,.55);
        "></div>`,
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        popupAnchor: [0, -12]
    });
}

function makeUserIcon() {
    return L.divIcon({
        className: '',
        html: `<div style="
            width:22px;height:22px;border-radius:50%;
            background:#353b69;border:3px solid #fff;
            box-shadow:0 4px 14px rgba(53,59,105,.55);
            display:flex;align-items:center;justify-content:center;
            font-size:11px;color:#fff;font-weight:900;
        ">📍</div>`,
        iconSize: [22, 22],
        iconAnchor: [11, 11],
        popupAnchor: [0, -14]
    });
}

const searchInput = document.getElementById("searchInput");
const filterButtons = document.querySelectorAll(".filter-btn[data-filter]");
const locateBtn = document.getElementById("locateBtn");
const resetBtn = document.getElementById("resetBtn");
const sortSelect = document.getElementById("sortSelect");
const listViewBtn = document.getElementById("listViewBtn");
const gridViewBtn = document.getElementById("gridViewBtn");

const cards = document.getElementById("cards");
const count = document.getElementById("count");
const resultInfo = document.getElementById("resultInfo");

let activeFilters = [];
let markers = [];
let userLocationMarker = null;
let userLocation = null;

/* helpers */
function isValidCoords(place) {
    if (!place) return false;
    const lat = parseFloat(place.latitude);
    const lng = parseFloat(place.longitude);
    return !isNaN(lat) && !isNaN(lng);
}

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

function getDistanceKm(lat1, lon1, lat2, lon2) {
    const toRad = deg => deg * Math.PI / 180;
    const R = 6371;

    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function getFacilityLabels(place) {
    const facilities = [];

    if (place.elevator === "t") facilities.push("🛗 Elevator");
    if (place.ramp === "t") facilities.push("♿ Ramp");
    if (place.toilet === "t") facilities.push("🚻 Toilet");
    if (place.parking === "t") facilities.push("🅿️ Parking");

    return facilities;
}

function updateResultInfo(list) {
    let text = `Showing ${list.length} place${list.length === 1 ? "" : "s"}`;

    if (activeFilters.length > 0) {
        text += ` with ${activeFilters.length} active filter${activeFilters.length === 1 ? "" : "s"}`;
    }

    if (userLocation) {
        text += ` near your current location`;
    }

    text += `.`;
    resultInfo.textContent = text;
}

function render(list) {
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];

    cards.innerHTML = "";
    count.textContent = list.length;
    updateResultInfo(list);

    if (!list.length) {
        cards.innerHTML = `
            <div class="empty-state">
                No places found with the selected search or filters.
            </div>
        `;
        return;
    }

    const bounds = [];

    list.forEach((place, index) => {
        if (!isValidCoords(place)) return;

        const lat = parseFloat(place.latitude);
        const lng = parseFloat(place.longitude);
        const name = place.name || "Unnamed Place";
        const address = place.address || "No address available";
        const facilities = getFacilityLabels(place);

        bounds.push([lat, lng]);

        const popupBadges = facilities.length
            ? facilities.map(item => `<span class="popup-badge">${escapeHtml(item)}</span>`).join("")
            : `<span class="popup-badge">No accessibility details</span>`;

        const popupDistance = place.distance_km !== undefined
            ? `<div style="margin-top:10px;font-size:12px;color:#11835a;font-weight:700;">Distance: ${place.distance_km.toFixed(2)} km</div>`
            : "";

        const popupContent = `
            <div style="min-width:220px;">
                <div class="popup-title">${escapeHtml(name)}</div>
                <div class="popup-address">${escapeHtml(address)}</div>
                <div class="popup-badges">${popupBadges}</div>
                ${popupDistance}
            </div>
        `;

        const marker = L.marker([lat, lng], { icon: makePlaceIcon() }).addTo(map).bindPopup(popupContent);
        markers.push(marker);

        const facilitiesHtml = facilities.length
            ? facilities.map(item => `<span class="badge">${escapeHtml(item)}</span>`).join("")
            : `<span class="badge">No accessibility details</span>`;

        const distanceHtml = place.distance_km !== undefined
            ? `<span class="badge distance">📍 ${place.distance_km.toFixed(2)} km away</span>`
            : "";

        const card = document.createElement("div");
        card.className = "place-card";

        card.innerHTML = `
            <div class="place-top">
                <div>
                    <div class="place-name">${escapeHtml(name)}</div>
                    <div class="place-meta">${escapeHtml(address)}</div>
                </div>
            </div>

            <div class="place-badges">
                ${facilitiesHtml}
                ${distanceHtml}
            </div>

            <div class="place-footer">
                <div class="rank">#${index + 1} in current results</div>
                <div class="card-actions">
                    <button class="small-btn light" type="button">Focus Map</button>
                    <button class="small-btn primary" type="button">Open Pin</button>
                </div>
            </div>
        `;

        const btns = card.querySelectorAll(".small-btn");

        btns[0].addEventListener("click", function(e) {
            e.stopPropagation();
            map.setView([lat, lng], 15);
        });

        btns[1].addEventListener("click", function(e) {
            e.stopPropagation();
            map.setView([lat, lng], 15);
            marker.openPopup();
        });

        card.addEventListener("click", function() {
            map.setView([lat, lng], 15);
            marker.openPopup();
        });

        cards.appendChild(card);
    });

    if (bounds.length > 0) {
        if (userLocation) {
            bounds.push([userLocation.lat, userLocation.lng]);
        }

        if (bounds.length === 1) {
            map.setView(bounds[0], 14);
        } else {
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    }
}

function applyFilters() {
    const query = searchInput.value.toLowerCase().trim();
    const sortValue = sortSelect.value;

    let filtered = PLACES.filter(place => {
        if (!isValidCoords(place)) return false;

        const name = (place.name || "").toLowerCase();
        const address = (place.address || "").toLowerCase();

        const matchText = name.includes(query) || address.includes(query);
        const matchFilters = activeFilters.every(filterKey => place[filterKey] === "t");

        return matchText && matchFilters;
    }).map(place => {
        const item = { ...place };

        if (userLocation) {
            item.distance_km = getDistanceKm(
                userLocation.lat,
                userLocation.lng,
                parseFloat(place.latitude),
                parseFloat(place.longitude)
            );
        }

        return item;
    });

    if (sortValue === "name_asc") {
        filtered.sort((a, b) => (a.name || "").localeCompare(b.name || ""));
    } else if (sortValue === "name_desc") {
        filtered.sort((a, b) => (b.name || "").localeCompare(a.name || ""));
    } else if (sortValue === "nearest" && userLocation) {
        filtered.sort((a, b) => (a.distance_km ?? 999999) - (b.distance_km ?? 999999));
    } else if (sortValue === "farthest" && userLocation) {
        filtered.sort((a, b) => (b.distance_km ?? -1) - (a.distance_km ?? -1));
    } else if (userLocation) {
        filtered.sort((a, b) => (a.distance_km ?? 999999) - (b.distance_km ?? 999999));
    }

    render(filtered);
}

/* search */
searchInput.addEventListener("input", applyFilters);

/* sort */
sortSelect.addEventListener("change", applyFilters);

/* filters */
filterButtons.forEach(btn => {
    btn.addEventListener("click", function() {
        const filterKey = btn.dataset.filter;
        if (!filterKey) return;

        if (activeFilters.includes(filterKey)) {
            activeFilters = activeFilters.filter(item => item !== filterKey);
            btn.classList.remove("active");
        } else {
            activeFilters.push(filterKey);
            btn.classList.add("active");
        }

        applyFilters();
    });
});

/* view switch */
listViewBtn.addEventListener("click", function() {
    cards.classList.remove("grid-view");
    cards.classList.add("list-view");
    listViewBtn.classList.add("active");
    gridViewBtn.classList.remove("active");
});

gridViewBtn.addEventListener("click", function() {
    cards.classList.remove("list-view");
    cards.classList.add("grid-view");
    gridViewBtn.classList.add("active");
    listViewBtn.classList.remove("active");
});

/* user location */
locateBtn.addEventListener("click", function() {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by this browser.");
        return;
    }

    locateBtn.textContent = "Locating...";

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            userLocation = { lat, lng };

            if (userLocationMarker) {
                map.removeLayer(userLocationMarker);
            }

            userLocationMarker = L.marker([lat, lng], { icon: makeUserIcon() })
                .addTo(map).bindPopup("📍 You are here").openPopup();

            map.setView([lat, lng], 15);

            locateBtn.textContent = "Location Enabled";
            locateBtn.classList.add("active");

            applyFilters();
        },
        function() {
            locateBtn.textContent = "Use My Location";
            alert("Unable to retrieve your location.");
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
});

/* reset */
resetBtn.addEventListener("click", function() {
    searchInput.value = "";
    activeFilters = [];
    userLocation = null;
    sortSelect.value = "default";

    filterButtons.forEach(btn => btn.classList.remove("active"));
    locateBtn.classList.remove("active");
    locateBtn.textContent = "Use My Location";

    if (userLocationMarker) {
        map.removeLayer(userLocationMarker);
        userLocationMarker = null;
    }

    map.setView(EGYPT_CENTER, EGYPT_ZOOM);
    render(PLACES.filter(place => isValidCoords(place)));
});

/* initial */
render(PLACES.filter(place => isValidCoords(place)));
</script>

<?php include '../general/footer.php'; ?>

</body>
</html>