<?php

namespace LucaSpl3tti\PdflibRenderingFoundation\Services;

abstract class AbstractPdfGenerator
{
    protected \PDFlib $pdf;
    protected string $pdflibLicense;
    protected string $searchPath;
    protected array $data;
    protected array $translations;

    // Global variables for dimensions
    protected int $pageWidth;
    protected int $pageHeight;
    protected int $rotatePage;

    // Global variables for positioning
    protected float $marginLeft;
    protected float $marginRight;
    protected float $elementStartLeft;
    protected float $elementStartHalf;
    protected float $elementEndRight;
    protected float $pageMidY;
    protected float $pageMidX;
    protected float $startPositionY;
    protected float $positionY;
    protected float $endPositionY;
    protected float $minDistanceEndBottom;

    // variables for colors
    protected string $colorBlack;
    protected string $colorWhite;
    protected string $colorGray;
    protected string $colorTextMark;
    protected string $colorPrimary;
    protected string $colorSecondary;

    // variables for fonts and text handling
    protected float $defaultFontsize;
    protected float $fontsizeSmall;
    protected float $fontsizeSmaller;
    protected int $fontUnicode;
    protected string $htmlListBullet;
    protected int $htmlListIndent;

    // other variables
    protected int $currentPageNo;
    protected string $templateMain;
    protected bool $renderTemplateAfterContent;

    protected bool $hasPagination;
    protected string $paginationLabel;
    protected float $paginationStartY;
    protected float $paginationStartX;
    protected float $paginationEndY;
    protected float $paginationEndX;
    protected int $fontPagination;
    protected float|string $paginationFontsize;
    protected string $paginationTextColor;
    protected string $paginationAlignment;

    abstract protected function generatePdfContents(): void;

    public function __construct(string $pdflibLicense)
    {
        $this->pdflibLicense = $pdflibLicense;
    }

    public function getPdfBuffer($arrayInput): string
    {
        $this->initializePdf($arrayInput);

        if (empty($this->searchPath)) {
            throw new \Exception('Error: No custom asset search path defined');
        }

        if (empty($this->templateMain)) {
            throw new \Exception('Error: No template defined');
        }

        $this->setPdfMetaDataAndOptions();

        return $this->createPdfBuffer();
    }

    protected function initializePdf($arrayInput): void
    {
        // Generate the \PDFlib Object
        $this->pdf = new \PDFlib();

        /* ---------------- Declaration of variables ---------------- */
        // Define searchpath for fallback assets in bundle (images, fonts, usw.)
        $fallbackSearchPath = __DIR__ . '/../Resources/pdflib/assets/';
        $this->pdf->set_option('searchpath={' . $fallbackSearchPath . '}');

        $this->searchPath = '';

        // Declare global data array
        $this->data = $arrayInput;
        $this->translations = $this->data['translations'] ?? [];
        $this->templateMain = '';

        /* ---------- Declare PDF options */
        // Set pdf dimensions
        $this->pageWidth = 595;
        $this->pageHeight = 842;
        $this->rotatePage = false;

        // Set positions where elements begin or end
        $this->marginLeft = 40;
        $this->marginRight = 40;
        $this->pageMidY = $this->pageHeight / 2;
        $this->pageMidX = $this->pageWidth / 2;
        $this->elementStartLeft = $this->marginLeft;
        $this->elementStartHalf = $this->pageMidX;
        $this->elementEndRight = $this->pageWidth - $this->marginRight;
        $this->minDistanceEndBottom = 30;

        // Set start and end coordinate for the pdf
        $this->startPositionY = 755;
        $this->endPositionY = 100;
        $this->positionY = $this->startPositionY;

        // Set colors
        $this->colorBlack = '{#000000}';
        $this->colorWhite = '{#ffffff}';
        $this->colorGray = '{#adb5bd}';
        $this->colorTextMark = '{#ffc107}';
        $this->colorPrimary = '{#bf203d}';
        $this->colorSecondary = '{#bea895}';

        // set various other options
        $this->fontUnicode = 0;
        $this->defaultFontsize = 10;
        $this->fontsizeSmall = 8;
        $this->fontsizeSmaller = 6;
        $this->htmlListBullet = '&mdash;';
        $this->htmlListIndent = 10;

        $this->currentPageNo = 0;
        $this->renderTemplateAfterContent = true;

        $this->hasPagination = true;
        $this->paginationLabel = '';
        $this->paginationStartY = 55;
        $this->paginationStartX = $this->elementEndRight - 100;
        $this->paginationEndY = $this->paginationStartY - 14;
        $this->paginationEndX = $this->elementEndRight;
        $this->fontPagination = 0;
        $this->paginationFontsize = 8;
        $this->paginationTextColor = $this->colorBlack;
        $this->paginationAlignment = 'right';

        // Load unicode font
        $this->fontUnicode = $this->pdf->load_font('Arial-Unicode', 'unicode', 'embedding');
        $this->fontPagination = $this->fontUnicode;
    }

