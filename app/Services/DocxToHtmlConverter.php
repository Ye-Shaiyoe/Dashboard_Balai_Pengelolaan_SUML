<?php

namespace App\Services;

/**
 * Convert .docx file to HTML for preview
 * .docx is a ZIP file containing XML files
 */
class DocxToHtmlConverter
{
    private string $filePath;
    private array $images = []; // Cache extracted images
    private string $relsXml = ''; // XML relationships untuk mapping gambar

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Extract images dari .docx dan convert ke base64
     */
    private function extractImages(): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->filePath) !== true) {
            return;
        }

        // Batas ukuran gambar untuk mencegah zip bomb / kehabisan memori.
        $maxImageBytes = 5 * 1024 * 1024;   // 5 MB per gambar
        $maxTotalBytes = 20 * 1024 * 1024;  // 20 MB total
        $totalBytes = 0;

        // Cari semua file gambar di word/media/
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('#^word/media/(.+)$#', $filename, $matches)) {
                $imageName = $matches[1];

                // Cek ukuran hasil dekompresi sebelum membaca isi file.
                $stat = $zip->statIndex($i);
                $size = (int) ($stat['size'] ?? 0);

                if ($size <= 0 || $size > $maxImageBytes || ($totalBytes + $size) > $maxTotalBytes) {
                    continue;
                }

                $imageData = $zip->getFromIndex($i);
                if ($imageData !== false) {
                    $totalBytes += $size;
                    $extension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                    $mimeType = match ($extension) {
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'bmp' => 'image/bmp',
                        'webp' => 'image/webp',
                        default => 'image/png',
                    };
                    $this->images[$imageName] = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }
            }
        }

        $zip->close();
    }

    /**
     * Get image src dari rels mapping
     */
    private function getImageSrc(string $imageId, string $relsXml): string
    {
        // Parse rels XML untuk cari Target
        $dom = new \DOMDocument();
        $dom->loadXML($relsXml, LIBXML_NONET);
        
        foreach ($dom->getElementsByTagName('Relationship') as $rel) {
            if ($rel->getAttribute('Id') === $imageId) {
                $target = $rel->getAttribute('Target');
                // Target biasanya seperti "media/image1.png"
                $imageName = basename($target);
                return $this->images[$imageName] ?? '';
            }
        }
        
        return '';
    }

    /**
     * Convert .docx to HTML
     */
    public function convert(): string
    {
        // Cek file exists
        if (!file_exists($this->filePath)) {
            return '<p style="color:red;">File tidak ditemukan: ' . htmlspecialchars($this->filePath) . '</p>';
        }

        // Cek ukuran file
        $fileSize = filesize($this->filePath);
        if ($fileSize === 0) {
            return '<p style="color:red;">File kosong (0 bytes).</p>';
        }

        // Extract images dulu
        $this->extractImages();

        $zip = new \ZipArchive();
        $result = $zip->open($this->filePath);

        if ($result !== true) {
            $errorMessages = [
                \ZipArchive::ER_EXISTS => 'File sudah ada',
                \ZipArchive::ER_INCONS => 'Inconsistency dalam ZIP',
                \ZipArchive::ER_INVAL => 'Argumen invalid',
                \ZipArchive::ER_MEMORY => 'Gagal alokasi memori',
                \ZipArchive::ER_NOENT => 'File tidak ada',
                \ZipArchive::ER_NOZIP => 'Bukan file ZIP yang valid',
                \ZipArchive::ER_OPEN => 'Gagal membuka file',
                \ZipArchive::ER_READ => 'Error membaca file',
                \ZipArchive::ER_SEEK => 'Error seek',
            ];
            $errorMsg = $errorMessages[$result] ?? 'Error code: ' . $result;
            return '<p style="color:red;">Gagal membuka file .docx: ' . $errorMsg . '</p><p style="color:gray; font-size:12px;">File size: ' . number_format($fileSize) . ' bytes</p>';
        }

        // Batasi ukuran XML yang akan didekompresi (proteksi zip bomb).
        $maxXmlBytes = 15 * 1024 * 1024;
        $docStat = $zip->statName('word/document.xml');

        if ($docStat !== false && (int) ($docStat['size'] ?? 0) > $maxXmlBytes) {
            $zip->close();
            return '<p style="color:red;">Dokumen terlalu besar untuk ditampilkan.</p><p style="color:gray; font-size:12px;">Ukuran XML dokumen melebihi batas 15 MB.</p>';
        }

        // Baca document.xml dan document.xml.rels
        $xmlContent = $zip->getFromName('word/document.xml');
        $relsContent = $zip->getFromName('word/_rels/document.xml.rels');
        $zip->close();

        if (!$xmlContent) {
            return '<p style="color:red;">File ini bukan format .docx yang valid (tidak ada word/document.xml).</p><p style="color:gray; font-size:12px;">Kemungkinan file .doc format lama atau file corrupt.</p>';
        }

        // Simpan rels untuk mapping gambar
        $this->relsXml = $relsContent ?: '';

        // Cek encoding - hapus BOM jika ada
        if (substr($xmlContent, 0, 3) === "\xEF\xBB\xBF") {
            $xmlContent = substr($xmlContent, 3);
        }

        // Parse XML dengan DOMDocument (lebih robust dari SimpleXML)
        $dom = new \DOMDocument();
        
        // Supress warnings tapi tetap capture errors
        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        // Coba load XML dengan DOMDocument
        $loaded = $dom->loadXML($xmlContent, LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_NOBLANKS | LIBXML_NONET);
        
        if (!$loaded) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
            
            if (empty($errors)) {
                return '<p style="color:red;">Gagal memparse dokumen XML (tidak ada detail error).</p><p style="color:gray; font-size:12px;">File size: ' . number_format($fileSize) . ' bytes. Kemungkinan file corrupt atau format tidak standar.</p>';
            }

            $errorMessages = array_map(function($e) {
                return trim($e->message) . ' (line ' . $e->line . ')';
            }, array_slice($errors, 0, 5));

            return '<p style="color:red;">Gagal memparse dokumen XML.</p><div style="background:#f9fafb; padding:12px; border-radius:6px; font-size:12px; margin-top:8px;"><strong>Error details:</strong><br>' . htmlspecialchars(implode('<br>', $errorMessages)) . '</div>';
        }
        
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
        
        // Convert DOMDocument ke HTML
        try {
            $html = $this->parseDomNode($dom->documentElement);
        } catch (\Exception $e) {
            return '<p style="color:red;">Error saat convert ke HTML: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }

        if (trim($html) === '') {
            return '<p style="color:gray;">Dokumen kosong atau tidak memiliki konten yang bisa ditampilkan.</p>';
        }

        return $html;
    }

    /**
     * Parse DOMDocument node ke HTML
     */
    private function parseDomNode(\DOMNode $node): string
    {
        $html = '';
        
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $localName = $child->localName;
                
                switch ($localName) {
                    case 'p': // Paragraph
                        $html .= $this->parseParagraphDom($child);
                        break;
                    case 'tbl': // Table
                        $html .= $this->parseTableDom($child);
                        break;
                    default:
                        // Cek children juga
                        $html .= $this->parseDomNode($child);
                        break;
                }
            }
        }
        
        return $html;
    }

    /**
     * Parse paragraph dari DOMDocument
     */
    private function parseParagraphDom(\DOMNode $p): string
    {
        // Get paragraph properties
        $pPr = null;
        $alignment = '';
        $headingLevel = 0;
        $indentLeft = '';
        
        foreach ($p->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'pPr') {
                $pPr = $child;
                break;
            }
        }
        
        // Extract paragraph properties
        if ($pPr) {
            foreach ($pPr->childNodes as $prop) {
                if ($prop->nodeType === XML_ELEMENT_NODE) {
                    if ($prop->localName === 'jc') {
                        $val = $prop->getAttribute('val');
                        $alignment = match ($val) {
                            'center' => 'text-align: center;',
                            'right' => 'text-align: right;',
                            'both' => 'text-align: justify;',
                            'distribute' => 'text-align: justify;',
                            default => ''
                        };
                    } elseif ($prop->localName === 'pStyle') {
                        $styleName = $prop->getAttribute('val');
                        if (preg_match('/Heading(\d+)/', $styleName, $matches)) {
                            $headingLevel = min(intval($matches[1]), 6);
                        }
                    } elseif ($prop->localName === 'ind') {
                        // Handle indentation
                        $left = $prop->getAttribute('left');
                        $firstLine = $prop->getAttribute('firstLine');
                        if ($left) {
                            $twips = intval($left);
                            $pixels = round($twips / 20 * 1.333);
                            $indentLeft = "margin-left: {$pixels}px; ";
                        }
                    }
                }
            }
        }
        
        // Build text content
        $text = '';
        foreach ($p->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'r') {
                $text .= $this->parseRunDom($child);
            } elseif ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tab') {
                $text .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            } elseif ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'br') {
                $text .= '<br>';
            }
        }

        if (trim($text) === '') {
            return '<br>';
        }

        // Combine styles
        $styleAttr = '';
        if ($alignment || $indentLeft) {
            $styleAttr = ' style="' . $alignment . $indentLeft . '"';
        }
        
        if ($headingLevel > 0) {
            return "<h$headingLevel$styleAttr>$text</h$headingLevel>";
        }
        
        return "<p$styleAttr>$text</p>";
    }

    /**
     * Parse run (text dengan formatting) dari DOMDocument
     */
    private function parseRunDom(\DOMNode $run): string
    {
        $text = '';
        $rPr = null;
        
        // Extract run properties (rPr)
        foreach ($run->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'rPr') {
                $rPr = $child;
                break;
            }
        }
        
        // Build formatting styles
        $styles = [];
        $beforeTags = [];
        $afterTags = [];
        
        if ($rPr) {
            foreach ($rPr->childNodes as $prop) {
                if ($prop->nodeType === XML_ELEMENT_NODE) {
                    // Bold
                    if ($prop->localName === 'b') {
                        $beforeTags[] = '<strong>';
                        $afterTags[] = '</strong>';
                    }
                    // Italic
                    elseif ($prop->localName === 'i') {
                        $beforeTags[] = '<em>';
                        $afterTags[] = '</em>';
                    }
                    // Underline
                    elseif ($prop->localName === 'u') {
                        $styles[] = 'text-decoration: underline;';
                    }
                    // Strike-through
                    elseif ($prop->localName === 'strike') {
                        $styles[] = 'text-decoration: line-through;';
                    }
                    // Font size
                    elseif ($prop->localName === 'sz') {
                        $halfPts = intval($prop->getAttribute('val'));
                        // Skip invalid font sizes (0 or negative)
                        if ($halfPts > 0) {
                            $pts = $halfPts / 2;
                            $styles[] = "font-size: {$pts}pt;";
                        }
                    }
                    // Color
                    elseif ($prop->localName === 'color') {
                        $color = $prop->getAttribute('val');
                        $styles[] = "color: #{$color};";
                    }
                    // Highlight/background
                    elseif ($prop->localName === 'highlight') {
                        $color = $prop->getAttribute('val');
                        // Map Word color names to hex
                        $colorMap = [
                            'yellow' => '#ffff00',
                            'green' => '#00ff00',
                            'cyan' => '#00ffff',
                            'magenta' => '#ff00ff',
                            'blue' => '#0000ff',
                            'red' => '#ff0000',
                            'darkBlue' => '#00008b',
                            'darkCyan' => '#008b8b',
                            'darkGreen' => '#006400',
                            'darkMagenta' => '#8b008b',
                            'darkRed' => '#8b0000',
                            'darkYellow' => '#808000',
                            'darkGray' => '#808080',
                            'lightGray' => '#d3d3d3',
                            'black' => '#000000',
                            'white' => '#ffffff',
                        ];
                        $hexColor = $colorMap[strtolower($color)] ?? $color;
                        $styles[] = "background-color: {$hexColor};";
                    }
                    // Font family
                    elseif ($prop->localName === 'rFonts') {
                        $ascii = $prop->getAttribute('ascii');
                        if ($ascii) {
                            $styles[] = "font-family: '{$ascii}', sans-serif;";
                        }
                    }
                }
            }
        }

        // Parse text content and other elements
        foreach ($run->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $localName = $child->localName;

                // Handle text
                if ($localName === 't') {
                    $textContent = '';
                    foreach ($child->childNodes as $textChild) {
                        if ($textChild->nodeType === XML_TEXT_NODE) {
                            $textContent .= $textChild->textContent;
                        }
                    }
                    $text .= htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8');
                }
                // Handle gambar (drawing - inline images)
                elseif ($localName === 'drawing') {
                    $text .= $this->extractImageFromDrawing($child);
                }
                // Handle gambar (pict - VML images, legacy format)
                elseif ($localName === 'pict') {
                    $text .= $this->extractImageFromPict($child);
                }
                // Handle line break
                elseif ($localName === 'br') {
                    $text .= '<br>';
                }
                // Handle tab
                elseif ($localName === 'tab') {
                    $text .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                }
            }
        }

        if ($text === '') {
            return '';
        }

        // Apply inline styles
        $styleAttr = '';
        if (!empty($styles)) {
            $styleAttr = ' style="' . implode(' ', $styles) . '"';
        }
        
        $wrappedText = $text;
        if ($styleAttr) {
            $wrappedText = "<span{$styleAttr}>$wrappedText</span>";
        }

        // Apply before/after tags (bold, italic, etc.)
        foreach ($beforeTags as $tag) {
            $wrappedText = $tag . $wrappedText;
        }
        foreach (array_reverse($afterTags) as $tag) {
            $wrappedText .= $tag;
        }

        return $wrappedText;
    }

    /**
     * Extract gambar dari <w:drawing> element
     */
    private function extractImageFromDrawing(\DOMNode $drawing): string
    {
        // Cari <a:blip r:embed="rId..."/> di dalam drawing
        foreach ($drawing->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'blip') as $blip) {
            $embedId = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
            if ($embedId && $this->relsXml) {
                $imgSrc = $this->getImageSrc($embedId, $this->relsXml);
                if ($imgSrc) {
                    // Get width/height dari extents jika ada
                    $width = 'auto';
                    $height = 'auto';
                    
                    foreach ($drawing->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'ext') as $ext) {
                        $cx = $ext->getAttribute('cx');
                        $cy = $ext->getAttribute('cy');
                        if ($cx) {
                            // Convert EMU to pixels (1 EMU = 1/914400 inch, 1 inch = 96px)
                            $width = round(intval($cx) * 96 / 914400) . 'px';
                        }
                        if ($cy) {
                            $height = round(intval($cy) * 96 / 914400) . 'px';
                        }
                    }
                    
                    return '<img src="' . $imgSrc . '" style="max-width:100%;height:auto;width:' . $width . ';max-height:' . $height . ';" />';
                }
            }
        }
        
        return '';
    }

    /**
     * Extract gambar dari <w:pict> element (legacy VML format)
     */
    private function extractImageFromPict(\DOMNode $pict): string
    {
        // Cari <v:imagedata r:id="rId..."/>
        foreach ($pict->getElementsByTagNameNS('urn:schemas-microsoft-com:vml', 'imagedata') as $imagedata) {
            $id = $imagedata->getAttributeNS('urn:schemas-microsoft-com:office:office', 'relid') 
                   ?: $imagedata->getAttribute('id');
            if ($id && $this->relsXml) {
                $imgSrc = $this->getImageSrc($id, $this->relsXml);
                if ($imgSrc) {
                    return '<img src="' . $imgSrc . '" style="max-width:100%;height:auto;" />';
                }
            }
        }
        
        return '';
    }

    /**
     * Parse table dari DOMDocument
     */
    private function parseTableDom(\DOMNode $tbl): string
    {
        // Get table style from table properties
        $tableStyle = 'border-collapse: collapse; margin: 10px 0;';
        $hasBorders = true;
        $tableWidth = '100%';
        
        foreach ($tbl->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tblPr') {
                foreach ($child->childNodes as $prop) {
                    if ($prop->nodeType === XML_ELEMENT_NODE && $prop->localName === 'tblBorders') {
                        // Check if table has borders defined
                        $hasBordersCheck = false;
                        foreach ($prop->childNodes as $border) {
                            if ($border->nodeType === XML_ELEMENT_NODE && $border->getAttribute('val') !== 'nil') {
                                $hasBordersCheck = true;
                                break;
                            }
                        }
                        if (!$hasBordersCheck) {
                            $hasBorders = false;
                        }
                    }
                    // Check for table width setting
                    elseif ($prop->nodeType === XML_ELEMENT_NODE && $prop->localName === 'tblW') {
                        $wType = $prop->getAttribute('type');
                        $wValue = $prop->getAttribute('w');
                        if ($wType === 'auto') {
                            $tableWidth = 'auto';
                            $tableStyle .= ' width: auto;';
                        } elseif ($wType === 'pct') {
                            // Percentage (in 50ths of percent, so 5000 = 100%)
                            $pct = intval($wValue) / 50;
                            $tableWidth = $pct . '%';
                            $tableStyle .= " width: {$pct}%;";
                        } elseif ($wType === 'dxa') {
                            // Twips (1/20 of point)
                            $points = intval($wValue) / 20;
                            $pixels = round($points * 1.333);
                            $tableStyle .= " width: {$pixels}px;";
                        }
                    }
                }
            }
        }
        
        if ($tableWidth === '100%') {
            $tableStyle .= ' width: 100%;';
        }
        
        $html = '<table style="' . $tableStyle . '">';

        $rowIndex = 0;
        foreach ($tbl->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tr') {
                // First row could be header
                $isHeader = ($rowIndex === 0);
                
                // Get row properties
                $rowPr = null;
                foreach ($child->childNodes as $rowChild) {
                    if ($rowChild->nodeType === XML_ELEMENT_NODE && $rowChild->localName === 'trPr') {
                        $rowPr = $rowChild;
                        foreach ($rowChild->childNodes as $prop) {
                            if ($prop->nodeType === XML_ELEMENT_NODE && $prop->localName === 'cnfStyle' && $prop->getAttribute('val') === '100000000000') {
                                $isHeader = true;
                            }
                        }
                    }
                }
                
                $html .= '<tr>';
                $colIndex = 0;
                foreach ($child->childNodes as $cell) {
                    if ($cell->nodeType === XML_ELEMENT_NODE && $cell->localName === 'tc') {
                        // Get cell properties
                        $cellStyle = '';
                        $cellWidth = '';
                        
                        // Add borders if table has them
                        if ($hasBorders) {
                            $cellStyle .= 'border: 1px solid #d1d5db; ';
                        }
                        $cellStyle .= 'padding: 6px 10px; ';
                        
                        $cellTag = $isHeader ? 'th' : 'td';
                        
                        foreach ($cell->childNodes as $cellChild) {
                            if ($cellChild->nodeType === XML_ELEMENT_NODE && $cellChild->localName === 'tcPr') {
                                foreach ($cellChild->childNodes as $prop) {
                                    if ($prop->nodeType === XML_ELEMENT_NODE) {
                                        // Cell width
                                        if ($prop->localName === 'tcW') {
                                            $wType = $prop->getAttribute('type');
                                            $wValue = $prop->getAttribute('w');
                                            if ($wType === 'pct') {
                                                $pct = intval($wValue) / 50;
                                                $cellWidth = "width: {$pct}%; ";
                                            } elseif ($wType === 'dxa') {
                                                $points = intval($wValue) / 20;
                                                $pixels = round($points * 1.333);
                                                $cellWidth = "width: {$pixels}px; ";
                                            }
                                        }
                                        // Background color/shading
                                        elseif ($prop->localName === 'shd') {
                                            $color = $prop->getAttribute('fill');
                                            if ($color && $color !== 'auto') {
                                                // Handle 3-char hex codes
                                                if (strlen($color) === 3) {
                                                    $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
                                                }
                                                if (strlen($color) === 6) {
                                                    $cellStyle .= "background-color: #{$color}; ";
                                                    // If header has dark background, set white text
                                                    $brightness = $this->getColorBrightness($color);
                                                    if ($brightness < 128) {
                                                        $cellStyle .= 'color: #ffffff; font-weight: bold; ';
                                                    }
                                                }
                                            }
                                        }
                                        // Vertical alignment
                                        elseif ($prop->localName === 'vAlign') {
                                            $valign = $prop->getAttribute('val');
                                            if ($valign) {
                                                $cellStyle .= "vertical-align: {$valign}; ";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Build cell content
                        $cellContent = '';
                        foreach ($cell->childNodes as $p) {
                            if ($p->nodeType === XML_ELEMENT_NODE && $p->localName === 'p') {
                                $cellContent .= $this->parseParagraphDom($p);
                            }
                        }
                        
                        $widthAttr = $cellWidth ? " style=\"{$cellWidth}{$cellStyle}\"" : ($cellStyle ? " style=\"{$cellStyle}\"" : '');
                        $html .= "<{$cellTag}{$widthAttr}>{$cellContent}</{$cellTag}>";
                        $colIndex++;
                    }
                }
                $html .= '</tr>';
                $rowIndex++;
            }
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Parse XML node ke HTML (legacy - tetap ada untuk backward compatibility)
     */
    private function parseXmlNode(\SimpleXMLElement $xml): string
    {
        $html = '';

        foreach ($xml->children('http://schemas.openxmlformats.org/wordprocessingml/2006/main') as $element) {
            $name = $element->getName();

            switch ($name) {
                case 'p': // Paragraph
                    $html .= $this->parseParagraph($element);
                    break;

                case 'tbl': // Table
                    $html .= $this->parseTable($element);
                    break;

                default:
                    break;
            }
        }

        return $html;
    }

    /**
     * Parse paragraph
     */
    private function parseParagraph(\SimpleXMLElement $p): string
    {
        // Cek properties paragraph (heading, alignment, dll)
        $props = $p->children('http://schemas.openxmlformats.org/wordprocessingml/2006/main')->pPr;
        $tag = 'p';
        $style = '';
        $alignment = '';

        if ($props && $props->pStyle) {
            $styleAttr = (string) $props->pStyle->attributes()->val;
            if (strpos($styleAttr, 'Heading') !== false) {
                $level = preg_match('/Heading(\d+)/', $styleAttr, $matches) ? $matches[1] : 1;
                $tag = 'h' . min($level, 6);
            }
        }

        if ($props && $props->jc) {
            $jc = (string) $props->jc->attributes()->val;
            $alignment = match ($jc) {
                'center' => 'text-align: center;',
                'right' => 'text-align: right;',
                'both' => 'text-align: justify;',
                default => ''
            };
        }

        // Parse runs (text dengan formatting)
        $text = '';
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        foreach ($p->children($ns) as $run) {
            if ($run->getName() === 'r') {
                $text .= $this->parseRun($run);
            } elseif ($run->getName() === 'tab') {
                $text .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            } elseif ($run->getName() === 'br') {
                $text .= '<br>';
            }
        }

        if (trim($text) === '') {
            return '<br>';
        }

        $styleAttr = $style ? " style=\"$style\"" : '';
        $alignAttr = $alignment ? " style=\"$alignment\"" : '';

        return "<$tag$alignAttr>$text</$tag>";
    }

    /**
     * Parse run (text dengan formatting)
     */
    private function parseRun(\SimpleXMLElement $run): string
    {
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $text = htmlspecialchars((string) $run->t, ENT_QUOTES, 'UTF-8');

        if ($text === '') {
            return '';
        }

        // Cek formatting
        $rPr = $run->rPr;
        $tags = [];

        if ($rPr) {
            if ($rPr->b) $tags[] = 'strong';
            if ($rPr->i) $tags[] = 'em';
            if ($rPr->u) $tags[] = 'u';

            // Font size
            if ($rPr->sz) {
                $halfPts = (int) $rPr->sz->attributes()->val;
                $pts = $halfPts / 2;
                $style = "font-size: {$pts}pt;";
                $text = "<span style=\"$style\">$text</span>";
            }

            // Color
            if ($rPr->color) {
                $color = (string) $rPr->color->attributes()->val;
                $text = "<span style=\"color: #$color;\">$text</span>";
            }

            // Highlight
            if ($rPr->highlight) {
                $color = (string) $rPr->highlight->attributes()->val;
                $text = "<span style=\"background-color: $color;\">$text</span>";
            }
        }

        // Wrap dengan tags
        foreach (array_reverse($tags) as $t) {
            $text = "<$t>$text</$t>";
        }

        return $text;
    }

    /**
     * Parse table
     */
    private function parseTable(\SimpleXMLElement $tbl): string
    {
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $html = '<table style="border-collapse: collapse; width: 100%; margin: 10px 0;">';

        foreach ($tbl->children($ns)->tr as $row) {
            $html .= '<tr>';
            foreach ($row->children($ns)->tc as $cell) {
                $cellText = '';
                foreach ($cell->children($ns)->p as $p) {
                    $cellText .= $this->parseParagraph($p);
                }
                $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $cellText . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Calculate brightness of hex color to determine if text should be white or black
     * Returns value 0-255 (0=black, 255=white)
     */
    private function getColorBrightness(string $hexColor): int
    {
        // Convert hex to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Calculate brightness using perceived luminance formula
        return (int) (($r * 299 + $g * 587 + $b * 114) / 1000);
    }
}
