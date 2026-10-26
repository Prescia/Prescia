<?php
/*--------------------------------\
  | storeFile : Simple upload handling, with type control
  | Made for Prescia free framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclaimer.
-*/

/**
 * Store an uploaded file while preserving the legacy numeric result contract.
 *
 * Error codes: 0=success, 1/2=upload size limits, 3=incomplete/failed upload,
 * 4=no file sent, 5=invalid extension, 7=content does not match extension.
 * Virtual uploads are used by tests/importers and must explicitly set virtual=true.
 */
function storeFile($file, &$destination, $type = "", $completeDebug = false) {
    $noScripts = true;
    $isVirtual = false;

    if (is_string($file)) {
        if (!isset($_FILES[$file]) || !is_array($_FILES[$file])) {
            return 4;
        }
        $file = $_FILES[$file];
    }
    if (!is_array($file)) {
        return 4;
    }

    $isVirtual = isset($file['virtual']) && $file['virtual'] === true;
    $error = $file['error'] ?? 4;
    $tmpName = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
    $originalName = isset($file['name']) && is_string($file['name']) ? basename($file['name']) : '';
    $desiredFilename = is_string($destination) ? $destination : '';

    if (!is_int($error) || $error < 0) {
        return 3;
    }
    if ($error !== 0) {
        if (!$isVirtual && $error === 3 && $tmpName !== '' && is_file($tmpName)) {
            @unlink($tmpName);
        }
        return $error;
    }
    if ($tmpName === '' || !is_file($tmpName) || $originalName === '' || strpos($originalName, "\0") !== false) {
        return 3;
    }

    $isAuto = false;
    $desiredExtension = '';
    $submittedExtension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    if ($submittedExtension === '' || !preg_match('/^[a-z0-9]{1,16}$/', $submittedExtension)) {
        if (!$isVirtual) {
            @unlink($tmpName);
        }
        return 5;
    }

    if ($type !== '') {
        if ($type === 'auto') {
            $isAuto = true;
            $desiredExtension = '.' . $submittedExtension;
        } else {
            switch ($type) {
                case 'image':
                    $type = 'udef:jpg,gif,png,jpeg';
                    break;
                case 'html':
                    $type = 'udef:htm,html,xhtml';
                    break;
                case 'docs':
                    $type = 'udef:doc,rtf,pps,ppt,pdf,htm,html,docx,xls,xlsx,txt,zip,rar,7z,odt,gz';
                    break;
            }
            if (str_starts_with($type, 'udef:')) {
                $allowedExtensions = array_filter(array_map(
                    static fn(string $extension): string => strtolower(trim($extension)),
                    explode(',', substr($type, 5))
                ));
                if (in_array($submittedExtension, $allowedExtensions, true)) {
                    $desiredExtension = '.' . $submittedExtension;
                }
            }
        }
    } else {
        $desiredExtension = '.' . $submittedExtension;
    }

    if ($desiredExtension === '') {
        if (!$isVirtual) {
            @unlink($tmpName);
        }
        if ($completeDebug) {
            echo "Invalid extension while checking upload type";
        }
        return 5;
    }

    if ($desiredExtension === '.jpg' || $desiredExtension === '.gif' || $desiredExtension === '.png' || $desiredExtension === '.jpeg') {
        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false || !isset($imageInfo[2]) || !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP], true)) {
            if (!$isVirtual) {
                @unlink($tmpName);
            }
            return 7;
        }
        if ($imageInfo[2] === IMAGETYPE_JPEG || $imageInfo[2] === IMAGETYPE_BMP) {
            $desiredExtension = '.jpg';
        } elseif ($imageInfo[2] === IMAGETYPE_PNG) {
            $desiredExtension = '.png';
        } elseif ($imageInfo[2] === IMAGETYPE_GIF) {
            $desiredExtension = '.gif';
        }
    }

    if ($desiredExtension === '.zip' || $desiredExtension === '.rar') {
        $handle = @fopen($tmpName, 'rb');
        if ($handle === false) {
            return 7;
        }
        $signature = (string)fread($handle, 5);
        fclose($handle);
        if (($desiredExtension === '.zip' && strpos($signature, 'PK') === false) || ($desiredExtension === '.rar' && strpos($signature, 'Rar') === false)) {
            return 7;
        }
    }

    if ($desiredFilename === '' || strpos($desiredFilename, "\0") !== false) {
        return 3;
    }
    if (strpos($desiredFilename, '.') !== false) {
        $desiredFilename = pathinfo($desiredFilename, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($desiredFilename, PATHINFO_FILENAME);
    }
    if ($desiredFilename === '' || basename($desiredFilename) === '') {
        return 3;
    }

    if ($noScripts && in_array($desiredExtension, ['.php', '.phtml', '.phar', '.asp', '.aspx', '.jsp', '.cgi', '.pl', '.py', '.sh'], true)) {
        $desiredExtension .= '.html';
    }

    $target = $desiredFilename . $desiredExtension;
    if (is_file($target)) {
        @unlink($target);
    }

    $ok = $isVirtual ? @copy($tmpName, $target) : @move_uploaded_file($tmpName, $target);
    if (!$ok) {
        return 3;
    }

    if (function_exists('safe_chmod')) {
        safe_chmod($target, '0640');
    } else {
        @chmod($target, 0640);
    }
    $destination = $target;
    return 0;
}
