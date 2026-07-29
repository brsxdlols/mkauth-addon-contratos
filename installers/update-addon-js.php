#!/usr/bin/env php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este utilitario deve ser executado pela linha de comando.\n");
    exit(1);
}

$path = $argv[1] ?? '';
if ($path === '') {
    fwrite(STDERR, "Informe o caminho do addon.js.\n");
    exit(1);
}

$begin = '/* MKAUTH-CONTRATOS-MENU-BEGIN */';
$end = '/* MKAUTH-CONTRATOS-MENU-END */';
$content = is_file($path) ? file_get_contents($path) : '';

if ($content === false) {
    fwrite(STDERR, "Nao foi possivel ler {$path}.\n");
    exit(1);
}

$managedPattern = '/' . preg_quote($begin, '/') . '.*?' . preg_quote($end, '/') . '\R*/s';
$content = preg_replace($managedPattern, '', $content);

if ($content === null) {
    fwrite(STDERR, "Falha ao processar o bloco gerenciado em {$path}.\n");
    exit(1);
}

// Remove atalhos legados do mesmo addon para evitar menu duplicado.
$content = preg_replace(
    '/^.*add_menu\.[^\r\n]*["\'](?:[^"\']*\/)?contratos(?:\/?["\']|[^a-z0-9_]).*\R?/mi',
    '',
    $content
);

if ($content === null) {
    fwrite(STDERR, "Falha ao remover atalhos legados em {$path}.\n");
    exit(1);
}

$block = <<<'JS'
/* MKAUTH-CONTRATOS-MENU-BEGIN */
(function () {
    var contratosAddonUrl = window.location.protocol + "//" + window.location.hostname
        + (window.location.port ? ":" + window.location.port : "")
        + "/admin/addons/contratos";
    add_menu.clientes(JSON.stringify({
        plink: contratosAddonUrl,
        ptext: "Contratos Assinados"
    }));
})();
/* MKAUTH-CONTRATOS-MENU-END */
JS;

$content = rtrim($content) . PHP_EOL . PHP_EOL . $block . PHP_EOL;
$directory = dirname($path);

if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    fwrite(STDERR, "Nao foi possivel criar {$directory}.\n");
    exit(1);
}

$temporary = $path . '.tmp.' . getmypid();
if (file_put_contents($temporary, $content, LOCK_EX) === false) {
    fwrite(STDERR, "Nao foi possivel gravar {$temporary}.\n");
    exit(1);
}

$mode = is_file($path) ? (fileperms($path) & 0777) : 0644;
chmod($temporary, $mode);

if (!rename($temporary, $path)) {
    @unlink($temporary);
    fwrite(STDERR, "Nao foi possivel substituir {$path}.\n");
    exit(1);
}

echo "Menu do addon registrado em {$path}.\n";
