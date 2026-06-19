<?php
/**
 * Read trend data points from daily CSV logs for the given range, returning
 * a continuous timeline at 5-minute intervals (matching the PLC upload
 * cadence) so the chart can render gaps (null values) for periods with no
 * logged data — before data starts, after it stops, or any outage in between.
 * range: '1h' | '6h' | '1d' | 'today'
 *   1h/6h/1d → last N hours rolling from now
 *   today    → from first logged entry today to now
 *
 * Returns ['points' => [...], 'avg_error' => float|null], where avg_error is
 * the mean absolute error (|actual - predicted|) over points with data.
 */
function getTrendFromLogs(string $range): array {
    $now_ts = time();
    $step   = 5 * 60;

    if ($range === 'today') {
        $dates     = [date('Ymd')];
        $file      = "logs/belt_log_{$dates[0]}.csv";
        $today_str = date('Y-m-d');
        $from_ts   = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
        if (file_exists($file)) {
            $fh = fopen($file, 'r');
            fgetcsv($fh, null, ',', '"', '\\'); // skip header
            while (($row = fgetcsv($fh, null, ',', '"', '\\')) !== false) {
                if (isset($row[0]) && strncmp($row[0], $today_str, 10) === 0) {
                    if (($t = strtotime($row[0])) !== false) $from_ts = $t;
                    break;
                }
            }
            fclose($fh);
        }
    } else {
        $hours   = match ($range) { '1h' => 1, '1d' => 24, default => 6 };
        $from_ts = $now_ts - ($hours * 3600);
        $dates   = [date('Ymd'), date('Ymd', $now_ts - 86400)];
    }

    // Load logged readings within range, keyed by unix timestamp
    $readings = [];
    foreach ($dates as $date) {
        $file = "logs/belt_log_{$date}.csv";
        if (!file_exists($file)) continue;
        $fh = fopen($file, 'r');
        fgetcsv($fh, null, ',', '"', '\\'); // skip header
        while (($row = fgetcsv($fh, null, ',', '"', '\\')) !== false) {
            if (count($row) < 5) continue;
            [$ts, $actual, $predicted] = $row;
            $unix = strtotime($ts);
            if ($unix >= $from_ts) {
                $readings[$unix] = ['actual' => (float)$actual, 'predicted' => (float)$predicted];
            }
        }
        fclose($fh);
    }

    // Build the continuous timeline, matching each grid point to the nearest
    // reading within half a step; unmatched points become gaps (null)
    $window = $step / 2;
    $trend  = [];
    for ($ts = $from_ts; $ts <= $now_ts; $ts += $step) {
        $match = null;
        foreach ($readings as $rt => $r) {
            if (abs($rt - $ts) <= $window) { $match = $rt; break; }
        }
        if ($match !== null) {
            $trend[] = [
                'timestamp' => date('Y-m-d H:i:s', $match),
                'label'     => date('H:i', $ts),
                'actual'    => $readings[$match]['actual'],
                'predicted' => $readings[$match]['predicted'],
            ];
            unset($readings[$match]);
        } else {
            $trend[] = [
                'timestamp' => date('Y-m-d H:i:s', $ts),
                'label'     => date('H:i', $ts),
                'actual'    => null,
                'predicted' => null,
            ];
        }
    }

    // Mean absolute error over points with data
    $errors = [];
    foreach ($trend as $p) {
        if ($p['actual'] !== null) $errors[] = abs($p['actual'] - $p['predicted']);
    }
    $avg_error = $errors ? round(array_sum($errors) / count($errors), 2) : null;

    return ['points' => $trend, 'avg_error' => $avg_error];
}
