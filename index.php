<?php

require_once __DIR__ . '/vendor/autoload.php';

use LucaSpl3tti\PdflibRenderingFoundation\Generator\ExampleRenderingPdfGenerator;

try {
    $data = include 'testData.php';
    $exampleRenderingPdfGenerator = new ExampleRenderingPdfGenerator('');
    $pdfBuffer = $exampleRenderingPdfGenerator->getPdfBuffer($data);
} catch (\Throwable $throwable) {
    throw $throwable;
    exit();
}

header('Content-type: application/pdf');
header('Content-Disposition: inline');

print $pdfBuffer;