    protected function setPdfMetaDataAndOptions(): void
    {
        $this->setOptions();

        $this->setMetaData(
            $this->data['documentInfo']['subject'],
            $this->data['documentInfo']['title'],
            $this->data['documentInfo']['creator']
        );
    }

    protected function createPdfBuffer(): string
    {
        $this->beginPdfDocument();
        $this->createPdfPage();
        $this->generatePdfContents();
        $this->endPdfDocument();

        // Get the content from the PDF output buffer and return it
        return $this->pdf->get_buffer();
    }

    protected function beginPdfDocument(): void
    {
        // Filename: If empty, the PDF is created in the working memory and must be fetched with get_buffer.
        $outfile = '';

        if ($this->pdf->begin_document($outfile, '') == 0) {
            throw new \Exception('Error: ' . $this->pdf->get_errmsg());
        }
    }

    protected function endPdfDocument(): void
    {
        $this->generatePagination();

        // Closes the generated pdf document
        $this->pdf->end_document('');
    }

    /* ---------------- PDFlib Functions ---------------- */
    /* ---------- Function to set the PDF options, the meta data and the template */
    protected function setOptions(): void
    {
        // set pdflib license
        if (!empty($this->pdflibLicense)) {
            $this->pdf->set_option('license=' . $this->pdflibLicense);
        }

        // Set path in which PDFlib should search for custom asset files
        $this->pdf->set_option('searchpath={' . $this->searchPath . '}');

        // Set error handeling -> Returns error code 0 and makes internal troubleshooting possible
        $this->pdf->set_option('errorpolicy=return');

        // Make the application Unicode compatible
        $this->pdf->set_option('stringformat=utf8');
    }

    protected function setMetaData(string $subject, string $title, string $creator): void
    {
        $this->pdf->set_info('Subject', $subject);
        $this->pdf->set_info('Title', $title);
        $this->pdf->set_info('Creator', $creator);
    }

    protected function loadFontsInitially(array $fonts): array
    {
        $fontStyles = [];

        // Embed fonts -> fontname: Path to the file based on the searchpath
        foreach ($fonts as $fontName => $fontConfig) {
            foreach ($fontConfig as $fontStyle => $fontPath) {
                $fontKey = $fontName . ucfirst($fontStyle);
                $fontStyles[$fontKey] = $this->pdf->load_font($fontPath, 'unicode', 'embedding');
            }
        }

        return $fontStyles;
    }

    protected function placeTemplateOnPage(string $filename, int $pageNumber): void
    {
        // Open a PDF and return PDI document handle
        $doc = $this->pdf->open_pdi_document($filename, '');
        if ($doc == 0) {
            throw new \Exception('Error: ' . $this->pdf->get_errmsg());
        }

        // Prepare the current page for usage
        $page = $this->pdf->open_pdi_page($doc, $pageNumber, '');
        if ($page == 0) {
            throw new \Exception('Error: ' . $this->pdf->get_errmsg());
        }

        // Place the imported PDF page on the output page with various options
        $this->pdf->fit_pdi_page($page, 0, 0, 'adjustpage');

        // Close the page handle and releases the resources.
        $this->pdf->close_pdi_page($page);
    }

    protected function createPageBaseTemplate(): void
    {
        // Start page template
        $pageTemplate = $this->pdf->begin_template_ext($this->pageWidth, $this->pageHeight, '');

        // Finish the template
        $this->pdf->end_template_ext(0, 0);

        // Place the template on the page, just like using an image
        $this->pdf->fit_image($pageTemplate, 0.0, 0.0, '');
    }

    /* ---------------- Content and PDF Helper Functions ---------------- */
    protected function createPdfPage(): void
    {
        $pageOptions = '';

        if ($this->rotatePage) {
            $pageOptions = 'rotate=90';
        }

        $this->pdf->begin_page_ext($this->pageWidth, $this->pageHeight, $pageOptions);
        ++$this->currentPageNo;

        if (!$this->renderTemplateAfterContent) {
            $this->placeTemplateOnPage($this->templateMain, 1);
            $this->createPageBaseTemplate();
        }
    }

    protected function generateNewPage(): void
    {
        // Suspend current page and begin a new page
        $this->pdf->suspend_page('');
        $this->createPdfPage();

        // Set height to default startpoint
        $this->positionY = $this->startPositionY;
    }

    protected function resumeOrGenerateNextPage(int &$pageNumber, float $startPositionY): void
    {
        if ($pageNumber > $this->currentPageNo) {
            $this->generateNewPage();
            ++$pageNumber;
            return;
        }

        $this->pdf->suspend_page('');
        $this->positionY = $startPositionY;

        $this->pdf->resume_page('pagenumber ' . $pageNumber);
        ++$pageNumber;
    }

