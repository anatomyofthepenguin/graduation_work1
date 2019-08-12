<?php

require '../vendor/autoload.php';

use Intervention\Image\ImageManager;

$manager = new ImageManager();

$image = $manager->make('../img/content/bgcopy.png');
$image->rotate(45);
$watermark = $manager->make('../img/content/watermark.png');
$image->insert($watermark, 'center');

$image->resize(200, null, function ($constraint) {
    $constraint->aspectRatio();
});

$image->save('../img/content/changed.png');
