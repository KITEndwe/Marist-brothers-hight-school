<?php
/**
 * Shared upload helper. Returns ['ok'=>true,'path'=>'relative/path.ext']
 * or ['ok'=>false,'error'=>'message'] — never throws, always safe to use
 * directly in a form-handling script.
 */
define('UPLOAD_ROOT', __DIR__ . '/../uploads');

function handle_upload(array $file, string $subDir, array $allowedExts, int $maxSizeMB = 5): array {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file was selected.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed (error code ' . $file['error'] . ').'];
    }
    if ($file['size'] > $maxSizeMB * 1024 * 1024) {
        return ['ok' => false, 'error' => "File is larger than {$maxSizeMB}MB."];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return ['ok' => false, 'error' => 'Allowed file types: ' . implode(', ', $allowedExts) . '.'];
    }

    $destDirAbs = UPLOAD_ROOT . '/' . trim($subDir, '/');
    if (!is_dir($destDirAbs)) {
        mkdir($destDirAbs, 0775, true);
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $destAbs = $destDirAbs . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded file to disk.'];
    }

    return ['ok' => true, 'path' => trim($subDir, '/') . '/' . $filename];
}

/** Renders an <img> or the initials avatar, whichever is available. */
function avatar_html(?string $photoPath, string $initials, string $sizeClass = ''): string {
    if ($photoPath) {
        $src = h('../uploads/' . $photoPath);
        return "<img src=\"{$src}\" class=\"avatar {$sizeClass}\" alt=\"\" style=\"object-fit:cover;\">";
    }
    return '<div class="avatar ' . h($sizeClass) . '">' . h($initials) . '</div>';
}