    protected function replaceHtml(
        ?string $string,
        int $fontRegular,
        int $fontMedium,
        float $normalFontsize = null
    ): string {
        if (empty($string)) {
            return $string;
        }

        $normalFontsize = $normalFontsize ?? $this->defaultFontsize;

        $string = $this->fixHtmlPaddings($string);
        $string = $this->removeHtmlTagAttributes($string);
        $this->replaceHtmlLists($string, $fontRegular);

        $mapping = [
            "</p>\n" => "\n",
            '</p><p>' => "\n",
            '<p> </p>' => "\n",
            '<p>' => '',
            '<strong>' => '<font=' . $fontMedium . '>',
            '</strong>' => '<font=' . $fontRegular . '>',
            '<sup>' => '<textrise=60% fontsize=6>',
            '</sup>' => '<textrise=0 fontsize=' . $normalFontsize . '>',
            '<sub>' => '<textrise=-60% fontsize=6>',
            '</sub>' => '<textrise=0 fontsize=' . $normalFontsize . '>',
            '<i>' => '<italicangle=-12>',
            '</i>' => '<italicangle=0>',
            '<em>' => '<italicangle=-12>',
            '</em>' => '<italicangle=0>',
            '<br/>' => "\n",
            '<br>' => "\n",
            '<u>' => '<underline=true underlinewidth=7% underlineposition=-20%>',
            '</u>' => '<underline=false>',
            '<s>' => '<strikeout=true>',
            '</s>' => '<strikeout=false>',
            '</p><ul>' => "\n\n",
            '</p><ol>' => "\n\n",
            '<ol>' => '',
            '</ol>' => '<leftindent=0>',
            '<ul>' => '',
            '</ul>' => '<leftindent=0>',
            '<span>' => '',
            '</span>' => '',
            '<br />' => "\n",
            "\t" => '',
            "\r\n" => '',
            "\r" => '',
            '</p>' => '',
            '<li>' => '',
            '</li>' => '',
            '</div><div>' => "\n",
            '<div> </div>' => "\n",
            '<div>' => '',
            '</div>' => "\n",
            '<table>' => '',
            '</table>' => "\n",
            '<tbody>' => '',
            '</tbody>' => "\n",
            '<tr>' => '',
            '</tr>' => "\n",
            '<td>' => '',
            '</td>' => "\n",
            '≤' => '<unicode>&le;</unicode>',
            '≥' => '<unicode>&ge;</unicode>',
            '√' => '<unicode>&#8730;</unicode>',
            '<unicode>' => '<font=' . $this->fontUnicode . '>',
            '</unicode>' => '<font=' . $fontRegular . '>',
            '<b>' => '<font=' . $fontMedium . '>',
            '</b>' => '<font=' . $fontRegular . '>',
            '<text-right>' => '<alignment=right>',
            '</text-right>' => '<alignment=left>',
            '<text-center>' => '<alignment=center>',
            '</text-center>' => '<alignment=left>',
            '<leader>' => "<leader={alignment={grid}}>\t",
            '<mark>' => '<matchbox={fillcolor=' . $this->colorTextMark . ' boxheight={ascender descender}}>',
            '</mark>' => '<matchbox=end>',
            '<a>' => '',
            '</a>' => '',
        ];

        // Handling html tags which are written in all uppercase
        foreach ($mapping as $key => $searchValue) {
            $string = str_replace(mb_strtoupper($key), $searchValue, $string);
        }

        // handling default html tags (lowercase)
        $string = str_replace(array_keys($mapping), array_values($mapping), $string);

        // Handling of special characters
        $string = $this->encodeHtmlEntities($string);

        // convert < and > html entity codes back into special chars so that pdflib inline option lists still work
        $string = str_replace(['&lt;', '&gt;'], ['<', '>'], $string);

        return $string;
    }

    protected function removeHtmlTagAttributes(?string $string): string
    {
        if (empty($string)) {
            return $string;
        }

        // remove any inline styles from html tags
        return preg_replace('/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si', '<$1$2>', $string);
    }

    protected function fixHtmlPaddings(?string $string): string
    {
        if (empty($string)) {
            return $string;
        }

        $mapping = [
            "<ul>\n" => '<ul>',
            "</ul>\n" => '</ul>',
            "</ul>\r\n" => '</ul><br>',
            "<ol>\n" => '<ol>',
            "</ol>\n" => '</ol>',
            "<br />\n" => '<br />'
        ];

        // fix paddings coming from br tags and html lists
        return str_replace(array_keys($mapping), array_values($mapping), $string);
    }

