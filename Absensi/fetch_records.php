<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

$id = isset($_GET['id']) ? $_GET['id'] : null; 
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null; 
$attendance_status = isset($_GET['attendance_status']) ? $_GET['attendance_status'] : null; 
$name = isset($_GET['name']) ? $_GET['name'] : null; 
$is_late = isset($_GET['is_late']) ? $_GET['is_late'] : null; 

$query = 'SELECT ar.*, u.full_name FROM attendance_records ar 
          JOIN users u ON ar.user_id = u.user_id WHERE 1=1';
$params = [];

if ($id !== null && $id !== '') {
    $query .= ' AND ar.id = :id';
    $params[':id'] = $id;
}

if ($user_id !== null && $user_id !== '') {
    $query .= ' AND ar.user_id LIKE :user_id';
    $params[':user_id'] = '%' . $user_id . '%';
}

if ($attendance_status !== null && $attendance_status !== '') {
    $query .= ' AND ar.attendance_status = :attendance_status';
    $params[':attendance_status'] = $attendance_status; 
}

if ($name !== null && $name !== '') {
    $query .= ' AND u.full_name LIKE :name';
    $params[':name'] = '%' . $name . '%';
}

if ($is_late !== null && $is_late !== '') {
    $query .= ' AND ar.is_late = :is_late';
    $params[':is_late'] = $is_late;
}

$stmt = $pdo->prepare($query);

if (!$stmt->execute($params)) {
    http_response_code(500); 
    echo json_encode(["error" => "An error occurred while fetching data."]);
    exit();
}

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$attendanceStatusMapping = [ 
    '0' => 'Absent', 
    '1' => 'Present', 
    '2' => 'Izin',
    '3' => 'Sakit',
    '4' => 'Alfa',
    '5' => 'Belum Checkout',
    '6' => 'Belum Checkin'
];

foreach ($records as &$record) {
    $record['attendance_status_text'] = $attendanceStatusMapping[$record['attendance_status']] ?? 'Unknown'; 
    $record['is_late_text'] = $record['is_late'] ? 'Late' : 'On Time';
}

header('Content-Type: application/json');
echo json_encode($records);
?>
