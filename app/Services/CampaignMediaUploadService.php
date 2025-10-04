<?php

class CampaignMediaUploadService
{
    private const COVER_MAX_FILE_SIZE = 5_242_880; // 5 MB
    private const GALLERY_MAX_FILE_SIZE = 6_291_456; // 6 MB
    private const ATTACHMENT_MAX_FILE_SIZE = 8_388_608; // 8 MB

    private const GALLERY_MAX_FILES = 5;
    private const ATTACHMENT_MAX_FILES = 5;

    public const PUBLIC_BASE_DIR = '/public/storage/campaigns';
    public const PRIVATE_BASE_DIR = '/storage/private/campaigns';
    public const DRAFT_PUBLIC_DIR = '/public/storage/drafts';
    public const DRAFT_PRIVATE_DIR = '/storage/private/drafts';

    private const IMAGE_MIME_TYPES = [
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/webp' => '.webp',
    ];

    private const ATTACHMENT_MIME_TYPES = [
        'application/pdf' => '.pdf',
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/webp' => '.webp',
    ];

    public function storeCoverImage(array $file, int $userId): string
    {
        $normalized = $this->normalizeSingleFile($file);
        $this->assertValidUpload($normalized, self::COVER_MAX_FILE_SIZE, self::IMAGE_MIME_TYPES, true);

        $targetDir = $this->ensureDirectory(self::PUBLIC_BASE_DIR);
        $extension = self::IMAGE_MIME_TYPES[$normalized['mime']];
        $uniqueName = $this->generateFilename('campaign', $userId, $extension);
        $destination = $targetDir . '/' . $uniqueName;

        if (!move_uploaded_file($normalized['tmp_name'], $destination)) {
            throw new RuntimeException('No pudimos guardar la imagen.');
        }

        @chmod($destination, 0644);

        return self::PUBLIC_BASE_DIR . '/' . $uniqueName;
    }

    /**
     * @return array<int, array{url:string, filename:string, mime:string, size:int}>
     */
    public function storeGalleryImages(array $files, int $campaignId, int $userId): array
    {
        $normalizedFiles = $this->normalizeMultipleFiles($files);
        if (empty($normalizedFiles)) {
            return [];
        }

        if (count($normalizedFiles) > self::GALLERY_MAX_FILES) {
            throw new RuntimeException('Puedes adjuntar hasta 5 imágenes en la galería.');
        }

        $galleryDir = $this->ensureDirectory(self::PUBLIC_BASE_DIR . '/' . $campaignId . '/gallery');
        $stored = [];

        foreach ($normalizedFiles as $file) {
            $this->assertValidUpload($file, self::GALLERY_MAX_FILE_SIZE, self::IMAGE_MIME_TYPES, true);

            $extension = self::IMAGE_MIME_TYPES[$file['mime']];
            $uniqueName = $this->generateFilename('gallery-' . $campaignId, $userId, $extension);
            $destination = $galleryDir . '/' . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException('No pudimos guardar una de las imágenes de la galería.');
            }
            @chmod($destination, 0644);

            $stored[] = [
                'url' => self::PUBLIC_BASE_DIR . '/' . $campaignId . '/gallery/' . $uniqueName,
                'filename' => $file['name'],
                'mime' => $file['mime'],
                'size' => $file['size']
            ];
        }