    protected function encodeHtmlEntities(?string $string): string
    {
        if (empty($string)) {
            return $string;
        }

        // decode all already existing html entities so that they don't break when encoding the rest
        $string = html_entity_decode($string, ENT_QUOTES);

        // convert all special characters to html entity codes
        return htmlentities($string, ENT_QUOTES);
    }

    protected function replaceHtmlLists(?string &$string)
    {
        if (empty($string)) {
            return $string;
        }

        while (strpos($string, '<ol>') !== false || strpos($string, '<ul>') !== false) {
            $explodeArray = explode('<ol>', $string);
            if (empty($explodeArray[0])) {
                array_shift($explodeArray);
            }

            // if there are nested ordered lists merge all array strings to one single string‚
            if (count($explodeArray) > 1) {
                $explodedText = '';

                foreach ($explodeArray as &$listArrayItem) {
                    $explodedText .= $listArrayItem;
                }

                $explodeArray = [$explodedText];
            }

            foreach ($explodeArray as &$listArrayItem) {
                if (
                    strpos($listArrayItem, '</ul>') !== false
                    && strpos($listArrayItem, '</ol>') === false
                ) {
                    // ---- If it's an unordered list
                    // search for lists in li elements so that they can be inserted with correct formatting
                    $explodeLiElements = explode('<li>', $listArrayItem);
                    $liElementsCount = count($explodeLiElements);

                    // if there are nested unordered lists remove the end html tags and spacings
                    foreach ($explodeLiElements as $liIndex => &$listElement) {
                        if ($liIndex !== 0) {
                            $listElement = str_replace(["<ul>\r\n", '<ul>'], ["\n", ''], $listElement);
                        }

                        if ($liIndex !== 0 && $liIndex !== $liElementsCount - 1) {
                            $listElement = str_replace(
                                [
                                    '</ul><br></li>',
                                    "</ul>\n</li>",
                                    "</ul></li>\n",
                                    '</ul></li>',
                                    "</ul><br>\t</li>",
                                    "</ul>\n\t</li>",
                                    "</ul>\t</li>\n",
                                    "</ul>\t</li>",
                                ],
                                ['', '', '', '', '', '', '', ''],
                                $listElement
                            );
                        }
                    }

                    $listArrayItem = implode('<li>', $explodeLiElements);

                    // search for li elements and replace them with corresponding pdflib inline option lists
                    $searchForUnorderedEls = [
                        1 => '<li>',
                        2 => "</li>\n",
                        3 => '</li>',
                    ];

                    $replaceWithUnorderedOptlists = [
                        1 => '<leftindent=0>' . $this->htmlListBullet . '<leftindent=' . $this->htmlListIndent . '>',
                        2 => "\n",
                        3 => "\n",
                    ];

                    $listArrayItem = str_replace(
                        $searchForUnorderedEls,
                        $replaceWithUnorderedOptlists,
                        $listArrayItem
                    );
                } elseif (
                    strpos($listArrayItem, '</ul>') !== false
                    && strpos($listArrayItem, '</ol>') !== false
                ) {
                    // ---- if the text contains both list types
                    $orderedNumber = 0;
                    $explodeArrayOrdered = explode('<ul>', $listArrayItem);

                    foreach ($explodeArrayOrdered as &$orderedListArrayItem) {
                        if (strpos($orderedListArrayItem, '</ol>') !== false) {
                            $explodeOrderedEls = explode("\t", $orderedListArrayItem);
                            if (empty($explodeOrderedEls[0])) {
                                array_shift($explodeOrderedEls);
                            }

                            foreach ($explodeOrderedEls as &$orderedEl) {
                                $orderedNumber++;

                                $searchForOrderedListItems = [
                                    1 => '<li>',
                                    2 => "</li>\n",
                                    3 => '</li>',
                                ];

                                $replaceWithOrderedOptlists = [
                                    1 => '<leftindent=0>' . $orderedNumber . '.<leftindent=10>',
                                    2 => "\n",
                                    3 => "\n",
                                ];

                                $orderedEl = str_replace(
                                    $searchForOrderedListItems,
                                    $replaceWithOrderedOptlists,
                                    $orderedEl
                                );
                            }

                            $orderedListArrayItem = implode($explodeOrderedEls);
                        } elseif (strpos($orderedListArrayItem, '</ul>') !== false) {
                            $searchForUnorderedEls = [
                                1 => '<li>',
                                2 => "</li>\n",
                                3 => '</li>',
                            ];

                            $replaceWithUnorderedOptlists = [
                                1 => '<leftindent=0>'
                                    . $this->htmlListBullet
                                    . '<leftindent='
                                    . $this->htmlListIndent
                                    . '>',
                                2 => "\n",
                                3 => "\n",
                            ];

                            $orderedListArrayItem = str_replace(
                                $searchForUnorderedEls,
                                $replaceWithUnorderedOptlists,
                                $orderedListArrayItem
                            );
                        }
                    }
                    $listArrayItem = implode($explodeArrayOrdered);
                } elseif (
                    strpos($listArrayItem, '</ul>') == false
                    && strpos($listArrayItem, '</ol>') !== false
                ) {
                    // ---- if it's an ordered list
                    $orderedNumber = 0;
                    $explodeOrderedEls = explode('<li>', $listArrayItem);
                    if (empty($explodeOrderedEls[0]) || $explodeOrderedEls[0] === "\t") {
                        array_shift($explodeOrderedEls);
                    }
                    $liElementsCount = count($explodeOrderedEls);

                    foreach ($explodeOrderedEls as $liIndex => &$orderedEl) {
                        $orderedNumber++;

                        // insert <li> again, so that the corresponding pdflib inline option list will be placed
                        $orderedEl = '<li>' . $orderedEl;

                        /**
                         * When there are nested unordered lists remove the end tags + spacing,
                         * so that there isn't too much padding between list elements
                         */
                        if ($liIndex !== 0 && $liIndex !== $liElementsCount - 1) {
                            $orderedEl = str_replace(
                                ["</ol></li>\n", "</ol>\n</li>\n", "</ol>\t</li>\n"],
                                ['', '', ''],
                                $orderedEl
                            );
                        }

                        // search for li elements and replace them with corresponding pdflib inline option lists
                        $searchForOrderedListItems = [
                            1 => '<li>',
                            2 => "</li>\n",
                            3 => '</li>',
                        ];

                        $replaceWithOrderedOptlists = [
                            1 => '<leftindent=0>' . $orderedNumber . '.<leftindent=' . $this->htmlListIndent . '>',
                            2 => "\n",
                            3 => "\n",
                        ];

                        $orderedEl = str_replace(
                            $searchForOrderedListItems,
                            $replaceWithOrderedOptlists,
                            $orderedEl
                        );
                    }

                    $listArrayItem = implode($explodeOrderedEls);
                }
            }

            $string = implode("\n", $explodeArray);
            return $string;
        }
    }

