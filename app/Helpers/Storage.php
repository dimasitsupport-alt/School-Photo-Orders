<?php

declare(strict_types=1);

namespace App\Helpers;

class Storage
{
    private const MAX_IMAGE_SIZE = 20 * 1024 * 1024;

    private const COMPRESSION_THRESHOLD = 7 * 1024 * 1024;

    private const MAX_COMPRESSED_SIZE = 10 * 1024 * 1024;

    private const MAX_IMAGE_DIMENSION = 10000;

    private const BACKGROUND_TOLERANCE = 58;

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function storeImage(array $file, string $folder, array $options = []): string
    {
        self::validateFolder($folder);

        $upload = self::validateUpload($file);
        $removeBackground = (bool) ($options['remove_background'] ?? false);
        $shouldCompress = $upload['size'] > self::COMPRESSION_THRESHOLD;
        $extension = $removeBackground ? 'png' : ($shouldCompress ? 'jpg' : $upload['extension']);
        $fileName = self::uuid() . '.' . $extension;
        $targetDir = PUBLIC_PATH . '/uploads/' . $folder;
        $targetPath = $targetDir . '/' . $fileName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if ($removeBackground) {
            self::storeBackgroundRemovedPng($upload['tmp_name'], $targetPath);
        } elseif ($shouldCompress) {
            self::storeCompressedJpeg($upload['tmp_name'], $targetPath);
        } elseif (!move_uploaded_file($upload['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Gagal menyimpan file upload.');
        }

        return 'uploads/' . $folder . '/' . $fileName;
    }

    public static function normalizeFiles(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [$files];
        }

        $normalized = [];
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            $normalized[] = [
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];
        }

