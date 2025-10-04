<?php

class AvatarUploadService {
    private const MAX_FILE_SIZE = 2_097_152; // 2 MB
    private const STORAGE_DIR = '/public/storage/avatars';

    /**
     * MIME types permitidos con sus extensiones.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/webp' => '.webp',
    ];

    /**
     * Procesa el archivo subido, valida que sea imagen y devuelve la URL pública.
     */
    public function storeUploadedAvatar(array $file, int $userId): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No pudimos recibir la imagen. Intenta nuevamente.');
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Archivo de imagen inválido.');
        }

        $size = $file['size'] ?? 0;
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('La imagen debe pesar menos de 2 MB.');
        }

        $mime = $this->detectMimeType($file['tmp_name']);
        if (!$mime || !array_key_exists($mime, self::ALLOWED_MIME_TYPES)) {
            throw new RuntimeException('Solo aceptamos imágenes JPG, PNG o WebP.');
        }

        $imageType = @exif_imagetype($file['tmp_name']);
        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('El archivo proporcionado no es una imagen válida.');
        }

        $targetDir = rtrim(ROOT_PATH, '/') . self::STORAGE_DIR;
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new RuntimeException('No podemos preparar el directorio para guardar la imagen.');
            }
        }

        $extension = self::ALLOWED_MIME_TYPES[$mime];
        try {
            $uniqueName = 'user-' . $userId . '-' . bin2hex(random_bytes(8)) . $extension;
        } catch (Exception $e) {
            throw new RuntimeException('No pudimos preparar el archivo de imagen.');
        }
        $destination = $targetDir . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('No pudimos guardar la imagen subida.');
        }

        @chmod($destination, 0644);

        // Persist a path relative to the application root to avoid pinning avatars
        // to the host/port used at upload time. Consumers can resolve it against
        // the current APP_URL, ensuring portability across environments.
        return self::STORAGE_DIR . '/' . $uniqueName;
    }

    /**
     * Elimina la imagen anterior si pertenece al almacenamiento gestionado.
     */
    public function deleteManagedAvatar(?string $url): void {
        if (!$url) {
            return;
        }

        $relative = $this->extractRelativePath($url);
        if (!$relative) {
            return;
        }

        $absolute = rtrim(ROOT_PATH, '/') . $relative;
        if (strpos($absolute, rtrim(ROOT_PATH, '/') . self::STORAGE_DIR) !== 0) {
            return;
        }

        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    private function detectMimeType(string $path): ?string {
        if (!class_exists('finfo')) {
            return mime_content_type($path) ?: null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: null;
    }

    private function extractRelativePath(string $url): ?string {
        $normalizedAppUrl = rtrim(APP_URL, '/');
        if (strpos($url, $normalizedAppUrl) === 0) {
            $relative = substr($url, strlen($normalizedAppUrl));
            return $relative ?: null;
        }

        // Soporte para rutas relativas ya almacenadas
        if (strpos($url, self::STORAGE_DIR) === 0) {
            return $url;
        }

        // Si viene con host distinto, intentar extraer la parte del path
        $parsed = parse_url($url, PHP_URL_PATH);
        if (!$parsed) {
            return null;
        }

        if (strpos($parsed, self::STORAGE_DIR) !== 0) {
            return null;
        }

        return $parsed;
    }
}