    protected function calculatePtfromMm(float $mm, int $roundPrecision = 0): float
    {
        $pt = ($mm * 72) / 25.4;
        return round($pt, $roundPrecision);
    }

    protected function convertHexToRgb(string $hexCode): array
    {
        $hexCode = str_replace(['{', '}', '#'], '', $hexCode);

        if (strlen($hexCode) === 3) {
            $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
        }

        $red = hexdec(substr($hexCode, 0, 2)) / 255;
        $green = hexdec(substr($hexCode, 2, 2)) / 255;
        $blue = hexdec(substr($hexCode, 4, 2)) / 255;

        return [$red, $green, $blue];
    }

    protected function placePartingLine(): void
    {
        // Get height value
        $this->positionY = $this->positionY - 5;

        $this->placeLine(
            $this->elementStartLeft,
            $this->positionY,
            $this->elementEndRight,
            $this->positionY
        );

        // Get new y position for following elements
        $this->positionY = $this->positionY - 5;
    }

    protected function placeDashedLine(
        float $startX,
        float $startY,
        float $endX,
        float $endY,
        string $dasharray = '1 1',
        float $lineWidth = 1,
        string $color = null,
    ): void {
        $this->pdf->set_graphics_option('dasharray={' . $dasharray . '}');

        $this->placeLine(
            $startX,
            $startY,
            $endX,
            $endY,
            $lineWidth,
            $color
        );
    }

    protected function placeLine(
        float $startX,
        float $startY,
        float $endX,
        float $endY,
        float $lineWidth = 1,
        string $color = null,
    ): void {
        $color = $color ?? $this->colorBlack;
        list($red, $green, $blue) = $this->convertHexToRgb($color);

        // Define width of the parting line
        $this->pdf->setlinewidth($lineWidth);

        // Define stroke and fill color
        $this->pdf->setcolor('stroke', 'rgb', $red, $green, $blue, 0);
        $this->pdf->setcolor('fill', 'rgb', $red, $green, $blue, 0);

        // Set starting point of parting line
        $this->pdf->moveto($startX, $startY);

        // Draw parting line from starting point to end point
        $this->pdf->lineto($endX, $endY);
        $this->pdf->stroke();
    }

    protected function createCoordinatesArray(float $startX, float $startY, float $endX, float $endY): array
    {
        return [
            'startX' => $startX,
            'startY' => $startY,
            'endX' => $endX,
            'endY' => $endY,
        ];
    }

