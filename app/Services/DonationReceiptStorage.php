<?php

class DonationReceiptStorage
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    private const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB

    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = STORAGE_PATH . '/donations/receipts';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Almacena un comprobante de transferencia de manera segura
     *
     * @param array $file Superglobal $_FILES['campo']
     * @param int $campaignId
     * @param int|null $userId
     * @return array{path:string, original_name:string, size:int, checksum:string, mime:string}
     */
    public function store(array $file, int $campaignId, ?int $userId = null): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo cargar el comprobante.');
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Subida inválida.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('El comprobante supera el tamaño permitido (2 MB).');
        }

        $mime = mime_content_type($file['tmp_name']) ?: '';
        if (!array_key_exists($mime, self::ALLOWED_MIME_TYPES)) {
            throw new RuntimeException('Formato de comprobante no soportado. Usa JPG, PNG o PDF.');
        }

        $extension = self::ALLOWED_MIME_TYPES[$mime];
        $originalName = $this->sanitizeFilename((string)($file['name'] ?? 'comprobante'));
        $checksum = hash_file('sha256', $file['tmp_name']);

        $uniqueName = sprintf(
            'campaign_%d_%s_%s.%s',
            $campaignId,
            $userId !== null ? 'user_' . $userId : 'guest',
            bin2hex(random_bytes(8)),
            $extension
        );

        $destination = $this->storagePath . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('No pudimos guardar el comprobante.');
        }

        chmod($destination, 0640);

        return [
            'path' => $this->relativePath($destination),
            'original_name' => $originalName,
            'size' => $size,
            'checksum' => $checksum,
            'mime' => $mime,
        ];
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9\-\._ ]/', '', $name) ?? 'comprobante';
        $name = trim($name);
        if ($name === '') {
            return 'comprobante';
        }
        return substr($name, 0, 120);
    }

    private function relativePath(string $absolutePath): string
    {
        if (str_starts_with($absolutePath, STORAGE_PATH)) {
            return ltrim(substr($absolutePath, strlen(STORAGE_PATH)), '/');
        }

        return basename($absolutePath);
    }
}
