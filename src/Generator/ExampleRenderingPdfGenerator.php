<?php

namespace LucaSpl3tti\PdflibRenderingFoundation\Generator;

use LucaSpl3tti\PdflibRenderingFoundation\Services\AbstractPdfGenerator;

class ExampleRenderingPdfGenerator extends AbstractPdfGenerator
{
    // Global variables for positioning
    private float $graphicsPositionY;
    private float $descriptionPositionY;

    // variables for fonts and text handling
    private array $fontPaths;
    private int $fontRegular;
    private int $fontBold;
    private int $fontItalic;

    public function initializePdf($arrayInput): void
    {
        parent::initializePdf($arrayInput);

        /* ---------------- Declaration of variables ---------------- */
        // Declare config variables
        $this->searchPath = __DIR__ . '/../Resources/pdflib/assets/';
        $this->templateMain = $this->data['templates']['templateMain'];

        $this->fontPaths = [
            'examplePdf' => [
                'regular' => 'Regular',
                'bold' => 'Bold',
                'italic' => 'Italic',
            ],
        ];

        // Declare font variables
        $this->fontRegular = 0;
        $this->fontBold = 0;
        $this->fontItalic = 0;

        // Declare variables for graphics and description y position
        $this->graphicsPositionY = 0;
        $this->descriptionPositionY = 0;

        // override default variables
        $this->marginLeft = 35;
        $this->marginRight = 35;
        $this->elementStartLeft = $this->marginLeft;
        $this->elementEndRight = $this->pageWidth - $this->marginRight;
        $this->endPositionY = 60;

        $this->paginationLabel = $this->translations['page'];
        $this->paginationStartY = 40;
        $this->paginationEndY = $this->paginationStartY - 14;
    }

    public function setPdfMetaDataAndOptions(): void
    {
        parent::setPdfMetaDataAndOptions();

        // load all fonts
        $fontStyles = $this->loadFontsInitially($this->fontPaths);

        $this->fontRegular = $fontStyles['examplePdfRegular'];
        $this->fontBold = $fontStyles['examplePdfBold'];
        $this->fontItalic = $fontStyles['examplePdfItalic'];
        $this->fontPagination = $this->fontItalic;
    }

    protected function beginPdfDocument(): void
    {
        // Filename: If empty, the PDF is created in the working memory and must be fetched with get_buffer.
        $outfile = '';

        if ($this->pdf->begin_document($outfile, 'pagelayout=twopageleft') == 0) {
            throw new \Exception('Error: ' . $this->pdf->get_errmsg());
        }
    }

    public function generatePdfContents(): void
    {
        $this->loadExampleGraphics();
        $this->createTextParagraph();
        $this->createTable();
        $this->loadExampleImage(true);
        $this->createTextParagraph(true);
        $this->loadExampleImage(true);
    }

    /* ---------------- PDFlib Functions ---------------- */
    /* ---------- Function to set the PDF options, the meta data and the template */

    public function createPageBaseTemplate(): void
    {
        parent::createPageBaseTemplate();

        // Place Heading and Subtitle on the page
        $this->createPdfHeadline(
            $this->data['headline'],
            $this->elementStartLeft,
            815,
            $this->elementEndRight,
            815 - 20,
            $this->fontBold,
            16,
            $this->colorWhite
        );
    }

    /* ---------------- Content Functions ---------------- */
    private function loadExampleGraphics(): void
    {
        $svgImages = $this->data['graphics'];

        if (empty($svgImages)) {
            return;
        }

        // start coordinates of the images
        $imagePositionY = $this->positionY - 50;
        $imagePositionX = $this->elementEndRight - 45;

        // image box delcaration
        $boxWidth = 45;
        $boxHeight = 45;

        // loop until all images are placed
        foreach ($svgImages as $svg) {
            // load svg graphic
            $graphics = $this->loadGraphic($svg);

            // place the image
            $this->fitGraphic(
                $graphics,
                $imagePositionX,
                $imagePositionY,
                $boxWidth,
                $boxHeight
            );

            $imagePositionY = $imagePositionY - 55;
        }

        $this->graphicsPositionY = $imagePositionY + 55;
    }

    private function createTextParagraph(bool $isLongParagraph = false): void
    {
        // Declare variables
        $text = $isLongParagraph ? $this->data['longParagraph'] : $this->data['paragraph'];

        if (empty($text)) {
            return;
        }

        // Set positions of textflow
        $coordinates = $isLongParagraph
            ? $coordinates = $this->createCoordinatesArray(
                $this->elementStartLeft,
                $this->positionY,
                $this->elementEndRight,
                $this->endPositionY
            )
            : $coordinates = $this->createCoordinatesArray(
                $this->elementStartLeft,
                $this->positionY,
                $this->elementEndRight - 100,
                $this->positionY - 180
            );

        // Add textflow
        $optlist = 'font='
            . $this->fontRegular
            . ' fontsize='
            . $this->defaultFontsize
            . ' fillcolor='
            . $this->colorBlack
            . ' wordspacing=0.5 leading=13 charref=true';

        $textflow = $this->createTextflow($text, $this->fontRegular, $this->fontBold, $this->defaultFontsize, $optlist);

        $textflowName = $isLongParagraph ? 'Long Paragraph' : 'Paragraph';
        $this->placeTextflowOnPage(
            $textflow,
            $coordinates,
            $isLongParagraph,
            $textflowName,
            '',
            $isLongParagraph ? 20 : 0,
            $isLongParagraph
        );

        // Get height of the fitbox
        if (!$isLongParagraph) {
            $infoHeight = $this->pdf->info_textflow($textflow, 'y2');
            $this->descriptionPositionY = $infoHeight;
        }
    }

