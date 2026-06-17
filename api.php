<?php
date_default_timezone_set('Asia/Tokyo');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require 'includes/predict.php';
require 'includes/log.php';
require 'includes/trend.php';

// Set to false if PLC current calibration needs further adjustment
const CURRENT_DATA_VALID = true;

function computeAndLog(array $entry): array {
    $b1_ampere_raw = (float)($entry['b1']['current'] ?? 0);
    $b1_actual     = (float)($entry['b1_input']['t_per_h'] ?? 0);
    $b1_voltage    = (float)($entry['b1']['voltage'] ?? 0);
    $timestamp     = isset($entry['date'], $entry['time'])
        ? str_replace('/', '-', $entry['date']) . ' ' . $entry['time']
        : date('Y-m-d H:i:s');

    $b1_predicted = CURRENT_DATA_VALID ? predictB1($b1_ampere_raw) : 0.0;
    $b1_error     = round($b1_actual - $b1_predicted, 2);
    $b1_ampere    = round($b1_ampere_raw, 2);

    appendBeltLog($timestamp, $b1_actual, $b1_predicted, $b1_ampere, $b1_error, $b1_voltage);

    // B2/B5 — no model yet, logged for behavior observation only
    $b2_actual  = (float)($entry['b2_return']['t_per_h'] ?? 0);
    $b2_ampere  = round((float)($entry['b2']['current'] ?? 0), 2);
    $b2_voltage = (float)($entry['b2']['voltage'] ?? 0);
    $b5_actual  = (float)($entry['b5_product']['t_per_h'] ?? 0);
    $b5_ampere  = round((float)($entry['b5']['current'] ?? 0), 2);
    $b5_voltage = (float)($entry['b5']['voltage'] ?? 0);
    appendRawB2B5Log($timestamp, $b2_actual, $b2_ampere, $b2_voltage, $b5_actual, $b5_ampere, $b5_voltage);

    return [
        'timestamp'    => $timestamp,
        'b1_actual'    => $b1_actual,
        'b1_predicted' => $b1_predicted,
        'b1_ampere'    => $b1_ampere,
        'b1_error'     => $b1_error,
    ];
}

$action = $_GET['action'] ?? 'latest';

// ── ACTION: trend ─────────────────────────────────────────────
if ($action === 'trend') {
    $result = getTrendFromLogs($_GET['range'] ?? '6h');
    echo json_encode(['range' => $_GET['range'] ?? '6h', 'trend' => $result['points'], 'avg_error' => $result['avg_error']]);
    exit;
}

// ── ACTION: latest ────────────────────────────────────────────
$response = @file_get_contents('https://niwmd.nglobal.jp/niw2589_rt');
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'RT API unreachable']);
    exit;
}

$computed = computeAndLog(json_decode($response, true));

$timestamp    = $computed['timestamp'];
$b1_actual    = $computed['b1_actual'];
$b1_predicted = $computed['b1_predicted'];
$b1_ampere    = $computed['b1_ampere'];
$b1_error     = $computed['b1_error'];

const OFFLINE_THRESHOLD_MIN = 45;
$data_age_min = round((time() - strtotime($timestamp)) / 60);

if ($data_age_min > OFFLINE_THRESHOLD_MIN) {
    $status = 'offline';
} elseif (CURRENT_DATA_VALID) {
    $status = $b1_ampere > 20.16 ? 'active' : 'idle';
} else {
    $status = $b1_actual > 0 ? 'active' : 'idle';
}

cleanOldLogs();

echo json_encode([
    'timestamp'        => $timestamp,
    'b1_actual'        => $b1_actual,
    'b1_predicted'     => $b1_predicted,
    'b1_ampere'        => $b1_ampere,
    'b1_error'         => $b1_error,
    'status'           => $status,
    'data_age_minutes' => $data_age_min,
]);
