<?php
/**
 * Append a reading to today's daily CSV log.
 */
function appendBeltLog($timestamp, $actual, $predicted, $ampere, $error, $voltage) {
    @mkdir('logs', 0755, true);
    $file = 'logs/belt_log_' . date('Ymd') . '.csv';
    if (!file_exists($file)) {
        file_put_contents($file, "timestamp,b1_actual,b1_predicted,b1_ampere,b1_error,b1_voltage\n");
    }

    // Skip if this reading was already logged — the RT API updates every
    // ~5 minutes, but the dashboard polls more often
    $lines    = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lastLine = end($lines);
    if ($lastLine !== false && explode(',', $lastLine)[0] === (string)$timestamp) {
        return;
    }

    $row = implode(',', [$timestamp, $actual, $predicted, $ampere, $error, $voltage]);
    file_put_contents($file, $row . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Append a raw B2/B5 reading to today's daily CSV log.
 * No model/calculation — just recorded for behavior observation.
 */
function appendRawB2B5Log($timestamp, $b2_actual, $b2_ampere, $b2_voltage, $b5_actual, $b5_ampere, $b5_voltage) {
    @mkdir('logs', 0755, true);
    $file = 'logs/raw_b2b5_' . date('Ymd') . '.csv';
    if (!file_exists($file)) {
        file_put_contents($file, "timestamp,b2_actual,b2_ampere,b2_voltage,b5_actual,b5_ampere,b5_voltage\n");
    }

    // Skip if this reading was already logged — the RT API updates every
    // ~5 minutes, but the dashboard polls more often
    $lines    = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lastLine = end($lines);
    if ($lastLine !== false && explode(',', $lastLine)[0] === (string)$timestamp) {
        return;
    }

    $row = implode(',', [$timestamp, $b2_actual, $b2_ampere, $b2_voltage, $b5_actual, $b5_ampere, $b5_voltage]);
    file_put_contents($file, $row . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Delete daily logs older than 30 days.
 */
function cleanOldLogs() {
    foreach (glob('logs/belt_log_*.csv') as $file) {
        if (filemtime($file) < strtotime('-30 days')) unlink($file);
    }
    foreach (glob('logs/raw_b2b5_*.csv') as $file) {
        if (filemtime($file) < strtotime('-30 days')) unlink($file);
    }
}
