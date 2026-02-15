<?php

$longParagraph = json_decode(file_get_contents('https://lorem.place/api/generate?p=12&w=150&r=true&start=true&html=true'));

return [
    'documentInfo' => [
        'subject' => 'Example PDF',
        'title' => 'Example PDF Rendering',
        'creator' => 'Jan-Luca Splettstößer',
        'author' => 'Jan-Luca Splettstößer',
    ],
    'templates' => [
        'templateMain' => '/templates/template_example.pdf',
    ],
    'headline' => 'PDF-Rendering Example',
    'paragraph' => '<p>Lorem ipsum <strong>dolor sit amet</strong>, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',
    'longParagraph' => implode("<br/><br/>", $longParagraph->data->paragraphs),
    'graphics' => [
        'images/database-file.svg',
        'images/it.svg',
    ],
    'image' => [
        'heading' => 'Image Example',
        'source' => 'images/482-1000x1000.jpg',
    ],
    'table' => [
        'tableHeading' => 'Table Example',
        'tableContent' => [
            'Lorem ipsum' => 'dolor sit amet',
            'consetetur' => 'sadipscing elitr',
            'sed diam' => 'nonumy eirmod tempor invidunt',
        ],
    ],
    'translations' => [
        'page' => 'Seite',
    ],
];
