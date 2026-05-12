<?php
// =============================================================
// FILE: rafiq/admin/admin_api.php
// =============================================================

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// ── CHANGE THESE TO MATCH YOUR DATABASE ──────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'rafiq');
define('DB_USER', 'postgres');
define('DB_PASS', '123456789');   // <-- put your postgres password here if you have one

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

$body = [];
if (in_array($method, ['POST','PUT','PATCH'])) {
    $raw = file_get_contents('php://input');
    if ($raw) $body = json_decode($raw, true) ?? [];
}

try {
    switch ($action) {
        case 'stats':                  echo json_encode(getStats());                              break;
        case 'providers':              echo json_encode(getProviders());                          break;
        case 'provider_detail':        echo json_encode(getProviderDetail($id));                  break;
        case 'update_provider_status': echo json_encode(updateProviderStatus($id, $body));        break;
        case 'places':                 echo json_encode(getPlaces());                             break;
        case 'add_place':              echo json_encode(addPlace($body));                         break;
        case 'edit_place':             echo json_encode(editPlace($id, $body));                   break;
        case 'delete_place':           echo json_encode(deletePlace($id));                        break;
        case 'update_place_status':    echo json_encode(updatePlaceStatus($id, $body));           break;
        case 'bookings':               echo json_encode(getBookings());                           break;
        case 'patients':               echo json_encode(getPatients());                           break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

// ── STATS ────────────────────────────────────────────────────
function getStats(): array {
    $db = db();
    $totalProviders   = (int)$db->query("SELECT COUNT(*) FROM public.provider")->fetchColumn();
    $pendingProviders = (int)$db->query("SELECT COUNT(*) FROM public.provider WHERE status='pending'")->fetchColumn();
    $acceptedProviders= (int)$db->query("SELECT COUNT(*) FROM public.provider WHERE status='accepted'")->fetchColumn();
    $rejectedProviders= (int)$db->query("SELECT COUNT(*) FROM public.provider WHERE status='rejected'")->fetchColumn();
    $totalPlaces      = (int)$db->query("SELECT COUNT(*) FROM public.place")->fetchColumn();
    $activePlaces     = (int)$db->query("SELECT COUNT(*) FROM public.place WHERE status='active'")->fetchColumn();
    $pendingPlaces    = (int)$db->query("SELECT COUNT(*) FROM public.place WHERE status='pending'")->fetchColumn();
    $totalPatients    = (int)$db->query("SELECT COUNT(*) FROM public.patient")->fetchColumn();
    $totalBookings    = (int)$db->query("SELECT COUNT(*) FROM public.booking")->fetchColumn();
    $pendingBookings  = (int)$db->query("SELECT COUNT(*) FROM public.booking WHERE status='pending'")->fetchColumn();
    $doneBookings     = (int)$db->query("SELECT COUNT(*) FROM public.booking WHERE status='completed'")->fetchColumn();

    $monthly = $db->query("
        SELECT TO_CHAR(date,'Mon') AS month, COUNT(*) AS count
        FROM public.booking
        WHERE date >= CURRENT_DATE - INTERVAL '6 months' AND date IS NOT NULL
        GROUP BY TO_CHAR(date,'Mon'), DATE_TRUNC('month',date)
        ORDER BY DATE_TRUNC('month',date)
    ")->fetchAll();

    $services = $db->query("
        SELECT service_type, COUNT(*) AS count
        FROM public.booking WHERE service_type IS NOT NULL
        GROUP BY service_type ORDER BY count DESC LIMIT 6
    ")->fetchAll();

    $recent = $db->query("
        SELECT b.booking_id, b.status, b.service_type, b.date, b.payment_total, b.is_urgent,
               pu.first_name||' '||pu.last_name  AS patient_name,
               pru.first_name||' '||pru.last_name AS provider_name
        FROM public.booking b
        LEFT JOIN public.patient pat ON pat.user_id=b.patient_id
        LEFT JOIN public.\"user\" pu  ON pu.user_id=pat.user_id
        LEFT JOIN public.provider pr  ON pr.user_id=b.provider_id
        LEFT JOIN public.\"user\" pru ON pru.user_id=pr.user_id
        ORDER BY b.booking_id DESC LIMIT 8
    ")->fetchAll();

    return compact('totalProviders','pendingProviders','acceptedProviders','rejectedProviders',
                   'totalPlaces','activePlaces','pendingPlaces','totalPatients',
                   'totalBookings','pendingBookings','doneBookings','monthly','services','recent');
}

// ── PROVIDERS ────────────────────────────────────────────────
function getProviders(): array {
    $db = db();
    $search   = $_GET['search']   ?? '';
    $status   = $_GET['status']   ?? '';
    $category = $_GET['category'] ?? '';
    $where = ['1=1']; $params = [];

    if ($search) { $where[] = "(u.first_name ILIKE :s OR u.last_name ILIKE :s OR u.email ILIKE :s)"; $params[':s'] = "%$search%"; }
    if ($status && $status !== 'all') { $where[] = "p.status=:st"; $params[':st'] = $status; }
    if ($category && $category !== 'all') {
        $map = ['driver'=>'driver','doctor'=>'doctor','caregiver'=>'caregiver','interpreter'=>'interpreter'];
        $tbl = $map[strtolower($category)] ?? null;
        if ($tbl) $where[] = "EXISTS(SELECT 1 FROM public.$tbl x WHERE x.user_id=p.user_id)";
    }
    $w = implode(' AND ', $where);

    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.photo,
               p.phone, p.address, p.national_id, p.gender, p.dob, p.cv,
               p.status, p.admin_note, p.created_at,
               CASE
                 WHEN EXISTS(SELECT 1 FROM public.driver      d WHERE d.user_id=p.user_id) THEN 'Driver'
                 WHEN EXISTS(SELECT 1 FROM public.doctor      d WHERE d.user_id=p.user_id) THEN 'Doctor'
                 WHEN EXISTS(SELECT 1 FROM public.caregiver   c WHERE c.user_id=p.user_id) THEN 'Caregiver'
                 WHEN EXISTS(SELECT 1 FROM public.interpreter i WHERE i.user_id=p.user_id) THEN 'Interpreter'
                 ELSE 'Provider'
               END AS category,
               (SELECT COUNT(*) FROM public.booking b WHERE b.provider_id=p.user_id) AS total_bookings
        FROM public.provider p JOIN public.\"user\" u ON u.user_id=p.user_id
        WHERE $w ORDER BY p.user_id DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProviderDetail(int $id): array {
    $db = db();
    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.photo,
               p.phone, p.address, p.national_id, p.gender, p.dob, p.cv, p.status, p.admin_note, p.created_at,
               CASE
                 WHEN EXISTS(SELECT 1 FROM public.driver      d WHERE d.user_id=p.user_id) THEN 'Driver'
                 WHEN EXISTS(SELECT 1 FROM public.doctor      d WHERE d.user_id=p.user_id) THEN 'Doctor'
                 WHEN EXISTS(SELECT 1 FROM public.caregiver   c WHERE c.user_id=p.user_id) THEN 'Caregiver'
                 WHEN EXISTS(SELECT 1 FROM public.interpreter i WHERE i.user_id=p.user_id) THEN 'Interpreter'
                 ELSE 'Provider'
               END AS category,
               dr.driving_license, dr.available_balance, dr.company_due, dr.total_earned, dr.total_trips,
               doc.medical_license, doc.speciality,
               cg.shift_preference, intp.languages,
               car.model AS car_model, car.make AS car_make, car.color AS car_color,
               car.license_plate, car.wheelchair_accessible
        FROM public.provider p JOIN public.\"user\" u ON u.user_id=p.user_id
        LEFT JOIN public.driver      dr   ON dr.user_id=p.user_id
        LEFT JOIN public.doctor      doc  ON doc.user_id=p.user_id
        LEFT JOIN public.caregiver   cg   ON cg.user_id=p.user_id
        LEFT JOIN public.interpreter intp ON intp.user_id=p.user_id
        LEFT JOIN public.car         car  ON car.driver_id=p.user_id
        WHERE p.user_id=:id
    ");
    $stmt->execute([':id'=>$id]);
    $p = $stmt->fetch();
    if (!$p) throw new Exception('Provider not found');

    $b = $db->prepare("
        SELECT b.booking_id, b.status, b.service_type, b.date, b.payment_total, b.payment_status, b.rating,
               pu.first_name||' '||pu.last_name AS patient_name
        FROM public.booking b
        LEFT JOIN public.patient pat ON pat.user_id=b.patient_id
        LEFT JOIN public.\"user\" pu ON pu.user_id=pat.user_id
        WHERE b.provider_id=:id ORDER BY b.booking_id DESC LIMIT 20
    ");
    $b->execute([':id'=>$id]);
    $p['bookings'] = $b->fetchAll();
    return $p;
}

function updateProviderStatus(int $id, array $body): array {
    $status = $body['status'] ?? 'pending';
    $note   = $body['note']   ?? '';
    if (!in_array($status, ['pending','accepted','rejected'])) throw new Exception('Invalid status');
    db()->prepare("UPDATE public.provider SET status=:s, admin_note=:n WHERE user_id=:id")
       ->execute([':s'=>$status, ':n'=>$note, ':id'=>$id]);
    return ['success'=>true];
}

// ── PLACES ───────────────────────────────────────────────────
function getPlaces(): array {
    $db = db();
    $search = $_GET['search'] ?? '';
    $type   = $_GET['type']   ?? '';
    $status = $_GET['status'] ?? '';
    $where = ['1=1']; $params = [];

    if ($search) { $where[] = "(pl.name ILIKE :s OR pl.address ILIKE :s OR pl.type ILIKE :s)"; $params[':s'] = "%$search%"; }
    if ($type   && $type   !== 'all') { $where[] = "pl.type=:t";   $params[':t']  = $type; }
    if ($status && $status !== 'all') { $where[] = "pl.status=:st";$params[':st'] = $status; }

    $w = implode(' AND ', $where);
    $stmt = $db->prepare("
        SELECT pl.*, (SELECT COUNT(*) FROM public.booking b WHERE b.place_id=pl.place_id) AS booking_count
        FROM public.place pl WHERE $w ORDER BY pl.place_id DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addPlace(array $d): array {
    $stmt = db()->prepare("
        INSERT INTO public.place (name,type,address,elevator,ramp,toilet,parking,comment,latitude,longitude,photo,status)
        VALUES (:name,:type,:address,:elevator,:ramp,:toilet,:parking,:comment,:lat,:lng,:photo,:status) RETURNING *
    ");
    $stmt->execute([
        ':name'=>$d['name']??'', ':type'=>$d['type']??'', ':address'=>$d['address']??'',
        ':elevator'=>($d['elevator']??false)?'true':'false', ':ramp'=>($d['ramp']??false)?'true':'false',
        ':toilet'=>($d['toilet']??false)?'true':'false',    ':parking'=>($d['parking']??false)?'true':'false',
        ':comment'=>$d['comment']??'', ':lat'=>$d['latitude']??null, ':lng'=>$d['longitude']??null,
        ':photo'=>$d['photo']??'', ':status'=>$d['status']??'active',
    ]);
    return $stmt->fetch();
}

function editPlace(int $id, array $d): array {
    $stmt = db()->prepare("
        UPDATE public.place SET name=:name,type=:type,address=:address,elevator=:elevator,
        ramp=:ramp,toilet=:toilet,parking=:parking,comment=:comment,
        latitude=:lat,longitude=:lng,photo=:photo,status=:status
        WHERE place_id=:id RETURNING *
    ");
    $stmt->execute([
        ':name'=>$d['name']??'', ':type'=>$d['type']??'', ':address'=>$d['address']??'',
        ':elevator'=>($d['elevator']??false)?'true':'false', ':ramp'=>($d['ramp']??false)?'true':'false',
        ':toilet'=>($d['toilet']??false)?'true':'false',    ':parking'=>($d['parking']??false)?'true':'false',
        ':comment'=>$d['comment']??'', ':lat'=>$d['latitude']??null, ':lng'=>$d['longitude']??null,
        ':photo'=>$d['photo']??'', ':status'=>$d['status']??'active', ':id'=>$id,
    ]);
    return $stmt->fetch();
}

function deletePlace(int $id): array {
    db()->prepare("DELETE FROM public.place WHERE place_id=:id")->execute([':id'=>$id]);
    return ['success'=>true, 'deleted_id'=>$id];
}

function updatePlaceStatus(int $id, array $body): array {
    $status = $body['status'] ?? 'active';
    if (!in_array($status, ['active','pending','hidden'])) throw new Exception('Invalid status');
    db()->prepare("UPDATE public.place SET status=:s WHERE place_id=:id")->execute([':s'=>$status,':id'=>$id]);
    return ['success'=>true];
}

// ── BOOKINGS ─────────────────────────────────────────────────
function getBookings(): array {
    $db = db();
    $search = $_GET['search']       ?? '';
    $status = $_GET['status']       ?? '';
    $stype  = $_GET['service_type'] ?? '';
    $where = ['1=1']; $params = [];

    if ($search) { $where[] = "(pu.first_name ILIKE :s OR pu.last_name ILIKE :s OR pru.first_name ILIKE :s)"; $params[':s'] = "%$search%"; }
    if ($status && $status !== 'all') { $where[] = "b.status=:st";    $params[':st']    = $status; }
    if ($stype  && $stype  !== 'all') { $where[] = "b.service_type=:stype"; $params[':stype'] = $stype; }

    $w = implode(' AND ', $where);
    $stmt = $db->prepare("
        SELECT b.booking_id, b.date, b.status, b.service_type, b.payment_total, b.payment_status,
               b.rating, b.is_urgent, b.is_full_day,
               pu.first_name||' '||pu.last_name   AS patient_name,
               pru.first_name||' '||pru.last_name AS provider_name
        FROM public.booking b
        LEFT JOIN public.patient pat ON pat.user_id=b.patient_id
        LEFT JOIN public.\"user\" pu  ON pu.user_id=pat.user_id
        LEFT JOIN public.provider pr  ON pr.user_id=b.provider_id
        LEFT JOIN public.\"user\" pru ON pru.user_id=pr.user_id
        WHERE $w ORDER BY b.booking_id DESC LIMIT 200
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── PATIENTS ─────────────────────────────────────────────────
function getPatients(): array {
    $db     = db();
    $search = $_GET['search'] ?? '';
    $where  = ['1=1']; $params = [];
    if ($search) { $where[] = "(u.first_name ILIKE :s OR u.last_name ILIKE :s OR u.email ILIKE :s)"; $params[':s'] = "%$search%"; }
    $w = implode(' AND ', $where);
    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.photo,
               pat.phone, pat.address, pat.disability, pat.gender, pat.dob,
               (SELECT COUNT(*) FROM public.booking b WHERE b.patient_id=pat.user_id) AS total_bookings
        FROM public.patient pat JOIN public.\"user\" u ON u.user_id=pat.user_id
        WHERE $w ORDER BY u.user_id DESC LIMIT 500
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}