    protected function createTableCell(
        int $table,
        int $row,
        int $column,
        string $optlistCell,
        string $cellText,
        float $fontSize,
        int $fontRegular,
        int $fontBold,
        string $optlistTextflow,
        bool $shouldReplaceHtml = true,
    ): int {
        $textflowCell = $this->createTextflow(
            $cellText,
            $fontRegular,
            $fontBold,
            $fontSize,
            $optlistTextflow,
            $shouldReplaceHtml,
        );

        $optlistTableCell = $optlistCell . ' textflow=' . $textflowCell;

        $table = $this->pdf->add_table_cell($table, $column, $row, '', $optlistTableCell);
        if ($table == 0) {
            throw new \Exception(
                'Error when creating table cell with textflow: ' . $this->pdf->get_errmsg()
            );
        }

        return $table;
    }

    protected function createTableCellWithTextline(
        int $table,
        int $row,
        int $column,
        string $cellText,
        string $optlistCell,
        string $optlistTextline
    ): int {
        $optlistTableCell = $optlistCell . ' fittextline={' . $optlistTextline . '}';

        $table = $this->pdf->add_table_cell($table, $column, $row, $cellText, $optlistTableCell);
        if ($table == 0) {
            throw new \Exception(
                'Error when creating table cell with textline: ' . $this->pdf->get_errmsg()
            );
        }

        return $table;
    }

    protected function createTableCellWithImage(
        int $table,
        int $row,
        int $column,
        string $optlistCell,
        string $imagePath,
        string $optlistImage = '',
        string $imageFitMethod = 'meet'
    ): int {
        $image = $this->loadImage($imagePath);

        // Add table cell with the image
        $optlistTableCell = $optlistCell
            . ' image='
            . $image
            . ' fitimage={fitmethod='
            . $imageFitMethod
            . ' '
            . $optlistImage
            .  '}';

        $table = $this->pdf->add_table_cell($table, $column, $row, '', $optlistTableCell);
        if (0 == $table) {
            throw new \Exception('Error when creating table cell with image: ' . $this->pdf->get_errmsg());
        }

        return $table;
    }

    protected function createTableCellWithoutContent(int $table, int $row, int $column, string $optlist): int
    {
        $table = $this->pdf->add_table_cell($table, $column, $row, '', $optlist);

        if ($table == 0) {
            throw new \Exception(
                'Error when creating table cell without content: ' . $this->pdf->get_errmsg()
            );
        }

        return $table;
    }

    protected function placeTableOnPage(
        int $table,
        array $coordinates,
        string $optlistTable,
        string $tableName,
        bool $shouldContinueOnNextPage,
        float $paddingBottom = 0,
        bool $calculateNewPositionY = true
    ): void {
        $resultTable = $this->pdf->fit_table(
            $table,
            $coordinates['startX'],
            $coordinates['startY'],
            $coordinates['endX'],
            $coordinates['endY'],
            $optlistTable
        );

        if ($shouldContinueOnNextPage) {
            /*
             * If table does not fit completely on current page,
             * generate a new one and place the rest of the table there
             */
            while ($resultTable != '_stop') {
                $this->generateNewPage();

                // Table coordinates for new page
                $startY2 = $this->positionY;
                $endY2 = $this->endPositionY;

                // Place table on new page
                $resultTable = $this->pdf->fit_table(
                    $table,
                    $coordinates['startX'],
                    $startY2,
                    $coordinates['endX'],
                    $endY2,
                    $optlistTable
                );
            }
        } else {
            if ($resultTable == '_boxfull') {
                throw new \Exception(
                    'Error: Table "' . $tableName . '" is too big to fit into the defined fitbox'
                );
            }
        }

        if ($calculateNewPositionY) {
            $tableBottomPositionY = $this->pdf->info_table($table, 'y2');
            $this->getNewPositionY($tableBottomPositionY, $paddingBottom);
        }
    }

    protected function createTextflow(
        string $text,
        int $fontRegular,
        int $fontBold,
        float $fontSize,
        string $optlist,
        bool $shouldReplaceHtml = true,
    ): int {
        if ($shouldReplaceHtml) {
            $text = $this->replaceHtml($text, $fontRegular, $fontBold, $fontSize);
        }

        $textflow = $this->pdf->create_textflow($text, $optlist);

        if ($textflow == 0) {
            throw new \Exception('Error when creating textflow: ' . $this->pdf->get_errmsg());
        }

        return $textflow;
    }

    protected function addTextflow(
        int $textflow,
        string $text,
        string $optlist
    ): int {
        $textflow = $this->pdf->add_textflow($textflow, $text, $optlist);

        if ($textflow == 0) {
            throw new \Exception('Error when adding textflow: ' . $this->pdf->get_errmsg());
        }

        return $textflow;
    }