        return $stored;
    }

    /**
     * @return array<int, array{path:string, filename:string, mime:string, size:int}>
     */
    public function storeSupportingFiles(array $files, int $campaignId, int $userId): array
    {
        $normalizedFiles = $this->normalizeMultipleFiles($files);
        if (empty($normalizedFiles)) {
            return [];
        }

        if (count($normalizedFiles) > self::ATTACHMENT_MAX_FILES) {
            throw new RuntimeException('Puedes adjuntar hasta 5 documentos de respaldo.');
        }

        $attachmentsDir = $this->ensureDirectory(self::PRIVATE_BASE_DIR . '/' . $campaignId . '/documents');
        $stored = [];

        foreach ($normalizedFiles as $file) {
            $this->assertValidUpload($file, self::ATTACHMENT_MAX_FILE_SIZE, self::ATTACHMENT_MIME_TYPES, false);

            $extension = self::ATTACHMENT_MIME_TYPES[$file['mime']];
            $uniqueName = $this->generateFilename('doc-' . $campaignId, $userId, $extension);
            $destination = $attachmentsDir . '/' . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException('No pudimos guardar uno de los documentos adjuntos.');
            }
            @chmod($destination, 0640);

            $stored[] = [
                'path' => self::PRIVATE_BASE_DIR . '/' . $campaignId . '/documents/' . $uniqueName,
                'filename' => $file['name'],
                'mime' => $file['mime'],
                'size' => $file['size']
            ];
        }

        return $stored;
    }

    public function storeDraftCover(array $file, int $userId): string
    {
        $normalized = $this->normalizeSingleFile($file);
        $this->assertValidUpload($normalized, self::COVER_MAX_FILE_SIZE, self::IMAGE_MIME_TYPES, true);

        $targetDir = $this->ensureDirectory(self::DRAFT_PUBLIC_DIR . '/' . $userId);
        $extension = self::IMAGE_MIME_TYPES[$normalized['mime']];
        $uniqueName = $this->generateFilename('draft-cover-' . $userId, $userId, $extension);
        $destination = $targetDir . '/' . $uniqueName;

        if (!move_uploaded_file($normalized['tmp_name'], $destination)) {
            throw new RuntimeException('No pudimos guardar la imagen de portada.');
        }

        @chmod($destination, 0644);

        return self::DRAFT_PUBLIC_DIR . '/' . $userId . '/' . $uniqueName;
    }

    public function storeDraftGalleryImages(array $files, int $userId, int $existingCount = 0): array
    {
        $normalizedFiles = $this->normalizeMultipleFiles($files);
        if (empty($normalizedFiles)) {
            return [];
        }

        if ($existingCount + count($normalizedFiles) > self::GALLERY_MAX_FILES) {
            throw new RuntimeException('Puedes adjuntar hasta 5 imágenes en la galería.');
        }

        $galleryDir = $this->ensureDirectory(self::DRAFT_PUBLIC_DIR . '/' . $userId . '/gallery');
        $stored = [];

        foreach ($normalizedFiles as $file) {
            $this->assertValidUpload($file, self::GALLERY_MAX_FILE_SIZE, self::IMAGE_MIME_TYPES, true);

            $extension = self::IMAGE_MIME_TYPES[$file['mime']];
            $uniqueName = $this->generateFilename('draft-gallery-' . $userId, $userId, $extension);
            $destination = $galleryDir . '/' . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException('No pudimos guardar una de las imágenes de la galería.');
            }
            @chmod($destination, 0644);

            $stored[] = [
                'url' => self::DRAFT_PUBLIC_DIR . '/' . $userId . '/gallery/' . $uniqueName,
                'filename' => $file['name'],
                'mime' => $file['mime'],
                'size' => $file['size']
            ];
        }

        return $stored;
    }

    /**
     * @return array<int, array{path:string, filename:string, mime:string, size:int}>
     */
    public function storeDraftAttachments(array $files, int $userId, int $existingCount = 0): array
    {
        $normalizedFiles = $this->normalizeMultipleFiles($files);
        if (empty($normalizedFiles)) {
            return [];
        }

        if ($existingCount + count($normalizedFiles) > self::ATTACHMENT_MAX_FILES) {
            throw new RuntimeException('Puedes adjuntar hasta 5 documentos de respaldo.');
        }

        $attachmentsDir = $this->ensureDirectory(self::DRAFT_PRIVATE_DIR . '/' . $userId . '/documents');
        $stored = [];

        foreach ($normalizedFiles as $file) {
            $this->assertValidUpload($file, self::ATTACHMENT_MAX_FILE_SIZE, self::ATTACHMENT_MIME_TYPES, false);

            $extension = self::ATTACHMENT_MIME_TYPES[$file['mime']];
            $uniqueName = $this->generateFilename('draft-doc-' . $userId, $userId, $extension);
            $destination = $attachmentsDir . '/' . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException('No pudimos guardar uno de los documentos adjuntos.');
            }
            @chmod($destination, 0640);

            $stored[] = [
                'path' => self::DRAFT_PRIVATE_DIR . '/' . $userId . '/documents/' . $uniqueName,
                'filename' => $file['name'],
                'mime' => $file['mime'],
                'size' => $file['size']
            ];
        }

        return $stored;
    }

    public function deleteDraftAsset(?string $path): void
    {
        if (!$path) {
            return;
        }

        $absolute = rtrim(ROOT_PATH, '/') . $path;
        if (!is_file($absolute)) {
            return;
        }

        $allowedRoots = [
            rtrim(ROOT_PATH, '/') . self::DRAFT_PUBLIC_DIR,
            rtrim(ROOT_PATH, '/') . self::DRAFT_PRIVATE_DIR,
        ];

        foreach ($allowedRoots as $root) {
            if (strpos($absolute, $root) === 0) {
                @unlink($absolute);
                $this->cleanupIfEmpty(dirname($absolute));
                return;
            }
        }
    }

    public function promoteDraftMedia(int $userId, int $campaignId, array $draftMedia): array
    {
        $result = [
            'cover' => $draftMedia['cover'] ?? null,
            'gallery' => [],
            'attachments' => [],
        ];

        if (!empty($draftMedia['cover'])) {
            $result['cover'] = $this->promoteDraftCover($draftMedia['cover'], $userId, $campaignId);
        }

        foreach ($draftMedia['gallery'] ?? [] as $item) {
            $result['gallery'][] = $this->promoteDraftGalleryItem($item, $userId, $campaignId);
        }

        foreach ($draftMedia['attachments'] ?? [] as $item) {
            $result['attachments'][] = $this->promoteDraftAttachment($item, $userId, $campaignId);
        }

        $this->cleanupDraftRoots($userId);

        return $result;
    }

    public function persistManifest(int $campaignId, array $data): void
    {
        $baseDir = $this->ensureDirectory(self::PRIVATE_BASE_DIR . '/' . $campaignId);
        $manifestPath = $baseDir . '/media.json';

        $existing = [];
        if (is_file($manifestPath)) {
            $raw = file_get_contents($manifestPath);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $existing = $decoded;
                }
            }
        }

        $merged = array_merge($existing, $data);
        $encoded = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || file_put_contents($manifestPath, $encoded) === false) {
            throw new RuntimeException('No pudimos registrar los archivos adjuntos de la campaña.');
        }
    }

    public function readManifest(int $campaignId): array
    {
        $basePath = rtrim(ROOT_PATH, '/') . self::PRIVATE_BASE_DIR . '/' . $campaignId;
        $manifestPath = $basePath . '/media.json';

        if (!is_readable($manifestPath)) {
            return [];
        }

        $raw = file_get_contents($manifestPath);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function deletePublicUrl(?string $url): void
    {
        if (!$url) {
            return;
        }

        $normalizedAppUrl = rtrim(APP_URL, '/');
        $path = $url;

        if (strpos($url, '//') === 0) {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https:' : 'http:') . $url;
        }

        if (strpos($url, $normalizedAppUrl) === 0) {
            $path = substr($url, strlen($normalizedAppUrl));
        }

        $absolute = rtrim(ROOT_PATH, '/') . '/' . ltrim($path, '/');
        if (is_file($absolute) && strpos($absolute, rtrim(ROOT_PATH, '/') . self::PUBLIC_BASE_DIR) === 0) {
            @unlink($absolute);
        }
    }

    public static function normalizePublicUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $value = trim($path);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        if (strpos($value, '//') === 0) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
            return $scheme . $value;
        }

        $absolutePath = '/' . ltrim($value, '/');
        return rtrim(APP_URL, '/') . $absolutePath;
    }

    private function normalizeSingleFile(array $file): array
    {
        if (!isset($file['name'], $file['tmp_name'])) {
            throw new RuntimeException('Archivo inválido.');
        }

        return [
            'name' => $file['name'],
            'tmp_name' => $file['tmp_name'],
            'size' => (int)($file['size'] ?? 0),
            'error' => $file['error'] ?? UPLOAD_ERR_OK,
            'mime' => $this->detectMimeType($file['tmp_name'])
        ];
    }

    /**
     * @return array<int, array{name:string,tmp_name:string,size:int,error:int,mime:?string}>
     */
    private function normalizeMultipleFiles(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $tmpName = $files['tmp_name'][$index] ?? null;
            if (!$tmpName) {
                continue;
            }

            $mime = $this->detectMimeType($tmpName);
            $normalized[] = [
                'name' => $name,
                'tmp_name' => $tmpName,
                'size' => (int)($files['size'][$index] ?? 0),
                'error' => $files['error'][$index] ?? UPLOAD_ERR_OK,
                'mime' => $mime
            ];
        }

        return $normalized;
    }

    /**
     * @param array{name:string,tmp_name:string,size:int,error:int,mime:?string} $file
     */
    private function assertValidUpload(array $file, int $maxSize, array $allowedMime, bool $requireImageSignature): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No pudimos procesar el archivo subido.');
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('El archivo recibido no es válido.');
        }

        if ($file['size'] <= 0 || $file['size'] > $maxSize) {
            throw new RuntimeException('El archivo excede el tamaño máximo permitido.');
        }

        $mime = $file['mime'];
        if (!$mime || !array_key_exists($mime, $allowedMime)) {
            throw new RuntimeException('El formato del archivo no es soportado.');
        }

        if ($requireImageSignature) {
            $imageType = @exif_imagetype($file['tmp_name']);
            if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                throw new RuntimeException('El archivo proporcionado no es una imagen válida.');
            }
        }
    }

    private function generateFilename(string $prefix, int $userId, string $extension): string
    {
        try {
            return $prefix . '-' . $userId . '-' . bin2hex(random_bytes(8)) . $extension;
        } catch (Exception $e) {
            throw new RuntimeException('No pudimos generar un nombre seguro para el archivo.');
        }
    }

    private function ensureDirectory(string $relativePath): string
    {
        $absolute = rtrim(ROOT_PATH, '/') . $relativePath;
        if (!is_dir($absolute)) {
            if (!mkdir($absolute, 0755, true) && !is_dir($absolute)) {
                throw new RuntimeException('No pudimos preparar el directorio de almacenamiento.');
            }
        }

        return $absolute;
    }

    private function promoteDraftCover(string $path, int $userId, int $campaignId): ?string
    {
        if (!$path || !$this->isDraftPublicPath($path, $userId)) {
            return $path ?: null;
        }

        $targetDir = $this->ensureDirectory(self::PUBLIC_BASE_DIR);
        $extension = '.' . ltrim(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg', '.');
        $uniqueName = $this->generateFilename('campaign-' . $campaignId, $userId, $extension);

        $absoluteSource = rtrim(ROOT_PATH, '/') . $path;
        $destination = $targetDir . '/' . $uniqueName;

        if (!rename($absoluteSource, $destination)) {
            throw new RuntimeException('No pudimos mover la imagen de portada temporal.');
        }

        @chmod($destination, 0644);
        $this->cleanupIfEmpty(dirname($absoluteSource));

        return self::PUBLIC_BASE_DIR . '/' . $uniqueName;
    }

    private function promoteDraftGalleryItem(array $item, int $userId, int $campaignId): array
    {
        $url = $item['url'] ?? null;
        if ($url && $this->isDraftPublicPath($url, $userId)) {
            $galleryDirRelative = self::PUBLIC_BASE_DIR . '/' . $campaignId . '/gallery';
            $galleryDir = $this->ensureDirectory($galleryDirRelative);
            $extension = '.' . ltrim(pathinfo($url, PATHINFO_EXTENSION) ?: 'jpg', '.');
            $uniqueName = $this->generateFilename('gallery-' . $campaignId, $userId, $extension);

            $absoluteSource = rtrim(ROOT_PATH, '/') . $url;
            $destination = $galleryDir . '/' . $uniqueName;

            if (!rename($absoluteSource, $destination)) {
                throw new RuntimeException('No pudimos mover una imagen de la galería temporal.');
            }
            @chmod($destination, 0644);
            $this->cleanupIfEmpty(dirname($absoluteSource));

            $url = $galleryDirRelative . '/' . $uniqueName;
            $item['size'] = filesize($destination) ?: ($item['size'] ?? 0);
        }

        $absoluteCurrent = rtrim(ROOT_PATH, '/') . $url;
        if (!isset($item['size']) && is_file($absoluteCurrent)) {
            $item['size'] = filesize($absoluteCurrent);
        }

        return [
            'url' => $url,
            'filename' => $item['filename'] ?? basename((string)$url),
            'mime' => $item['mime'] ?? $this->detectMimeType($absoluteCurrent) ?? 'image/jpeg',
            'size' => $item['size'] ?? 0,
        ];
    }

    private function promoteDraftAttachment(array $item, int $userId, int $campaignId): array
    {
        $path = $item['path'] ?? null;
        if ($path && $this->isDraftPrivatePath($path, $userId)) {
            $attachmentsDirRelative = self::PRIVATE_BASE_DIR . '/' . $campaignId . '/documents';
            $attachmentsDir = $this->ensureDirectory($attachmentsDirRelative);
            $extension = '.' . ltrim(pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf', '.');
            $uniqueName = $this->generateFilename('doc-' . $campaignId, $userId, $extension);

            $absoluteSource = rtrim(ROOT_PATH, '/') . $path;
            $destination = $attachmentsDir . '/' . $uniqueName;

            if (!rename($absoluteSource, $destination)) {
                throw new RuntimeException('No pudimos mover un documento temporal.');
            }
            @chmod($destination, 0640);
            $this->cleanupIfEmpty(dirname($absoluteSource));

            $path = $attachmentsDirRelative . '/' . $uniqueName;
            $item['size'] = filesize($destination) ?: ($item['size'] ?? 0);
        }

        $absoluteCurrent = rtrim(ROOT_PATH, '/') . $path;
        if (!isset($item['size']) && is_file($absoluteCurrent)) {
            $item['size'] = filesize($absoluteCurrent);
        }

        if (!isset($item['mime'])) {
            $item['mime'] = $this->detectMimeType($absoluteCurrent) ?? 'application/octet-stream';
        }

        return [
            'path' => $path,
            'filename' => $item['filename'] ?? basename((string)$path),
            'mime' => $item['mime'],
            'size' => $item['size'] ?? 0,
        ];
    }

    private function cleanupDraftRoots(int $userId): void
    {
        $publicDraftDir = rtrim(ROOT_PATH, '/') . self::DRAFT_PUBLIC_DIR . '/' . $userId;
        $privateDraftDir = rtrim(ROOT_PATH, '/') . self::DRAFT_PRIVATE_DIR . '/' . $userId;

        $this->cleanupIfEmpty($publicDraftDir);
        $this->cleanupIfEmpty($privateDraftDir);
    }

    private function cleanupIfEmpty(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        if (empty($files)) {
            @rmdir($directory);
            $parent = dirname($directory);
            $draftRoots = [
                rtrim(ROOT_PATH, '/') . self::DRAFT_PUBLIC_DIR,
                rtrim(ROOT_PATH, '/') . self::DRAFT_PRIVATE_DIR,
            ];

            if (in_array($parent, $draftRoots, true)) {
                $this->cleanupIfEmpty($parent);
            } elseif (strpos($parent, rtrim(ROOT_PATH, '/') . self::DRAFT_PUBLIC_DIR) === 0 || strpos($parent, rtrim(ROOT_PATH, '/') . self::DRAFT_PRIVATE_DIR) === 0) {
                $this->cleanupIfEmpty($parent);
            }
        }
    }

    private function isDraftPublicPath(string $path, int $userId): bool
    {
        return strpos($path, self::DRAFT_PUBLIC_DIR . '/' . $userId . '/') === 0;
    }

    private function isDraftPrivatePath(string $path, int $userId): bool
    {
        return strpos($path, self::DRAFT_PRIVATE_DIR . '/' . $userId . '/') === 0;
    }

    private function detectMimeType(string $path): ?string
    {
        if (!class_exists('finfo')) {
            return mime_content_type($path) ?: null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: null;
    }
}
