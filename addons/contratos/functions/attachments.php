<?php
function contratos_attachment_schema($db) {
    if (!$db->query("CREATE TABLE IF NOT EXISTS sis_contrato_anexo (
        uuid_cliente VARCHAR(64) PRIMARY KEY, filename VARCHAR(180) NOT NULL,
        start_date DATE NOT NULL, end_date DATE NULL,
        origin VARCHAR(30) NOT NULL, uploaded_by VARCHAR(120) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        throw new RuntimeException('Não foi possível preparar os anexos.');
    }
}
function contratos_attachment_dates($start, $end) {
    foreach (array($start, $end) as $date) {
        if ($date === '') continue;
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new RuntimeException('Data inválida.');
    }
    if ($start === '' || ($end !== '' && $end < $start)) throw new RuntimeException('Informe a data do contrato e um vencimento igual ou posterior.');
}
function contratos_attachment_type($file) {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
    if ($mime === 'application/pdf' && file_get_contents($file, false, null, 0, 5) === '%PDF-') return 'pdf';
    if (in_array($mime, array('image/jpeg', 'image/png'), true) && @getimagesize($file)) return $mime === 'image/png' ? 'png' : 'jpg';
    throw new RuntimeException('Envie um PDF, JPG ou PNG válido.');
}
