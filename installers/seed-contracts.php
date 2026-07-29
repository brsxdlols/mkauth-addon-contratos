#!/usr/bin/env php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este utilitario deve ser executado pela linha de comando.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$addonDir = $argv[1] ?? '';
if ($addonDir === '' || !is_dir($addonDir)) {
    fwrite(STDERR, "Diretorio do addon invalido: {$addonDir}\n");
    exit(1);
}

$contracts = [
    [
        'code' => 'addoncontrato_fidelidade_1ano',
        'name' => 'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO',
        'file' => $addonDir . '/modelo_contrato_fidelidade.html',
        'default' => 'sim',
    ],
    [
        'code' => 'addoncontrato_internet_padrao',
        'name' => 'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET',
        'file' => $addonDir . '/modelo_contrato_padrao.html',
        'default' => 'nao',
    ],
];

$password = getenv('MKAUTH_DB_PASSWORD');
if ($password === false || $password === '') {
    $password = 'vertrigo';
}

$database = new mysqli('127.0.0.1', 'root', $password, 'mkradius');
$database->set_charset('utf8');
$database->begin_transaction();

try {
    $columns = $database->query("SHOW COLUMNS FROM sis_contrato");
    $foundColumns = [];
    while ($column = $columns->fetch_assoc()) {
        $foundColumns[$column['Field']] = true;
    }

    foreach (['id', 'codigo', 'texto', 'data', 'ativo', 'nome', 'padrao'] as $requiredColumn) {
        if (!isset($foundColumns[$requiredColumn])) {
            throw new RuntimeException("A tabela sis_contrato nao possui a coluna obrigatoria {$requiredColumn}.");
        }
    }

    $select = $database->prepare(
        "SELECT id, codigo
           FROM sis_contrato
          WHERE codigo = ? OR nome = ?
          ORDER BY (codigo = ?) DESC, id ASC
          LIMIT 1
          FOR UPDATE"
    );
    $update = $database->prepare(
        "UPDATE sis_contrato
            SET texto = ?, ativo = 'sim', nome = ?, padrao = ?
          WHERE id = ?"
    );
    $insert = $database->prepare(
        "INSERT INTO sis_contrato (codigo, texto, data, ativo, nome, padrao)
         VALUES (?, ?, NOW(), 'sim', ?, ?)"
    );

    foreach ($contracts as $contract) {
        $text = file_get_contents($contract['file']);
        if ($text === false || trim($text) === '') {
            throw new RuntimeException("Modelo vazio ou ilegivel: {$contract['file']}");
        }

        foreach (['%nomecliente%', '%cpfcliente%', '%provedorcidade%'] as $requiredToken) {
            if (strpos($text, $requiredToken) === false) {
                throw new RuntimeException("Token {$requiredToken} ausente em {$contract['file']}");
            }
        }

        $select->bind_param('sss', $contract['code'], $contract['name'], $contract['code']);
        $select->execute();
        $result = $select->get_result();
        $existing = $result->fetch_assoc();

        if ($existing !== null) {
            $id = (int) $existing['id'];
            $update->bind_param('sssi', $text, $contract['name'], $contract['default'], $id);
            $update->execute();
            printf(
                "Contrato atualizado: %s (id=%d, codigo=%s)\n",
                $contract['name'],
                $id,
                $existing['codigo']
            );
            continue;
        }

        $insert->bind_param(
            'ssss',
            $contract['code'],
            $text,
            $contract['name'],
            $contract['default']
        );
        $insert->execute();
        printf(
            "Contrato criado: %s (id=%d, codigo=%s)\n",
            $contract['name'],
            $insert->insert_id,
            $contract['code']
        );
    }

    $database->commit();
} catch (Throwable $error) {
    $database->rollback();
    fwrite(STDERR, "Falha ao criar os contratos iniciais: {$error->getMessage()}\n");
    exit(1);
} finally {
    $database->close();
}