    protected function placeTextflowOnPage(
        int $textflow,
        array $coordinates,
        bool $shouldContinueOnNextPage,
        string $textflowName,
        string $optlist = '',
        float $paddingBottom = 0,
        bool $calculateNewHeight = true,
        string $text = null
    ): void {
        $result = $this->pdf->fit_textflow(
            $textflow,
            $coordinates['startX'],
            $coordinates['startY'],
            $coordinates['endX'],
            $coordinates['endY'],
            $optlist,
        );

        if ($shouldContinueOnNextPage) {
            /*
             * If textflow does not fit completely on current page,
             * generate a new one and place the rest of the table there
             */
            while ($result != '_stop') {
                $this->generateNewPage();

                // textflow coordinates for new page
                $startY2 = $this->positionY;
                $endY2 = $this->endPositionY;

                // Place textflow on new page
                $result = $this->pdf->fit_textflow(
                    $textflow,
                    $coordinates['startX'],
                    $startY2,
                    $coordinates['endX'],
                    $endY2,
                    ''
                );
            }
        } else {
            $errorMessage = 'Error: Textflow "' . $textflowName . '" is too big to fit into the defined fitbox.';

            if (!empty($text)) {
                $errorMessage = 'Error: The following Text for Textflow "'
                    . $textflowName
                    . '" is too big to fit into it\'s defined fitbox:'
                    . "\n"
                    . $text;
            }

            // If the text doesn't fit into the fitbox throw an Exception
            if ($result == '_boxfull') {
                throw new \Exception($errorMessage);
            }
        }

        if ($calculateNewHeight) {
            // Get height of the fitbox
            $textflowBottomY = $this->pdf->info_textflow($textflow, 'y2');
            $this->getNewPositionY($textflowBottomY, $paddingBottom);
        }
    }

    protected function loadImage(string $imagePath): int
    {
        $image = $this->pdf->load_image('auto', $imagePath, '');

        if ($image == 0) {
            throw new \Exception(
                'Could not load image from path "' . $imagePath . '". Error: ' . $this->pdf->get_errmsg()
            );
        }

        return $image;
    }

    protected function fitImage(
        int $image,
        float $imagePositionX,
        float $imagePositionY,
        string|float $boxWidth = 'auto',
        string|float $boxHeight = 'auto',
        string $optlist = '',
        string $position = 'center',
        string $fitmethod = 'auto'
    ): void {
        $optlistImage = 'fitmethod=' . $fitmethod . ' ' . $optlist;

        if ($boxWidth != 'auto' && $boxHeight != 'auto') {
            $optlistImage = $optlistImage . ' boxsize={' . $boxWidth . ' ' . $boxHeight . '}';
        }

        if (!empty($position)) {
            $optlistImage = $optlistImage . ' position={' . $position . '}';
        }

        $this->pdf->fit_image($image, $imagePositionX, $imagePositionY, $optlistImage);
    }

    protected function loadGraphic(string $graphicsPath): int
    {
        $graphics = $this->pdf->load_graphics('auto', $graphicsPath, '');

        if ($graphics == 0) {
            throw new \Exception(
                'Could not load vector graphic from path "' . $graphicsPath
                . '". Error: ' . $this->pdf->get_errmsg()
            );
        }

        return $graphics;
    }

    protected function fitGraphic(
        int $graphic,
        float $graphicPositionX,
        float $graphicPositionY,
        string|float $boxWidth = 'auto',
        string|float $boxHeight = 'auto',
        string $optlist = '',
        string $position = 'center',
        string $fitmethod = 'auto'
    ): void {
        $optlistGraphic = 'fitmethod=' . $fitmethod . ' ' . $optlist;

        if ($boxWidth != 0 && $boxHeight != 0) {
            $optlistGraphic = $optlistGraphic . ' boxsize={' . $boxWidth . ' ' . $boxHeight . '}';
        }

        if (!empty($position)) {
            $optlistGraphic = $optlistGraphic . ' position={' . $position . '}';
        }

        $this->pdf->fit_graphics($graphic, $graphicPositionX, $graphicPositionY, $optlistGraphic);
    }

    protected function getSvgDimensions(string $filePath): array
    {
        $path = $this->searchPath . $filePath;

        if (!file_exists($path)) {
            return ['width' => 0, 'height' => 0];
        }

        $xml = simplexml_load_file($path);
        $attributes = $xml->attributes();

        $width = (string) $attributes->width;
        $height = (string) $attributes->height;
        $viewBox = (string) $attributes->viewBox;

        if ($width && $height) {
            return [
                'width' => (float) $width,
                'height' => (float) $height
            ];
        }

        if ($viewBox) {
            $parts = preg_split('/[\s,]+/', trim($viewBox));
            if (count($parts) === 4) {
                return [
                    'width' => (float) $parts[2],
                    'height' => (float) $parts[3],
                ];
            }
        }

        return ['width' => 0, 'height' => 0];
    }

