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
class CKFinder_Connector_ErrorHandler_QuickUpload extends CKFinder_Connector_ErrorHandler_Base {
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

        $editorFunctionNumber = isset($_GET['CKEditorFuncNum']) && is_scalar($_GET['CKEditorFuncNum']) ? (string)$_GET['CKEditorFuncNum'] : '';
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Type: text/html; charset=utf-8');

		/**
		 * echo <script> is not called before CKFinder_Connector_Utils_Misc::getErrorMessage
		 * because PHP has problems with including files that contain BOM character.
		 * Having BOM character after <script> tag causes a javascript error.
		 */
        echo "<script type=\"text/javascript\">";
        if (!empty($_GET['CKEditor'])) {
            $errorMessage = CKFinder_Connector_Utils_Misc::getErrorMessage($number, $sEncodedFileName);

            if (!$uploaded) {
                $sFileUrl = "";
                $sFileName = "";
                $sEncodedFileName = "";
            }

            $funcNum = preg_replace("/[^0-9]/", "", $editorFunctionNumber);
            $callbackUrl = json_encode($sFileUrl . CKFinder_Connector_Utils_Misc::encodeURIComponent($sEncodedFileName), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $callbackError = json_encode($errorMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            echo "window.parent.CKEDITOR.tools.callFunction(" . (int) $funcNum . ", " . $callbackUrl . ", " . $callbackError . ");";
        }
        else {
            if (!$uploaded) {
                echo "window.parent.OnUploadCompleted(" . $number . ", '', '', '') ;";
            } else {
                $callbackUrl = json_encode($sFileUrl . CKFinder_Connector_Utils_Misc::encodeURIComponent($sEncodedFileName), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                $callbackFileName = json_encode($sEncodedFileName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                echo "window.parent.OnUploadCompleted(" . $number . ", " . $callbackUrl . ", " . $callbackFileName . ", '') ;";
            }
        }
        echo "</script>";

        if ($exit) {
            exit;
        }
    }
}
