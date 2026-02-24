<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ProductMediaFileRule implements ValidationRule
{
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    private const MAX_VIDEO_BYTES = 100 * 1024 * 1024;
    private const MAX_VIDEO_DURATION_SECONDS = 30;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $mimeType = (string) $value->getMimeType();
        $size = (int) $value->getSize();

        if ($this->isImage($mimeType)) {
            if ($size > self::MAX_IMAGE_BYTES) {
                $fail('Each image must be at most 5MB.');
            }

            return;
        }

        if ($this->isVideo($mimeType)) {
            if ($size > self::MAX_VIDEO_BYTES) {
                $fail('Each video must be at most 100MB.');
                return;
            }

            $duration = self::detectVideoDurationSeconds($value);

            if ($duration === null) {
                $fail('Could not read video duration. Please upload an MP4/MOV/AVI/WebM video up to 30 seconds.');
                return;
            }

            if ($duration > self::MAX_VIDEO_DURATION_SECONDS) {
                $fail('Each video must be 30 seconds or less.');
            }
        }
    }

    public static function detectVideoDurationSeconds(UploadedFile $file): ?float
    {
        $path = $file->getPathname();
        if (!is_string($path) || $path === '') {
            return null;
        }

        $output = @shell_exec(
            'ffprobe -v error -show_entries format=duration -of default=nokey=1:noprint_wrappers=1 '
            . escapeshellarg($path)
            . ' 2>/dev/null'
        );

        if (!is_string($output)) {
            return null;
        }

        $duration = (float) trim($output);
        return $duration > 0 ? $duration : null;
    }

    private function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    private function isVideo(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }
}