    protected function getNewPositionY(float $currentPositionY, float $paddingBottom): void
    {
        $this->positionY = $currentPositionY - $paddingBottom;
    }

    protected function checkIfBlockShouldBeRenderedOnNewPage(
        float $sectionMarginTop = 0,
        float $endBottomY = null
    ): void {
        $endBottomY = $endBottomY ?? $this->endPositionY;

        if ($this->positionY < $endBottomY + $this->minDistanceEndBottom) {
            $this->generateNewPage();
        } else {
            $this->getNewPositionY($this->positionY, $sectionMarginTop);
        }
    }

    /* ---------- Function for the pdf header, footer and pagination */
    protected function createPdfHeadline(
        string $headline,
        float $startX,
        float $startY,
        float $endX,
        float $endY,
        int $font,
        float $fontsize,
        string $textColor,
        string $optlistExtras = ''
    ): void {
        if (empty($headline)) {
            return;
        }

        $coordinates = $this->createCoordinatesArray(
            $startX,
            $startY,
            $endX,
            $endY
        );

        $optlist = 'font='
            . $font
            . ' fontsize='
            . $fontsize
            . ' fillcolor='
            . $textColor
            . ' wordspacing=0 charref=true';

        if (!empty($optlistExtras)) {
            $optlist = $optlist . ' ' . $optlistExtras;
        }

        $textflow = $this->addTextflow(0, $headline, $optlist);
        $this->placeTextflowOnPage(
            $textflow,
            $coordinates,
            false,
            'PDF Headline',
            '',
            0,
            false
        );
    }

    protected function generatePagination(): void
    {
        // Set optlist for pagination styling
        $optlistPagination = 'font='
            . $this->fontPagination
            . ' fontsize='
            . $this->paginationFontsize
            . ' fillcolor='
            . $this->paginationTextColor
            . ' wordspacing=0.5 charref=true alignment='
            . $this->paginationAlignment;

        // Set maximum page count and current page
        $paginationMax = $this->currentPageNo;
        $paginationCurrent = 1;

        // set default coordinates for pagination
        $startX = $this->paginationStartX;
        $startY = $this->paginationStartY;
        $endX = $this->paginationEndX;
        $endY = $this->paginationEndY;

        /* --- Place Pagination on all pages */
        // Suspend current page
        $this->pdf->suspend_page('');

        // resume page number 1
        $this->pdf->resume_page('pagenumber ' . $paginationCurrent);

        if ($this->renderTemplateAfterContent) {
            $this->placeTemplateOnPage($this->templateMain, 1);
            $this->createPageBaseTemplate();
        }

        if ($this->hasPagination) {
            // Place Pagination on Page 1
            $paginationText = $paginationCurrent . '/' . $paginationMax;

            if (!empty($this->paginationLabel)) {
                $paginationText = $this->paginationLabel . ': ' . $paginationText;
            }

            $this->placePagination(
                $paginationText,
                $paginationCurrent,
                $optlistPagination,
                $startY,
                $startX,
                $endY,
                $endX
            );
        }

        // Complete the current page and apply relevant options
        $this->pdf->end_page_ext('');

        /*
         * If maximum page count is higher than 1 iterate through every page after the first one
         * as long as $index is less then the maximum page count
         */
        if ($paginationMax === 1) {
            return;
        }

        while ($paginationCurrent < $paginationMax) {
            // get the current page number
            ++$paginationCurrent;

            // Resume the page
            $this->pdf->resume_page('pagenumber ' . $paginationCurrent);

            if ($this->renderTemplateAfterContent) {
                $this->placeTemplateOnPage($this->templateMain, 1);
                $this->createPageBaseTemplate();
            }

            if ($this->hasPagination) {
                // Place Pagination on the Page
                $paginationText = $paginationCurrent . '/' . $paginationMax;

                if (!empty($this->paginationLabel)) {
                    $paginationText = $this->paginationLabel . ': ' . $paginationText;
                }

                $this->placePagination(
                    $paginationText,
                    $paginationCurrent,
                    $optlistPagination,
                    $startY,
                    $startX,
                    $endY,
                    $endX
                );
            }

            // Complete the current page and apply relevant options
            $this->pdf->end_page_ext('');
        }
    }

    protected function placePagination(
        string $text,
        int $page,
        string $optlist,
        float $startY,
        float $startX,
        float $endY,
        float $endX
    ): void {
        $coordinates = $this->createCoordinatesArray(
            $startX,
            $startY,
            $endX,
            $endY,
        );

        $textflow = $this->addTextflow(0, $text, $optlist);

        // Output textflow on page
        $textflowName = 'PDF Pagination for Page' . $page;
        $this->placeTextflowOnPage($textflow, $coordinates, false, $textflowName, '', 0, false);
    }
}