        return $normalized;
    }

    public static function deletePublicFile(string $relativePath): void
    {
        $uploadsRoot = realpath(PUBLIC_PATH . '/uploads');
        $path = realpath(PUBLIC_PATH . '/' . ltrim($relativePath, '/'));

        if (
            $uploadsRoot !== false
            && $path !== false
            && str_starts_with($path, $uploadsRoot . DIRECTORY_SEPARATOR)
            && is_file($path)
        ) {
            unlink($path);
        }
    }

    private static function validateFolder(string $folder): void
    {
        if (!in_array($folder, ['logos', 'photos'], true)) {
            throw new \InvalidArgumentException('Folder upload tidak valid.');
        }
    }

    private static function validateUpload(array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadErrorMessage($error));
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            throw new \RuntimeException('File upload kosong atau tidak valid.');
        }

        if ($size > self::MAX_IMAGE_SIZE) {
            throw new \RuntimeException('Ukuran foto maksimal 20 MB per file.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \RuntimeException('Format file wajib JPG, JPEG, PNG, atau WEBP.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new \RuntimeException('File upload tidak valid.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);

        if (!is_string($mimeType) || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \RuntimeException('MIME type file tidak valid.');
        }

        $imageInfo = @getimagesize($tmpName);

        if ($imageInfo === false) {
            throw new \RuntimeException('File gambar tidak valid atau rusak.');
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);

        if ($width <= 0 || $height <= 0 || max($width, $height) > self::MAX_IMAGE_DIMENSION) {
            throw new \RuntimeException('Dimensi gambar tidak valid atau terlalu besar.');
        }

        return [
            'tmp_name' => $tmpName,
            'size' => $size,
            'mime_type' => $mimeType,
            'extension' => self::MIME_EXTENSIONS[$mimeType],
        ];
    }

    private static function storeCompressedJpeg(string $sourcePath, string $targetPath): void
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            throw new \RuntimeException('Kompresi foto membutuhkan ekstensi PHP GD.');
        }

        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new \RuntimeException('Gagal membaca file upload untuk kompresi.');
        }

        $sourceImage = @imagecreatefromstring($contents);
        unset($contents);

        if ($sourceImage === false) {
            throw new \RuntimeException('Gagal memproses gambar untuk kompresi.');
        }

        $image = self::copyToJpegCanvas($sourceImage);
        imagedestroy($sourceImage);

        try {
            if (self::writeJpegWithinLimit($image, $targetPath)) {
                return;
            }

            while (max(imagesx($image), imagesy($image)) > 1200) {
                $resized = self::resizeJpegCanvas($image, 0.85);
                imagedestroy($image);
                $image = $resized;

                if (self::writeJpegWithinLimit($image, $targetPath)) {
                    return;
                }
            }
        } finally {
            imagedestroy($image);
        }

        if (is_file($targetPath)) {
            unlink($targetPath);
        }

        throw new \RuntimeException('Hasil kompresi masih melebihi 10 MB. Gunakan foto dengan resolusi lebih kecil.');
    }

    private static function storeBackgroundRemovedPng(string $sourcePath, string $targetPath): void
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            throw new \RuntimeException('Hapus background membutuhkan ekstensi PHP GD.');
        }

        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new \RuntimeException('Gagal membaca file upload untuk hapus background.');
        }

        $sourceImage = @imagecreatefromstring($contents);
        unset($contents);

        if ($sourceImage === false) {
            throw new \RuntimeException('Gagal memproses gambar untuk hapus background.');
        }

        $image = self::copyToAlphaCanvas($sourceImage);
        imagedestroy($sourceImage);

        try {
            self::removeEdgeBackground($image);

            if (self::writePngWithinLimit($image, $targetPath)) {
                return;
            }

            while (max(imagesx($image), imagesy($image)) > 1200) {
                $resized = self::resizeAlphaCanvas($image, 0.85);
                imagedestroy($image);
                $image = $resized;

                if (self::writePngWithinLimit($image, $targetPath)) {
                    return;
                }
            }
        } finally {
            imagedestroy($image);
        }

        if (is_file($targetPath)) {
            unlink($targetPath);
        }

        throw new \RuntimeException('Hasil hapus background masih melebihi 10 MB. Gunakan foto dengan resolusi lebih kecil.');
    }

    private static function copyToJpegCanvas(\GdImage $sourceImage): \GdImage
    {
        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            throw new \RuntimeException('Gagal menyiapkan kanvas kompresi.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $sourceImage, 0, 0, 0, 0, $width, $height);

        return $canvas;
    }

    private static function resizeJpegCanvas(\GdImage $image, float $scale): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $newWidth = max(1, (int) floor($width * $scale));
        $newHeight = max(1, (int) floor($height * $scale));
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($resized === false) {
            throw new \RuntimeException('Gagal mengecilkan foto.');
        }

        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $resized;
    }

    private static function copyToAlphaCanvas(\GdImage $sourceImage): \GdImage
    {
        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            throw new \RuntimeException('Gagal menyiapkan kanvas hapus background.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        imagecopy($canvas, $sourceImage, 0, 0, 0, 0, $width, $height);

        return $canvas;
    }

    private static function resizeAlphaCanvas(\GdImage $image, float $scale): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $newWidth = max(1, (int) floor($width * $scale));
        $newHeight = max(1, (int) floor($height * $scale));
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($resized === false) {
            throw new \RuntimeException('Gagal mengecilkan hasil hapus background.');
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $resized;
    }

    private static function writeJpegWithinLimit(\GdImage $image, string $targetPath): bool
    {
        foreach ([86, 82, 78, 74, 70, 65, 60, 55, 50, 45, 40] as $quality) {
            if (!imagejpeg($image, $targetPath, $quality)) {
                throw new \RuntimeException('Gagal menyimpan hasil kompresi.');
            }

            clearstatcache(true, $targetPath);

            if ((int) filesize($targetPath) <= self::MAX_COMPRESSED_SIZE) {
                return true;
            }
        }

        return false;
    }

    private static function writePngWithinLimit(\GdImage $image, string $targetPath): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        if (!imagepng($image, $targetPath, 9)) {
            throw new \RuntimeException('Gagal menyimpan hasil hapus background.');
        }

        clearstatcache(true, $targetPath);

        return (int) filesize($targetPath) <= self::MAX_COMPRESSED_SIZE;
    }

    private static function removeEdgeBackground(\GdImage $image): void
    {
        $backgroundColors = self::sampleBackgroundColors($image);
        $width = imagesx($image);
        $height = imagesy($image);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (!self::isBackgroundPixel($image, $x, $y, $backgroundColors)) {
                    break;
                }

                imagesetpixel($image, $x, $y, $transparent);
            }

            for ($x = $width - 1; $x >= 0; $x--) {
                if (!self::isBackgroundPixel($image, $x, $y, $backgroundColors)) {
                    break;
                }

                imagesetpixel($image, $x, $y, $transparent);
            }
        }

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                if (!self::isBackgroundPixel($image, $x, $y, $backgroundColors)) {
                    break;
                }

                imagesetpixel($image, $x, $y, $transparent);
            }

            for ($y = $height - 1; $y >= 0; $y--) {
                if (!self::isBackgroundPixel($image, $x, $y, $backgroundColors)) {
                    break;
                }

                imagesetpixel($image, $x, $y, $transparent);
            }
        }
    }

    private static function sampleBackgroundColors(\GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $sampleSize = max(2, min(12, (int) floor(min($width, $height) / 20)));
        $regions = [
            [0, 0],
            [max(0, $width - $sampleSize), 0],
            [0, max(0, $height - $sampleSize)],
            [max(0, $width - $sampleSize), max(0, $height - $sampleSize)],
        ];
        $colors = [];

        foreach ($regions as [$startX, $startY]) {
            $red = 0;
            $green = 0;
            $blue = 0;
            $count = 0;

            for ($y = $startY; $y < min($height, $startY + $sampleSize); $y++) {
                for ($x = $startX; $x < min($width, $startX + $sampleSize); $x++) {
                    $rgb = imagecolorat($image, $x, $y);
                    $red += ($rgb >> 16) & 0xff;
                    $green += ($rgb >> 8) & 0xff;
                    $blue += $rgb & 0xff;
                    $count++;
                }
            }

            if ($count > 0) {
                $colors[] = [
                    'r' => (int) round($red / $count),
                    'g' => (int) round($green / $count),
                    'b' => (int) round($blue / $count),
                ];
            }
        }

        return $colors;
    }

    private static function isBackgroundPixel(\GdImage $image, int $x, int $y, array $backgroundColors): bool
    {
        $rgb = imagecolorat($image, $x, $y);
        $red = ($rgb >> 16) & 0xff;
        $green = ($rgb >> 8) & 0xff;
        $blue = $rgb & 0xff;

        foreach ($backgroundColors as $color) {
            $distance = sqrt(
                (($red - $color['r']) ** 2)
                + (($green - $color['g']) ** 2)
                + (($blue - $color['b']) ** 2)
            );

            if ($distance <= self::BACKGROUND_TOLERANCE) {
                return true;
            }
        }

        return false;
    }

    private static function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melewati batas server.',
            UPLOAD_ERR_PARTIAL => 'Upload file tidak lengkap.',
            UPLOAD_ERR_NO_FILE => 'File belum dipilih.',
            default => 'Upload file gagal.',
        };
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
