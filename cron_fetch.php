<?php
// Called by Windows Task Scheduler every 5 minutes to log the latest belt reading.
// Runs via PHP CLI — does not require the web server to be running.
// Task Scheduler setup: use run_scheduler.bat as the action.
date_default_timezone_set('Asia/Tokyo');

const CURRENT_DATA_VALID = true;

require __DIR__ . '/includes/predict.php';
require __DIR__ . '/includes/log.php';

chdir(__DIR__);

$response = @file_get_contents('https://niwmd.nglobal.jp/niw2589_rt');
if ($response === false) {
    fwrite(STDERR, date('Y-m-d H:i:s') . " ERROR: RT API unreachable\n");
    exit(1);
}

$data = json_decode($response, true);
if (!$data) {
    fwrite(STDERR, date('Y-m-d H:i:s') . " ERROR: invalid JSON from RT API\n");
    exit(1);
}

$timestamp     = str_replace('/', '-', $data['date']) . ' ' . $data['time'];
$b1_ampere_raw = (float)($data['b1']['current'] ?? 0);
$b1_actual     = (float)($data['b1_input']['t_per_h'] ?? 0);
$b1_voltage    = (float)($data['b1']['voltage'] ?? 0);
$b1_predicted  = CURRENT_DATA_VALID ? predictB1($b1_ampere_raw) : 0.0;
$b1_error      = round($b1_actual - $b1_predicted, 2);
$b1_ampere     = round($b1_ampere_raw, 2);

appendBeltLog($timestamp, $b1_actual, $b1_predicted, $b1_ampere, $b1_error, $b1_voltage);

$b2_actual  = (float)($data['b2_return']['t_per_h'] ?? 0);
$b2_ampere  = round((float)($data['b2']['current'] ?? 0), 2);
$b2_voltage = (float)($data['b2']['voltage'] ?? 0);
$b5_actual  = (float)($data['b5_product']['t_per_h'] ?? 0);
$b5_ampere  = round((float)($data['b5']['current'] ?? 0), 2);
$b5_voltage = (float)($data['b5']['voltage'] ?? 0);

appendRawB2B5Log($timestamp, $b2_actual, $b2_ampere, $b2_voltage, $b5_actual, $b5_ampere, $b5_voltage);

cleanOldLogs();
