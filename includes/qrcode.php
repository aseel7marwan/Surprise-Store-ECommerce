<?php
/**
 * Surprise! Store - Minimal QR Code Generator
 * Generates QR codes as inline SVG — no external libraries
 * 
 * Based on a minimal QR encoder supporting alphanumeric/byte mode
 * Optimized for otpauth:// URIs (typically ~100 chars)
 * 
 * @version 6.0.0
 */

/**
 * Generate QR code as SVG string
 * @param string $data Data to encode
 * @param int $pixelSize Size of each module in pixels
 * @param int $margin Quiet zone modules
 * @return string SVG markup
 */
function generateQRCodeSVG($data, $pixelSize = 6, $margin = 2) {
    $matrix = generateQRMatrix($data);
    if (!$matrix) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><text x="10" y="100" fill="red">QR Error</text></svg>';
    }
    
    $size = count($matrix);
    $totalSize = ($size + $margin * 2) * $pixelSize;
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $totalSize . '" height="' . $totalSize . '" viewBox="0 0 ' . $totalSize . ' ' . $totalSize . '">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';
    
    // Draw dark modules as a single path for efficiency
    $path = '';
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if ($matrix[$y][$x]) {
                $px = ($x + $margin) * $pixelSize;
                $py = ($y + $margin) * $pixelSize;
                $path .= 'M' . $px . ',' . $py . 'h' . $pixelSize . 'v' . $pixelSize . 'h-' . $pixelSize . 'z';
            }
        }
    }
    
    $svg .= '<path d="' . $path . '" fill="black"/>';
    $svg .= '</svg>';
    
    return $svg;
}

/**
 * Generate QR code matrix using Google Charts API fallback
 * For simplicity and reliability, we use a PHP-native approach
 * This generates a QR matrix for byte-mode data
 */
function generateQRMatrix($data) {
    // Use the built-in PHP QR generation via a compact implementation
    // We encode using Version auto-detection, Error Correction Level M
    
    $qr = new SimpleQREncoder();
    return $qr->encode($data);
}

/**
 * Minimal QR Code Encoder
 * Supports byte mode encoding with error correction level M
 * Handles data up to ~120 bytes (sufficient for otpauth URIs)
 */
class SimpleQREncoder {
    private $modules = [];
    private $size = 0;
    private $version = 0;
    
    // Galois Field tables for Reed-Solomon
    private $gfExp = [];
    private $gfLog = [];
    
    // Version capacities for EC level M (byte mode)
    private $capacities = [
        1 => 14, 2 => 26, 3 => 42, 4 => 62, 5 => 84,
        6 => 106, 7 => 122, 8 => 152, 9 => 180, 10 => 213,
        11 => 251, 12 => 287, 13 => 331, 14 => 362, 15 => 412,
        16 => 450, 17 => 504, 18 => 560, 19 => 611, 20 => 661
    ];
    
    // EC codewords per block for level M
    private $ecCodewords = [
        1 => 10, 2 => 16, 3 => 26, 4 => 18, 5 => 24,
        6 => 16, 7 => 18, 8 => 22, 9 => 22, 10 => 26,
        11 => 30, 12 => 22, 13 => 22, 14 => 24, 15 => 24,
        16 => 28, 17 => 28, 18 => 26, 19 => 26, 20 => 26
    ];
    
    // Number of EC blocks for level M
    private $ecBlocks = [
        1 => [1], 2 => [1], 3 => [1], 4 => [2], 5 => [2],
        6 => [4], 7 => [4], 8 => [2,2], 9 => [3,2], 10 => [4,1],
        11 => [1,4], 12 => [6,2], 13 => [8,1], 14 => [4,5], 15 => [5,5],
        16 => [7,3], 17 => [10,1], 18 => [9,4], 19 => [3,11], 20 => [3,13]
    ];
    
    // Data codewords per block for level M  
    private $dataPerBlock = [
        1 => [16], 2 => [28], 3 => [44], 4 => [32], 5 => [43],
        6 => [27], 7 => [31], 8 => [38,39], 9 => [36,37], 10 => [43,44],
        11 => [50,51], 12 => [36,37], 13 => [37,38], 14 => [40,41], 15 => [41,42],
        16 => [45,46], 17 => [46,47], 18 => [43,44], 19 => [44,45], 20 => [41,42]
    ];
    
