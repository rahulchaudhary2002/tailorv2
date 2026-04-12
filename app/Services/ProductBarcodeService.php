<?php

namespace App\Services;

class ProductBarcodeService
{
    public function generateForProductId(int $productId): string
    {
        $base = '20' . str_pad((string) $productId, 10, '0', STR_PAD_LEFT);

        return $base . $this->checksumDigit($base);
    }

    public function isValid(string $barcode): bool
    {
        $barcode = trim($barcode);

        if (!preg_match('/^\d{13}$/', $barcode)) {
            return false;
        }

        return $barcode === $this->normalize($barcode);
    }

    public function normalize(string $barcode): string
    {
        $barcode = preg_replace('/\D+/', '', $barcode) ?? '';
        $base = substr($barcode, 0, 12);

        if (strlen($base) !== 12) {
            return $barcode;
        }

        return $base . $this->checksumDigit($base);
    }

    public function renderSvg(string $barcode, int $barWidth = 2, int $height = 72): string
    {
        $normalized = $this->normalize($barcode);

        if (!$this->isValid($normalized)) {
            return '';
        }

        $patterns = [
            'A' => ['0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101', '4' => '0100011', '5' => '0110001', '6' => '0101111', '7' => '0111011', '8' => '0110111', '9' => '0001011'],
            'B' => ['0' => '0100111', '1' => '0110011', '2' => '0011011', '3' => '0100001', '4' => '0011101', '5' => '0111001', '6' => '0000101', '7' => '0010001', '8' => '0001001', '9' => '0010111'],
            'C' => ['0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010', '4' => '1011100', '5' => '1001110', '6' => '1010000', '7' => '1000100', '8' => '1001000', '9' => '1110100'],
        ];
        $parityMap = [
            '0' => 'AAAAAA', '1' => 'AABABB', '2' => 'AABBAB', '3' => 'AABBBA', '4' => 'ABAABB',
            '5' => 'ABBAAB', '6' => 'ABBBAA', '7' => 'ABABAB', '8' => 'ABABBA', '9' => 'ABBABA',
        ];

        $digits = str_split($normalized);
        $first = array_shift($digits);
        $left = array_slice($digits, 0, 6);
        $right = array_slice($digits, 6, 6);
        $parity = str_split($parityMap[$first]);

        $binary = '101';
        foreach ($left as $index => $digit) {
            $binary .= $patterns[$parity[$index]][$digit];
        }

        $binary .= '01010';

        foreach ($right as $digit) {
            $binary .= $patterns['C'][$digit];
        }

        $binary .= '101';

        $margin = 10;
        $textHeight = 18;
        $fullHeight = $height + $textHeight;
        $width = (strlen($binary) * $barWidth) + ($margin * 2);
        $guardHeight = $height + 10;

        $bars = [];
        foreach (str_split($binary) as $index => $bit) {
            if ($bit !== '1') {
                continue;
            }

            $isGuard = $index < 3 || ($index >= 45 && $index < 50) || $index >= 92;
            $bars[] = sprintf(
                '<rect x="%d" y="0" width="%d" height="%d" fill="#111827" />',
                $margin + ($index * $barWidth),
                $barWidth,
                $isGuard ? $guardHeight : $height
            );
        }

        $fontSize = 14;
        $textY = $fullHeight - 2;
        $leftText = implode('', $left);
        $rightText = implode('', $right);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" role="img" aria-label="Barcode %3$s">' .
            '<rect width="%1$d" height="%2$d" fill="#ffffff" />%4$s' .
            '<text x="%5$d" y="%6$d" font-size="%7$d" font-family="monospace" fill="#111827">%8$s</text>' .
            '<text x="%9$d" y="%6$d" font-size="%7$d" font-family="monospace" fill="#111827">%10$s</text>' .
            '<text x="%11$d" y="%6$d" font-size="%7$d" font-family="monospace" fill="#111827">%12$s</text>' .
            '</svg>',
            $width,
            $fullHeight,
            htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8'),
            implode('', $bars),
            0,
            $textY,
            $fontSize,
            $first,
            $margin + (3 * $barWidth) + 6,
            $leftText,
            $margin + (50 * $barWidth) + 6,
            $rightText
        );
    }

    private function checksumDigit(string $payload): int
    {
        $digits = str_split($payload);
        $sum = 0;
        $length = count($digits);

        foreach ($digits as $index => $digit) {
            $positionFromRight = $length - $index;
            $sum += ((int) $digit) * ($positionFromRight % 2 === 0 ? 3 : 1);
        }

        return (10 - ($sum % 10)) % 10;
    }
}