    private function createHeading(
        string $string,
        string $blockName,
        float $paddingBottom = 20,
        bool $renderPartingLine = true
    ): void {
        if (empty($string)) {
            return;
        }

        $coordinates = $this->createCoordinatesArray(
            $this->elementStartLeft,
            $this->positionY,
            $this->elementEndRight,
            $this->positionY - 20
        );

        // Add textflow
        $optlistHeading = 'font=' . $this->fontBold
            . ' fontsize=12 fillcolor=black wordspacing=0.5 leading=13 charref=true';

        // create textflow
        $textflow = $this->addTextflow(0, $string, $optlistHeading);
        $this->placeTextflowOnPage(
            $textflow,
            $coordinates,
            false,
            'Heading for ' . $blockName,
            '',
            0,
            false
        );

        if ($renderPartingLine) {
            // Place Parting Line
            $this->getNewPositionY($this->positionY, 15);
            $this->placePartingLine();
        }

        $this->getNewPositionY($this->positionY, $paddingBottom);
    }

    private function createTable(): void
    {
        // get new height value for following elements
        if ($this->descriptionPositionY < $this->graphicsPositionY) {
            $this->positionY = $this->descriptionPositionY - 20;
        } else {
            $this->positionY = $this->graphicsPositionY - 20;
        }

        $tableHeading = $this->data['table']['tableHeading'];
        $tableContent = $this->data['table']['tableContent'];
        $optlistTableTf = 'font=' . $this->fontRegular . ' fontsize=10 fillcolor=black wordspacing=0' . ' leading=13';

        if (empty($tableContent)) {
            return;
        }

        $this->createHeading($tableHeading, 'Table', 20, false);

        // Declara Variables
        $table = 0;
        $row = 0;

        // Coordinates for productProfile Table
        $coordinates = $this->createCoordinatesArray(
            $this->elementStartLeft,
            $this->positionY,
            $this->elementEndRight,
            310
        );

        /* --- create table */
        // add cell for every item in $tableContent
        foreach ($tableContent as $key => $value) {
            $row++;
            $column = 0;

            // Add $key cell
            ++$column;

            $optlistTableCellLeft = 'colwidth=50% margintop=4 marginbottom=4 marginleft=4 marginright=4'
                . ' fittextflow={verticalalign=top}';

            $table = $this->createTableCell(
                $table,
                $row,
                $column,
                $optlistTableCellLeft,
                $key,
                $this->defaultFontsize,
                $this->fontRegular,
                $this->fontBold,
                $optlistTableTf
            );

            // Add $value cell
            ++$column;

            $optlistTableCellRight = 'colwidth=50% margintop=4 marginbottom=4 marginleft=4 marginright=4'
                . ' fittextflow={verticalalign=top}';

            $table = $this->createTableCell(
                $table,
                $row,
                $column,
                $optlistTableCellRight,
                $value,
                $this->defaultFontsize,
                $this->fontRegular,
                $this->fontBold,
                $optlistTableTf
            );
        }

        // Output technical Values Table on page
        $optlistTable = 'rowheightdefault=15 '
            . 'stroke={{line=frame linewidth=1} {line=vertother linewidth=1} {line=horother linewidth=1}} ';

        $this->placeTableOnPage(
            $table,
            $coordinates,
            $optlistTable,
            'Example Table',
            false,
            30
        );
    }


    private function loadExampleImage($placeHeadline = false): void
    {
        $imagePath = $this->data['image']['source'];
        $imageHeading = $this->data['image']['heading'];

        if (empty($imagePath)) {
            return;
        }

        if ($placeHeadline && !empty($imageHeading)) {
            $this->createHeading($imageHeading, 'Image', 5);
        }

        $table = 0;
        $row = 1;
        $column = 1;
        $imageWidth = 140;
        $imageHeight = $imageWidth; // aspect-ratio: 1x1

        $coordinatesTable = $this->createCoordinatesArray(
            $this->elementStartLeft,
            $this->positionY,
            $this->elementEndRight,
            $this->positionY - $imageHeight,
        );

        $optlistTable = 'rowheightdefault=' . $imageHeight;

        $table = $this->createTableCellWithImage(
            $table,
            $row,
            $column,
            '',
            $imagePath,
            'position={top left}',
        );

        $this->placeTableOnPage(
            $table,
            $coordinatesTable,
            $optlistTable,
            'Product Dimensonal Drawing',
            false,
            20,
            true
        );
    }
}
