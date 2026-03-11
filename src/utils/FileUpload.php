<?php
/**
 * VelocityPHP File Upload Utility
 * Simple, secure file uploads for shared hosting
 *
 * @package VelocityPhp
 */

namespace App\Utils;

class FileUpload
{
    // Default allowed MIME types → mapped to safe extensions
    private static $defaultAllowedTypes = [
        'image/jpeg'                                                  => 'jpg',
        'image/png'                                                   => 'png',
        'image/gif'                                                   => 'gif',
        'image/webp'                                                  => 'webp',
        'image/svg+xml'                                               => 'svg',
        'application/pdf'                                             => 'pdf',
        'text/plain'                                                  => 'txt',
        'application/zip'                                             => 'zip',
        'application/x-zip-compressed'                               => 'zip',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword'                                          => 'doc',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'xlsx',
        'application/vnd.ms-excel'                                    => 'xls',
    ];

    /**
     * Handle a file upload.
     *
     * @param string $fileKey      Key in $_FILES (e.g. 'file')
     * @param array  $options {
     *     @type string[] $allowedTypes  MIME types to accept (default: images + pdf + common docs)
     *     @type int      $maxSize       Max bytes (default: 5 MB)
     *     @type string   $uploadDir     Absolute path to upload directory (default: public/uploads)
     *     @type bool     $randomName    Rename file to random UUID (default: true — recommended)
     * }
     * @return array {
     *     @type bool   success
     *     @type string message
     *     @type string url       Public URL of uploaded file (on success)
     *     @type string filename  Stored filename (on success)
     *     @type string path      Absolute filesystem path (on success)
     *     @type int    size      File size in bytes (on success)
     * }
     */
    public static function upload($fileKey = 'file', array $options = [])
    {
        // ── Defaults ─────────────────────────────────────────────────────────
        $allowedTypes = $options['allowedTypes'] ?? array_keys(self::$defaultAllowedTypes);
        $maxSize      = $options['maxSize']      ?? 5 * 1024 * 1024; // 5 MB
        $uploadDir    = $options['uploadDir']    ?? PUBLIC_PATH . '/uploads';
        $randomName   = $options['randomName']   ?? true;

        // ── Check $_FILES ─────────────────────────────────────────────────────
        if (!isset($_FILES[$fileKey])) {
            return self::fail('No file field "' . $fileKey . '" in request.');
        }

        $file = $_FILES[$fileKey];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return self::fail(self::uploadErrorMessage($file['error']));
        }

        // ── Size check ────────────────────────────────────────────────────────
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            return self::fail("File exceeds the maximum allowed size of {$maxMB} MB.");
        }

        // ── MIME type check (use finfo for accuracy, not $_FILES['type']) ─────
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes, true)) {
            return self::fail("File type '{$mimeType}' is not allowed.");
        }

        // Determine extension from MIME
        $ext = self::$defaultAllowedTypes[$mimeType]
            ?? pathinfo($file['name'], PATHINFO_EXTENSION);
        $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
        if (empty($ext)) {
            return self::fail('Could not determine a safe file extension.');
        }

        // ── Ensure upload directory exists ────────────────────────────────────
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return self::fail('Upload directory could not be created.');
            }
        }

        // Prevent direct PHP execution inside the upload dir
        $htaccess = $uploadDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -ExecCGI\nAddHandler cgi-script .php .php3 .php4 .php5 .phtml .pl .py .jsp .asp .sh .cgi\n");
        }

        // ── Determine filename ────────────────────────────────────────────────
        if ($randomName) {
            $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        } else {
            $orig        = pathinfo($file['name'], PATHINFO_FILENAME);
            $safeBase    = preg_replace('/[^a-zA-Z0-9_-]/', '_', $orig);
            $storedName  = $safeBase . '_' . time() . '.' . $ext;
        }

        $destPath = $uploadDir . '/' . $storedName;

        // ── Move file ─────────────────────────────────────────────────────────
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return self::fail('Failed to move uploaded file to destination.');
        }

        // Build public URL (relative to document root / public/)
        $relativePath = str_replace(PUBLIC_PATH, '', $destPath);
        $publicUrl    = str_replace('\\', '/', $relativePath);

        return [
            'success'  => true,
            'message'  => 'File uploaded successfully.',
            'url'      => $publicUrl,
            'filename' => $storedName,
            'path'     => $destPath,
            'size'     => $file['size'],
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function fail($message)
    {
        return ['success' => false, 'message' => $message];
    }

    private static function uploadErrorMessage($code)
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'The file exceeds the server upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the form MAX_FILE_SIZE limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A server extension stopped the file upload.',
        ];
        return $messages[$code] ?? "Unknown upload error (code {$code}).";
    }
}
