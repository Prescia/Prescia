<?php

declare(strict_types=1);

/**
 * Conservative migration helper for the PHP 8.3 transition.
 *
 * Default mode is a read-only audit. Use --apply only after reviewing the
 * proposed files; every changed file is copied to a timestamped backup.
 */

const ROOT = __DIR__ . '/..';
const DEFAULT_BACKUP_ROOT = ROOT . '/_temp/migration-backups';

$options = getopt('', ['apply', 'backup-dir:', 'report:']);
$apply = array_key_exists('apply', $options);
$backupRoot = isset($options['backup-dir'])
    ? (string) $options['backup-dir']
    : DEFAULT_BACKUP_ROOT;
$reportPath = isset($options['report'])
    ? (string) $options['report']
    : ROOT . '/_temp/php83-migration-report.md';

$phpFiles = collectPhpFiles(ROOT);
$changes = [];
$blockers = [];

foreach ($phpFiles as $file) {
    $contents = (string) file_get_contents($file);
    $migrated = replaceShortOpenTags($contents);

    if ($migrated !== $contents) {
        $changes[$file] = $migrated;
    }

    if (preg_match('/\b(mysql_|ereg\s*\(|each\s*\(|create_function\s*\(|get_magic_quotes_gpc\s*\()/i', $contents)) {
        $blockers[] = "API removida/obsoleta ainda encontrada: {$file}";
    }
}

$dockerfile = ROOT . '/Dockerfile';
if (is_file($dockerfile)) {
    $contents = (string) file_get_contents($dockerfile);
    $migrated = preg_replace('/^FROM\s+php:8\.2-apache\s*$/mi', 'FROM php:8.3-apache', $contents);
    if (is_string($migrated) && $migrated !== $contents) {
        $changes[$dockerfile] = $migrated;
    }
    if (!preg_match('/^FROM\s+php:8\.3(?:[.-]|$)/mi', $contents)) {
        $blockers[] = 'Dockerfile não usa imagem PHP 8.3.';
    }
}

$securityPatterns = [
    'Senha mestre/credenciais em texto puro: revisar #11 e #13.',
    'SQL concatenado: revisar #9; prepared statements não podem ser migrados automaticamente.',
    'CSRF: revisar #15; tokens precisam ser adicionados por fluxo.',
    'Sessão/cookies: revisar #17; atributos e regeneração exigem teste de integração.',
    'unserialize(): revisar #19; preferir JSON ou allowed_classes explícito.',
    'Uploads/CKFinder: revisar #16; isolar armazenamento e bloquear execução.',
    'Hardening Docker/debug/headers: revisar #21 e #23.',
    'XSS por sanitização contextual: revisar #24.',
    'Rate limiting de login: revisar #26.',
];

if ($apply && $changes !== []) {
    $backupDir = $backupRoot . '/' . date('Ymd_His');
    if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true) && !is_dir($backupDir)) {
        fwrite(STDERR, "Não foi possível criar backup em {$backupDir}.\n");
        exit(2);
    }

    foreach ($changes as $file => $newContents) {
        $relative = ltrim(str_replace(ROOT, '', $file), DIRECTORY_SEPARATOR);
        $backupFile = $backupDir . '/' . $relative;
        $backupParent = dirname($backupFile);
        if (!is_dir($backupParent) && !mkdir($backupParent, 0750, true) && !is_dir($backupParent)) {
            fwrite(STDERR, "Não foi possível criar diretório de backup para {$relative}.\n");
            exit(2);
        }
        if (!copy($file, $backupFile)) {
            fwrite(STDERR, "Backup falhou para {$relative}; nenhuma alteração adicional será aplicada.\n");
            exit(2);
        }
        if (file_put_contents($file, $newContents, LOCK_EX) === false) {
            fwrite(STDERR, "Escrita falhou para {$relative}; restaure o backup {$backupDir}.\n");
            exit(2);
        }
    }
    $mode = "Aplicado. Backup: {$backupDir}";
} else {
    $mode = $changes === [] ? 'Nenhuma alteração mecânica pendente.' : 'Dry-run: nenhuma alteração foi escrita. Use --apply após revisão.';
}

$report = "# Relatório de migração PHP 8.3\n\n";
$report .= '> Este relatório é gerado pelo migrador conservador. Vulnerabilidades de autenticação, autorização e entrada exigem implementação e testes manuais; não são corrigidas automaticamente.\n\n';
$report .= "**Modo:** {$mode}\n\n";
$report .= '## Alterações mecânicas detectadas' . "\n\n";
if ($changes === []) {
    $report .= "Nenhuma alteração mecânica pendente.\n\n";
} else {
    foreach (array_keys($changes) as $file) {
        $report .= '- `' . ltrim(str_replace(ROOT, '', $file), DIRECTORY_SEPARATOR) . "`\n";
    }
    $report .= "\n";
}
$report .= '## Bloqueadores de compatibilidade' . "\n\n";
if ($blockers === []) {
    $report .= "Nenhum bloqueador mecânico detectado pelo scanner.\n\n";
} else {
    foreach ($blockers as $blocker) {
        $report .= '- ' . $blocker . "\n";
    }
    $report .= "\n";
}
$report .= '## Itens de segurança que exigem correção manual' . "\n\n";
foreach ($securityPatterns as $item) {
    $report .= '- ' . $item . "\n";
}
$report .= "\n## Próximos passos\n\n";
$report .= "1. Revise o diff e os backups antes de executar a aplicação.\n";
$report .= "2. Execute `composer test` em PHP 8.3.\n";
$report .= "3. Corrija e teste todos os bloqueadores e issues de segurança no GitHub.\n";
$report .= "4. Só então faça deploy; o migrador não substitui revisão de segurança.\n";

$reportParent = dirname($reportPath);
if (!is_dir($reportParent) && !mkdir($reportParent, 0750, true) && !is_dir($reportParent)) {
    fwrite(STDERR, "Não foi possível criar o diretório do relatório: {$reportParent}\n");
    exit(2);
}
if (file_put_contents($reportPath, $report, LOCK_EX) === false) {
    fwrite(STDERR, "Não foi possível gravar o relatório: {$reportPath}\n");
    exit(2);
}

echo "{$mode}\nRelatório: {$reportPath}\n";
exit($blockers === [] ? 0 : 1);

/** @return list<string> */
function collectPhpFiles(string $root): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }
        if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
            continue;
        }
        if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . '_temp' . DIRECTORY_SEPARATOR)) {
            continue;
        }
        $files[] = $file->getPathname();
    }

    sort($files);
    return $files;
}

function replaceShortOpenTags(string $contents): string
{
    // Conservative source migration: preserves <?php, <?= and XML declarations.
    return (string) preg_replace('/<\?(?!php|=|xml)/i', '<?php', $contents);
}
