<?php
/**
 * Generate a proper .xlsx file with embedded images using ZipArchive + Open XML.
 * No external libraries required.
 */

class SimpleXlsxWriter {
    private $rows = [];
    private $images = []; // [{row, col, path, width, height}]
    private $title = 'Sheet1';
    private $colWidths = [];

    public function setTitle($title) { $this->title = $title; }
    public function setColWidths($widths) { $this->colWidths = $widths; }

    public function addRow($cells, $styles = []) {
        $this->rows[] = ['cells' => $cells, 'styles' => $styles];
    }

    public function addImage($row, $col, $filePath, $width = 80, $height = 80) {
        if (file_exists($filePath)) {
            $this->images[] = [
                'row' => $row,
                'col' => $col,
                'path' => $filePath,
                'width' => $width,
                'height' => $height
            ];
        }
    }

    public function generate() {
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        // _rels/.rels
        $zip->addFromString('_rels/.rels', $this->rootRels());
        // xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        // xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        // xl/styles.xml
        $zip->addFromString('xl/styles.xml', $this->styles());
        // xl/sharedStrings.xml
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStrings());
        // xl/worksheets/sheet1.xml
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());

        // Add images and drawing
        if (!empty($this->images)) {
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->sheetRels());
            $zip->addFromString('xl/drawings/drawing1.xml', $this->drawing());
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->drawingRels());

            foreach ($this->images as $i => $img) {
                $ext = strtolower(pathinfo($img['path'], PATHINFO_EXTENSION));
                $zip->addFile($img['path'], 'xl/media/image' . ($i + 1) . '.' . $ext);
            }
        }

        $zip->close();
        return $tmpFile;
    }

    private function contentTypes() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Default Extension="png" ContentType="image/png"/>';
        $xml .= '<Default Extension="jpg" ContentType="image/jpeg"/>';
        $xml .= '<Default Extension="jpeg" ContentType="image/jpeg"/>';
        $xml .= '<Default Extension="gif" ContentType="image/gif"/>';
        $xml .= '<Default Extension="webp" ContentType="image/webp"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $xml .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';
        if (!empty($this->images)) {
            $xml .= '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
        }
        $xml .= '</Types>';
        return $xml;
    }

    private function rootRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook() {
        $t = htmlspecialchars($this->title);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $t . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';
    }

    private function styles() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1A1A1A"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left/><right/><top/><bottom style="thin"><color rgb="FFE5E7EB"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'                  // 0: normal
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="1"/>'     // 1: bold
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" applyFont="1" applyFill="1"/>' // 2: header (white on dark)
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1"/>'   // 3: bordered
            . '</cellXfs>'
            . '</styleSheet>';
    }

    // Build shared strings table
    private function getStringTable() {
        $strings = [];
        $map = [];
        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                if (is_string($cell) && !is_numeric($cell)) {
                    $key = $cell;
                    if (!isset($map[$key])) {
                        $map[$key] = count($strings);
                        $strings[] = $cell;
                    }
                }
            }
        }
        return ['strings' => $strings, 'map' => $map];
    }

    private function sharedStrings() {
        $table = $this->getStringTable();
        $count = count($table['strings']);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">';
        foreach ($table['strings'] as $s) {
            $xml .= '<si><t>' . htmlspecialchars($s) . '</t></si>';
        }
        $xml .= '</sst>';
        return $xml;
    }

    private function colLetter($col) {
        $letter = '';
        while ($col >= 0) {
            $letter = chr(65 + ($col % 26)) . $letter;
            $col = intval($col / 26) - 1;
        }
        return $letter;
    }

    private function sheet() {
        $table = $this->getStringTable();
        $map = $table['map'];

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        // Column widths
        if (!empty($this->colWidths)) {
            $xml .= '<cols>';
            foreach ($this->colWidths as $i => $w) {
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach ($this->rows as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $hasImageRow = false;
            foreach ($this->images as $img) {
                if ($img['row'] === $rIdx) { $hasImageRow = true; break; }
            }
            $rowHeight = $hasImageRow ? ' ht="65" customHeight="1"' : '';
            $xml .= '<row r="' . $rowNum . '"' . $rowHeight . '>';

            foreach ($row['cells'] as $cIdx => $cell) {
                $ref = $this->colLetter($cIdx) . $rowNum;
                $style = isset($row['styles'][$cIdx]) ? $row['styles'][$cIdx] : 0;

                if ($cell === null || $cell === '') {
                    $xml .= '<c r="' . $ref . '" s="' . $style . '"/>';
                } elseif (is_numeric($cell) && !is_string($cell)) {
                    $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $cell . '</v></c>';
                } else {
                    $strIdx = $map[$cell] ?? 0;
                    $xml .= '<c r="' . $ref . '" t="s" s="' . $style . '"><v>' . $strIdx . '</v></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if (!empty($this->images)) {
            $xml .= '<drawing r:id="rId1"/>';
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    private function sheetRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            . '</Relationships>';
    }

    private function drawing() {
        $ns = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';
        $ans = 'http://schemas.openxmlformats.org/drawingml/2006/main';
        $rns = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<xdr:wsDr xmlns:xdr="' . $ns . '" xmlns:a="' . $ans . '" xmlns:r="' . $rns . '">';

        foreach ($this->images as $i => $img) {
            $emu_w = $img['width'] * 9525;
            $emu_h = $img['height'] * 9525;

            $xml .= '<xdr:twoCellAnchor editAs="oneCell">';
            $xml .= '<xdr:from><xdr:col>' . $img['col'] . '</xdr:col><xdr:colOff>38100</xdr:colOff><xdr:row>' . $img['row'] . '</xdr:row><xdr:rowOff>38100</xdr:rowOff></xdr:from>';
            $xml .= '<xdr:to><xdr:col>' . $img['col'] . '</xdr:col><xdr:colOff>' . ($emu_w + 38100) . '</xdr:colOff><xdr:row>' . $img['row'] . '</xdr:row><xdr:rowOff>' . ($emu_h + 38100) . '</xdr:rowOff></xdr:to>';
            $xml .= '<xdr:pic>';
            $xml .= '<xdr:nvPicPr><xdr:cNvPr id="' . ($i + 2) . '" name="Image' . ($i + 1) . '"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>';
            $xml .= '<xdr:blipFill><a:blip r:embed="rId' . ($i + 1) . '"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>';
            $xml .= '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $emu_w . '" cy="' . $emu_h . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>';
            $xml .= '</xdr:pic>';
            $xml .= '<xdr:clientData/>';
            $xml .= '</xdr:twoCellAnchor>';
        }

        $xml .= '</xdr:wsDr>';
        return $xml;
    }

    private function drawingRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->images as $i => $img) {
            $ext = strtolower(pathinfo($img['path'], PATHINFO_EXTENSION));
            $xml .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image' . ($i + 1) . '.' . $ext . '"/>';
        }
        $xml .= '</Relationships>';
        return $xml;
    }
}
