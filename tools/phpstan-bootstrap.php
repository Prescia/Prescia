<?php

declare(strict_types=1);
require_once __DIR__ . '/phpstan-constants.php';

/**
 * PHPStan-only bootstrap.
 *
 * Only pure procedural libraries are loaded here. The application entry point,
 * session handling, database setup, mail delivery and filesystem side effects
 * are deliberately excluded from static analysis bootstrap execution.
 */
require_once __DIR__ . '/../prescia/lib/basic.php';
require_once __DIR__ . '/../prescia/lib/arrayToString.php';
require_once __DIR__ . '/../prescia/lib/listFiles.php';
require_once __DIR__ . '/../prescia/lib/removeSimbols.php';
