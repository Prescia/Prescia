<?php

/*
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
 * @subpackage ErrorHandler
 * @copyright CKSource - Frederico Knabben
 */

/**
 * Include base error handling class
 */
require_once CKFINDER_CONNECTOR_LIB_DIR . "/ErrorHandler/Base.php";

/**
 * File upload error handler
 *
 * @package CKFinder
 * @subpackage ErrorHandler
 * @copyright CKSource - Frederico Knabben
 */
class CKFinder_Connector_ErrorHandler_FileUpload extends CKFinder_Connector_ErrorHandler_Base {
    /**
     * Throw file upload error, return true if error has been thrown, false if error has been catched
     *
     * @param int $number
     * @param string $text
     * @access public
     */
    public function throwError($number, $uploaded = false, $exit = true) {
        if ($this->_catchAllErrors || in_array($number, $this->_skipErrorsArray)) {
            return false;
        }

        $oRegistry = & CKFinder_Connector_Core_Factory :: getInstance("Core_Registry");
        $sFileName = $oRegistry->get("FileUpload_fileName");
        $sFileUrl = $oRegistry->get("FileUpload_url");
        $sEncodedFileName = CKFinder_Connector_Utils_FileSystem::convertToConnectorEncoding($sFileName);

        $errorMessage = CKFinder_Connector_Utils_Misc::getErrorMessage($number, $sEncodedFileName);
        if (!$uploaded) {
            $sFileName = "";
            $sEncodedFileName = "";
        }
        $responseType = isset($_GET['response_type']) && is_string($_GET['response_type']) ? $_GET['response_type'] : '';
        $functionNumber = isset($_GET['CKFinderFuncNum']) && is_scalar($_GET['CKFinderFuncNum']) ? (string)$_GET['CKFinderFuncNum'] : '';
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        if ($responseType === 'txt') {
            header('Content-Type: text/plain; charset=utf-8');
            echo $sFileName."|".$errorMessage;
        }
        else {
            header('Content-Type: text/html; charset=utf-8');
            echo "<script type=\"text/javascript\">";
            if ($functionNumber !== '') {

                if (!$uploaded) {
                    $sFileUrl = "";
                    $sFileName = "";
                }

                $funcNum = preg_replace("/[^0-9]/", "", $functionNumber);
                $callbackUrl = json_encode($sFileUrl . $sFileName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                $callbackError = json_encode($errorMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                echo "window.parent.CKFinder.tools.callFunction(" . (int) $funcNum . ", " . $callbackUrl . ", " . $callbackError . ");";
            }
            else {
                $callbackFileName = json_encode($sEncodedFileName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                $callbackError = json_encode($errorMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                echo "window.parent.OnUploadCompleted(" . $callbackFileName . ", " . $callbackError . ") ;";
            }
            echo "</script>";
        }

        if ($exit) {
            exit;
        }
    }
}
