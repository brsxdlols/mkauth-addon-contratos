<?php
declare(strict_types=1);

/**
 * Converte uma imagem de assinatura para PNG com fundo branco e traços pretos.
 *
 * A cor predominante das bordas é usada como referência do fundo. Isso permite
 * tratar fotos de papel cinza/amarelado, fundos coloridos e imagens transparentes.
 *
 * @return array{width:int,height:int,background:array{red:int,green:int,blue:int}}
 */
function normalizarAssinaturaProvedor(string $sourcePath, string $destinationPath): array
{
    if (!extension_loaded('gd')) {
        throw new RuntimeException('A extensão GD do PHP não está disponível.');
    }

    $contents = @file_get_contents($sourcePath);
    if ($contents === false || $contents === '') {
        throw new RuntimeException('Não foi possível ler a imagem enviada.');
    }

    $decoded = @imagecreatefromstring($contents);
    if ($decoded === false) {
        throw new RuntimeException('Não foi possível decodificar a imagem enviada.');
    }

    $originalWidth = imagesx($decoded);
    $originalHeight = imagesy($decoded);
    if ($originalWidth <= 0 || $originalHeight <= 0) {
        imagedestroy($decoded);
        throw new RuntimeException('A imagem enviada possui dimensões inválidas.');
    }

    // Limita o custo do processamento sem perder definição para uso em contratos.
    $maximumDimension = 2400;
    $scale = min(1, $maximumDimension / max($originalWidth, $originalHeight));
    $width = max(1, (int) round($originalWidth * $scale));
    $height = max(1, (int) round($originalHeight * $scale));

    // Um canvas branco também elimina transparência e garante o fundo final.
    $flattened = imagecreatetruecolor($width, $height);
    if ($flattened === false) {
        imagedestroy($decoded);
        throw new RuntimeException('Não foi possível preparar a imagem da assinatura.');
    }

    $white = imagecolorallocate($flattened, 255, 255, 255);
    imagefill($flattened, 0, 0, $white);
    imagealphablending($flattened, true);
    imagecopyresampled(
        $flattened,
        $decoded,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $originalWidth,
        $originalHeight
    );
    imagedestroy($decoded);

    $borderColors = coletarCoresDaBordaAssinatura($flattened, $width, $height);
    $backgroundRed = medianaAssinatura(array_column($borderColors, 0));
    $backgroundGreen = medianaAssinatura(array_column($borderColors, 1));
    $backgroundBlue = medianaAssinatura(array_column($borderColors, 2));

    // Mede a variação natural do fundo para não transformar sombra/papel em tinta.
    $borderDistances = [];
    foreach ($borderColors as $color) {
        $borderDistances[] = distanciaCorAssinatura(
            $color[0],
            $color[1],
            $color[2],
            $backgroundRed,
            $backgroundGreen,
            $backgroundBlue
        );
    }
    sort($borderDistances, SORT_NUMERIC);
    $noiseIndex = max(0, (int) floor((count($borderDistances) - 1) * 0.90));
    $backgroundNoise = $borderDistances[$noiseIndex] ?? 0.0;
    $whitePoint = max(0.035, min(0.18, $backgroundNoise + 0.025));
    $blackPoint = min(0.65, max(0.24, $whitePoint + 0.24));
    $range = max(0.01, $blackPoint - $whitePoint);

    $normalized = imagecreatetruecolor($width, $height);
    if ($normalized === false) {
        imagedestroy($flattened);
        throw new RuntimeException('Não foi possível criar a imagem normalizada.');
    }

    // Uma paleta de cinza evita milhares de alocações durante o processamento.
    $grayPalette = [];
    for ($gray = 0; $gray <= 255; $gray++) {
        $grayPalette[$gray] = imagecolorallocate($normalized, $gray, $gray, $gray);
    }

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($flattened, $x, $y);
            $red = ($rgb >> 16) & 0xFF;
            $green = ($rgb >> 8) & 0xFF;
            $blue = $rgb & 0xFF;

            $distance = distanciaCorAssinatura(
                $red,
                $green,
                $blue,
                $backgroundRed,
                $backgroundGreen,
                $backgroundBlue
            );

            $ink = max(0.0, min(1.0, ($distance - $whitePoint) / $range));
            // Reforça traços claros mantendo antialias nas bordas.
            $ink = pow($ink, 0.72);
            $outputGray = 255 - (int) round($ink * 255);
            imagesetpixel($normalized, $x, $y, $grayPalette[$outputGray]);
        }
    }
    imagedestroy($flattened);

    imageinterlace($normalized, true);
    $saved = @imagepng($normalized, $destinationPath, 6);
    imagedestroy($normalized);

    if (!$saved || !is_file($destinationPath) || filesize($destinationPath) === 0) {
        @unlink($destinationPath);
        throw new RuntimeException('Não foi possível gerar a assinatura padronizada.');
    }

    return [
        'width' => $width,
        'height' => $height,
        'background' => [
            'red' => $backgroundRed,
            'green' => $backgroundGreen,
            'blue' => $backgroundBlue,
        ],
    ];
}

/**
 * @return array<int,array{0:int,1:int,2:int}>
 */
function coletarCoresDaBordaAssinatura($image, int $width, int $height): array
{
    $colors = [];
    $horizontalStep = max(1, (int) floor($width / 240));
    $verticalStep = max(1, (int) floor($height / 180));
    $insets = array_values(array_unique([
        0,
        min(2, max(0, $width - 1), max(0, $height - 1)),
        min(5, max(0, $width - 1), max(0, $height - 1)),
    ]));

    foreach ($insets as $inset) {
        $top = min($inset, $height - 1);
        $bottom = max(0, $height - 1 - $inset);
        $left = min($inset, $width - 1);
        $right = max(0, $width - 1 - $inset);

        for ($x = 0; $x < $width; $x += $horizontalStep) {
            $colors[] = componentesCorAssinatura(imagecolorat($image, $x, $top));
            $colors[] = componentesCorAssinatura(imagecolorat($image, $x, $bottom));
        }

        for ($y = 0; $y < $height; $y += $verticalStep) {
            $colors[] = componentesCorAssinatura(imagecolorat($image, $left, $y));
            $colors[] = componentesCorAssinatura(imagecolorat($image, $right, $y));
        }
    }

    return $colors;
}

/**
 * @return array{0:int,1:int,2:int}
 */
function componentesCorAssinatura(int $rgb): array
{
    return [
        ($rgb >> 16) & 0xFF,
        ($rgb >> 8) & 0xFF,
        $rgb & 0xFF,
    ];
}

/**
 * Distância ponderada entre cores, normalizada para o intervalo de 0 a 1.
 */
function distanciaCorAssinatura(
    int $red,
    int $green,
    int $blue,
    int $backgroundRed,
    int $backgroundGreen,
    int $backgroundBlue
): float {
    $redDifference = $red - $backgroundRed;
    $greenDifference = $green - $backgroundGreen;
    $blueDifference = $blue - $backgroundBlue;

    return sqrt(
        (0.30 * $redDifference * $redDifference)
        + (0.59 * $greenDifference * $greenDifference)
        + (0.11 * $blueDifference * $blueDifference)
    ) / 255;
}

/**
 * @param int[] $values
 */
function medianaAssinatura(array $values): int
{
    if ($values === []) {
        return 255;
    }

    sort($values, SORT_NUMERIC);
    return (int) $values[(int) floor((count($values) - 1) / 2)];
}
