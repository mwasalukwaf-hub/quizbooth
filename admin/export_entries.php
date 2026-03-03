<?php
session_start();

// Security Check
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}

include '../api/db.php';

// Filter options
$filter_flavor = isset($_GET['filter_flavor']) ? trim($_GET['filter_flavor']) : '';
$filter_from = isset($_GET['filter_from']) ? trim($_GET['filter_from']) : '';
$filter_to = isset($_GET['filter_to']) ? trim($_GET['filter_to']) : '';

// Build dynamic WHERE clause
$entries_where = "WHERE 1=1";
$entries_params = [];

if ($filter_flavor !== '') {
    $entries_where .= " AND qs.result_key = ?";
    $entries_params[] = $filter_flavor;
}
if ($filter_from !== '') {
    $entries_where .= " AND qs.created_at >= ?";
    $entries_params[] = $filter_from . ' 00:00:00';
}
if ($filter_to !== '') {
    $entries_where .= " AND qs.created_at <= ?";
    $entries_params[] = $filter_to . ' 23:59:59';
}

// Fetch all entries matching filters
$entries_stmt = $pdo->prepare("
    SELECT qs.id, qs.player_name, qs.bar_name, qs.result_key, qs.created_at, qs.device
    FROM quiz_sessions qs
    $entries_where
    ORDER BY qs.created_at DESC
");
$entries_stmt->execute($entries_params);
$entries = $entries_stmt->fetchAll(PDO::FETCH_ASSOC);

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=quiz_entries_' . date('Y-m-d_H-i-s') . '.csv');

$output = fopen('php://output', 'w');

// Add BOM for Excel compatibility with UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
fputcsv($output, ['ID', 'Player Name', 'Bar Name', 'Flavour Result', 'Date', 'Time', 'User Agent']);

foreach ($entries as $row) {
    $datetime = new DateTime($row['created_at']);
    fputcsv($output, [
        $row['id'],
        $row['player_name'] ?: 'Unknown',
        $row['bar_name'] ?: 'N/A',
        $row['result_key'] ?: 'Pending',
        $datetime->format('Y-m-d'),
        $datetime->format('H:i:s'),
        $row['device']
    ]);
}

fclose($output);
exit;
