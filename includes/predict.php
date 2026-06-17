<?php
/**
 * B1 throughput prediction
 * Formula: ton/h = 36.1997 × (I_A − 20.16) + 48.5640
 * I_A = motor current in Amperes (directly from RT API b1.current)
 * R² = 0.8378 | RMSE = 12.67 t/h
 */
function predictB1($ampere_raw) {
    $I_idle     = 20.16;
    $ampere_net = $ampere_raw - $I_idle;
    if ($ampere_net <= 0) return 0.0;
    return round(36.1997 * $ampere_net + 48.5640, 2);
}
