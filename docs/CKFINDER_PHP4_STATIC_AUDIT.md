# CKFinder upload static audit

## PHP4/PHP5 upload-related files
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CommandHandlerBase.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CreateFolder.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFolder.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DownloadFile.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/GetFiles.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/GetFolders.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Init.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/LoadCookies.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/QuickUpload.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFolder.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Thumbnail.php
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/XmlCommandHandlerBase.php
pages/_js/ckfinder/core/connector/php/php4/Core/AccessControlConfig.php
pages/_js/ckfinder/core/connector/php/php4/Core/Config.php
pages/_js/ckfinder/core/connector/php/php4/Core/Connector.php
pages/_js/ckfinder/core/connector/php/php4/Core/Factory.php
pages/_js/ckfinder/core/connector/php/php4/Core/FolderHandler.php
pages/_js/ckfinder/core/connector/php/php4/Core/Hooks.php
pages/_js/ckfinder/core/connector/php/php4/Core/ImagesConfig.php
pages/_js/ckfinder/core/connector/php/php4/Core/Registry.php
pages/_js/ckfinder/core/connector/php/php4/Core/ResourceTypeConfig.php
pages/_js/ckfinder/core/connector/php/php4/Core/ThumbnailsConfig.php
pages/_js/ckfinder/core/connector/php/php4/Core/Xml.php
pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/Base.php
pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/FileUpload.php
pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/Http.php
pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/QuickUpload.php
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php
pages/_js/ckfinder/core/connector/php/php4/Utils/Misc.php
pages/_js/ckfinder/core/connector/php/php4/Utils/Security.php
pages/_js/ckfinder/core/connector/php/php4/Utils/XmlNode.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CommandHandlerBase.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CreateFolder.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFolder.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/GetFiles.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/GetFolders.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Init.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/LoadCookies.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/QuickUpload.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Thumbnail.php
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/XmlCommandHandlerBase.php
pages/_js/ckfinder/core/connector/php/php5/Core/AccessControlConfig.php
pages/_js/ckfinder/core/connector/php/php5/Core/Config.php
pages/_js/ckfinder/core/connector/php/php5/Core/Connector.php
pages/_js/ckfinder/core/connector/php/php5/Core/Factory.php
pages/_js/ckfinder/core/connector/php/php5/Core/FolderHandler.php
pages/_js/ckfinder/core/connector/php/php5/Core/Hooks.php
pages/_js/ckfinder/core/connector/php/php5/Core/ImagesConfig.php
pages/_js/ckfinder/core/connector/php/php5/Core/Registry.php
pages/_js/ckfinder/core/connector/php/php5/Core/ResourceTypeConfig.php
pages/_js/ckfinder/core/connector/php/php5/Core/ThumbnailsConfig.php
pages/_js/ckfinder/core/connector/php/php5/Core/Xml.php
pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/Base.php
pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/FileUpload.php
pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/Http.php
pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/QuickUpload.php
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php
pages/_js/ckfinder/core/connector/php/php5/Utils/Misc.php
pages/_js/ckfinder/core/connector/php/php5/Utils/Security.php
pages/_js/ckfinder/core/connector/php/php5/Utils/XmlNode.php

