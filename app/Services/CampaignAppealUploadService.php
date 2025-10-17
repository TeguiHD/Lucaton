<?php

class CampaignAppealUploadService
{
    private const MAX_FILES = 5;
    private const MAX_FILE_SIZE = 8_388_608; // 8 MB
    private const STORAGE_BASE_DIR = '/storage/private/campaigns';

    private const ALLOWED_MIME_TYPES = [
        'application/pdf' => '.pdf',
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/webp' => '.webp',
    ];

    /**
     * @return array<int, array{name:string,tmp_name:string,size:int,error:int,mime:?string}>
     */
    public function normalizeFiles(array $files): array
    {
        if (empty($files) || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $normalized = [];
        $total = count($files['name']);

        for ($index = 0; $index < $total; $index++) {
            $error = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $tmpName = $files['tmp_name'][$index] ?? null;
            if (!$tmpName) {
                continue;
            }

            $mime = $this->detectMimeType($tmpName);

            $normalized[] = [
                'name' => $files['name'][$index],
                'tmp_name' => $tmpName,
                'size' => (int)($files['size'][$index] ?? 0),
                'error' => $error,
                'mime' => $mime,
            ];
        }

        if (count($normalized) > self::MAX_FILES) {
            throw new RuntimeException('Puedes adjuntar hasta ' . self::MAX_FILES . ' archivos por apelación.');
        }

        return $normalized;
    }

    /**
     * @param array<int, array{name:string,tmp_name:string,size:int,error:int,mime:?string}> $files
     * @return array<int, array{path:string,filename:string,mime:string,size:int,uploaded_by:int}>
     */
    public function storeFiles(array $files, int $campaignId, int $appealId, int $userId): array
    {
        if (empty($files)) {
            return [];
        }

        $targetDirRelative = self::STORAGE_BASE_DIR . '/' . $campaignId . '/appeals/' . $appealId;
        $targetDir = $this->ensureDirectory($targetDirRelative);

        $stored = [];
        foreach ($files as $file) {
            $this->assertValidFile($file);

            $extension = self::ALLOWED_MIME_TYPES[$file['mime']];
            $uniqueName = $this->generateFilename($appealId, $userId, $extension);
            $destination = $targetDir . '/' . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $this->cleanupStoredFiles($stored);
                throw new RuntimeException('No pudimos guardar uno de los documentos adjuntos.');
            }

            @chmod($destination, 0640);

            $stored[] = [
                'path' => $targetDirRelative . '/' . $uniqueName,
                'filename' => $file['name'],
                'mime' => $file['mime'],
                'size' => $file['size'],
                'uploaded_by' => $userId,
            ];
        }

        return $stored;
    }

    /**
     * @param array<int, array{path:string}> $stored
     */
    public function cleanupStoredFiles(array $stored): void
    {
        foreach ($stored as $item) {
            $path = $item['path'] ?? null;
            if (!$path) {
                continue;
            }

            $absolute = rtrim(ROOT_PATH, '/') . $path;
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    /**
     * @param array{name:string,tmp_name:string,size:int,error:int,mime:?string} $file
     */
    private function assertValidFile(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No pudimos procesar uno de los archivos adjuntos.');
        }

        if ($file['size'] <= 0 || $file['size'] > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Cada archivo debe pesar menos de 8 MB.');
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('El archivo recibido no es válido.');
        }

        $mime = $file['mime'];
        if (!$mime || !array_key_exists($mime, self::ALLOWED_MIME_TYPES)) {
            throw new RuntimeException('Formato de archivo no permitido. Usa PDF o imágenes (JPG, PNG, WEBP).');
        }
    }

    private function ensureDirectory(string $relativePath): string
    {
        $absolute = rtrim(ROOT_PATH, '/') . $relativePath;
        if (!is_dir($absolute)) {
            if (!mkdir($absolute, 0755, true) && !is_dir($absolute)) {
                throw new RuntimeException('No pudimos preparar la carpeta para tus adjuntos.');
            }
        }

        return $absolute;
    }

    private function generateFilename(int $appealId, int $userId, string $extension): string
    {
        try {
            return 'appeal-' . $appealId . '-' . $userId . '-' . bin2hex(random_bytes(8)) . $extension;
        } catch (Exception $exception) {
            throw new RuntimeException('No pudimos generar un nombre seguro para el archivo.');
        }
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