    // Alignment pattern positions
    private $alignmentPositions = [
        2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46],
        10 => [6, 28, 50], 11 => [6, 30, 54], 12 => [6, 32, 58], 13 => [6, 34, 62],
        14 => [6, 26, 46, 66], 15 => [6, 26, 48, 70], 16 => [6, 26, 50, 74],
        17 => [6, 30, 54, 78], 18 => [6, 30, 56, 82], 19 => [6, 30, 58, 86],
        20 => [6, 34, 62, 90]
    ];
    
    public function encode($data) {
        $this->initGaloisField();
        
        // Determine version
        $dataLen = strlen($data);
        $this->version = 0;
        foreach ($this->capacities as $v => $cap) {
            if ($dataLen <= $cap) {
                $this->version = $v;
                break;
            }
        }
        
        if ($this->version === 0) return null;
        
        $this->size = 17 + $this->version * 4;
        
        // Initialize modules
        $this->modules = array_fill(0, $this->size, array_fill(0, $this->size, null));
        
        // Place function patterns
        $this->placeFinderPatterns();
        $this->placeAlignmentPatterns();
        $this->placeTimingPatterns();
        $this->placeDarkModule();
        $this->reserveFormatInfo();
        if ($this->version >= 7) {
            $this->reserveVersionInfo();
        }
        
        // Encode data
        $bits = $this->encodeData($data);
        
        // Add error correction
        $codewords = $this->bitsToCodewords($bits);
        $finalData = $this->addErrorCorrection($codewords);
        
        // Place data
        $this->placeData($finalData);
        
        // Apply best mask
        $bestMask = $this->selectBestMask();
        $this->applyMask($bestMask);
        
        // Write format info
        $this->writeFormatInfo($bestMask);
        if ($this->version >= 7) {
            $this->writeVersionInfo();
        }
        
        // Convert null to 0
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->modules[$y][$x] === null) {
                    $this->modules[$y][$x] = 0;
                }
            }
        }
        
        return $this->modules;
    }
    
    private function initGaloisField() {
        $this->gfExp = array_fill(0, 512, 0);
        $this->gfLog = array_fill(0, 256, 0);
        
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $this->gfExp[$i] = $x;
            $this->gfLog[$x] = $i;
            $x <<= 1;
            if ($x & 256) {
                $x ^= 0x11D; // QR polynomial
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $this->gfExp[$i] = $this->gfExp[$i - 255];
        }
    }
    
    private function placeFinderPatterns() {
        $positions = [[0, 0], [0, $this->size - 7], [$this->size - 7, 0]];
        
        foreach ($positions as $pos) {
            $row = $pos[0];
            $col = $pos[1];
            
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    if (($r === 0 || $r === 6 || $c === 0 || $c === 6) ||
                        ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                        $this->modules[$row + $r][$col + $c] = 1;
                    } else {
                        $this->modules[$row + $r][$col + $c] = 0;
                    }
                }
            }
            
            // Separators
            for ($i = -1; $i <= 7; $i++) {
                $this->setModule($row - 1, $col + $i, 0);
                $this->setModule($row + 7, $col + $i, 0);
                $this->setModule($row + $i, $col - 1, 0);
                $this->setModule($row + $i, $col + 7, 0);
            }
        }
    }
    
    private function setModule($row, $col, $val) {
        if ($row >= 0 && $row < $this->size && $col >= 0 && $col < $this->size) {
            $this->modules[$row][$col] = $val;
        }
    }
    
    private function placeAlignmentPatterns() {
        if ($this->version < 2) return;
        
        $positions = $this->alignmentPositions[$this->version];
        $count = count($positions);
        
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $row = $positions[$i];
                $col = $positions[$j];
                
                // Skip if overlaps with finder patterns
                if ($this->modules[$row][$col] !== null) continue;
                
                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        if (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0)) {
                            $this->modules[$row + $r][$col + $c] = 1;
                        } else {
                            $this->modules[$row + $r][$col + $c] = 0;
                        }
                    }
                }
            }
        }
    }
    
    private function placeTimingPatterns() {
        for ($i = 8; $i < $this->size - 8; $i++) {
            if ($this->modules[6][$i] === null) {
                $this->modules[6][$i] = ($i % 2 === 0) ? 1 : 0;
            }
            if ($this->modules[$i][6] === null) {
                $this->modules[$i][6] = ($i % 2 === 0) ? 1 : 0;
            }
        }
    }
    
    private function placeDarkModule() {
        $this->modules[4 * $this->version + 9][8] = 1;
    }
    
    private function reserveFormatInfo() {
        // Reserve format info areas (will be written after masking)
        for ($i = 0; $i < 8; $i++) {
            if ($this->modules[8][$i] === null) $this->modules[8][$i] = 0;
            if ($this->modules[$i][8] === null) $this->modules[$i][8] = 0;
        }
        if ($this->modules[8][7] === null) $this->modules[8][7] = 0;
        if ($this->modules[8][8] === null) $this->modules[8][8] = 0;
        if ($this->modules[7][8] === null) $this->modules[7][8] = 0;
        
        for ($i = 0; $i < 8; $i++) {
            if ($this->modules[$this->size - 1 - $i][8] === null) $this->modules[$this->size - 1 - $i][8] = 0;
            if ($this->modules[8][$this->size - 8 + $i] === null) $this->modules[8][$this->size - 8 + $i] = 0;
        }
    }
    
    private function reserveVersionInfo() {
        for ($i = 0; $i < 6; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($this->modules[$i][$this->size - 11 + $j] === null) 
                    $this->modules[$i][$this->size - 11 + $j] = 0;
                if ($this->modules[$this->size - 11 + $j][$i] === null) 
                    $this->modules[$this->size - 11 + $j][$i] = 0;
            }
        }
    }
    
    private function encodeData($data) {
        $bits = '';
        
        // Mode indicator: Byte mode = 0100
        $bits .= '0100';
        
        // Character count (8 bits for v1-9, 16 bits for v10+)
        $countBits = ($this->version <= 9) ? 8 : 16;
        $bits .= str_pad(decbin(strlen($data)), $countBits, '0', STR_PAD_LEFT);
        
        // Data
        for ($i = 0; $i < strlen($data); $i++) {
            $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        
        // Terminator (up to 4 zeros)
        $totalBits = $this->getTotalDataBits();
        $remaining = $totalBits - strlen($bits);
        $bits .= str_repeat('0', min(4, $remaining));
        
        // Pad to byte boundary
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }
        
        // Pad with alternating bytes
        $padBytes = ['11101100', '00010001'];
        $padIndex = 0;
        while (strlen($bits) < $totalBits) {
            $bits .= $padBytes[$padIndex % 2];
            $padIndex++;
        }
        
        return substr($bits, 0, $totalBits);
    }
    
    private function getTotalDataBits() {
        $blocks = $this->ecBlocks[$this->version];
        $dataPerBlock = $this->dataPerBlock[$this->version];
        
        $total = 0;
        if (count($blocks) === 1) {
            $total = $blocks[0] * $dataPerBlock[0];
        } else {
            $total = $blocks[0] * $dataPerBlock[0] + $blocks[1] * $dataPerBlock[1];
        }
        
        return $total * 8;
    }
    
    private function bitsToCodewords($bits) {
        $codewords = [];
        $chunks = str_split($bits, 8);
        foreach ($chunks as $chunk) {
            $codewords[] = bindec(str_pad($chunk, 8, '0', STR_PAD_RIGHT));
        }
        return $codewords;
    }
    
    private function addErrorCorrection($dataCodewords) {
        $blocks = $this->ecBlocks[$this->version];
        $dataPerBlock = $this->dataPerBlock[$this->version];
        $ecPerBlock = $this->ecCodewords[$this->version];
        
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;
        
        $numGroups = count($blocks);
        
        for ($g = 0; $g < $numGroups; $g++) {
            $numBlocks = $blocks[$g];
            $blockSize = $dataPerBlock[$g];
            
            for ($b = 0; $b < $numBlocks; $b++) {
                $block = array_slice($dataCodewords, $offset, $blockSize);
                $dataBlocks[] = $block;
                $ecBlocks[] = $this->reedSolomonEncode($block, $ecPerBlock);
                $offset += $blockSize;
            }
        }
        
        // Interleave data blocks
        $result = [];
        $maxDataLen = 0;
        foreach ($dataBlocks as $block) {
            $maxDataLen = max($maxDataLen, count($block));
        }
        
        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($dataBlocks as $block) {
                if ($i < count($block)) {
                    $result[] = $block[$i];
                }
            }
        }
        
        // Interleave EC blocks
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $block) {
                if ($i < count($block)) {
                    $result[] = $block[$i];
                }
            }
        }
        
        return $result;
    }
    
    private function reedSolomonEncode($data, $ecLen) {
        // Generate generator polynomial
        $gen = [1];
        for ($i = 0; $i < $ecLen; $i++) {
            $gen = $this->gfPolyMultiply($gen, [1, $this->gfExp[$i]]);
        }
        
        // Polynomial division
        $msg = array_merge($data, array_fill(0, $ecLen, 0));
        
        for ($i = 0; $i < count($data); $i++) {
            $coef = $msg[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($gen); $j++) {
                    $msg[$i + $j] ^= $this->gfMultiply($gen[$j], $coef);
                }
            }
        }
        
        return array_slice($msg, count($data));
    }
    
    private function gfMultiply($a, $b) {
        if ($a === 0 || $b === 0) return 0;
        return $this->gfExp[$this->gfLog[$a] + $this->gfLog[$b]];
    }
    
    private function gfPolyMultiply($p1, $p2) {
        $result = array_fill(0, count($p1) + count($p2) - 1, 0);
        for ($i = 0; $i < count($p1); $i++) {
            for ($j = 0; $j < count($p2); $j++) {
                $result[$i + $j] ^= $this->gfMultiply($p1[$i], $p2[$j]);
            }
        }
        return $result;
    }
    
    private function placeData($data) {
        $bitIndex = 0;
        $bits = '';
        foreach ($data as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }
        
        $col = $this->size - 1;
        $goingUp = true;
        
        while ($col > 0) {
            // Skip timing pattern column
            if ($col === 6) $col--;
            
            $rowRange = $goingUp 
                ? range($this->size - 1, 0, -1) 
                : range(0, $this->size - 1);
            
            foreach ($rowRange as $row) {
                for ($c = 0; $c < 2; $c++) {
                    $curCol = $col - $c;
                    if ($curCol < 0) continue;
                    
                    if ($this->modules[$row][$curCol] !== null) continue;
                    
                    $dark = false;
                    if ($bitIndex < strlen($bits)) {
                        $dark = $bits[$bitIndex] === '1';
                        $bitIndex++;
                    }
                    
                    $this->modules[$row][$curCol] = $dark ? 1 : 0;
                }
            }
            
            $col -= 2;
            $goingUp = !$goingUp;
        }
    }
    
    private function selectBestMask() {
        $bestMask = 0;
        $bestScore = PHP_INT_MAX;
        
        for ($mask = 0; $mask < 8; $mask++) {
            // Save state
            $saved = [];
            for ($y = 0; $y < $this->size; $y++) {
                $saved[$y] = $this->modules[$y];
            }
            
            $this->applyMask($mask);
            $score = $this->calculatePenalty();
            
            // Restore state
            for ($y = 0; $y < $this->size; $y++) {
                $this->modules[$y] = $saved[$y];
            }
            
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestMask = $mask;
            }
        }
        
        return $bestMask;
    }
    
    private function applyMask($mask) {
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if ($this->isDataModule($row, $col)) {
                    $invert = false;
                    switch ($mask) {
                        case 0: $invert = (($row + $col) % 2 === 0); break;
                        case 1: $invert = ($row % 2 === 0); break;
                        case 2: $invert = ($col % 3 === 0); break;
                        case 3: $invert = (($row + $col) % 3 === 0); break;
                        case 4: $invert = ((floor($row / 2) + floor($col / 3)) % 2 === 0); break;
                        case 5: $invert = (($row * $col) % 2 + ($row * $col) % 3 === 0); break;
                        case 6: $invert = ((($row * $col) % 2 + ($row * $col) % 3) % 2 === 0); break;
                        case 7: $invert = ((($row + $col) % 2 + ($row * $col) % 3) % 2 === 0); break;
                    }
                    if ($invert) {
                        $this->modules[$row][$col] ^= 1;
                    }
                }
            }
        }
    }
    
    private function isDataModule($row, $col) {
        // Check if this position is a data module (not function pattern)
        // Finder patterns
        if ($row < 9 && $col < 9) return false;
        if ($row < 9 && $col >= $this->size - 8) return false;
        if ($row >= $this->size - 8 && $col < 9) return false;
        
        // Timing patterns
        if ($row === 6 || $col === 6) return false;
        
        // Alignment patterns (approximate check for overlap)
        if ($this->version >= 2) {
            $positions = $this->alignmentPositions[$this->version];
            foreach ($positions as $py) {
                foreach ($positions as $px) {
                    if (abs($row - $py) <= 2 && abs($col - $px) <= 2) {
                        // Check not overlapping with finder
                        if (!($py < 9 && $px < 9) && !($py < 9 && $px >= $this->size - 8) && !($py >= $this->size - 8 && $px < 9)) {
                            return false;
                        }
                    }
                }
            }
        }
        
        // Version info
        if ($this->version >= 7) {
            if ($col >= $this->size - 11 && $col < $this->size - 8 && $row < 6) return false;
            if ($row >= $this->size - 11 && $row < $this->size - 8 && $col < 6) return false;
        }
        
        // Dark module
        if ($row === 4 * $this->version + 9 && $col === 8) return false;
        
        return true;
    }
    
    private function calculatePenalty() {
        $score = 0;
        
        // Rule 1: Consecutive same-color modules
        for ($row = 0; $row < $this->size; $row++) {
            $count = 1;
            for ($col = 1; $col < $this->size; $col++) {
                if ($this->modules[$row][$col] === $this->modules[$row][$col - 1]) {
                    $count++;
                    if ($count === 5) $score += 3;
                    elseif ($count > 5) $score++;
                } else {
                    $count = 1;
                }
            }
        }
        
        for ($col = 0; $col < $this->size; $col++) {
            $count = 1;
            for ($row = 1; $row < $this->size; $row++) {
                if ($this->modules[$row][$col] === $this->modules[$row - 1][$col]) {
                    $count++;
                    if ($count === 5) $score += 3;
                    elseif ($count > 5) $score++;
                } else {
                    $count = 1;
                }
            }
        }
        
        // Rule 2: 2x2 blocks
        for ($row = 0; $row < $this->size - 1; $row++) {
            for ($col = 0; $col < $this->size - 1; $col++) {
                $val = $this->modules[$row][$col];
                if ($val === $this->modules[$row][$col + 1] &&
                    $val === $this->modules[$row + 1][$col] &&
                    $val === $this->modules[$row + 1][$col + 1]) {
                    $score += 3;
                }
            }
        }
        
        return $score;
    }
    
    private function writeFormatInfo($mask) {
        // Error correction level M = 00, mask pattern
        $formatInfo = (0b00 << 3) | $mask;
        
        // BCH encoding
        $data = $formatInfo << 10;
        $generator = 0b10100110111;
        
        $bits = $data;
        for ($i = 14; $i >= 10; $i--) {
            if ($bits & (1 << $i)) {
                $bits ^= $generator << ($i - 10);
            }
        }
        
        $formatBits = (($formatInfo << 10) | $bits) ^ 0b101010000010010;
        
        // Place format info
        $formatPositions1 = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5],
            [8, 7], [8, 8], [7, 8], [5, 8], [4, 8], [3, 8],
            [2, 8], [1, 8], [0, 8]
        ];
        
        $formatPositions2 = [];
        for ($i = 0; $i < 7; $i++) {
            $formatPositions2[] = [$this->size - 1 - $i, 8];
        }
        for ($i = 0; $i < 8; $i++) {
            $formatPositions2[] = [8, $this->size - 8 + $i];
        }
        
        for ($i = 0; $i < 15; $i++) {
            $bit = ($formatBits >> (14 - $i)) & 1;
            $this->modules[$formatPositions1[$i][0]][$formatPositions1[$i][1]] = $bit;
            $this->modules[$formatPositions2[$i][0]][$formatPositions2[$i][1]] = $bit;
        }
    }
    
    private function writeVersionInfo() {
        if ($this->version < 7) return;
        
        // Version info encoding
        $versionInfo = $this->version;
        $data = $versionInfo << 12;
        $generator = 0b1111100100101;
        
        $bits = $data;
        for ($i = 17; $i >= 12; $i--) {
            if ($bits & (1 << $i)) {
                $bits ^= $generator << ($i - 12);
            }
        }
        
        $versionBits = ($versionInfo << 12) | $bits;
        
        for ($i = 0; $i < 18; $i++) {
            $bit = ($versionBits >> $i) & 1;
            $row = floor($i / 3);
            $col = $this->size - 11 + ($i % 3);
            $this->modules[$row][$col] = $bit;
            $this->modules[$col][$row] = $bit;
        }
    }
}
