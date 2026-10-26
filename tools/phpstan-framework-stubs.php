<?php

/**
 * Static-analysis declarations for Prescia's legacy global API.
 *
 * This file is never loaded by the application. It intentionally contains
 * signatures only, so PHPStan can analyze dynamically loaded framework code
 * without executing filesystem, mail, image, or database side effects.
 */

function addslashes_EX(mixed $value, bool $isHtml = true, mixed $dbo = false): string {}
/**
 * @param array<int|string, scalar|array<int|string, scalar>>|false $array
 * @param array<int, int|string> $exclude
 */
function arrayToString(mixed $array = false, array $exclude = [], bool $noArrays = false): string {}
function cWriteFile(string $file, string $content, bool $append = false, bool $binary = false): bool {}
function dieFreakingThumbs(string $folder): void {}
function cleanString(mixed $data, bool $isHtml = false, bool $allowAdvanced = false, mixed $dbo = false): string {}
function console(mixed $core, mixed $command): mixed { return null; }
function cropImage(mixed ...$arguments): mixed { return false; }
function datecalc(mixed ...$arguments): mixed { return false; }
/** @param array<string, int|string> $date */
function adodb_daylight_sv(array &$date, bool $isGmt = false): void {}
function datecompare(mixed ...$arguments): mixed { return false; }
/** @return array{0: list<string>, 1: string, 2: string, 3: string} */
function extractUri(string $installRoot = '', string $uri = ''): array {}
function fd(mixed $date, string $mask = 'd/m/Y'): string {}
function fv(mixed $value): string {}
function getmicrotime(): float {}
function htmlentities_ex(mixed $value, mixed ...$arguments): string {}
function humanSize(mixed $size): string {}
function isMail(mixed $mail, bool $allowExtended = false): int {}
/** @return list<string> */
function listFiles(mixed ...$arguments): array {}
function locateAnyFile(mixed ...$arguments): mixed { return false; }
function locateFile(mixed ...$arguments): mixed { return false; }
function makeDirs(mixed ...$arguments): bool {}
function parseHTML(mixed $html, bool $simplify = false): mixed {}
function quota(mixed ...$arguments): mixed { return false; }
function recursive_del(mixed ...$arguments): bool {}
function removeBOM(mixed &$data): void {}
function removeSimbols(mixed ...$arguments): string {}
function resizeImage(mixed ...$arguments): mixed { return false; }
function resizeImageCond(mixed ...$arguments): mixed { return false; }
function safe_mkdir(mixed ...$arguments): bool {}
function scriptTime(): float {}
function sendMail(mixed ...$arguments): mixed { return false; }
function storeFile(mixed ...$arguments): mixed { return 0; }
function stripHTML(mixed ...$arguments): string {}
function time_diff(mixed ...$arguments): mixed { return 0; }
function tomktime(mixed ...$arguments): int {}
function truncate(mixed $content, int $size = 50, string $final = '…', bool $stripHtml = false, bool $preserveEol = false): string {}
/** @return array<string, string>|string */
function xmlParamsParser(mixed $data): array|string {}

class CPrescia
{
    /** @var array<string, mixed> */
    public array $templateParams = [];
    /** @var list<array<int, mixed>> */
    public array $moduleOptions = [];
    public mixed $errorControl = null;

    public function applyMetaData(): void {}
    public function saveConfig(bool $force = false): void {}
}

class CPresciaFull extends CPrescia {}
class CKTCexternal {}
class ttree
{
    /** @param array<int, array<int|string, mixed>> $items */
    public function arrayToTree(array $items, string $separator, string $parentKey, string $titleKey): void {}
}
class xmlHandler {}

