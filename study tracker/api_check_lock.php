<?php 
require_once 'db_connect.php'; 

$now = date('H:i');
$day = date('l');

// Using stronger time comparison logic for MySQL
$stmt = $pdo->prepare("
    SELECT t.id
    FROM timetable t
    WHERE t.day_of_week = ? 
    AND ? >= t.start_time 
    AND ? < t.end_time
    LIMIT 1
");
$stmt->execute([$day, $now, $now]);
$current_slot = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode(['locked' => (bool)$current_slot, 'server_time' => $now, 'day' => $day]);
?>
