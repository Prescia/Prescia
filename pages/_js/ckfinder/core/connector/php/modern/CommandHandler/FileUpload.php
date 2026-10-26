<?php
/**
 * CKFinder
 * ========
 * http://ckfinder.com
 * Copyright (C) 2007-2012, CKSource - Frederico Knabben. All rights reserved.
 *
 * The software, this file and its contents are subject to the CKFinder
 * License. Please read the license.txt file before using, installing, copying,
 * modifying or distribute this file or part of its contents. The contents of
 * this file is part of the Source Code of CKFinder.
 */
if (!defined('IN_CKFINDER')) exit;

/**
 * @package CKFinder
 * @subpackage CommandHandlers
 * @copyright CKSource - Frederico Knabben
 */

/**
 * Handle FileUpload command
 *
 * @package CKFinder
 * @subpackage CommandHandlers
 * @copyright CKSource - Frederico Knabben
 */
class CKFinder_Connector_CommandHandler_FileUpload extends CKFinder_Connector_CommandHandler_CommandHandlerBase
{
    /**
     * Command name
     *
     * @access protected
     * @var string
     */
    protected $command = "FileUpload";

    /**
     * send response (save uploaded file, resize if required)
     * @access public
     *
     */
    public function sendResponse()
    {
        $iErrorNumber = CKFINDER_CONNECTOR_ERROR_NONE;

        $_config =& CKFinder_Connector_Core_Factory::getInstance("Core_Config");
        $oRegistry =& CKFinder_Connector_Core_Factory::getInstance("Core_Registry");
        $oRegistry->set("FileUpload_fileName", "unknown file");

        if (!is_array($_FILES) || count($_FILES) !== 1) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_INVALID);
        }

        $uploadedFile = array_shift($_FILES);
        if (!is_array($uploadedFile)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_INVALID);
        }
        $requiredUploadFields = array('name', 'type', 'tmp_name', 'error', 'size');
        foreach ($requiredUploadFields as $field) {
            if (!array_key_exists($field, $uploadedFile)) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_INVALID);
            }
        }

        if (!is_string($uploadedFile['name']) || !is_string($uploadedFile['type'])
            || !is_string($uploadedFile['tmp_name']) || !is_int($uploadedFile['error'])
            || !is_int($uploadedFile['size']) || $uploadedFile['size'] < 0) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_INVALID);
        }

        switch ($uploadedFile['error']) {
            case UPLOAD_ERR_OK:
                if (!is_uploaded_file($uploadedFile['tmp_name'])) {
                    $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_CORRUPT);
                }
                break;

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_TOO_BIG);
                break;

            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_CORRUPT);
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_NO_TMP_DIR);
                break;

            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_ACCESS_DENIED);
                break;

            default:
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_INVALID);
        }

        $sUnsafeFileName = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding(CKFinder_Connector_Utils_Misc::mbBasename($uploadedFile['name']));
        $sFileName = str_replace(array(":", "*", "?", "|", "/"), "_", $sUnsafeFileName);
        if ($_config->getDisallowUnsafeCharacters()) {
          $sFileName = str_replace(";", "_", $sFileName);
        }
        if ($_config->forceAscii()) {
            $sFileName = CKFinder_Connector_Utils_FileSystem::convertToAscii($sFileName);
        }
        if ($sFileName != $sUnsafeFileName) {
          $iErrorNumber = CKFINDER_CONNECTOR_ERROR_UPLOADED_INVALID_NAME_RENAMED;
        }
        $oRegistry->set("FileUpload_fileName", $sFileName);

        $this->checkConnector();
        $this->checkRequest();

        if (!$this->_currentFolder->checkAcl(CKFINDER_CONNECTOR_ACL_FILE_UPLOAD)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UNAUTHORIZED);
        }

        $_resourceTypeConfig = $this->_currentFolder->getResourceTypeConfig();
        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($sFileName) || $_resourceTypeConfig->checkIsHiddenFile($sFileName)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_NAME);
        }

        $resourceTypeInfo = $this->_currentFolder->getResourceTypeConfig();
        if (!$resourceTypeInfo->checkExtension($sFileName)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_EXTENSION);
        }

        $sFileNameOrginal = $sFileName;
        $oRegistry->set("FileUpload_fileName", $sFileName);
        $oRegistry->set("FileUpload_url", $this->_currentFolder->getUrl());

        $maxSize = $resourceTypeInfo->getMaxSize();
        if ($maxSize && $uploadedFile['size']>$maxSize) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_TOO_BIG);
        }

        if ($uploadedFile['size'] < 1 || !is_readable($uploadedFile['tmp_name'])) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_CORRUPT);
        }

        if (function_exists('finfo_open')) {
            $mimeInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = $mimeInfo !== false ? finfo_file($mimeInfo, $uploadedFile['tmp_name']) : false;
            if ($mimeInfo !== false) finfo_close($mimeInfo);
            $activeMimes = array(
                'text/html', 'text/javascript', 'application/javascript', 'application/x-javascript',
                'application/xml', 'text/xml', 'image/svg+xml', 'application/x-shockwave-flash',
                'application/x-httpd-php', 'text/x-php',
            );
            if (is_string($detectedMime) && in_array(strtolower($detectedMime), $activeMimes, true)) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_EXTENSION);
            }
        }

        $sExtension = strtolower(CKFinder_Connector_Utils_FileSystem::getExtension($sFileNameOrginal));

        if (($detectHtml = CKFinder_Connector_Utils_FileSystem::detectHtml($uploadedFile['tmp_name'])) === true ) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_WRONG_HTML_FILE);
        }

        $sExtension = strtolower(CKFinder_Connector_Utils_FileSystem::getExtension($sFileNameOrginal));
        $secureImageUploads = $_config->getSecureImageUploads();
        if ($secureImageUploads
        && ($isImageValid = CKFinder_Connector_Utils_FileSystem::isImageValid($uploadedFile['tmp_name'], $sExtension)) === false ) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_CORRUPT);
        }

        $sServerDir = $this->_currentFolder->getServerPath();
        $iCounter = 0;

        while (true)
        {
            $sFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($sServerDir, $sFileName);

            if (!CKFinder_Connector_Utils_FileSystem::isPathInside($sServerDir, $sFilePath)) {
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_ACCESS_DENIED);
            }

            $destinationHandle = @fopen($sFilePath, 'x');
            if ($destinationHandle === false) {
                $iCounter++;
                $sFileName =
                CKFinder_Connector_Utils_FileSystem::getFileNameWithoutExtension($sFileNameOrginal) .
                "(" . $iCounter . ")" . "." .
                CKFinder_Connector_Utils_FileSystem::getExtension($sFileNameOrginal);
                $oRegistry->set("FileUpload_fileName", $sFileName);

                $iErrorNumber = CKFINDER_CONNECTOR_ERROR_UPLOADED_FILE_RENAMED;
            } else {
                fclose($destinationHandle);
                if (false === move_uploaded_file($uploadedFile['tmp_name'], $sFilePath)) {
                    @unlink($sFilePath);
                    $iErrorNumber = CKFINDER_CONNECTOR_ERROR_ACCESS_DENIED;
                }
                else {
	                    if ($detectHtml === -1 && CKFinder_Connector_Utils_FileSystem::detectHtml($sFilePath) === true) {
                        @unlink($sFilePath);
                        $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_WRONG_HTML_FILE);
                    }
                    else if (isset($isImageValid) && $isImageValid === -1 && CKFinder_Connector_Utils_FileSystem::isImageValid($sFilePath, $sExtension) === false) {
                        @unlink($sFilePath);
                        $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_CORRUPT);
                    }
                }
                if (is_file($sFilePath) && ($perms = $_config->getChmodFiles())) {
                    @chmod($sFilePath, $perms & 0770);
                }
                break;
            }
        }

        if (!$_config->checkSizeAfterScaling()) {
            $this->_errorHandler->throwError($iErrorNumber, true, false);
        }

        //resize image if required
        require_once CKFINDER_CONNECTOR_LIB_DIR . "/CommandHandler/Thumbnail.php";
        $_imagesConfig = $_config->getImagesConfig();

        if ($_imagesConfig->getMaxWidth()>0 && $_imagesConfig->getMaxHeight()>0 && $_imagesConfig->getQuality()>0) {
            CKFinder_Connector_CommandHandler_Thumbnail::createThumb($sFilePath, $sFilePath, $_imagesConfig->getMaxWidth(), $_imagesConfig->getMaxHeight(), $_imagesConfig->getQuality(), true) ;
        }

        if ($_config->checkSizeAfterScaling()) {
            //check file size after scaling, attempt to delete if too big
            clearstatcache();
            if ($maxSize && filesize($sFilePath)>$maxSize) {
                @unlink($sFilePath);
                $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UPLOADED_TOO_BIG);
            }
            else {
                $this->_errorHandler->throwError($iErrorNumber, true, false);
            }
        }

        CKFinder_Connector_Core_Hooks::run('AfterFileUpload', array(&$this->_currentFolder, &$uploadedFile, &$sFilePath));
    }
}