/** Constants discovered in the framework runtime and declared for analysis. */
const ADODB_TWODIGITYEAR_OFFSET = 0;
const AFF_BUILD = 0;
const AFF_VERSION = 0;
const CONS_ACTION_DELETE = 0;
const CONS_ACTION_INCLUDE = 0;
const CONS_ACTION_SELECT = 0;
const CONS_ACTION_UPDATE = 0;
const CONS_AFF_DATABASECONNECTOR = 0;
const CONS_AFF_ERRORHANDLER = 0;
const CONS_AFF_ERRORHANDLER_NOWARNING = 0;
const CONS_AUTH_SESSION_FAIL_EXPIRED = 0;
const CONS_AUTH_SESSION_FAIL_INACTIVE = 0;
const CONS_AUTH_SESSION_FAIL_UNKNOWN = 0;
const CONS_AUTH_SESSION_GUEST = 0;
const CONS_AUTH_SESSION_KEEP = 0;
const CONS_AUTH_SESSION_LOGGEDOUT = 0;
const CONS_AUTH_SESSION_NEW = 0;
const CONS_BROWSER = 0;
const CONS_BROWSER_ISMOB = 0;
const CONS_BROWSER_VERSION = 0;
const CONS_CACHE = 0;
const CONS_CACHECONTROL_MOD = 0;
const CONS_DEFAULT_IPP = 0;
const CONS_DEFAULT_MAX_BROWSERCACHETIME = 0;
const CONS_DEFAULT_MAX_OBJECTCACHETIME = 0;
const CONS_DEFAULT_MIN_BROWSERCACHETIME = 0;
const CONS_DEFAULT_MIN_OBJECTCACHETIME = 0;
const CONS_DEFAULT_PAGESIZE = 0;
const CONS_DEVELOPER = 0;
const CONS_ECONOMICMODE = 0;
const CONS_ERROR_TAG = 0;
const CONS_FILESEARCH_EXTENSIONS = 0;
const CONS_FLATTENURL = 0;
const CONS_FMANAGER_SAFE = 0;
const CONS_FOWARDER = 0;
const CONS_FREECPU = 0;
const CONS_GZIP_MINSIZE = 0;
const CONS_GZIP_OK = 0;
const CONS_HONEYPOT = 0;
const CONS_HONEYPOTURL = 0;
const CONS_HTTPD_ERRDIR = 0;
const CONS_HTTPD_ERRFILE = 0;
const CONS_IP = 0;
const CONS_LOGGING_ERROR = 0;
const CONS_LOGGING_NOTICE = 0;
const CONS_LOGGING_SUCCESS = 0;
const CONS_LOGGING_WARNING = 0;
const CONS_MASTERDOMAINS = 0;
const CONS_MASTERMAIL = 0;
const CONS_MAXRUNCONTENTSIZE = 0;
const CONS_MAX_QUOTA = 0;
const CONS_MODULE_AUTOCLEAN = 1201;
const CONS_MODULE_META = 1202;
const CONS_MODULE_NOUNDO = 1203;
const CONS_MODULE_PARENT = 1204;
const CONS_MODULE_SYSTEM = 1205;
const CONS_MODULE_VOLATILE = 1206;
const CONS_ONSERVER = 0;
const CONS_PM_MINTIME = 0;
const CONS_PM_TIME = 0;
const CONS_RUNCONTENT_NOIMGOVERRIDE = 0;
const CONS_SESSION_ACCESS_LEVEL = 0;
const CONS_SESSION_ACCESS_LEVEL_GUEST = 0;
const CONS_SESSION_ACCESS_PERMISSIONS = 0;
const CONS_SESSION_ACCESS_USER = 0;
const CONS_SESSION_CACHE = 0;
const CONS_SESSION_HONEYPOTLIST = 0;
const CONS_SESSION_LANG = 0;
const CONS_SESSION_LOG = 0;
const CONS_SESSION_LOGLEVEL = 0;
const CONS_SESSION_LOG_REQ = 0;
const CONS_SESSION_NOROBOTS = 0;
const CONS_SITESELECTOR = 0;
const CONS_SLOWQUERY_TH = 0;
const CONS_TIMELIMIT = 0;
const CONS_TIPO_ARRAY = 0;
const CONS_TIPO_DATE = 0;
const CONS_TIPO_DATETIME = 0;
const CONS_TIPO_ENUM = 0;
const CONS_TIPO_FLOAT = 0;
const CONS_TIPO_INT = 0;
const CONS_TIPO_LINK = 0;
const CONS_TIPO_OPTIONS = 0;
const CONS_TIPO_SERIALIZED = 0;
const CONS_TIPO_TEXT = 0;
const CONS_TIPO_UPLOAD = 0;
const CONS_TIPO_VC = 0;
const CONS_XML_AUTOFILL = 0;
const CONS_XML_AUTOPRUNE = 0;
const CONS_XML_CONDTHUMBNAILS = 0;
const CONS_XML_CUSTOM = 0;
const CONS_XML_DEFAULT = 0;
const CONS_XML_FIELDLIMIT = 0;
const CONS_XML_FILEMAXSIZE = 0;
const CONS_XML_FILEPATH = 0;
const CONS_XML_FILETYPES = 0;
const CONS_XML_FILTEREDBY = 0;
const CONS_XML_HTML = 0;
const CONS_XML_IGNORENEDIT = 0;
const CONS_XML_ISOWNER = 0;
const CONS_XML_JOIN = 0;
const CONS_XML_LINKTYPE = 0;
const CONS_XML_MANDATORY = 0;
const CONS_XML_META = 0;
const CONS_XML_MODULE = 0;
const CONS_XML_NOIMG = 0;
const CONS_XML_OPTIONS = 0;
const CONS_XML_READONLY = 0;
const CONS_XML_RESTRICT = 0;
const CONS_XML_SERIALIZED = 0;
const CONS_XML_SERIALIZEDMODEL = 0;
const CONS_XML_SIMPLEEDITFORCE = 0;
const CONS_XML_SOURCE = 0;
const CONS_XML_SPECIAL = 0;
const CONS_XML_SQL = 0;
const CONS_XML_THUMBNAILS = 0;
const CONS_XML_TIMESTAMP = 0;
const CONS_XML_TIPO = 0;
const CONS_XML_TWEAKIMAGES = 0;
const CONS_XML_UPDATESTAMP = 0;
const C_XHTML_LINKS = 0;
const C_XML_AUTOPARSE = 0;
const C_XML_LAX = 0;
const SWF_OBJECT = 0;

/** Legacy global functions discovered in the framework runtime. */
function IPv6To4(mixed ...$arguments): mixed { return null; }
function checkHTML(mixed ...$arguments): mixed { return null; }
function cleanHTML(mixed ...$arguments): mixed { return null; }
function date_diff_ex(mixed ...$arguments): mixed { return null; }
function getBrowser(mixed ...$arguments): mixed { return null; }
function getVideoFrame(mixed ...$arguments): mixed { return null; }
function isData(mixed ...$arguments): mixed { return null; }
function multiexplode(mixed ...$arguments): mixed { return null; }
function outputBrowserName(mixed ...$arguments): mixed { return null; }
function removeNull(mixed ...$arguments): mixed { return null; }
function vardump(mixed ...$arguments): mixed { return null; }
