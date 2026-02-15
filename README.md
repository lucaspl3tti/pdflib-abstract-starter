# Pdflib Rendering Foundation

A powerful, abstract base library for generating PDFs using [PDFLib](https://www.pdflib.com/) in PHP.

This package abstracts the complex logic of PDFLib and provides simple helper methods for layouts, text flows, tables, pagination, and HTML rendering. It allows developers to focus on the content of the PDF rather than dealing with low-level coordinate calculations.

## 📂 Project Structure

```text
src/
├── Generator/
│   └── ExampleRenderingPdfGenerator.php  # Example implementation
├── Resources/                            # Assets (Fonts, Images, Templates)
└── Services/
    └── AbstractPdfGenerator.php          # The Base Class (Core Logic)
```

## ✨ Features

- **Simplified PDF Workflow:** Wraps complex PDFLib logic for standard elements (text, tables, images) into easy-to-use helper methods.
- - **HTML Support:** Easy rendering of HTML snippets (e.g., from WYSIWYG editors) including list sanitization.
- **Automatic Page Handling:** When content exceeds the printable area, the automatic generation of new pages can be toggled on or off via just one simple parameter in all functions.
- **Template System:** Support for PDF templates (background stationeries).
- **Pagination:** Integrated page numbering.

## 🚀 Usage

To create your own PDF generator, simply extend the `AbstractPdfGenerator` class and implement the `generatePdfContents()` method.

### 1. Create a Class

Create a new class (e.g., in the `src/Generator` folder) that inherits from `AbstractPdfGenerator`.

```php
<?php

namespace YourNamespace\Generator;

use LucaSpl3tti\PdflibRenderingFoundation\Services\AbstractPdfGenerator;

class MyCustomPdfGenerator extends AbstractPdfGenerator
{
    /**
     * Initialize PDF settings.
     * Paths and templates must be defined here.
     */
    protected function initializePdf($arrayInput): void
    {
        // 1. Initialize parent (sets default values)
        parent::initializePdf($arrayInput);

        // 2. Define path to your assets (images, fonts)
        // Ensure this path exists and contains your templates/images
        $this->searchPath = __DIR__ . '/../Resources/assets/';

        // 3. Define main template (filename located inside searchPath)
        $this->templateMain = 'Stationery.pdf';

        // 4. Define your font paths in an array like this
        $this->fontPaths = [
            'Sora' => [
                'regular' => 'Sora-Regular',
                'bold' => 'Sora-Bold',
                'italic' => 'Sora-Italic',
            ],
        ];

        // 5. Define your global font variables
        $this->fontRegular = 0;
        $this->fontBold = 0;
        $this->fontItalic = 0;

        // Optional: Customize colors or margins
        $this->colorPrimary = '{#005599}';
        $this->marginLeft = 35;
        $this->marginRight = 35;
    }

    /**
     * Load your fonts
     */
    public function setPdfMetaDataAndOptions(): void
    {
        parent::setPdfMetaDataAndOptions();

        $fontStyles = $this->loadFontsInitially($this->fontPaths);

        $this->fontRegular = $fontStyles['examplePdfRegular'];
        $this->fontBold = $fontStyles['examplePdfBold'];
        $this->fontItalic = $fontStyles['examplePdfItalic'];
        $this->fontPagination = $this->fontItalic;
    }

    /**
     * This is where the magic happens.
     * Add content to your PDF.
     */
    protected function generatePdfContents(): void
    {
        $this->placeDescription();
        $this->placeAttributesTable();
        $this->placeMainImage();
    }
}
```

### 2. Call the Generator

The generation is triggered via the public method `getPdfBuffer`.

```php
use YourNamespace\Generator\MyCustomPdfGenerator;

// License key (or "0" for demo mode)
$license = $_ENV['PDFLIB_LICENSE'] ?? '0';

// Data to be passed to the PDF
$data = [
    'documentInfo' => [
        'subject' => 'Example PDF',
        'title' => 'Example PDF Rendering',
        'creator' => 'Twocream',
        'author' => 'Twocream',
    ],
    'templates' => [
        'templateMain' => '/templates/template_example.pdf',
    ],
    'headline' => 'PDF-Rendering Example',
    // ...
];

try {
    $generator = new MyCustomPdfGenerator($license);
    $pdfBuffer = $generator->getPdfBuffer($data);
} catch (\Throwable $throwable) {
    throw $throwable;
    exit();
}

header('Content-type: application/pdf');
header('Content-Disposition: inline; filename=document.pdf');

print $pdfBuffer;
```

## 🛠 Available Methods (Selection)

When extending the AbstractPdfGenerator Class, you have access to various protected helpers methods:

| Method | Parameters | Description |
| :--- | :--- | :--- |
| `$this->createPdfHeadline()` | - `string $headline`<br>- `float $startX`, `$startY, $endX`, `$endY`<br>- `int $font`<br>- `float $fontsize`<br>- `string $textColor`<br>- `string $optlistExtras` | Adds a headline within the defined coordinates using the specified font settings. |
| `$this->placeTextflowOnPage()` | - `int $textflow`<br>- `array $coordinates(x1, y1, x2, y2)`<br>- `bool $shouldContinueOnNextPage`<br>- `string $textflowName`<br>- `string $optlist` <br>- `float $paddingBottom` <br>- `bool $calculateNewHeight` <br>- `string $text` | Places an existing textflow into a box defined by `$coordinates`. Handles page breaks automatically with `$shouldContinueOnNextPage` enabled. |
| `$this->placePartingLine()` | *(none)* | Draws a horizontal separator line at the current position. |
| `$this->loadImage()` | - `string $imagePath` | Loads an image from the `searchPath` and returns the PDFLib image handle (int). Use `fitImage()` to place it. |
| `$this->generateNewPage()` | *(none)* | Creates a new page and applies the standard background template. |
| `$this->placeTableOnPage()` | - `int $table`<br>- `array $coordinates`<br>- `string $optlistTable`<br>- `string $tableName`<br>- `bool $shouldContinueOnNextPage`<br>- `float $paddingBottom` (opt)<br>- `bool $calculateNewPositionY` (opt) | Places a PDFLib table instance within the defined coordinates. |
| `$this->getNewPositionY()` | - `float $currentPositionY`<br>- `float $paddingBottom` | Calculates the next Y-position. Returns `void` but updates internal state `$this->positionY`. |

## 📦 Requirements

- PHP 8.1+
- [PDFLib Extension](https://www.pdflib.com/download/pdflib-product-family/)