## File write/move/delete sinks
pages/_js/ckfinder/core/connector/php/php4/Utils/Misc.php:276:        if (false === ($f1 = fopen($filename, "rb"))) {
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:130:    function unlink($path)
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:139:            return @unlink($path);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:151:                CKFinder_Connector_Utils_FileSystem::unlink($file);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:156:        if(!@rmdir($path)) {
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:233:        $handle = fopen($filename, 'rb');
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:451:            $bCreated = @mkdir($dir, $perms);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:455:            $bCreated = @mkdir($dir);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:470:            $result = @mkdir($dir, $perms);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:474:            $result = @mkdir($dir);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:492:        $fp = @fopen($filePath, 'rb');
pages/_js/ckfinder/core/connector/php/php5/Utils/Misc.php:274:        if (false === ($f1 = fopen($filename, "rb"))) {
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:116:        if (!@copy($sourcePath, $temporaryPath) || !@rename($temporaryPath, $destinationPath)) {
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:117:            @unlink($temporaryPath);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:182:    public static function unlink($path)
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:191:            return @unlink($path);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:203:                CKFinder_Connector_Utils_FileSystem::unlink($file);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:208:        if(!@rmdir($path)) {
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:263:        $handle = fopen($filename, 'rb');
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:482:            $bCreated = @mkdir($dir, $perms, true);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:486:            $bCreated = @mkdir($dir, 0777, true);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:504:        $fp = @fopen($filePath, 'rb');
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Thumbnail.php:160:                copy($sourceFile, $targetFile);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFolder.php:93:        $bMoved = @rename($oldFolderPath, $newFolderPath);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFolder.php:99:            if (!@rename($this->_currentFolder->getThumbsServerPath(), $newThumbsServerPath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFolder.php:100:                CKFinder_Connector_Utils_FileSystem::unlink($this->_currentFolder->getThumbsServerPath());
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:114:        $bMoved = @rename($filePath, $newFilePath);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:122:            CKFinder_Connector_Utils_FileSystem::unlink($thumbPath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Thumbnail.php:161:                copy($sourceFile, $targetFile);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:186:                        if (!@unlink($destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:192:                            if (!@rename($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:218:                        if (!@rename($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:234:                    if (!@rename($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:103:        $bMoved = @rename($oldFolderPath, $newFolderPath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:109:            if (!@rename($this->_currentFolder->getThumbsServerPath(), $newThumbsServerPath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:110:                CKFinder_Connector_Utils_FileSystem::unlink($this->_currentFolder->getThumbsServerPath());
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:121:        $bMoved = @rename($filePath, $newFilePath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:132:            CKFinder_Connector_Utils_FileSystem::unlink($thumbPath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:191:                        if (!@rename($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:214:                        if (!@rename($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:230:                    if (!@rename($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFolder.php:76:        if (!CKFinder_Connector_Utils_FileSystem::unlink($folderServerPath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFolder.php:80:        CKFinder_Connector_Utils_FileSystem::unlink($this->_currentFolder->getThumbsServerPath());
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php:158:                if (false === move_uploaded_file($uploadedFile['tmp_name'], $sFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php:163:                        @unlink($sFilePath);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php:167:                        @unlink($sFilePath);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php:196:                @unlink($sFilePath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:86:        if (!@unlink($filePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:100:            @unlink($thumbPath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CreateFolder.php:86:            $bCreated = @mkdir($sServerDir, $perms);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CreateFolder.php:90:            $bCreated = @mkdir($sServerDir);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:183:            $destinationHandle = @fopen($sFilePath, 'x');
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:195:                if (false === move_uploaded_file($uploadedFile['tmp_name'], $sFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:196:                    @unlink($sFilePath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:201:                        @unlink($sFilePath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:205:                        @unlink($sFilePath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:232:                @unlink($sFilePath);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php:221:                // copy() overwrites without warning
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFolder.php:69:        if (!CKFinder_Connector_Utils_FileSystem::unlink($folderServerPath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFolder.php:73:        CKFinder_Connector_Utils_FileSystem::unlink($this->_currentFolder->getThumbsServerPath());
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php:201:                        if (!@copy($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php:216:                // copy() overwrites without warning
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php:218:                    if (!@copy($sourceFilePath, $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php:83:        if (!@unlink($filePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php:92:            @unlink($thumbPath);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CreateFolder.php:83:            $bCreated = @mkdir($sServerDir, $perms);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CreateFolder.php:87:            $bCreated = @mkdir($sServerDir);

## Path containment and filename checks
pages/_js/ckfinder/core/connector/php/php4/Utils/Misc.php:234:    * UTF-8 compatible version of basename()
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:38:    function combinePaths($path1, $path2)
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:80:    function checkFileName($fileName)
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:119:        return CKFinder_Connector_Utils_FileSystem::checkFileName($folderName);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:413:            $sRealPath = dirname($_SERVER['SCRIPT_FILENAME']);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:417:             * realpath — Returns canonicalized absolute pathname
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:419:            $sRealPath = realpath( './' ) ;
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:427:        $sSelfPath = dirname($_SERVER['PHP_SELF']);
pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php:463:        if (!CKFinder_Connector_Utils_FileSystem::createDirectoryRecursively(dirname($dir))) {
pages/_js/ckfinder/core/connector/php/php5/Utils/Misc.php:232:    * UTF-8 compatible version of basename()
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:38:    public static function combinePaths($path1, $path2)
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:81:    public static function isPathInside($basePath, $candidatePath)
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:83:        $base = realpath($basePath);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:88:        $candidate = realpath($candidatePath);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:90:            $parent = realpath(dirname($candidatePath));
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:94:            $candidate = self::combinePaths($parent, basename($candidatePath));
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:111:        $temporaryPath = tempnam(dirname($destinationPath), '.ckfinder-');
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:132:    public static function checkFileName($fileName)
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:171:        return CKFinder_Connector_Utils_FileSystem::checkFileName($folderName);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:443:            $sRealPath = dirname($_SERVER['SCRIPT_FILENAME']);
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:447:             * realpath — Returns canonicalized absolute pathname
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:449:            $sRealPath = realpath( './' ) ;
pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php:457:        $sSelfPath = dirname($_SERVER['PHP_SELF']);
pages/_js/ckfinder/core/connector/php/php4/Core/ResourceTypeConfig.php:228:    function checkExtension(&$fileName, $renameIfRequired = true)
pages/_js/ckfinder/core/connector/php/php4/Core/ResourceTypeConfig.php:299:    function checkIsHiddenFile($fileName)
pages/_js/ckfinder/core/connector/php/php5/Core/ResourceTypeConfig.php:228:    public function checkExtension(&$fileName, $renameIfRequired = true)
pages/_js/ckfinder/core/connector/php/php5/Core/ResourceTypeConfig.php:299:    public function checkIsHiddenFile($fileName)
pages/_js/ckfinder/core/connector/php/php4/Core/FolderHandler.php:200:            $this->_serverPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_resourceTypeConfig->getDirectory(), ltrim($this->_clientPath, "/"));
pages/_js/ckfinder/core/connector/php/php4/Core/FolderHandler.php:221:            $this->_thumbsServerPath = CKFinder_Connector_Utils_FileSystem::combinePaths($_thumbnailsConfig->getDirectory(), $this->_resourceTypeConfig->getName());
pages/_js/ckfinder/core/connector/php/php4/Core/FolderHandler.php:224:            $this->_thumbsServerPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_thumbsServerPath, ltrim($this->_clientPath, '/'));
pages/_js/ckfinder/core/connector/php/php5/Core/FolderHandler.php:200:            $this->_serverPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_resourceTypeConfig->getDirectory(), ltrim($this->_clientPath, "/"));
pages/_js/ckfinder/core/connector/php/php5/Core/FolderHandler.php:221:            $this->_thumbsServerPath = CKFinder_Connector_Utils_FileSystem::combinePaths($_thumbnailsConfig->getDirectory(), $this->_resourceTypeConfig->getName());
pages/_js/ckfinder/core/connector/php/php5/Core/FolderHandler.php:224:            $this->_thumbsServerPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_thumbsServerPath, ltrim($this->_clientPath, '/'));
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Thumbnail.php:72:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Thumbnail.php:75:        $sourceFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Thumbnail.php:77:        if ($_resourceTypeInfo->checkIsHiddenFile($fileName) || !file_exists($sourceFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Thumbnail.php:81:        $thumbFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/GetFiles.php:98:                    if (!$resourceTypeInfo->checkExtension($filename, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/GetFiles.php:101:                    if ($resourceTypeInfo->checkIsHiddenFile($filename)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFolder.php:87:        $newFolderPath = dirname($oldFolderPath).DIRECTORY_SEPARATOR.$newFolderName.DIRECTORY_SEPARATOR;
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFolder.php:98:            $newThumbsServerPath = dirname($this->_currentFolder->getThumbsServerPath()) . '/' . $newFolderName . '/';
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php:79:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($sFileName) || $_resourceTypeConfig->checkIsHiddenFile($sFileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php:84:        if (!$resourceTypeInfo->checkExtension($sFileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php:144:            $sFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($sServerDir, $sFileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:75:        if (!$resourceTypeInfo->checkExtension($newFileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:79:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName) || $resourceTypeInfo->checkIsHiddenFile($fileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:83:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($newFileName) || $resourceTypeInfo->checkIsHiddenFile($newFileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:87:        if (!$resourceTypeInfo->checkExtension($fileName, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:94:        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:95:        $newFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $newFileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:103:        if (!is_writable(dirname($newFilePath))) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php:121:            $thumbPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DownloadFile.php:60:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DownloadFile.php:64:        if (!$_resourceTypeInfo->checkExtension($fileName, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DownloadFile.php:68:        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DownloadFile.php:69:        if ($_resourceTypeInfo->checkIsHiddenFile($fileName) || !file_exists($filePath) || !is_file($filePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Thumbnail.php:72:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Thumbnail.php:76:        $sourceFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Thumbnail.php:78:        if ($_resourceTypeInfo->checkIsHiddenFile($fileName) || !file_exists($sourceFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Thumbnail.php:82:        $thumbFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php:67:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName) || $_resourceTypeInfo->checkIsHiddenFile($fileName)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php:71:        if (!$_resourceTypeInfo->checkExtension($fileName, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php:75:        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php:90:            $thumbPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:87:        $newFolderPath = dirname($oldFolderPath).DIRECTORY_SEPARATOR.$newFolderName.DIRECTORY_SEPARATOR;
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:89:        if (!CKFinder_Connector_Utils_FileSystem::isPathInside($resourceTypeInfo->getDirectory(), $oldFolderPath)
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:90:            || !CKFinder_Connector_Utils_FileSystem::isPathInside($resourceTypeInfo->getDirectory(), $newFolderPath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:95:        if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_thumbnailsConfig->getDirectory(), $this->_currentFolder->getThumbsServerPath())) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php:108:            $newThumbsServerPath = dirname($this->_currentFolder->getThumbsServerPath()) . '/' . $newFolderName . '/';
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:100:                if (!CKFinder_Connector_Utils_FileSystem::checkFileName($name) || preg_match(CKFINDER_REGEX_INVALID_PATH, $path)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:115:                if (!$_resourceTypeConfig[$type]->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:123:                    if (!$currentResourceTypeConfig->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php:143:                if ($currentResourceTypeConfig->checkIsHiddenFile($name)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:75:        if (!$resourceTypeInfo->checkExtension($newFileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:79:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName) || $resourceTypeInfo->checkIsHiddenFile($fileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:83:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($newFileName) || $resourceTypeInfo->checkIsHiddenFile($newFileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:87:        if (!$resourceTypeInfo->checkExtension($fileName, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:95:        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:96:        $newFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $newFileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:98:        if (!CKFinder_Connector_Utils_FileSystem::isPathInside($resourceTypeInfo->getDirectory(), $filePath)
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:99:            || !CKFinder_Connector_Utils_FileSystem::isPathInside($resourceTypeInfo->getDirectory(), $newFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:109:        if (!is_writable(dirname($newFilePath))) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:128:            $thumbPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php:129:            if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_config->getThumbnailsConfig()->getDirectory(), $thumbPath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CreateFolder.php:70:        $sServerDir = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $sNewFolderName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php:60:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php:64:        if (!$_resourceTypeInfo->checkExtension($fileName, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php:68:        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php:69:        if ($_resourceTypeInfo->checkIsHiddenFile($fileName)
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php:70:            || !CKFinder_Connector_Utils_FileSystem::isPathInside($_resourceTypeInfo->getDirectory(), $filePath)
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:100:                if (!CKFinder_Connector_Utils_FileSystem::checkFileName($name) || preg_match(CKFINDER_REGEX_INVALID_PATH, $path)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:115:                if (!$_resourceTypeConfig[$type]->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:123:                    if (!$currentResourceTypeConfig->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:143:                if ($currentResourceTypeConfig->checkIsHiddenFile($name)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:164:                if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_resourceTypeConfig[$type]->getDirectory(), $sourceFilePath)
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php:165:                    || !CKFinder_Connector_Utils_FileSystem::isPathInside($currentResourceTypeConfig->getDirectory(), $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php:100:                if (!CKFinder_Connector_Utils_FileSystem::checkFileName($name) || preg_match(CKFINDER_REGEX_INVALID_PATH, $path)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php:115:                if (!$_resourceTypeConfig[$type]->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php:123:                    if (!$currentResourceTypeConfig->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php:143:                if ($currentResourceTypeConfig->checkIsHiddenFile($name)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFolder.php:71:        if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_resourceTypeInfo->getDirectory(), $folderServerPath)
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFolder.php:72:            || !CKFinder_Connector_Utils_FileSystem::isPathInside($_config->getThumbnailsConfig()->getDirectory(), $this->_currentFolder->getThumbsServerPath())) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php:100:                if (!CKFinder_Connector_Utils_FileSystem::checkFileName($name) || preg_match(CKFINDER_REGEX_INVALID_PATH, $path)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php:115:                if (!$_resourceTypeConfig[$type]->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php:123:                    if (!$currentResourceTypeConfig->checkExtension($name, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php:143:                if ($currentResourceTypeConfig->checkIsHiddenFile($name)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php:164:                if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_resourceTypeConfig[$type]->getDirectory(), $sourceFilePath)
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php:165:                    || !CKFinder_Connector_Utils_FileSystem::isPathInside($currentResourceTypeConfig->getDirectory(), $destinationFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CreateFolder.php:70:        $sServerDir = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $sNewFolderName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CreateFolder.php:71:        if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_resourceTypeConfig->getDirectory(), $sServerDir)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:66:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName) || $_resourceTypeInfo->checkIsHiddenFile($fileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:70:        if (!$_resourceTypeInfo->checkExtension($fileName, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:74:        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:76:        if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_resourceTypeInfo->getDirectory(), $filePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:93:            $thumbPath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $fileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php:96:            if (!CKFinder_Connector_Utils_FileSystem::isPathInside($_config->getThumbnailsConfig()->getDirectory(), $thumbPath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:123:        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($sFileName) || $_resourceTypeConfig->checkIsHiddenFile($sFileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:128:        if (!$resourceTypeInfo->checkExtension($sFileName)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:177:            $sFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($sServerDir, $sFileName);
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php:179:            if (!CKFinder_Connector_Utils_FileSystem::isPathInside($sServerDir, $sFilePath)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/GetFiles.php:98:                    if (!$resourceTypeInfo->checkExtension($filename, false)) {
pages/_js/ckfinder/core/connector/php/php5/CommandHandler/GetFiles.php:101:                    if ($resourceTypeInfo->checkIsHiddenFile($filename)) {

## PHP4 helper availability
38:    function combinePaths($path1, $path2)
80:    function checkFileName($fileName)
