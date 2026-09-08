<?php

if (!function_exists('contratos_escape')) {
    function contratos_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('contratos_ensure_history_schema')) {
    function contratos_ensure_history_schema($db)
    {
        static $ready = false;
        if ($ready || !$db) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS sis_contrato_historico (
            id INT NOT NULL AUTO_INCREMENT,
            uuid_cliente VARCHAR(64) NOT NULL,
            login VARCHAR(120) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            duration_months INT NOT NULL DEFAULT 12,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            activated_at DATETIME NOT NULL,
            activated_by VARCHAR(120) NOT NULL DEFAULT '',
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_uuid_cliente (uuid_cliente),
            KEY idx_login (login),
            KEY idx_end_date (end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!mysqli_query($db, $sql)) {
            error_log('[contratos] Falha ao preparar historico: ' . mysqli_error($db));
        }
        $ready = true;
    }
}

if (!function_exists('contratos_allowed_durations')) {
    function contratos_allowed_durations()
    {
        return array(1, 6, 12, 24, 36, 48);
    }
}

if (!function_exists('contratos_get_latest_history')) {
    function contratos_get_latest_history($db, $uuid, $login = '')
    {
        contratos_ensure_history_schema($db);
        $parts = array();
        $uuidSql = mysqli_real_escape_string($db, trim((string) $uuid));
        $loginSql = mysqli_real_escape_string($db, trim((string) $login));
        if ($uuidSql !== '') {
            $parts[] = "uuid_cliente = '{$uuidSql}'";
        }
        if ($loginSql !== '') {
            $parts[] = "login = '{$loginSql}'";
        }
        if (!$parts) {
            return null;
        }

        $result = mysqli_query($db, "SELECT * FROM sis_contrato_historico WHERE " . implode(' OR ', $parts) . " ORDER BY end_date DESC, id DESC LIMIT 1");
        if (!$result) {
            return null;
        }
        $row = mysqli_fetch_assoc($result);
        return $row ?: null;
    }
}

if (!function_exists('contratos_save_history')) {
    function contratos_save_history($db, $uuid, $login, $duration, $user, $startDate = null, $notes = '')
    {
        contratos_ensure_history_schema($db);
        $duration = (int) $duration;
        if (!in_array($duration, contratos_allowed_durations(), true)) {
            $duration = 12;
        }
        $startDate = $startDate ?: date('Y-m-d');
        $startObject = DateTime::createFromFormat('Y-m-d', $startDate);
        if (!$startObject || $startObject->format('Y-m-d') !== $startDate) {
            return false;
        }
        $endObject = clone $startObject;
        $endObject->modify('+' . $duration . ' months');

        $uuidSql = mysqli_real_escape_string($db, trim((string) $uuid));
        $loginSql = mysqli_real_escape_string($db, trim((string) $login));
        $userSql = mysqli_real_escape_string($db, trim((string) $user));
        $notesSql = mysqli_real_escape_string($db, trim((string) $notes));
        $startSql = $startObject->format('Y-m-d');
        $endSql = $endObject->format('Y-m-d');

        return (bool) mysqli_query($db, "INSERT INTO sis_contrato_historico
            (uuid_cliente, login, status, duration_months, start_date, end_date, activated_at, activated_by, notes)
            VALUES ('{$uuidSql}', '{$loginSql}', 'active', {$duration}, '{$startSql}', '{$endSql}', NOW(), '{$userSql}', '{$notesSql}')");
    }
}

if (!function_exists('contratos_temporal_status')) {
    function contratos_temporal_status($endDate, $warningDays = 60)
    {
        $today = new DateTime('today');
        $end = $endDate instanceof DateTime ? clone $endDate : new DateTime((string) $endDate);
        $days = (int) $today->diff($end)->format('%r%a');
        if ($days < 0) {
            return array('key' => 'expired', 'label' => 'VENCIDO', 'color' => '#e53935', 'days' => $days);
        }
        if ($days <= $warningDays) {
            return array('key' => 'warning', 'label' => 'A VENCER', 'color' => '#f4b400', 'days' => $days);
        }
        return array('key' => 'active', 'label' => 'ATIVO', 'color' => '#35b779', 'days' => $days);
    }
}
