<?php

namespace App\Services;

class ProductBarcodeService
{
    public function renderCode128Svg(string $data, int $barWidth = 2, int $height = 72): string
    {
        if ($data === '') {
            return '';
        }

        // Code 128 symbol patterns (index = symbol value, 1=bar, 0=space)
        $patterns = [
            '11011001100', '11001101100', '11001100110', '10010011000',
            '10010001100', '10001001100', '10011001000', '10011000100',
            '10001100100', '11001001000', '11001000100', '11000100100',
            '10110011100', '10011011100', '10011001110', '10111001100',
            '10011101100', '10011100110', '11001110010', '11001011100',
            '11001001110', '11011100100', '11001110100', '11101101110',
            '11101001100', '11100101100', '11100100110', '11101100100',
            '11100110100', '11100110010', '11011011000', '11011000110',
            '11000110110', '10100011000', '10001011000', '10001000110',
            '10110001000', '10001101000', '10001100010', '11010001000',
            '11000101000', '11000100010', '10110111000', '10110001110',
            '10001101110', '10111011000', '10111000110', '10001110110',
            '11101110110', '11010001110', '11000101110', '11011101000',
            '11011100010', '11011101110', '11101011000', '11101000110',
            '11100010110', '11101101000', '11101100010', '11100011010',
            '11101111010', '11001000010', '11110001010', '10100110000',
            '10100001100', '10010110000', '10010000110', '10000101100',
            '10000100110', '10110010000', '10110000100', '10011010000',
            '10011000010', '10000110100', '10000110010', '11000010010',
            '11001010000', '11110111010', '11000010100', '10001111010',
            '10100111100', '10010111100', '10010011110', '10111100100',
            '10011110100', '10011110010', '11110100100', '11110010100',
            '11110010010', '11011011110', '11011110110', '11110110110',
            '10101111000', '10100011110', '10001011110', '10111101000',
            '10111100010', '11110101000', '11110100010', '10111011110',
            '10111101110', '11101011110', '11110101110',
            '11010000100', // 103 START A
            '11010010000', // 104 START B
            '11010011110', // 105 START C
        ];
        $stopPattern = '1100011101011';

        // Code 128B: value = ASCII - 32, supports characters 32–126
        $startValue = 104;
        $symbolValues = [];

        for ($i = 0; $i < strlen($data); $i++) {
            $ascii = ord($data[$i]);

            if ($ascii < 32 || $ascii > 126) {
                return '';
            }

            $symbolValues[] = $ascii - 32;
        }

        $checksum = $startValue;

        foreach ($symbolValues as $position => $value) {
            $checksum += ($position + 1) * $value;
        }

        $checksum %= 103;

        $binary = $patterns[$startValue];

        foreach ($symbolValues as $value) {
            $binary .= $patterns[$value];
        }

        $binary .= $patterns[$checksum];
        $binary .= $stopPattern;

        $margin = 10;
        $textHeight = 18;
        $fullHeight = $height + $textHeight;
        $width = (strlen($binary) * $barWidth) + ($margin * 2);

        $bars = [];

        foreach (str_split($binary) as $index => $bit) {
            if ($bit === '1') {
                $bars[] = sprintf(
                    '<rect x="%d" y="0" width="%d" height="%d" fill="#111827" />',
                    $margin + ($index * $barWidth),
                    $barWidth,
                    $height
                );
            }
        }

        $textX = (int) round($width / 2);
        $textY = $fullHeight - 2;

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" role="img" aria-label="Barcode %3$s">'.
            '<rect width="%1$d" height="%2$d" fill="#ffffff" />'.
            '%4$s'.
            '<text x="%5$d" y="%6$d" font-size="12" font-family="monospace" fill="#111827" text-anchor="middle">%3$s</text>'.
            '</svg>',
            $width,
            $fullHeight,
            htmlspecialchars($data, ENT_QUOTES, 'UTF-8'),
            implode('', $bars),
            $textX,
            $textY,
        );
    }
}
