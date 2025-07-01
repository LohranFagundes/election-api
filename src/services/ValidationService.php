<?php
// src/services/FileUploadService.php

require_once __DIR__ . '/../services/ValidationService.php';

class FileUploadService
{
    private $config;
    private $validationService;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->validationService = new ValidationService();
    }

    public function uploadCandidatePhoto($file)
    {
        $validation = $this->validationService->validateFileUpload(
            $file,
            $this->config['uploads']['allowed_types'],
            $this->config['uploads']['max_size']
        );

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => implode(', ', $validation['errors'])
            ];
        }

        try {
            $imageData = file_get_contents($file['tmp_name']);
            $resizedImage = $this->resizeImage($imageData, $validation['mime_type']);

            return [
                'success' => true,
                'photo_data' => $resizedImage,
                'filename' => $this->generateFileName($file['name']),
                'mime_type' => $validation['mime_type'],
                'size' => strlen($resizedImage)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to process image: ' . $e->getMessage()
            ];
        }
    }

    public function uploadToStorage($file, $directory = 'uploads')
    {
        $validation = $this->validationService->validateFileUpload(
            $file,
            $this->config['uploads']['allowed_types'],
            $this->config['uploads']['max_size']
        );

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => implode(', ', $validation['errors'])
            ];
        }

        try {
            $uploadDir = $this->config['uploads']['path'] . $directory . '/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = $this->generateFileName($file['name']);
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'url' => '/' . $directory . '/' . $filename,
                    'size' => filesize($filepath),
                    'mime_type' => $validation['mime_type']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to move uploaded file'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    public function deleteFile($filepath)
    {
        try {
            if (file_exists($filepath)) {
                unlink($filepath);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getFileInfo($filepath)
    {
        if (!file_exists($filepath)) {
            return null;
        }

        return [
            'size' => filesize($filepath),
            'mime_type' => mime_content_type($filepath),
            'modified' => filemtime($filepath),
            'hash' => hash_file('sha256', $filepath)
        ];
    }

    private function resizeImage($imageData, $mimeType)
    {
        $maxWidth = $this->config['uploads']['candidates']['max_width'];
        $maxHeight = $this->config['uploads']['candidates']['max_height'];

        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromstring($imageData);
                break;
            case 'image/png':
                $image = imagecreatefromstring($imageData);
                break;
            case 'image/gif':
                $image = imagecreatefromstring($imageData);
                break;
            default:
                throw new Exception('Unsupported image type');
        }

        if (!$image) {
            throw new Exception('Failed to create image resource');
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            ob_start();
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($image, null, 90);
                    break;
                case 'image/png':
                    imagepng($image);
                    break;
                case 'image/gif':
                    imagegif($image);
                    break;
            }
            $resizedData = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);
            return $resizedData;
        }

        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled(
            $resizedImage, $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        ob_start();
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($resizedImage, null, 90);
                break;
            case 'image/png':
                imagepng($resizedImage);
                break;
            case 'image/gif':
                imagegif($resizedImage);
                break;
        }
        $resizedData = ob_get_contents();
        ob_end_clean();

        imagedestroy($image);
        imagedestroy($resizedImage);

        return $resizedData;
    }

    private function generateFileName($originalName)
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
        $basename = substr($basename, 0, 50);
        
        return $basename . '_' . uniqid() . '.' . $extension;
    }

    public function validateImageDimensions($file, $minWidth = 0, $minHeight = 0, $maxWidth = 2000, $maxHeight = 2000)
    {
        $imageInfo = getimagesize($file['tmp_name']);
        
        if (!$imageInfo) {
            return [
                'valid' => false,
                'message' => 'Invalid image file'
            ];
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width < $minWidth || $height < $minHeight) {
            return [
                'valid' => false,
                'message' => "Image too small. Minimum dimensions: {$minWidth}x{$minHeight}"
            ];
        }

        if ($width > $maxWidth || $height > $maxHeight) {
            return [
                'valid' => false,
                'message' => "Image too large. Maximum dimensions: {$maxWidth}x{$maxHeight}"
            ];
        }

        return [
            'valid' => true,
            'width' => $width,
            'height' => $height,
            'mime_type' => $imageInfo['mime']
        ];
    }

    public function generateThumbnail($imageData, $mimeType, $width = 150, $height = 150)
    {
        $image = imagecreatefromstring($imageData);
        if (!$image) {
            throw new Exception('Failed to create image resource');
        }

        $thumbnail = imagecreatetruecolor($width, $height);

        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        imagecopyresampled(
            $thumbnail, $image,
            0, 0, 0, 0,
            $width, $height,
            $originalWidth, $originalHeight
        );

        ob_start();
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumbnail, null, 85);
                break;
            case 'image/png':
                imagepng($thumbnail);
                break;
            case 'image/gif':
                imagegif($thumbnail);
                break;
        }
        $thumbnailData = ob_get_contents();
        ob_end_clean();

        imagedestroy($image);
        imagedestroy($thumbnail);

        return $thumbnailData;
    }

    public function cleanupTempFiles($directory = '/tmp')
    {
        $files = glob($directory . '/php*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > 3600) {
                unlink($file);
            }
        }
    }
}