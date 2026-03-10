<?php
// Generate PWA icon as PNG using GD
// Usage: icon.php?size=192 or icon.php?size=512

$size = intval($_GET['size'] ?? 192);
if (!in_array($size, [192, 512])) $size = 192;

// Check if cached file exists
$cacheFile = __DIR__ . '/icon-' . $size . '.png';
if (file_exists($cacheFile)) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=31536000');
    readfile($cacheFile);
    exit;
}

$img = imagecreatetruecolor($size, $size);
imagesavealpha($img, true);

// Red background with rounded corners
$red = imagecolorallocate($img, 200, 16, 46); // #C8102E
$white = imagecolorallocate($img, 255, 255, 255);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

imagefill($img, 0, 0, $transparent);

// Draw rounded rectangle
$radius = intval($size * 0.2);
imagefilledrectangle($img, $radius, 0, $size - $radius, $size, $red);
imagefilledrectangle($img, 0, $radius, $size, $size - $radius, $red);
imagefilledellipse($img, $radius, $radius, $radius * 2, $radius * 2, $red);
imagefilledellipse($img, $size - $radius, $radius, $radius * 2, $radius * 2, $red);
imagefilledellipse($img, $radius, $size - $radius, $radius * 2, $radius * 2, $red);
imagefilledellipse($img, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $red);

// Draw "PWS" text
$fontSize = intval($size * 0.28);
$font = null;

// Try to use a system font
$fontPaths = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
    '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
];
foreach ($fontPaths as $fp) {
    if (file_exists($fp)) { $font = $fp; break; }
}

if ($font) {
    $bbox = imagettfbbox($fontSize, 0, $font, 'PWS');
    $textW = $bbox[2] - $bbox[0];
    $textH = $bbox[1] - $bbox[7];
    $x = ($size - $textW) / 2 - $bbox[0];
    $y = ($size - $textH) / 2 - $bbox[7];
    imagettftext($img, $fontSize, 0, intval($x), intval($y), $white, $font, 'PWS');
} else {
    // Fallback: use built-in font scaled
    $text = 'PWS';
    $charW = imagefontwidth(5);
    $charH = imagefontheight(5);
    $textW = $charW * strlen($text);
    $x = ($size - $textW) / 2;
    $y = ($size - $charH) / 2;
    imagestring($img, 5, intval($x), intval($y), $text, $white);
}

// Save to cache
imagepng($img, $cacheFile, 9);

// Output
header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000');
imagepng($img, null, 9);
imagedestroy($img);
