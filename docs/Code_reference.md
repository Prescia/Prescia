# Prescia Framework — Code Reference

> **Note:** Original content last updated 2014-06-30. Converted to Markdown: 2026.
> The [Usage Reference](Usage_reference.md) covers the dataflow, templating system, and metadata XML.

## Table of Contents

- [Template Core](#template-core)
- [Core](#core)
- [CoreFull](#corefull)
- [Module (components/module.php)](#module)

---

## Template Core

The Template Core is the template object responsible for rendering HTML output.

> Values marked with `*` are **protected/private** to the class.

### Properties

| Property | Type | Description |
|---|---|---|
| `$debugmode` | bool | Pause and show errors |
| `$path` | string | Full path to templates |
| `$cachepath` | string | Full path to caches |
| `$cacheSeed` | string | Unique key to the current page |
| `$decimais` | int | How many decimal digits to show in numbers |
| `$std_date` | string | Standard date format (`"dd/mm/yyyy"`) |
| `$std_datetime` | string | Standard date-time format (`"hh:nn dd/mm/yyyy"`) |
| `$str_monthlabels` | array | Array of 12 strings representing the months in the current language (i18n) |
| `$str_decimal` | string | Decimal separator |
| `$str_tseparador` | string | Thousands separator |
| `$lang_replacer` | array | Translation tags for `_t` tags as tuples `"tag" => "translation"` |
| `$constants` | array | Array of constants to be replaced in tags automatically |

### Methods

#### `__construct($parent = null, $mypath = "", $debugmode = false)`

Constructs the template object.

- `$parent` — Parent template (`null` for master)
- `$mypath` — Path to the template library
- `$debugmode` — Enable/disable debug mode on this child node

If there is a parent, all settings are loaded from it.

---

#### `addcontent($nome, $tipo, $conteudo)`

Adds a new content entry to the template.

- `$nome` — Tag name for this content (e.g. `{name}`)
- `$tipo` — Parameters for this content (classes to be run)
- `$conteudo` — What is currently inside this tag

---

#### `assign($mkey, $valor)`

Replaces the values of all matching tags with a given value.

```php
$template->assign('page_title', 'Welcome');
// Replaces all {page_title} occurrences with "Welcome"
```

---

#### `assignFile($tag, $file, $checkfirst = false)`

Fills a whole file (turned into a template object) inside a template tag.

- `$tag` — Where to put the file
- `$file` — Full path to the file to be loaded
- `$checkfirst` — Check if the `$tag` exists before performing the action (improves performance if the tag might not exist)

```php
$template->assignFile('sidebar', '/var/www/site/templates/sidebar.html');
```

---

#### `*applyCachedLang()`

*(Private)* Applies all translations based on `$lang_replacer`.

---

#### `append($content)`

Adds new content (string or template object) to the end of the template.

```php
$template->append('<p>Extra content</p>');
$template->append($anotherTemplate);
```

---

#### `clear($preserve_constants = true)`

Completely clears the template and some of its parameters.

- `$preserve_constants` — Whether to preserve the constants in this template

---

#### `copyfrom($outro)`

Copies data from another template object into this one.

> **Note:** Similar to `clone`, but was implemented for PHP 4 compatibility.

---

#### `createPaging($tag, $total_itens, $p_init = 0, $p_size = 30, $numberOfPagesToShow = 7)`

Builds a paging menu based on the given parameters.

- `$tag` — Which content tag the paging exists in (e.g. `"_paging"`)
- `$total_itens` — Total number of items
- `$p_init` — Initial item
- `$p_size` — Items per page
- `$numberOfPagesToShow` — How many page links to show (current page centered if possible)

The template inside `$tag` **must** include:

| Tag | Description |
|---|---|
| `{_has_page_previous}` | Removed if there is no previous page |
| `{_has_page_next}` | Removed if there is no next page |
| `{page_previous}` | Data for the previous page |
| `{page_next}` | Data for the next page |
| `{p_total}` | Total number of pages |
| `{last_page}` | Data for the last page |
| `{_pages}` | Repeated for each page link (see sub-tags below) |

Sub-tags inside `{_pages}`:

| Tag | Description |
|---|---|
| `{qs}` | Query string, parsed to remove paging data |
| `{p_number}` | Current page number |
| `{current_page}` | `1` if this is the current page, `0` otherwise |
| `{p_init}` | Item that starts the current page |

**HTML example:**

```html
{_paginacao}
  <ul>
    {_has_page_previous}
      <li><a href="{vir|/}?{qs}&p_init={page_previous}">{_t}anterior{/t}</a></li>
    {/has_page_previous}
    {_pages}
      <li>
        <a href="{vir|/}?{qs}&p_init={p_init}" {current_page|map|1=class="pag-atual"}>
          {p_number}
        </a>
      </li>
    {/pages}
    {_has_page_next}
      <li><a href="{vir|/}?{qs}&p_init={page_next}">{_t}proxima{/t}</a></li>
    {/has_page_next}
  </ul>
{/paginacao}
```

---

#### `fetch($arquivo)`

Loads a file into the template (full replace).

```php
$template->fetch('/var/www/site/templates/main.html');
```

---

#### `fill($arrayin, $emptyme)`

Applies all data from `$arrayin` to the current template, recursively into sub-templates.

- `$arrayin` — Array of `$tag => $content` pairs
- `$emptyme` — Tag name to remove (e.g. `"_removeme"`)

```php
$template->fill([
    'title'   => 'My Page',
    'content' => 'Hello, world!',
], '_empty');
```

---

#### `*flushCache()`

*(Private)* Actually applies all pending replacements (fill, assign, etc.) to the template. Called internally when necessary.

---

#### `fullpage($tag, &$dbo, $dbr, $n, $params = [], $callback = [], $overflowprotect = 0)`

Loops through a list and fills `$tag` with its contents. Can use a database result set or an array.

- `$tag` — Tag to use for paging (e.g. `"_item"`)
- `$dbo` — Database object
- `$dbr` — Database resource or array
- `$n` — Number of items to show
- `$params` — Paging and display parameters (see below)
- `$callback` — Function called on each iteration: `callback($template, $params, $item)` → returns new data array
- `$overflowprotect` — Hard limit on items shown (useful for tree/nested display)

`$params` keys:

| Key | Description |
|---|---|
| `p_init` | Starting item |
| `p_size` | Items per page (IPP) |
| `excludes` | Array of tags to remove on each iteration |
| `no_paging` | Disable paging and show all items |
| `grouping` | Group items: `"field:tag"` format (SQL must be ordered by the group field) |

Sets `$lastReturnedSet` and `$firstReturnedSet` on the template object after execution.

---

#### `getAllTags($forcelower = false)`

Returns all tags at the current level of the template.

- `$forcelower` — Force returned tag names to lowercase

---

#### `&get($nome, $noerror = true)`

Returns the tag as a template object or string.

- `$nome` — Name of the tag to return
- `$noerror` — Generate an error if not found?

---

#### `gettxt($nome, $noerror = true)`

Same as `get()`, but always returns a string.

---

#### `getTreeTemplate($dt, $sdt, &$tree, $startingId)`

Initiates a tree structure using parent template `$dt` and children template `$sdt`, with `$tree` as the data source.

- `$dt` — Parent template name
- `$sdt` — Child template name
- `$tree` — Tree data array
- `$startingId` — ID of the root item

**Template example:**

```html
{_dirs}
  {chields} <!-- number of child nodes -->
  {id}       <!-- item id -->
  {nid}      <!-- unique item number -->
  {title}    <!-- item title -->
  {_insubdirs}{subdirs}{/insubdirs}
{/dirs}

{_dirs_subdirs}
  {chields}
  {id}
  {nid}
  {title}
  {_insubdirs}{subdirs}{/insubdirs}
{/dirs_subdirs}
```

**PHP example:**

```php
$tree = $categoryObj->getContents('', '', '', '', $sql);
$this->template->getTreeTemplate('_dirs', '_dirs_subdirs', $tree, 0);
```

---

#### `*getTreeTemplate_ex(&$dt, &$sdt, &$tree, $level = 0, $parent = 0, $fulldir = "/")`

*(Private)* Internal recursive function called by `getTreeTemplate` for building the tree.

---

#### `getPagingLinks($total_itens, $p_init, $p_size)`

Returns an array with all data needed for a paging system.

| Index | Description |
|---|---|
| `0` | Total number of pages |
| `1` | Item that starts the last page |
| `2` | Current page number |
| `3` | Item that starts the previous page |
| `4` | Item that starts the next page |

---

#### `havetag($conteudo)`

Returns `true`/`false` whether the given tag exists.

```php
if ($template->havetag('sidebar')) {
    $template->assign('sidebar', $sidebarContent);
}
```

---

#### `reset()`

Completely resets the object.

---

#### `*runclasses($arrayin = false)`

*(Private)* Called automatically to process classes on the template.

---

#### `*runclass($params, $content, $arrayin)`

*(Private)* Called automatically to run a specific class on a specific template part.

---

#### `tbreak($entrada)`

Parses a string into a template object.

---

#### `techo($arrayin, $emptyme, $recursive)`

Fills the template with tags, including translation (`{_t}..{/t}`) and flash (`{_FLASHME}..{/FLASHME}`) data.

- `$arrayin` — Data to fill into the template
- `$emptyme` — Array of tags to delete (e.g. `"_remove"`)
- `$recursive` — Whether to fill `$arrayin` into nested templates

---

## Core

The Core is the main system object, usually referenced as `$this` inside action/content scripts.

#### `addPlugin($script, $relateToModule = "", $renamePluginTo = "", $noRaise = false)`

Loads a plugin.

```php
// In config.php
$this->addPlugin('bi_stats');

// With module association
$bb = $this->addPlugin('bi_bb');
$bb->bbfolder = "bb";
```

---

#### `addLink($file, $preceed = false)`

Adds a CSS or JS file into the `<head>` of the output HTML. Automatically checks for the file in `[site]/files/`, `js/`, or the absolute path.

Returns `true`/`false` if the file was found and linked.

```php
$this->addLink('custom.css');
$this->addLink('analytics.js', false); // append at end
```

---

#### `addMeta($str)`

Adds a string to the `<meta>` field in the page `<head>`.

```php
$this->addMeta('<meta name="author" content="Prescia">');
```

---

#### `*builddomains()`

*(Private)* Automatically rebuilds the domain cache if necessary.

---

#### `checkActions()`

Checks if any action was requested by the page. Also verifies the maintenance `maint.txt` file.

- Sends `haveinfo=1` in the request to automatically load all modules (safer)
- Handles downloads and `ajaxQuery` automatically
- Runs plugin actions and loads page actions
- Initiates basic `lockPermission`

---

#### `checkHackAttempt($command)`

Checks if `$command` is a hack attempt. If it is, immediately aborts the script. Otherwise returns the command itself.

```php
$id = $this->checkHackAttempt($_GET['id']);
```

---

#### `close($stop)`

Ends the script by cleaning up memory and variables.

- `$stop` — Whether to completely stop the script

---

#### `cronCheck()`

Runs scheduled cron calls.

---

#### `deleteAllFrom(&$module, $data, $zerothem = false, $startedAt)`

Deletes (or zeros out) all links to an item that is being deleted. Fills the gap of MyISAM not having foreign key checks.

- `$module` — Which module has data being deleted
- `$data` — Keys of the item in that module
- `$zerothem` — Other modules that link to the deleted item will be set to `0` instead of removed
- `$startedAt` — Which module started `deleteAllFrom` (prevents recursion)

---

#### `domainlock()`

Detects which domain is being called and loads its settings. Also handles domain changes based on `aff_changelocalsite`.

---

#### `dbconnect()`

Connects to the database.

---

#### `fastClose($action, $context)`

Ends the script by generating an error page (usually 404), trying to end gracefully. For an abrupt end, use `close()`.

---

#### `frame($f1, $f2, $f3, $f4)`

Loads files in order, one inside the other. Each file argument is a `"file:TAG"` tuple, where `file` is the file to load and `TAG` is where the next content goes. The last tag is where the actual page content fits.

```php
$this->frame('layouts/master.html:CONTENT', 'layouts/inner.html:INNER', '', '');
```

---

#### `feedReader($url, $cancache)`

Reads an RSS feed and returns its contents as an array.

```php
$feed = $this->feedReader('https://example.com/rss.xml', true);
```

---

#### `fullSearch($parameters, $groupPerModule)`

Returns a list of results across ALL databases based on `$parameters`. See `extra/fullSearch.php` for parameter list.

---

#### `getTemplate($action, $context, $PluginCheck, $secondPass)`

Searches for a template file and returns the full path, or `false`.

---

#### `langOut($tag)`

Translates `$tag` based on the i18n array.

```php
$label = $this->langOut('submit_button');
```

---

#### `loadDimconfig($force = false)`

Loads the site dynamic config. Set `$force = true` to bypass cached data.

---

#### `loadIntlControl()`

Loads all i18n data and translation tags. Can also translate the context from `site/en` to `site/?lang=en`.

---

#### `loadPermissions()`

Loads all permissions for metadata.

---

#### `loadMetadata()`

Loads all database metadata (cached) or builds it (CoreFull).

---

#### `loadmodule($name, $dbname)`

Loads one module (interface only).

```php
$this->loadmodule('news', 'bi_news');
```

---

#### `loadAllmodules()`

Loads ALL modules, including interface and options. Also loads plugins.

---

#### `loaded($moduleName)`

Returns the specified module object or `false`. If the module exists but was not fully loaded, loads it first.

```php
$news = $this->loaded('news');
if ($news) {
    // work with module
}
```

---

#### `lockPermissions()`

Based on current action/context/logged user, loads all permissions from metadata.

---

#### `logged()`

Returns `true` if a user is logged in, `false` for guest users.

```php
if ($this->logged()) {
    // show user-only content
}
```

---

#### `nearTimeLimit()`

Returns `true`/`false` if the script is within ~5 seconds of the PHP time limit.

---

#### `notifyEvent(&$module, $action, $data, $startedAt, $early)`

System-wide notification of an action performed on the database, so other modules can react.

- `$module` — Module that performed the action
- `$action` — Action constant (e.g. `CONS_ACTION_INCLUDE`)
- `$data` — Data that was changed (or keys if deleting)
- `$startedAt` — Which module started this change (prevents loops)
- `$early` — `true` for the first pass of two-pass actions (like delete)

---

#### `onShow()`

Runs the `onShow` scripts to generate a page (usually the last step in page generation).

---

#### `queryOk($testFields)`

Validates `$_POST` fields: checks for hack attempts, existence, and numeric validity (prefix field name with `#` to require numeric).

Returns `true` if all fields are valid, `false` if any are missing or invalid. Stops the script if a hack attempt is detected.

```php
if ($this->queryOk(['title', '#id', 'content'])) {
    // safe to process
}
```

---

#### `parseRequest()`

Parses the page request. Handles `robots.txt`, `favicon`, and multimedia files directly. Prepares layout treatment:

| Value | Layout |
|---|---|
| `0` | Normal |
| `1` | Without frame |
| `2` | Raw |
| `3` | Mobile |

Also runs bot protection and translates `folder/` to `folder/index.html`.

---

#### `prepareMail($name, $fillArray)`

Prepares a template with full valid external URLs ready for use as an HTML email. Does **not** send the email — returns a template object.

```php
$mail = $this->prepareMail('welcome_email', [
    'username' => $user['name'],
    'link'     => 'https://example.com/activate',
]);
// then send $mail using your preferred mailer
```

---

#### `readfile($file, $ext, $exit, $filename, $forceattach)`

Loads and outputs a file with proper HTTP headers.

- `$file` — File to load
- `$ext` — Force this file extension (`""` to auto-detect)
- `$exit` — Output the file and stop the script (`true`/`false`)
- `$filename` — Name of the file presented to the browser
- `$forceattach` — Force download instead of inline display

Also closes the database connection during upload and restarts it if the script is not stopped.

---

#### `registerTclass($script, $class)`

Registers a script as the handler for a template class.

---

#### `removeAutoTags()`

Automatically removes layout-related tags from the template.

---

#### `renderPage($pass)`

Renders the current page (but does not parse it to a string — `onShow` does that). Calls `onRender`, loads templates, tests for 404, removes automatic tags, and runs custom page contents.

- `$pass` — Number of times this has been run (some actions cannot be performed on a second pass)

---

#### `rss($data, $echoHeader, $imgtitle, $imgurl, $imglink)`

Generates an RSS feed from the provided data. See `extra/rss.php`.

---

#### `runAction($module, $action, $data, $startedAt = "")`

Runs an action (include, edit, delete) on a database. Automatically handles images, foreign keys, safety, etc. Returns `true`/`false`.

```php
$result = $this->runAction($newsModule, CONS_ACTION_INCLUDE, [
    'title'   => 'New Article',
    'content' => 'Body text here',
]);
```

---

#### `runContent($module, &$tp, $sql, $tag, $usePaging, $cacheTAG, $callback)`

Runs a content (display) query from the specified module.

```php
$this->runContent(
    $newsModule,
    $this->template,
    ['published = 1', 'date DESC', '10'],
    '_news_item',
    true,
    'news_list',
    false
);
```

---

#### `saveConfig($NO_RAISE)`

Saves system settings. `$NO_RAISE` prevents errors from being raised.

---

#### `showTemplate()`

Shows the final template. Applies all automation: i18n, dynamic configuration, metadata, canonical settings, etc.

---

## CoreFull

CoreFull is the version run during **debug mode**. It does not use any caches — instead it uses raw data and regenerates everything. It is safer but approximately **4× slower** than the normal cached version.

Force it by setting `debugmode=true` in the query string.

Some functions behave differently from Core:

#### `addPlugin($script, $relateToModule, $renamePlugin, $noRaise)`

In addition to Core behavior, also checks if the `monitor.xml` metadata is set.

---

#### `applyMetaData()`

Runs metadata checks on all modules and checks for errors.

---

#### `check_sql()`

Checks for missing database tables or fields and adds them. Also checks for missing keys or unique keys.

---

#### `checkConfig()`

Loads the site dynamic config and checks that all variables exist. Sets defaults for any that are missing.

---

#### `checkinstall()`

Checks that the system is properly installed with all necessary folders. Also checks write permissions on required folders.

---

#### `dbconnect()`

In addition to Core behavior, tries multiple times to connect to the database and will attempt to **create** the database if it is missing.

---

#### `loadMetadata()`

Performs a basic install of folders and configs, reads the `meta.xml`, creates all databases and meta caches. Also reads `custom.xml` after the main `meta.xml`.

---

#### `loadmodule()`

In addition to Core behavior, prevents resetting a module when it is only being redefined from `custom.xml`.

---

#### `*processParameters($thiscampo, $fields, $module)`

*(Private)* Breaks down an XML tag for `$thiscampo` and detects its parameters. Called by `loadMetadata` for each field of a database.

---

#### `save_model()`

Saves caches for modules and permissions.

---

## Module

Each module is represented by the `module` object defined in `components/module.php`.

### Properties

| Property | Type | Description |
|---|---|---|
| `$name` | string | Module name |
| `$parent` | object | Reference to the Core |
| `$title` | string | Which field represents the title of this module |
| `$dbname` | string | Which database is linked to this module |
| `$keys` | array | Main keys (current 2.0 cannot handle multi-keys fully) |
| `$plugins` | array | Plugins this module uses |
| `$order` | string | Default ordering field |
| `$permissionOverride` | string | 9-char permission string (`c`, `a`, `d` = custom, allowed, denied) |
| `$unique` | array | Unique field constraints |
| `$hash` | array | Hash keys that improve search performance |
| `$linker` | bool | `true` if this is a link/join database between two modules |
| `$freeModule` | bool | `true` if this module is not connected to a user/group |
| `$loaded` | bool | Whether the module is fully loaded or just the interface |
| `$fields` | array | Database fields generated by CoreFull from metadata |

**`$options` defaults:**

| Key | Type | Description |
|---|---|---|
| `CONS_MODULE_VOLATILE` | bool | If `true`, module is free of any other module or file; no foreign key control or I/O monitoring |
| `CONS_MODULE_MULTIKEYS` | array | Multi-key definitions |
| `CONS_MODULE_SYSTEM` | bool | If `true`, this is a system module not shown to the user |
| `CONS_MODULE_AUTOCLEAN` | string | SQL `WHERE` statement run on cron to auto-prune old data |
| `CONS_MODULE_PARENT` | string | Another module that is parent for this module |
| `CONS_MODULE_META` | mixed | Metadata about this module |

---

### Methods

#### `__construct($parent, $name, $dbname)`

Default constructor.

---

#### `*autoPrune($enumPruneCache, $data)`

*(Private)* Auto-prune controller called by cron. Controls the number of items per ENUM value. For example, `autoprune="1,3,0"` on `ENUM='a','b','c'` allows 1 "a", 3 "b", and unlimited "c". Exceeding items are transferred to the next enum value; if that overflows, the oldest is moved again.

---

#### `check_mandatory($data, $action)`

Checks if `$data` for an `$action` has all mandatory fields.

- `$action` — `CONS_ACTION_UPDATE` or `CONS_ACTION_INCLUDE`

Returns `true`/`false`.

---

#### `deleteUploads($kA, $field, $ids)`

Deletes all files related to an item, or only the specified ones.

- `$kA` — Array with all valid keys of the module
- `$field` — Which file/field to delete (or empty for all)
- `$ids` — Which thumbnail to delete (e.g. `1_5`), or empty for all

---

### Global Helper: `prepareDataToOutput($template, $params, $data, $processed)`

Defined in the main namespace — callable at any time.

Prepares special fields (images, files, videos) from a module for output.

For each **image/upload** field (named `[image]_1`, `[image]_2`, etc.):

| Tag | Description |
|---|---|
| `[image]` | Full path to image |
| `[image]w` | Width |
| `[image]h` | Height |
| `[image]t` | Complete `<img>` HTML tag |
| `[image]s` | File size (human-readable) |
| `[image]filename` | Filename only (no path) |

If an image is missing but the field has `CONS_XML_NOIMG` set, the standard no-image placeholder is used.

For **non-image files**, only `[image]`, `[image]s`, and `[image]filename` are filled.

For **online video** fields (`CONS_XML_SPECIAL == 'onlinevideo'`):

| Tag | Description |
|---|---|
| `[field]_url` | Link to the video |
| `[field]_embed` | Embed URL (e.g. `https://www.youtube.com/embed/xxxx`) |

If a field is blank, it is added to `$params['excludes']` as `"_toggle_[field]"`.

Returns the processed data array.

```php
// Typical usage inside a callback
function myCallback(&$template, &$params, $data, $processed = false) {
    $data = prepareDataToOutput($template, $params, $data, $processed);
    // further customization...
    return $data;
}
```
