<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class SecurityRegressionTest extends TestCase
{
    public function testForumPreviewUsesParameterizedQueriesForRequestIds(): void
    {
        $payload = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/payload/content/preview.php');

        self::assertStringContainsString('$core->dbo->queryPrepared(', $payload);
        self::assertStringContainsString("'ii'", $payload);
        self::assertStringContainsString("'i'", $payload);
        self::assertStringNotContainsString(<<<'SQL'
WHERE id_forum=".$_POST['id_forum']
SQL, $payload);
        self::assertStringNotContainsString(<<<'SQL'
WHERE f.id=".$_POST['id_forum']
SQL, $payload);
    }

    public function testAuthenticatedUserHistoryUsesParameterizedUpdate(): void
    {
        $authControl = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/authControl.php');

        self::assertStringContainsString('SET history=?,userprefs=? WHERE id=?', $authControl);
        self::assertStringContainsString("array(\$history, serialize(\$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']), (int)\$_SESSION[CONS_SESSION_ACCESS_USER]['id'])", $authControl);
        self::assertStringNotContainsString('simpleQuery("UPDATE ".$loginModule->dbname." SET history=', $authControl);
    }

    public function testAuthBootstrapAndPermissionUpdatesUsePreparedValues(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/module.php');
        $authControl = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/authControl.php');

        self::assertStringContainsString('INSERT INTO ".$groupTable." SET name=?, level=?, id=?, permissions=?', $module);
        self::assertStringContainsString('INSERT INTO ".$userTable." SET name=?, id=?, id_group=?, login=?, password=?, active=?', $module);
        self::assertStringContainsString('UPDATE ".$groups->dbname." SET permissions=? WHERE id=?', $authControl);
        self::assertStringNotContainsString('simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname', $module);
        self::assertStringNotContainsString("addslashes_EX(\$_REQUEST['authcode']", $module);
    }

    public function testAuthBootstrapReadsUsePreparedExecutionForSqlArrays(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/module.php');

        self::assertSame(2, substr_count($module, 'queryPrepared($this->parent->dbo->sqlarray_echo($sql),"",array(),$r,$n)'));
        self::assertStringNotContainsString('$this->parent->dbo->query($sql,$r,$n)', $module);
    }

    public function testLoginUsesAccountAndIpRateLimiterWithoutUserEnumeration(): void
    {
        $authControl = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/authControl.php');

        self::assertStringContainsString("require_once dirname(__DIR__, 2) . '/lib/loginRateLimiter.php';", $authControl);
        self::assertStringContainsString('$this->getLoginLimiter()->isAllowed($login, $ip)', $authControl);
        self::assertStringContainsString('$this->recordLoginFailure($login, $ip);', $authControl);
        self::assertStringContainsString('$this->getLoginLimiter()->clear($login, $ip);', $authControl);
        self::assertStringContainsString('return CONS_AUTH_SESSION_FAIL_UNKNOWN;', $authControl);
    }

    public function testPresciaTesterDoesNotShipDefaultDatabaseCredentials(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../pages/presciatester/_config/config.php');

        self::assertStringContainsString('getenv("PRESCIA_DB_USER")', $config);
        self::assertStringContainsString('getenv("PRESCIA_DB_PASSWORD")', $config);
        self::assertStringNotContainsString('define("CONS_DB_USER","root")', $config);
        self::assertStringNotContainsString('define("CONS_DB_PASS","root")', $config);
    }

    public function testLabelTestValidatesDimensionsAndCellBudget(): void
    {
        $labelTest = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_labels/payload/content/label_test.php');

        self::assertStringContainsString('filter_var($value, FILTER_VALIDATE_INT)', $labelTest);
        self::assertStringContainsString('$cols * $rows > 10000', $labelTest);
        self::assertStringContainsString('$_REQUEST[\'cols\'] ?? null', $labelTest);
        self::assertStringContainsString('$_REQUEST[\'rows\'] ?? null', $labelTest);
        self::assertStringNotContainsString('$cols = $_REQUEST[\'cols\'];', $labelTest);
        self::assertStringNotContainsString('$rows = $_REQUEST[\'rows\'];', $labelTest);
    }

    public function testMasterLogsRestrictsTenantCodeToCanonicalLogDirectory(): void
    {
        $logs = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/master_logs.php');

        self::assertStringContainsString('preg_match(\'/^[A-Za-z0-9_-]+$/\', $code)', $logs);
        self::assertStringContainsString('realpath(CONS_PATH_LOGS)', $logs);
        self::assertStringContainsString('dirname($logDirectory) !== rtrim($logRoot, DIRECTORY_SEPARATOR)', $logs);
        self::assertStringNotContainsString('CONS_PATH_LOGS.$code', $logs);
    }

    public function testPresciaTesterResetRequiresNonProductionTestDatabase(): void
    {
        $reset = (string) file_get_contents(__DIR__ . '/../pages/presciatester/actions/reset.php');
        $dockerfile = (string) file_get_contents(__DIR__ . '/../Dockerfile');
        $configs = array(
            (string) file_get_contents(__DIR__ . '/../pages/prescia/_config/config.php'),
            (string) file_get_contents(__DIR__ . '/../pages/_newProjectTemplate/_config/config.php'),
        );

        self::assertStringContainsString("!CONS_ONSERVER && CONS_DB_BASE === 'presciatester'", $reset);
        self::assertStringContainsString('DirectoryMatch "^/var/www/app/pages/(presciatester|_newProjectTemplate)(/|$)"', $dockerfile);
        foreach ($configs as $config) {
            self::assertStringContainsString('getenv("PRESCIA_DB_USER")', $config);
            self::assertStringContainsString('getenv("PRESCIA_DB_PASSWORD")', $config);
            self::assertStringNotContainsString('define("CONS_DB_USER","root")', $config);
            self::assertStringNotContainsString('define("CONS_DB_PASS","root")', $config);
        }
    }

    public function testPresciaTesterNextstepUsesPreparedDatabaseReads(): void
    {
        $nextstep = (string) file_get_contents(__DIR__ . '/../pages/presciatester/actions/nextstep.php');

        self::assertSame(3, substr_count($nextstep, '$this->dbo->fetchPrepared('));
        self::assertStringContainsString('$this->dbo->queryPrepared("SELECT alpha,beta,oneofakind FROM dbp LIMIT 2", "", array(), $r, $n)', $nextstep);
        self::assertStringContainsString('SELECT id FROM auth_users WHERE login=?', $nextstep);
        self::assertStringNotContainsString('$this->dbo->fetch("SELECT id FROM dba LIMIT 1")', $nextstep);
        self::assertStringNotContainsString('$this->dbo->query("SELECT alpha,beta,oneofakind FROM dbp LIMIT 2"', $nextstep);
        self::assertStringNotContainsString("WHERE login='wd40user'", $nextstep);
    }

    public function testUndoCleanupLookupAndRemoteChecksUsePreparedQueries(): void
    {
        $undo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_undo/module.php');

        self::assertStringContainsString('queryPrepared($sql,\'i\',array($id),$record,$n)', $undo);
        self::assertStringContainsString('fetchPrepared($sql,$whereTypes,$whereParams,false)', $undo);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n)', $undo);
        self::assertStringNotContainsString('$core->dbo->simpleQuery("DELETE FROM ".$undoModule->dbname', $undo);
    }

    public function testAdministrativeUndoAndLinkerUsePreparedValues(): void
    {
        $undo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/undo.php');
        $multipleUndo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/multipleundo.php');
        $list = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/list.php');

        self::assertStringContainsString('queryPrepared($sql,\'i\',array($id),$r,$n)', $undo);
        self::assertStringContainsString('queryPrepared($sql,\'i\',array($id),$r,$n)', $multipleUndo);
        self::assertStringContainsString('queryPrepared("INSERT INTO ".$lmod->dbname', $list);
        self::assertStringNotContainsString('$core->dbo->simpleQuery($m)', $list);
    }

    public function testAdministrativeDashboardReadsUsePreparedExecution(): void
    {
        $index = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/index.php');
        $default = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/default.php');

        self::assertStringContainsString('$core->dbo->fetchPrepared($sql, "", array())', $index);
        self::assertStringContainsString('$core->dbo->queryPrepared($core->dbo->sqlarray_echo($sql), "", array(), $r, $n)', $default);
        self::assertStringNotContainsString('$core->dbo->fetch($sql)', $index);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n)', $default);
    }

    public function testAdministrativeAjaxMonitorUsesPreparedExecution(): void
    {
        $monitor = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/ajaxmonitor.php');

        self::assertStringContainsString('$core->dbo->queryPrepared($core->dbo->sqlarray_echo($sql), "", array(), $r, $n)', $monitor);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n)', $monitor);
    }

    public function testAdministrativeReorderIdsUsePreparedParameters(): void
    {
        $reorder = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/reorder.php');

        self::assertStringContainsString('filter_var($msi,FILTER_VALIDATE_INT)', $reorder);
        self::assertStringContainsString('$core->dbo->queryPrepared($core->dbo->sqlarray_echo($sql),$preparedTypes,$preparedParams,$r,$n)', $reorder);
        self::assertStringNotContainsString('IN ($ids)', $reorder);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n)', $reorder);
    }

    public function testAdministrativeListingHasNoRawSelectionFallbacks(): void
    {
        $listing = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/list.php');

        self::assertStringContainsString('$core->dbo->queryPrepared($sqlText,$preparedTypes,$preparedParams,$r,$n)', $listing);
        self::assertStringContainsString('$core->dbo->fetchPrepared("SELECT count(*) FROM ".$mod->dbname, "", array())', $listing);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n);', $listing);
        self::assertStringNotContainsString('$core->dbo->fetch("SELECT count(*) FROM ".$mod->dbname)', $listing);
    }

    public function testAdministrativeMultipleEditLookupsUsePreparedIds(): void
    {
        $edit = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/edit.php');

        self::assertStringContainsString('$fetchItemsByKeys = function (array $keyGroups)', $edit);
        self::assertStringContainsString('$core->dbo->queryPrepared($sql,str_repeat(\'s\',count($ids)),$ids,$r,$n)', $edit);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n);', $edit);
        self::assertStringNotContainsString('implode(",",$okKeys)', $edit);
        self::assertStringNotContainsString('implode(",",$errorKeys)', $edit);
    }

    public function testLabelPrintingUsesPreparedSelectedKeys(): void
    {
        $labels = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_labels/payload/content/labels_print.php');

        self::assertStringContainsString('$core->dbo->queryPrepared($core->dbo->sqlarray_echo($sql),$preparedTypes,$preparedParams,$r,$n)', $labels);
        self::assertStringContainsString('$sql[\'WHERE\'][] = $module->name.".".$keys[$pos]."=?";', $labels);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n)', $labels);
        self::assertStringNotContainsString('="".$regs[$pos+1].""', $labels);
    }

    public function testFriendlyUrlActionAndQueryFiltersUsePreparedValues(): void
    {
        $friendlyUrl = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/friendlyurl.php');

        self::assertStringContainsString('$this->dbo->queryPrepared($this->dbo->sqlarray_echo($sql),$preparedTypes,$preparedParams,$r,$n)', $friendlyUrl);
        self::assertStringContainsString('$sql[\'WHERE\'][] = $module->name.".".$field."=?";', $friendlyUrl);
        self::assertStringNotContainsString('$this->dbo->query($sql,$r,$n)', $friendlyUrl);
        self::assertStringNotContainsString('checkHackAttempt($_REQUEST[$field])."\\""', $friendlyUrl);
    }

    public function testFullSearchUsesPreparedStructuredExecution(): void
    {
        $fullSearch = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/fullSearch.php');

        self::assertStringContainsString('$this->dbo->queryPrepared($this->dbo->sqlarray_echo($sql),"",array(),$r,$n)', $fullSearch);
        self::assertStringNotContainsString('$this->dbo->query($sql,$r,$n)', $fullSearch);
    }

    public function testRssUsesPreparedStructuredExecution(): void
    {
        $rss = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/rss.php');

        self::assertStringContainsString('$this->dbo->queryPrepared($this->dbo->sqlarray_echo($sql),"",array(),$r,$n)', $rss);
        self::assertStringNotContainsString('$this->dbo->query($sql,$r,$n)', $rss);
    }

    public function testUdmVirtualFolderValuesUsePreparedExecution(): void
    {
        $udm = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/udm.php');

        self::assertStringContainsString('$this->dbo->queryPrepared($this->dbo->sqlarray_echo($sql),$preparedTypes,$preparedParams,$r,$n)', $udm);
        self::assertStringContainsString('$sql[\'WHERE\'][] = $module->name.".".$param[$vFn][\'key\']', $udm);
        self::assertStringNotContainsString('$this->dbo->query($sql,$r,$n)', $udm);
        self::assertStringNotContainsString('=\\\"".$vF', $udm);
    }

    public function testAdministrativeHistoryLinkLookupUsesPreparedRemoteKeys(): void
    {
        $history = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/history.php');

        self::assertStringContainsString('$module->getRemotePreparedKeys($rmodule,$where,$whereTypes,$whereParams,$data)', $history);
        self::assertStringContainsString('$core->dbo->queryPrepared($sql,$whereTypes,$whereParams,$r,$n)', $history);
        self::assertStringNotContainsString('$module->getRemoteKeys($rmodule,$data)', $history);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n)', $history);
    }

    public function testAdministrativeExportReadsUsePreparedExecution(): void
    {
        $export = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/export.php');

        self::assertSame(2, substr_count($export, '$core->dbo->queryPrepared($sql, "", array(), $r, $n)'));
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n)', $export);
    }

    public function testAdministrativePreviewAndPreferencesUsePreparedValues(): void
    {
        $preview = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/preview.php');
        $list = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/list.php');

        self::assertStringContainsString('$module->getPreparedKeys($preparedWhere,$preparedTypes,$preparedParams,$preparedKeys,$keyData)', $preview);
        self::assertStringContainsString('$core->dbo->queryPrepared($sql,$preparedTypes,$preparedParams,$r,$n)', $preview);
        self::assertStringNotContainsString('$_REQUEST[$key]."\\\""', $preview);
        self::assertStringContainsString('UPDATE ".$uMod->dbname." SET userprefs=? WHERE id=?', $list);
        self::assertStringNotContainsString('$core->dbo->simpleQuery($usql)', $list);
    }

    public function testBiBbMessageCountUsesPreparedRecipientAndDateFilters(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/module.php');

        self::assertStringContainsString('return $this->parent->dbo->fetchPrepared($sql, $types, $params);', $module);
        self::assertStringContainsString('$types = \'i\';', $module);
        self::assertStringContainsString('$params[] = \'0000-00-00 00:00:00\';', $module);
        self::assertStringNotContainsString('id_recipient=".$_SESSION[CONS_SESSION_ACCESS_USER][\'id\']', $module);
    }

    public function testBiBbThreadPostCountUsesPreparedInternalIds(): void
    {
        $thread = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/payload/content/thread.php');

        self::assertStringContainsString('fetchPrepared(', $thread);
        self::assertStringContainsString('WHERE id_forum=? AND id_forumthread=?', $thread);
        self::assertStringContainsString("'ii'", $thread);
        self::assertStringContainsString('array((int)$idf, (int)$idt)', $thread);
        self::assertStringNotContainsString('WHERE id_forum=$idf AND id_forumthread=$idt', $thread);
    }

    public function testBiBbThreadPostsUseStructuredSqlAndPreparedIds(): void
    {
        $thread = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/payload/content/thread.php');

        self::assertStringContainsString('"SELECT" => array("p.*", "u.login", "u.image", "u.name")', $thread);
        self::assertStringContainsString('"WHERE" => array("p.id_forumthread = ?", "p.id_forum = ?", "u.id = p.id_author")', $thread);
        self::assertStringContainsString('"_preparedTypes" => "ii"', $thread);
        self::assertStringContainsString('"_preparedParams" => array((int)$idt, (int)$idf)', $thread);
        self::assertStringNotContainsString('p.id_forumthread = $idt', $thread);
        self::assertStringNotContainsString('p.id_forum = $idf', $thread);
    }

    public function testBiBbIndexUsesPreparedForumAndLanguageFilters(): void
    {
        $index = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/payload/content/index.php');

        self::assertStringContainsString('"_preparedTypes" => "s"', $index);
        self::assertStringContainsString('$sql[\'_preparedTypes\'] = "is"', $index);
        self::assertStringContainsString('queryPrepared($sql,\'i\',array((int)$data[\'id\']),$r,$n)', $index);
        self::assertStringContainsString('"WHERE" => array("f.lang=?"', $index);
        self::assertStringNotContainsString('forum.lang=\'".$lang."\'', $index);
        self::assertStringNotContainsString('f.lang=\'$lang\'', $index);
        self::assertStringNotContainsString('forum.id_parent=$idF', $index);
        self::assertStringNotContainsString('p.id_forum=".$data[\'id\']', $index);
        self::assertStringNotContainsString('t.id_forum=".$data[\'id\']', $index);
    }

    public function testAuthControlGuestGroupUsesPreparedQuery(): void
    {
        $auth = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/authControl.php');

        self::assertStringContainsString('$groups->get_base_sql("id=?")', $auth);
        self::assertStringContainsString('queryPrepared($sql,\'i\',array((int)$this->parent->dimconfig[\'guest_group\']),$r,$n)', $auth);
        self::assertStringNotContainsString('$groups->get_base_sql("id=".(int)$this->parent->dimconfig[\'guest_group\'])', $auth);
        self::assertStringContainsString('$module->getPreparedKeys($wS, $wTypes, $wParams, $kA, $keys)', $auth);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sql,$wTypes,$wParams,$r,$n,$this->parent->debugmode)', $auth);
        self::assertStringNotContainsString('$module->getKeys($wS, $kA, $keys,"",true)', $auth);
        self::assertStringContainsString('$module->getRemotePreparedKeys($remoteModule,$where,$whereTypes,$whereParams,$myData)', $auth);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sql,$whereTypes,$whereParams,$r,$n)', $auth);
        self::assertStringNotContainsString('$module->getRemoteKeys($remoteModule,$myData)', $auth);
    }

    public function testModuleNotificationKeyExtractionUsesPreparedKeyContract(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $notification = strstr($module, 'if ($action == CONS_ACTION_DELETE)');

        self::assertNotFalse($notification);
        self::assertStringContainsString('$module->getPreparedKeys($wS,$wTypes,$wParams,$kA,$data);', $notification);
        self::assertStringNotContainsString('$module->getKeys($wS,$kA,$data);', $notification);
    }

    public function testGenericBackupUsesStructuredQueryContract(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $backup = strstr($module, 'function generateBackup(');

        self::assertNotFalse($backup);
        self::assertStringContainsString('"SELECT" => array("*")', $backup);
        self::assertStringContainsString('queryPrepared($this->parent->dbo->sqlarray_echo($sql),"",array(),$r,$n)', $backup);
        self::assertStringNotContainsString('$this->parent->dbo->query($sql,$r,$n);', $backup);
    }

    public function testGenericGetContentsUsesPreparedExecutionPath(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $contents = strstr($module, 'function getContents(');
        $contents = strstr((string)$contents, 'function invalidHTML(', true);

        self::assertNotFalse($contents);
        self::assertStringContainsString('queryPrepared($sqlText,$preparedTypes,$preparedParams,$r,$n)', $contents);
        self::assertStringContainsString('queryPrepared($this->parent->dbo->sqlarray_echo($sql),"",array(),$r,$n)', $contents);
        self::assertStringNotContainsString('$this->parent->dbo->query($sql,$r,$n)', $contents);
        self::assertStringNotContainsString('->dbo->fetch($countSQL', $contents);
        self::assertStringNotContainsString('->dbo->query($sql,$r,$n', $contents);
    }

    public function testGenericNumericLookupUsesPreparedIdParameter(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');

        self::assertStringContainsString('$preparedTypes = "i";', $module);
        self::assertStringContainsString('$preparedParams = array((int)$sql);', $module);
        self::assertStringContainsString('$this->get_base_sql($this->name.".".$this->keys[0]."=?")', $module);
        self::assertStringNotContainsString('$this->get_base_sql($this->name.".".$this->keys[0]."=\'".(int)$sql."\'")', $module);
    }

    public function testBiBbArchiveFiltersUsePreparedValuesAndMetadataFields(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/module.php');

        self::assertStringContainsString('function getTags(array $filters = array())', $module);
        self::assertStringContainsString('function getArchieveDates(array $filters = array())', $module);
        self::assertStringContainsString('array_key_exists($field, $mod->fields)', $module);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sql,$types,$params,$r,$n);', $module);
        self::assertStringNotContainsString('function getTags($filter="")', $module);
        self::assertStringNotContainsString('function getArchieveDates($filter="")', $module);
        self::assertStringNotContainsString('" AND ".$filter', $module);
    }

    public function testAdministrativeEditUsesPreparedSingleAndMultipleKeys(): void
    {
        $edit = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/edit.php');

        self::assertStringContainsString('$sql[\'WHERE\'][] = $module->name.".$key = ?";', $edit);
        self::assertStringContainsString('$sql[\'_preparedParams\'] = $preparedParams;', $edit);
        self::assertStringContainsString('queryPrepared($sql,str_repeat(\'s\',count($msi_nfiltered)),array_map(\'strval\',$msi_nfiltered),$r,$n)', $edit);
        self::assertStringNotContainsString('IN ($msi_nfiltered)', $edit);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n);', $edit);
    }

    public function testAdministrativeRelatedOptionsUsePreparedPrerequisitesAndSelectedValue(): void
    {
        $edit = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/edit.php');

        self::assertStringContainsString('"if (".$mod->name.".".$mod->keys[0]."=?,1,0) as selected"', $edit);
        self::assertStringContainsString('$sql[\'WHERE\'][] = $mod->name.".".$remodeField."=?";', $edit);
        self::assertStringContainsString('$sql[\'_preparedTypes\'] = $preparedTypes;', $edit);
        self::assertStringContainsString('$sql[\'_preparedParams\'] = $preparedParams;', $edit);
        self::assertStringNotContainsString('$mod->name.".".$remodeField."=\\\"".$data[$filterfield]."\\\""', $edit);
        self::assertStringNotContainsString('$mod->name.".".$mod->keys[0]."=\\\"".$data[$name]."\\\""', $edit);
    }

    public function testParentalContentFiltersUsePreparedSelectedValues(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $edit = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/edit.php');
        $options = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/options.php');

        self::assertStringContainsString('$preparedTypes = (string)$sql[\'_preparedTypes\'];', $module);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sqlText,$preparedTypes,$preparedParams,$r,$n)', $module);
        self::assertStringContainsString('$sql[\'_preparedTypes\'] = \'s\';', $edit);
        self::assertStringContainsString('$sql[\'_preparedParams\'] = array((string)$data[$name]);', $edit);
        self::assertStringContainsString('$sql[\'_preparedTypes\'] = \'s\';', $options);
        self::assertStringContainsString('$sql[\'_preparedParams\'] = array((string)$data[\'value\']);', $options);
        self::assertStringNotContainsString('"if (".$mod->name.".".$mod->keys[0]."=\'".$data[$name]."\',1,0) as selected"', $edit);
        self::assertStringNotContainsString('"if (".$mod->name.".".$mod->keys[0]."=\'".$data[\'value\']."\',1,0) as selected"', $options);
    }

    public function testUniqueAjaxRouteUsesFieldAllowlistRbacAndPreparedValues(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/ajaxqueryunique.php');

        self::assertStringContainsString('in_array($_REQUEST[\'field\'],$obj->keys,true)', $route);
        self::assertStringContainsString('checkPermission($obj,CONS_ACTION_SELECT)', $route);
        self::assertStringContainsString(<<<'PHP'
fetchPrepared($sql,'s',array($_REQUEST['value']))
PHP, $route);
        self::assertStringNotContainsString('addslashes($_REQUEST[\'value\'])', $route);
        self::assertStringNotContainsString('error on SQL: ".$sql', $route);
    }

    public function testAjaxSelectRouteDoesNotDisableSafetyChecks(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/ajaxQuery.php');

        self::assertStringContainsString('checkPermission($module,CONS_ACTION_SELECT)', $route);
        self::assertStringNotContainsString('$this->safety = false', $route);
    }

    public function testAdministrativeImportRestoresSafetyAfterIgnoreErrors(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/import.php');

        self::assertStringContainsString('$previousSafety = $core->safety;', $route);
        self::assertStringContainsString('$core->safety = $previousSafety;', $route);
    }

    public function testAdministrativeImportLinkLookupUsesPreparedTitleAndKeyValues(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/import.php');

        self::assertStringContainsString('$lookupTitle = isset($_REQUEST[\'exactlinkers\']) ? $lookupValue : "%".$lookupValue."%";', $route);
        self::assertStringContainsString('"(".$remoteModule->title." LIKE ? OR ".$remoteModule->keys[0]."=?)"', $route);
        self::assertStringContainsString('$core->dbo->queryPrepared($sqlText,\'ss\',array($lookupTitle,$lookupValue),$r,$n);', $route);
        self::assertStringNotContainsString('LIKE \"".cleanString($regs[$c])', $route);
        self::assertStringNotContainsString('=\"".cleanString($regs[$c])', $route);
        self::assertStringNotContainsString('$core->dbo->query($sql,$r,$n);', $route);
    }

    public function testFileManagerDoesNotUseRawDeletePathOrPrefixOnlySafeCheck(): void
    {
        $deleteRoute = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/files.php');
        $fileManager = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_fm/module.php');

        self::assertStringNotContainsString('$_REQUEST[\'delfile\']\n', $deleteRoute);
        self::assertStringContainsString("preg_match('#^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$#'", $deleteRoute);
        self::assertStringContainsString('realpath(CONS_FMANAGER.CONS_FMANAGER_SAFE)', $fileManager);
        self::assertStringContainsString('str_starts_with($candidate, $base.DIRECTORY_SEPARATOR)', $fileManager);
        self::assertStringNotContainsString('substr($dir,0,strlen("/".CONS_FMANAGER.CONS_FMANAGER_SAFE', $fileManager);
    }

    public function testInputDrivenSqlFiltersUsePreparedValuesAndMetadataIdentifiers(): void
    {
        $remoteKeys = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $ajaxQuery = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/ajaxQuery.php');
        $adminList = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/list.php');

        self::assertStringContainsString('addslashes_EX((string)$value, true, $rmodule->parent->dbo)', $remoteKeys);
        self::assertStringContainsString('$sql[\'_preparedTypes\'] = $preparedTypes;', $ajaxQuery);
        self::assertStringContainsString('$sm->getRemotePreparedKeys($module,$remoteWhere,$remoteTypes,$remoteParams,$remoteData)', $ajaxQuery);
        self::assertStringContainsString('$sql[\'WHERE\'][] = $module->name.".".$fname."=?";', $ajaxQuery);
        self::assertStringContainsString('$innersql[\'_preparedTypes\'] = \'s\';', $adminList);
        self::assertStringContainsString('$innersql[\'_preparedParams\'] = array((string)$_REQUEST[\'affrefererkeys\']);', $adminList);
        self::assertStringNotContainsString('addslashes_EX((string)$_GET[$fname]', $ajaxQuery);
        self::assertStringNotContainsString('addslashes_EX((string)$_REQUEST[\'affrefererkeys\']', $adminList);
        self::assertStringNotContainsString('getRemoteKeys($module,$_GET)', $ajaxQuery);
    }

    public function testGenericCrudUsesPreparedKeysForPruningParentChecksAndMutations(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');

        self::assertStringContainsString('function getPreparedKeys(', $module);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sql,"s",array((string)$data[$field])', $module);
        self::assertStringContainsString('fetchPrepared("SELECT ".$name." FROM ".$this->dbname." WHERE ".$this->keys[0]."=?"', $module);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared("DELETE FROM ".$this->dbname." WHERE ".$wS', $module);
    }

    public function testGenericCrudUsesPreparedKeysForSerializedPartialUpdates(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');

        self::assertStringContainsString('$this->parent->dbo->fetchPrepared($sql,$wTypes,$wParams)', $module);
        self::assertStringNotContainsString('$this->parent->dbo->fetch($sql);', $module);
    }

    public function testRemoteKeysAndAdministrativeLinkPreviewUsePreparedValues(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $preview = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/preview.php');

        self::assertStringContainsString('function getRemotePreparedKeys(', $module);
        self::assertStringContainsString('$whereTypes .= $isInteger ? \'i\' : \'s\';', $module);
        self::assertStringContainsString('$module->getRemotePreparedKeys($mod,$remoteWhere,$remoteTypes,$remoteParams,$data)', $preview);
        self::assertStringContainsString('$core->dbo->fetchPrepared($sql,$remoteTypes,$remoteParams)', $preview);
        self::assertStringNotContainsString('$core->dbo->fetch($sql)', $preview);
    }

    public function testGenericDownloadUsesPreparedKeysAndLookup(): void
    {
        $core = (string) file_get_contents(__DIR__ . '/../prescia/core.php');

        self::assertStringContainsString('$m->getPreparedKeys($ws,$wTypes,$wParams,$ka,$_REQUEST)', $core);
        self::assertStringContainsString('$this->dbo->fetchPrepared($sql,$wTypes,$wParams)', $core);
        self::assertStringNotContainsString('$m->getKeys($ws,$ka,$_REQUEST)', $core);
        self::assertStringNotContainsString('$this->dbo->fetch($sql)', $core);
    }

    public function testGenericListingsNormalizePaginationAndWhitelistSqlArrayOrdering(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');

        self::assertStringContainsString('private function normalizeContentSqlArray(&$sql)', $module);
        self::assertStringContainsString("preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\.[A-Za-z_][A-Za-z0-9_]*)?(?:\\s+(?:ASC|DESC))?$/i'", $module);
        self::assertStringContainsString('preg_match(\'/^[0-9]+$/\',trim($limitPart))', $module);
        self::assertStringContainsString('$this->parent->templateParams[\'p_init\'] = (int)$_REQUEST[\'p_init\'];', $module);
        self::assertStringContainsString('$this->parent->templateParams[\'p_size\'] = (int)$_REQUEST[\'p_size\'];', $module);
        self::assertStringContainsString('if (!$this->normalizeContentSqlArray($sql))', $module);
    }

    public function testGenericListingsAcceptStructuredPreparedFilters(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');

        self::assertStringContainsString("isset(\$sql['where'],\$sql['types'],\$sql['params'])", $module);
        self::assertStringContainsString('$preparedTypes = (string)$sql[\'types\'];', $module);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sqlText,$preparedTypes,$preparedParams', $module);
        self::assertStringContainsString('$this->parent->dbo->fetchPrepared($countSqlText,$preparedTypes,$preparedParams)', $module);
        self::assertStringNotContainsString('WHERE ".$sql[\'where\']', $module);
    }

    public function testForumListingUsesPreparedLanguageFilterAndIntegerPaging(): void
    {
        $bbModule = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/module.php');
        $forum = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/payload/content/forum.php');

        self::assertStringContainsString('forum.lang=?', $bbModule);
        self::assertStringContainsString("'types'=>'s'", $bbModule);
        self::assertStringContainsString("'params'=>array((string)\$_SESSION[CONS_SESSION_LANG])", $bbModule);
        self::assertStringContainsString("?(int)\$_REQUEST['p_init']", $forum);
        self::assertStringContainsString("?(int)\$_REQUEST['id_forum']", $forum);
        self::assertStringNotContainsString("forum.lang=\"'.\$_SESSION[CONS_SESSION_LANG]", $bbModule);
    }

    public function testAdminListingPassesExternalFiltersAsPreparedParameters(): void
    {
        $listing = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/list.php');

        self::assertStringContainsString('$addPreparedWhere = function ($fragment,$value)', $listing);
        self::assertStringContainsString('$sql[\'WHERE\'][] = $fragment.\'?\';', $listing);
        self::assertStringContainsString('$sql[\'_preparedTypes\'] = $preparedTypes;', $listing);
        self::assertStringContainsString('$core->dbo->queryPrepared($sqlText,$preparedTypes,$preparedParams', $listing);
        self::assertStringNotContainsString('$sql[\'WHERE\'][] = $module->name.".".$name."$compare\\\"".$_REQUEST[$name]', $listing);
    }

    public function testPhpStanFrameworkContractsCoverIssue41Symbols(): void
    {
        $core = (string) file_get_contents(__DIR__ . '/../prescia/core.php');
        $scripted = (string) file_get_contents(__DIR__ . '/../prescia/components/scripted.php');
        $stubs = (string) file_get_contents(__DIR__ . '/../tools/phpstan-framework-stubs.php');
        $englishLocale = (string) file_get_contents(__DIR__ . '/../pages/prescia/_config/locale/en.php');
        $portugueseLocale = (string) file_get_contents(__DIR__ . '/../pages/prescia/_config/locale/pt-br.php');

        self::assertSame(1, substr_count($englishLocale, '"quick_reference"'));
        self::assertSame(1, substr_count($portugueseLocale, '"quick_reference"'));
        self::assertStringContainsString('function langOut(', $core);
        self::assertStringContainsString('function langOut(', $scripted);
        self::assertStringContainsString('function saveConfig(', $core);
        self::assertStringContainsString('function saveConfig(bool $force = false): void', $stubs);
    }

    public function testPhpStanFrameworkSymbolInventoryIsExplicitlyConfigured(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../phpstan.neon.dist');
        $stubs = (string) file_get_contents(__DIR__ . '/../tools/phpstan-framework-stubs.php');

        foreach (['arrayToString.php', 'storeFile.php', 'quota.php', 'console.php'] as $file) {
            self::assertStringContainsString($file, $config);
        }
        foreach (['function addslashes_EX', 'class CPrescia', 'class CPresciaFull', 'class CKTCexternal', 'class ttree', 'class xmlHandler'] as $symbol) {
            self::assertStringContainsString($symbol, $stubs);
        }
        self::assertStringContainsString('reportUnmatchedIgnoredErrors: true', $config);
    }

    public function testCModuleDeclaresCorePropertiesAndDynamicContracts(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $stubs = (string) file_get_contents(__DIR__ . '/../tools/phpstan-framework-stubs.php');

        foreach (['public ?CPrescia $parent', 'public string $name', 'public string $dbname', 'public array $keys', 'public array $fields'] as $property) {
            self::assertStringContainsString($property, $module);
        }
        self::assertStringContainsString('public array $templateParams', $stubs);
        self::assertStringContainsString('class CPresciaFull extends CPrescia', $stubs);
    }

    public function testBiStatsEscapesExternalTelemetryBeforeLegacySql(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('$sqlEscape = static function ($value) use ($core): string', $stats);
        self::assertStringContainsString('$pageToBelogged = $sqlEscape($pageToBelogged);', $stats);
        self::assertStringContainsString('$domain = $sqlEscape($domain);', $stats);
        self::assertStringContainsString('$pages = $sqlEscape($pages);', $stats);
        self::assertStringContainsString('$referer = $sqlEscape($referer);', $stats);
        self::assertStringContainsString('$browser = $sqlEscape($browser);', $stats);
    }

    public function testBiStatsResolutionUsesValidationAndPreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');
        $client = (string) file_get_contents(__DIR__ . '/../pages/_js/getmyres.js');

        self::assertStringContainsString("is_string(\$_REQUEST['res'])", $stats);
        self::assertStringContainsString("\$_SERVER['REQUEST_METHOD'] === 'POST'", $stats);
        self::assertStringContainsString("preg_match('/^([1-9][0-9]{0,4})x([1-9][0-9]{0,4})$/", $stats);
        self::assertStringContainsString('(int)$resolutionParts[1] <= 10000', $stats);
        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsResolution." WHERE data=? AND resolution=?"', $stats);
        self::assertStringContainsString('queryPrepared("UPDATE ".$statsResolution." SET hits=hits+1 WHERE data=? AND resolution=?"', $stats);
        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsResolution." WHERE data=? AND resolution=?", \'ss\'', $stats);
        self::assertStringNotContainsString('resolution=\\"".$_SESSION[CONS_USER_RESOLUTION]', $stats);
        self::assertStringContainsString('xhReq.open("POST", "/setres.ajax?layout=2", false);', $client);
        self::assertStringContainsString('encodeURIComponent(screen.width + "x" + screen.height)', $client);
    }

    public function testBiStatsBrowserCountersUsePreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsBrowser." WHERE data=? AND browser=?"', $stats);
        self::assertStringContainsString('queryPrepared("INSERT INTO ".$statsBrowser." SET data=NOW(), browser=?,hits=1"', $stats);
        self::assertStringContainsString('queryPrepared("UPDATE ".$statsBrowser." SET hits=hits+1 WHERE data=? AND browser=?"', $stats);
        self::assertStringNotContainsString('browser=\\"$browser\\"', $stats);
    }

    public function testBiStatsAdminAndBotCountersUsePreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsTable." WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?"', $stats);
        self::assertStringContainsString('queryPrepared("INSERT INTO ".$statsTable." SET data=?, hour=?, page=?, hid=?, hits=0', $stats);
        self::assertStringContainsString('queryPrepared("SELECT hits FROM ".$statsBots." WHERE data=?"', $stats);
        self::assertStringContainsString('queryPrepared("UPDATE ".$statsBots." SET hits=hits+1 WHERE data=?"', $stats);
    }

    public function testBiStatsNavigationCountersUsePreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('queryPrepared("SELECT page,fullpath FROM ".$statsRealtime." WHERE ip=?"', $stats);
        self::assertStringContainsString('queryPrepared("INSERT INTO ".$statsRealtime." SET ip=?, page=?, pagelast=?', $stats);
        self::assertStringContainsString('queryPrepared("UPDATE ".$statsRealtime." SET page=?, pagelast=?', $stats);
        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsPath." WHERE data=? AND page=? AND pagefoward=?"', $stats);
        self::assertStringContainsString('queryPrepared("INSERT INTO ".$statsPath." SET data=?, page=?, pagefoward=?, hits=1"', $stats);
        self::assertStringNotContainsString('WHERE ip=\'".CONS_IP."\'', $stats);
    }

    public function testBiStatsModuleUsesPreparedPageFilter(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/content/module.php');

        self::assertStringContainsString('WHERE s.page=?', $module);
        self::assertStringContainsString('queryPrepared($sql,"s",array($page),$r,$n)', $module);
        self::assertStringNotContainsString("WHERE s.page='\$page'", $module);
    }

    public function testBiStatsRefererCountersUsePreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('queryPrepared("SELECT hits, pages FROM ".$statsReferer." WHERE data=? AND referer=? AND entrypage=?"', $stats);
        self::assertStringContainsString('queryPrepared("INSERT INTO ".$statsReferer." SET data=?, referer=?, entrypage=?, hits=?, pages=?"', $stats);
        self::assertStringContainsString('queryPrepared("UPDATE ".$statsReferer." SET hits=?, pages=? WHERE data=? AND referer=? AND entrypage=?"', $stats);
        self::assertStringNotContainsString('referer=\\"$domain\\"', $stats);
    }

    public function testBiStatsGeneralHitCountersUsePreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsTable." WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?"', $stats);
        self::assertStringContainsString('queryPrepared("INSERT INTO ".$statsTable." SET data=?, hour=?, page=?, hid=?, hits=1', $stats);
        self::assertStringContainsString('queryPrepared("UPDATE ".$statsTable." SET hits=hits+1', $stats);
        self::assertStringNotContainsString('SET hits=hits+1, uhits=uhits+1 ".($isReturning', $stats);
    }

    public function testBiStatsMaintenanceUsesPreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$sb->dbname." WHERE data=?"', $stats);
        self::assertStringContainsString('queryPrepared("SELECT sum(hits), browser FROM ".$sb->dbname', $stats);
        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsDaily." WHERE data=?"', $stats);
        self::assertStringContainsString('queryPrepared("SELECT referer,entrypage,hits FROM ".$statsReferer." WHERE data=?"', $stats);
        self::assertStringNotContainsString('WHERE data=\'".$previousDay."\'', $stats);
    }

    public function testBiStatsCounterMethodsUsePreparedFilters(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('fetchPrepared($sql, $types, $params)', $stats);
        self::assertStringContainsString('queryPrepared($sql,$types,$params,$r,$n)', $stats);
        self::assertStringContainsString('$days = max(1, (int)$days);', $stats);
        self::assertStringNotContainsString('WHERE page=\"$filterPage\"', $stats);
        self::assertStringNotContainsString('$this->parent->dbo->query($sql,$r,$n)', $stats);
        self::assertStringNotContainsString('$this->parent->dbo->fetch($sql)', $stats);
    }

    public function testBiFileManagerUsesPreparedFileNameFilters(): void
    {
        $fileManager = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_fm/module.php');
        $settings = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_fm/payload/actions/affbi_fmset.php');

        self::assertStringContainsString('fetchPrepared($sql, \'s\', array($arquivo))', $fileManager);
        self::assertStringContainsString('queryPrepared($sql, \'s\', array($arquivo), $r, $n)', $fileManager);
        self::assertStringContainsString('queryPrepared($sql, \'s\', array($file), $r, $n)', $fileManager);
        self::assertStringContainsString('queryPrepared($sql, \'s\', array($dir.\'%\'), $r, $n)', $fileManager);
        self::assertStringContainsString('fetchPrepared($sql, \'s\', array($data[\'filenm\']))', $settings);
        self::assertStringNotContainsString('->dbo->fetch($sql)', $fileManager);
        self::assertStringNotContainsString('->dbo->simpleQuery($sql)', $fileManager);
    }

    public function testBiDevUsesPreparedDatabaseOperations(): void
    {
        $dev = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_dev/module.php');

        self::assertStringContainsString('fetchPrepared("SELECT ".$rmodule->keys[0]', $dev);
        self::assertStringContainsString('queryPrepared($sql, "", array(), $r, $n)', $dev);
        self::assertStringNotContainsString('->dbo->query($sql,$r,$n)', $dev);
        self::assertStringNotContainsString('->dbo->simpleQuery($sql)', $dev);
        self::assertStringNotContainsString('->dbo->fetch("SELECT ', $dev);
    }

    public function testBiCmsUsesPreparedContentAndPermissionQueries(): void
    {
        $cms = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_cms/module.php');

        self::assertStringContainsString('WHERE code=? AND page=? AND lang=?', $cms);
        self::assertStringContainsString('fetchPrepared($sql, $types, $params)', $cms);
        self::assertStringContainsString('fetchPrepared($sql, \'i\', array((int)$data[\'id\']))', $cms);
        self::assertStringContainsString('queryPrepared($sql, \'ss\', array($this->serveThisPage, $_SESSION[CONS_SESSION_LANG])', $cms);
        self::assertStringContainsString('queryPrepared($sql, \'i\', array((int)$id)', $cms);
        self::assertStringNotContainsString('->dbo->query($sql,$r,$n)', $cms);
        self::assertStringNotContainsString('->dbo->fetch($sql)', $cms);
        self::assertStringNotContainsString('->dbo->simpleQuery(', $cms);
    }

    public function testBiSeoUsesPreparedQueries(): void
    {
        $seo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_seo/module.php');

        self::assertStringContainsString('WHERE alias=? AND lang=?', $seo);
        self::assertStringContainsString('queryPrepared($sql, \'ss\'', $seo);
        self::assertStringContainsString('WHERE publicar=\'y\' AND lang=?', $seo);
        self::assertStringContainsString('queryPrepared($sql, \'s\'', $seo);
        self::assertStringNotContainsString('alias=\\".$context_for_seo', $seo);
    }

    public function testBiStatsPathAnalyticsUsesPreparedQueries(): void
    {
        $analytics = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/content/stats_pathajax.php');

        self::assertStringContainsString('queryPrepared("SELECT data,SUM(hits) as shits', $analytics);
        self::assertStringContainsString('fetchPrepared("SELECT sum(hits) FROM ".$statsh->dbname." WHERE data >= ? AND data < ? AND page=?"', $analytics);
        self::assertStringContainsString('queryPrepared($sql, \'sss\'', $analytics);
        self::assertStringNotContainsString('page=\\"$page\\"', $analytics);
    }

    public function testBiStatsAnalyticsSummaryUsesPreparedQueries(): void
    {
        $analytics = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/content/stats_analytics.php');

        self::assertStringContainsString('queryPrepared($sql, "", array(), $r, $n)', $analytics);
        self::assertStringContainsString('fetchPrepared("SELECT ".$mod->title." FROM ".$mod->dbname." WHERE id=?"', $analytics);
        self::assertStringContainsString('WHERE referer=? GROUP BY data', $analytics);
        self::assertStringNotContainsString('WHERE id=".$pages[$c][2]', $analytics);
        self::assertStringNotContainsString('->dbo->query(', $analytics);
        self::assertStringNotContainsString('->dbo->fetch(', $analytics);
    }

    public function testBiStatsRealtimeEndpointAuthorizesAndEscapesOutput(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/actions/stats_rtajax.php');

        self::assertStringContainsString('$_SESSION[CONS_SESSION_ACCESS_LEVEL] < 10', $route);
        self::assertStringContainsString("filter_var(\$_REQUEST['ip'] ?? '', FILTER_VALIDATE_IP)", $route);
        self::assertStringContainsString('queryPrepared("SELECT * from ".$rt->dbname." WHERE ip=?"', $route);
        self::assertStringContainsString("htmlspecialchars(substr((string)\$value, 0, 4096), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')", $route);
        self::assertStringContainsString("header('X-Content-Type-Options: nosniff')", $route);
        self::assertStringContainsString("header('Cache-Control: no-store, no-cache, must-revalidate')", $route);
        self::assertStringContainsString('substr((string)$value, 0, 4096)', $route);
        self::assertStringNotContainsString('WHERE ip=\'$ip\'', $route);
        self::assertStringNotContainsString("echo \"Navegador: \".\$dados['agent']", $route);
    }

    public function testBiStatsCsvExportUsesStructuredRowsAndSafeHeaders(): void
    {
        $export = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/actions/stats_export.php');

        self::assertStringContainsString('queryPrepared($sql, "", array(), $r, $n)', $export);
        self::assertStringNotContainsString('->dbo->query(', $export);
        self::assertStringContainsString("fputcsv(\$csv", $export);
        self::assertStringContainsString("preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', \$date)", $export);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $export);
        self::assertStringContainsString('Cache-Control: no-store, no-cache, must-revalidate', $export);
        self::assertStringNotContainsString('$outputstr .= "\\\"".array_shift($o)', $export);
    }

    public function testBiStatsReferencesUsesPreparedQueries(): void
    {
        $references = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/content/stats_ref.php');

        self::assertStringContainsString('queryPrepared($sql, "", array(), $r, $n)', $references);
        self::assertStringNotContainsString('->dbo->query(', $references);
    }

    public function testUndoUsesPreparedQueriesForRecordKeysAndHistoryDeletion(): void
    {
        $undo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_undo/module.php');

        self::assertStringContainsString('$module->getPreparedKeys($ws,$wTypes,$wParams,$ka,$data);', $undo);
        self::assertStringNotContainsString('$module->getKeys($ws,$ka,$data);', $undo);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sql,$wTypes,$wParams,$r,$n)', $undo);
        self::assertStringContainsString('queryPrepared("DELETE FROM ".$undo->dbname." WHERE id=?", \'i\'', $undo);
        self::assertStringContainsString("queryPrepared(\$sql, 'sssssi'", $undo);
        self::assertStringNotContainsString('$core->dbo->simpleQuery($sql);', $undo);
    }

    public function testCronMaintenanceUsesPreparedExecutionForInternalIdentifiers(): void
    {
        $cron = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/cron.php');

        self::assertStringContainsString('REPAIR TABLE ".implode(",",$quotedMods)', $cron);
        self::assertStringContainsString('OPTIMIZE TABLE ".implode(",",$quotedMods)', $cron);
        self::assertSame(2, substr_count($cron, '$this->dbo->queryPrepared($sql,"",array(),$maintenanceResult,$maintenanceRows,false);'));
        self::assertStringNotContainsString('$this->dbo->simpleQuery($sql,false);', $cron);
        self::assertStringContainsString('$this->dbo->quoteIdentifier($mod)', $cron);
    }

    public function testAdministrativeUndoRoutesValidateRequestIdsBeforeBuildingSql(): void
    {
        $undo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/undo.php');
        $multipleUndo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/multipleundo.php');

        self::assertStringContainsString('filter_var($_REQUEST[\'id\'], FILTER_VALIDATE_INT)', $undo);
        self::assertStringContainsString('$sql = "SELECT * FROM ".$undo->dbname." WHERE id=?";', $undo);
        self::assertStringNotContainsString('$undo->name.".id=".$_REQUEST[\'id\']', $undo);
        self::assertStringContainsString('$id = filter_var($id, FILTER_VALIDATE_INT);', $multipleUndo);
        self::assertStringContainsString('if ($id === false || $id < 1) continue;', $multipleUndo);
    }

    public function testThumbnailRedirectUsesContextualQueryEncoding(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/edit_thumbnails.php');

        self::assertStringContainsString("http_build_query(\$query, '', '&', PHP_QUERY_RFC3986)", $route);
        self::assertStringContainsString("'field' => \$field", $route);
        self::assertStringNotContainsString('implode("&",$qs)', $route);
        self::assertStringNotContainsString('$_REQUEST[\'field\']."&"', $route);
    }

    public function testLoadUrlRestrictsProtocolsPortsAndPrivateNetworks(): void
    {
        $loader = (string) file_get_contents(__DIR__ . '/../prescia/lib/loadURL.php');

        self::assertStringContainsString("in_array(\$scheme, array('http', 'https'), true)", $loader);
        self::assertStringContainsString("FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE", $loader);
        self::assertStringContainsString('function presciaLoadUrlIsValidHost', $loader);
        self::assertStringContainsString('strpbrk($host, "\\r\\n\\0")', $loader);
        self::assertStringContainsString('$port < 1 || $port > 65535', $loader);
        self::assertStringContainsString('foreach ($ips as $ip)', $loader);
        self::assertStringContainsString("stream_socket_client", $loader);
        self::assertStringContainsString("'verify_peer' => true", $loader);
        self::assertStringContainsString("PRESCIA_LOADURL_MAX_BYTES", $loader);
        self::assertStringContainsString('PRESCIA_LOADURL_MAX_DNS_RECORDS', $loader);
        self::assertStringContainsString('PRESCIA_LOADURL_MAX_IPS', $loader);
        self::assertStringContainsString('count($records) > PRESCIA_LOADURL_MAX_DNS_RECORDS', $loader);
        self::assertStringContainsString('count($ips) > PRESCIA_LOADURL_MAX_IPS', $loader);
        self::assertStringNotContainsString('gethostbynamel(', $loader);
        self::assertStringNotContainsString('fsockopen(', $loader);
        self::assertStringNotContainsString('Location:', $loader);
    }

    public function testLegacyHtmlHelpersDelegateToContextualSanitizer(): void
    {
        $helpers = (string) file_get_contents(__DIR__ . '/../prescia/lib/inputSanatizing.php');
        $template = (string) file_get_contents(__DIR__ . '/../prescia/lib/template/tc.php');

        self::assertStringContainsString('Sanitizer::sanitizeHtml((string)$htmlinput)', $helpers);
        self::assertStringContainsString('Sanitizer::stripTags((string)$str,$preserveEndOfLine)', $helpers);
        self::assertStringContainsString('Sanitizer::escapeHtml((string)$content)', $template);
        self::assertStringNotContainsString('preg_replace("@<\\/?script', $helpers);
        $legacyEscaper = (string) file_get_contents(__DIR__ . '/../prescia/lib/htmlentities_ex.php');
        self::assertStringContainsString('Sanitizer::escapeHtml((string)$str)', $legacyEscaper);
    }

    public function testLoadUrlSecurityEventsAreVersionedRedactedAndFailClosed(): void
    {
        $loader = (string) file_get_contents(__DIR__ . '/../prescia/lib/loadURL.php');

        self::assertStringContainsString("'schema' => 'prescia.security.v1'", $loader);
        self::assertStringContainsString("'event' => 'ssrf_blocked'", $loader);
        self::assertStringContainsString("'reason' => \$reason", $loader);
        self::assertStringContainsString("preg_replace('/[^a-z0-9.:%_-]/i'", $loader);
        self::assertStringContainsString('JSON_THROW_ON_ERROR', $loader);
        self::assertStringContainsString('catch (Throwable $exception)', $loader);
        self::assertStringNotContainsString("'url' => \$url", $loader);
        self::assertStringNotContainsString("'query' => \$query", $loader);
    }

    public function testCkfinderImagePluginsValidateRequestsAndBootstrap(): void
    {
        $resize = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/plugins/imageresize/plugin.php');
        $watermark = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/plugins/watermark/plugin.php');

        self::assertStringContainsString('preg_match("/^[1-9]\\d*$/", $newWidth)', $resize);
        self::assertStringContainsString('preg_match("/^[1-9]\\d*$/", $newHeight)', $resize);
        self::assertStringContainsString('$overwrite = isset($_POST[\'overwrite\'])', $resize);
        self::assertStringContainsString('$imageInfo = @getimagesize($filePath)', $resize);
        self::assertStringContainsString('CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST', $resize);
        self::assertStringContainsString("if (!defined('IN_CKFINDER')) exit;", $watermark);
    }

    public function testCkfinderUploadAndCopyHandlersRejectMalformedInputAndUseSafeCallbacks(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/FileUpload.php');
        $fileUploadErrors = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/ErrorHandler/FileUpload.php');
        $quickUploadErrors = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/ErrorHandler/QuickUpload.php');
        $copy = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/CopyFiles.php');

        self::assertStringContainsString('is_uploaded_file($uploadedFile[\'tmp_name\'])', $upload);
        self::assertStringContainsString('UPLOAD_ERR_OK', $upload);
        self::assertStringContainsString('JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP', $fileUploadErrors);
        self::assertStringContainsString('JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP', $quickUploadErrors);
        self::assertStringContainsString('if (!is_array($arr))', $copy);
        self::assertStringContainsString('!is_string($arr[\'type\'])', $copy);
        self::assertStringContainsString('!is_string($arr[\'folder\'])', $copy);
    }

    public function testCkfinderFolderHandlerContainsResourceAndThumbnailPaths(): void
    {
        $folderHandler = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/Core/FolderHandler.php');

        self::assertStringContainsString('isPathInside($this->_resourceTypeConfig->getDirectory(), $this->_serverPath)', $folderHandler);
        self::assertStringContainsString('isPathInside($_thumbnailsConfig->getDirectory(), $this->_thumbsServerPath)', $folderHandler);
        self::assertStringContainsString('CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST', $folderHandler);
    }

    public function testDockerImageDeniesFrameworkInternalsFromHttpSurface(): void
    {
        $dockerfile = (string) file_get_contents(__DIR__ . '/../Dockerfile');

        self::assertStringContainsString('COPY --chown=root:root . /var/www/app/', $dockerfile);
        self::assertStringContainsString('&& apt-get upgrade -y', $dockerfile);
        self::assertStringContainsString('ENV APACHE_DOCUMENT_ROOT=/var/www/public', $dockerfile);
        self::assertStringContainsString('cp /var/www/app/public/index.php /var/www/public/index.php', $dockerfile);
        self::assertStringContainsString('Alias /pages/ /var/www/app/pages/', $dockerfile);
        self::assertStringContainsString('a2enconf prescia-hardening', $dockerfile);
        self::assertStringContainsString('<DirectoryMatch "^/var/www/app/(config|prescia|tests|tools|docs)(/|$)">', $dockerfile);
        self::assertStringContainsString('Require all denied', $dockerfile);
        self::assertStringContainsString('chown -R www-data:www-data _temp', $dockerfile);

        $workflow = (string) file_get_contents(__DIR__ . '/../.github/workflows/php83.yml');
        self::assertStringContainsString('aquasecurity/trivy-action@57a97c7e7821a5776cebc9bb87c984fa69cba8f1', $workflow);
        self::assertStringContainsString('severity: CRITICAL,HIGH', $workflow);
    }

    public function testValidatedConnectionDoesNotReResolveHostnameDuringDnsRebinding(): void
    {
        require_once __DIR__ . '/../prescia/lib/loadURL.php';
        $targets = array();
        $socket = fopen('php://temp', 'r+');
        self::assertIsResource($socket);

        $connector = static function (string $target, int $port, $context) use (&$targets, $socket) {
            $targets[] = $target . ':' . $port;
            // A later DNS lookup would return 127.0.0.1; the connector must
            // still receive the IP approved by the first validation step.
            return $socket;
        };

        $connection = presciaLoadUrlOpenValidatedConnection(
            array('93.184.216.34'),
            'http',
            80,
            stream_context_create(),
            $connector
        );

        self::assertSame($socket, $connection);
        self::assertSame(array('93.184.216.34:80'), $targets);
        self::assertNotContains('127.0.0.1:80', $targets);
        fclose($socket);
    }

    public function testLoadUrlRejectsNumericHostRepresentationsAndShortNames(): void
    {
        require_once __DIR__ . '/../prescia/lib/loadURL.php';

        self::assertTrue(presciaLoadUrlIsValidHost('example.com'));
        self::assertTrue(presciaLoadUrlIsValidHost('198.51.100.10'));
        foreach (array('localhost', 'localhost.', 'internal', '2130706433', '0x7f000001', '0177.0.0.1', '127.1') as $host) {
            self::assertFalse(presciaLoadUrlIsValidHost($host), $host . ' must not be accepted as a remote host');
        }
        self::assertFalse(presciaLoadUrlIsPublicIp('127.0.0.1'));
        self::assertFalse(presciaLoadUrlIsPublicIp('::1'));
        self::assertFalse(presciaLoadUrlIsPublicIp('fc00::1'));
    }

    public function testLoadUrlRejectsMixedDnsFamiliesAndInternalAliases(): void
    {
        require_once __DIR__ . '/../prescia/lib/loadURL.php';

        $mixedRecords = static function (string $host): array {
            return array(
                array('type' => 'A', 'ip' => '198.51.100.10'),
                array('type' => 'AAAA', 'ipv6' => '::1'),
            );
        };
        $internalAlias = static function (string $host): array {
            return array(
                array('type' => 'A', 'ip' => '10.0.0.5'),
            );
        };

        self::assertSame(array(), presciaLoadUrlResolvePublicIps('mixed.example', $mixedRecords));
        self::assertSame(array(), presciaLoadUrlResolvePublicIps('attacker.example', $internalAlias));
        self::assertSame(array('198.51.100.10'), presciaLoadUrlResolvePublicIps('public.example', static function (string $host): array {
            return array(array('type' => 'A', 'ip' => '198.51.100.10'));
        }));
    }

    public function testFgetValidatesFtpHostsAndConnectsOnlyToResolvedPublicIps(): void
    {
        $loader = (string) file_get_contents(__DIR__ . '/../prescia/lib/loadURL.php');
        $fget = substr($loader, strpos($loader, 'function fget('));

        self::assertStringContainsString("\$scheme !== 'ftp'", $fget);
        self::assertStringContainsString('presciaLoadUrlResolvePublicIps($host)', $fget);
        self::assertStringContainsString('ftp_connect($ip, $port, 10)', $fget);
        self::assertStringNotContainsString('ftp_connect($url)', $fget);
    }

    public function testFrontControllerEmitsBaselineSecurityHeaders(): void
    {
        $frontController = (string) file_get_contents(__DIR__ . '/../index.php');

        self::assertStringContainsString('X-Content-Type-Options: nosniff', $frontController);
        self::assertStringContainsString('X-Frame-Options: SAMEORIGIN', $frontController);
        self::assertStringContainsString('Referrer-Policy: strict-origin-when-cross-origin', $frontController);
        self::assertStringContainsString('Permissions-Policy:', $frontController);
        self::assertStringContainsString('X-Download-Options: noopen', $frontController);
        self::assertStringContainsString('X-DNS-Prefetch-Control: off', $frontController);
        self::assertStringContainsString('Cross-Origin-Opener-Policy: same-origin', $frontController);
        self::assertStringContainsString('Cross-Origin-Resource-Policy: same-origin', $frontController);
        self::assertStringContainsString('Strict-Transport-Security:', $frontController);
    }

    public function testFrontControllerUsesPerRequestNonceInEnforcedCsp(): void
    {
        $frontController = (string) file_get_contents(__DIR__ . '/../index.php');
        $core = (string) file_get_contents(__DIR__ . '/../prescia/core.php');

        self::assertStringContainsString('$cspNonce = base64_encode(random_bytes(16));', $frontController);
        self::assertStringContainsString("Content-Security-Policy: default-src 'self';", $frontController);
        self::assertStringContainsString("script-src 'self' 'nonce-", $frontController);
        self::assertStringContainsString("object-src 'none'", $frontController);
        self::assertStringContainsString("'CSP_NONCE' =>", $frontController);
        self::assertStringContainsString('htmlspecialchars($cspNonce, ENT_QUOTES, \'UTF-8\')', $core);
        self::assertStringContainsString("preg_replace('/<script\\b(?![^>]*\\bnonce=)/i'", $core);
        self::assertStringNotContainsString('Content-Security-Policy-Report-Only:', $frontController);
    }

    public function testCkfinderUsesOnlyTheModernRuntime(): void
    {
        $entrypoint = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/ckfinder.php');
        $constants = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/constants.php');

        self::assertStringContainsString("require_once 'core/ckfinder.php';", $entrypoint);
        self::assertStringNotContainsString('ckfinder_php4.php', $entrypoint);
        self::assertStringNotContainsString('ckfinder_php5.php', $entrypoint);
        self::assertStringContainsString("define('CKFINDER_CONNECTOR_LIB_DIR', \"./modern\");", $constants);
        self::assertStringNotContainsString('"./php4"', $constants);
        self::assertStringNotContainsString('"./php5"', $constants);
        self::assertStringNotContainsString('CKFINDER_CONNECTOR_PHP_MODE', $constants);
        self::assertDirectoryDoesNotExist(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php4');
        self::assertFileDoesNotExist(__DIR__ . '/../pages/_js/ckfinder/core/ckfinder_php4.php');
    }

    public function testCKFinderP0UploadPolicyUsesPositiveLimitsAndNoActiveTypes(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/config.php');

        self::assertSame(2, substr_count($config, "'maxSize' => '"));
        self::assertStringContainsString("'maxSize' => '8M'", $config);
        self::assertStringContainsString("'maxSize' => '5M'", $config);
        self::assertStringNotContainsString("'name' => 'Flash'", $config);
        self::assertStringNotContainsString("'allowedExtensions' => 'swf,flv'", $config);
        self::assertStringContainsString("\$config['HtmlExtensions'] = array('html', 'htm', 'xml', 'js', 'svg')", $config);
    }

    public function testCKFinderUploadValidatesMimeAndCanonicalDestination(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/FileUpload.php');
        $resourceConfig = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/Core/ResourceTypeConfig.php');

        self::assertStringContainsString("is_uploaded_file(", $upload);
        self::assertStringContainsString("finfo_open(FILEINFO_MIME_TYPE)", $upload);
        self::assertStringContainsString('isPathInside($sServerDir, $sFilePath)', $upload);
        self::assertStringContainsString('$extension));', $resourceConfig);
        self::assertStringNotContainsString('$this->_deniedExtensions[] = strtolower(trim((string)$e));', $resourceConfig);
    }

    public function testCKFinderDownloadUsesSafeContentDisposition(): void
    {
        $download = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/DownloadFile.php');

        self::assertStringContainsString("filename*=UTF-8''", $download);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $download);
        self::assertStringNotContainsString('HTTP_USER_AGENT', $download);
        self::assertStringNotContainsString('Content-type: application/octet-stream; name=', $download);
    }

    public function testCKFinderConfigurationDoesNotExposeErrorsOrGrantWorldWrite(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/config.php');

        self::assertStringContainsString("ini_set('display_errors', '0')", $config);
        self::assertStringContainsString(<<<'PHP'
$config['ChmodFiles'] = 0640
PHP, $config);
        self::assertStringContainsString(<<<'PHP'
$config['ChmodFolders'] = 0750
PHP, $config);
        self::assertStringNotContainsString("ini_set('display_errors', 1)", $config);
        self::assertStringNotContainsString(<<<'PHP'
$config['ChmodFiles'] = 0775
PHP, $config);
        self::assertStringNotContainsString(<<<'PHP'
$config['ChmodFolders'] = 0775
PHP, $config);
    }

    public function testCKFinderAccessControlBindsFullAccessToAuthenticatedAdminRole(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/config.php');

        self::assertStringContainsString("\$_SESSION['CKFinder_UserRole'] = 'admin'", $config);
        self::assertStringContainsString("'role' => 'admin'", $config);
        self::assertStringNotContainsString("'role' => '*',\n\t\t'resourceType' => '*',\n\t\t'folder' => '/'", $config);
    }

    public function testCKFinderUploadEnforcesSizeBeforeScalingAndDetectsHtmlForAllExtensions(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/FileUpload.php');

        self::assertStringContainsString('if ($maxSize && $uploadedFile[\'size\']>$maxSize)', $upload);
        self::assertStringContainsString(<<<'PHP'
if (($detectHtml = CKFinder_Connector_Utils_FileSystem::detectHtml($uploadedFile['tmp_name'])) === true )
PHP, $upload);
        self::assertStringNotContainsString('!$_config->checkSizeAfterScaling() && $maxSize', $upload);
        self::assertStringNotContainsString('inArrayCaseInsensitive($sExtension, $htmlExtensions)', $upload);
    }

    public function testCKFinderImageValidationNormalizesUppercaseExtensions(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/FileUpload.php');

        self::assertSame(2, substr_count($upload, 'strtolower(CKFinder_Connector_Utils_FileSystem::getExtension($sFileNameOrginal))'));
    }

    public function testCKFinderMutableHandlersUseCanonicalPathContainment(): void
    {
        $fileSystem = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/Utils/FileSystem.php');
        self::assertStringContainsString('public static function isPathInside($basePath, $candidatePath)', $fileSystem);
        self::assertStringContainsString('$base = realpath($basePath);', $fileSystem);
        self::assertStringContainsString('$candidate = realpath($candidatePath);', $fileSystem);
        self::assertStringContainsString('self::combinePaths($parent, basename($candidatePath))', $fileSystem);

        foreach (array('CopyFiles', 'MoveFiles', 'RenameFile', 'RenameFolder', 'DeleteFile', 'DeleteFolder', 'CreateFolder') as $handler) {
            $source = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/' . $handler . '.php');
            self::assertStringContainsString('isPathInside(', $source, $handler . ' must validate canonical paths');
        }
    }

    public function testCKFinderDownloadAndCallbacksRejectHeaderAndScriptInjection(): void
    {
        $download = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/DownloadFile.php');
        self::assertStringContainsString('isPathInside($_resourceTypeInfo->getDirectory(), $filePath)', $download);
        self::assertStringContainsString('strpbrk($fileName, "\\r\\n\\0")', $download);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $download);

        foreach (array('FileUpload', 'QuickUpload') as $handler) {
            $source = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/ErrorHandler/' . $handler . '.php');
            self::assertStringContainsString('json_encode(', $source, $handler . ' must encode callback values as JSON');
            self::assertStringContainsString('JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP', $source);
            self::assertStringNotContainsString("str_replace(\"'\", \"\\\\'\"", $source);
        }
    }

    public function testCKFinderUploadValidatesTheCompleteHttpUploadContract(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/FileUpload.php');

        foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
            self::assertStringContainsString("'" . $field . "'", $upload);
        }
        self::assertStringContainsString('is_uploaded_file($uploadedFile[\'tmp_name\'])', $upload);
        self::assertStringContainsString('$uploadedFile[\'size\'] < 0', $upload);
        self::assertStringContainsString('default:', $upload);
        self::assertStringNotContainsString('switch ($uploadedFile[\'error\'])', substr($upload, strpos($upload, '$sServerDir')));
    }

    public function testCKFinderUploadAndCallbacksRejectAmbiguousRequestShapes(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/FileUpload.php');
        $fileUploadError = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/ErrorHandler/FileUpload.php');
        $quickUploadError = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/ErrorHandler/QuickUpload.php');

        self::assertStringContainsString('count($_FILES) !== 1', $upload);
        self::assertStringContainsString("is_scalar(\$_GET['CKFinderFuncNum'])", $fileUploadError);
        self::assertStringContainsString("is_scalar(\$_GET['CKEditorFuncNum'])", $quickUploadError);
        foreach (array($fileUploadError, $quickUploadError) as $handler) {
            self::assertStringContainsString('X-Content-Type-Options: nosniff', $handler);
            self::assertStringContainsString('Cache-Control: no-store, no-cache, must-revalidate', $handler);
        }
        self::assertStringNotContainsString('preg_replace("/[^0-9]/", "", $_GET[', $fileUploadError);
        self::assertStringNotContainsString('preg_replace("/[^0-9]/", "", $_GET[', $quickUploadError);
    }

    public function testCKFinderUploadReservesDestinationWithoutCheckThenMoveRace(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/FileUpload.php');

        self::assertStringContainsString("fopen(\$sFilePath, 'x')", $upload);
        self::assertStringContainsString('fclose($destinationHandle)', $upload);
        self::assertStringContainsString('@unlink($sFilePath)', $upload);
        self::assertStringNotContainsString('if (file_exists($sFilePath))', $upload);
    }

    public function testCKFinderMutableCopiesPublishAtomically(): void
    {
        $fileSystem = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/Utils/FileSystem.php');
        $copy = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/CopyFiles.php');
        $move = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/MoveFiles.php');

        self::assertStringContainsString('public static function copyFileAtomic($sourcePath, $destinationPath)', $fileSystem);
        self::assertStringContainsString('tempnam(dirname($destinationPath), \'.ckfinder-\')', $fileSystem);
        self::assertStringContainsString('copyFileAtomic($sourceFilePath, $destinationFilePath)', $copy);
        self::assertStringNotContainsString('@unlink($destinationFilePath)', $move);
        self::assertStringContainsString('@rename($sourceFilePath, $destinationFilePath)', $move);
    }

    public function testCKFinderRenameFolderValidatesThumbnailDestinationAndNeverDeletesOnFailure(): void
    {
        $rename = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/modern/CommandHandler/RenameFolder.php');

        self::assertStringContainsString('$oldThumbsServerPath = $this->_currentFolder->getThumbsServerPath();', $rename);
        self::assertStringContainsString('isPathInside($_thumbnailsConfig->getDirectory(), $newThumbsServerPath)', $rename);
        self::assertStringContainsString('is_dir($oldThumbsServerPath) && !@rename($oldThumbsServerPath, $newThumbsServerPath)', $rename);
        self::assertStringNotContainsString('Utils_FileSystem::unlink($this->_currentFolder->getThumbsServerPath())', $rename);
    }
}
