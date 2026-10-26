# Prescia Framework — Usage Reference

> Original content last updated: 2015-05-15, for version 1.0 final.
> Converted to Markdown: 2026.
> For the API class reference, see [Code Reference](Code_reference.md).

## Table of Contents

1. [Dataflow](#1-dataflow)
2. [Cached Content](#2-making-cached-content)
3. [Plugins](#3-plugins)
4. [Templating — Layout Tags](#4-templating)
5. [Template Core Parameters & Tag Functions](#5-template-core-parameters)
6. [Other Useful Tag Replacements](#6-other-useful-tag-replacements)
7. [Metadata XML](#7-metadata-xml)
8. [Monitor XML](#8-monitor-xml)
9. [Tree Structures (Nested)](#9-building-tree-structures)
10. [CMS Tags](#10-cms-tags)
11. [Template Callbacks](#11-template-callbacks)
12. [Meta Tags Filled by Core](#12-meta-tags-filled-by-core)
13. [Quick Function Reference](#13-quick-function-reference)

---

## 1. Dataflow

The diagram below shows the full request lifecycle. Lines marked `###` indicate where **your code** runs.

```
/index.php
  │
  ├─ Loads settings.php
  │
  ├─ core::domainLoad()
  │     Detect which domain to serve
  │     Load your config.php
  │     Load i18n variables and settings
  │     Break down the request (action, context, extension, …)
  │
  ├─ core::dbconnect()            (automatic)
  │     Connect to the database
  │
  ├─ core::loadMetadata()
  │     Load metadata for each module/plugin
  │     └─ [plugins]::onMeta      (debug mode only)
  │
  ├─ core::parseRequest()
  │     Finalize request detection
  │     Append any queued session messages to output
  │
  ├─ Initialize template
  │
  ├─ core::loadIntlControl()
  │     Detect which i18n to serve
  │
  ├─ core::checkActions()
  │     Detect if request action exists (virtual)
  │     Perform authentication
  │     Handle ajaxQuery, ajaxqueryunique, download
  │     └─ [plugins]::onCheckActions
  │     ### actions/default.php ###      ← your default action code
  │     ### actions/[action].php ###     ← your action code (no template refs here)
  │
  ├─ core::cronCheck()
  │     └─ [plugins]::onCron
  │
  │   (if no cache is served →)
  │
  ├─ core::renderPage()
  │     └─ [plugins]::onRender
  │     ### content/default.php ###      ← load your template / HTML here
  │     ### content/[action].php ###     ← your page-specific content code
  │     └─ [plugins]::onShow
  │
  ├─ core::showHeaders()
  │
  ├─ core::showTemplate()
  │     Fill dynamic config, metadata, canonical, OG, link rel, favicon…
  │     Add alternate i18n link tags
  │     i18n cleanup
  │     Prepare printver link
  │     Echo (stringify) the template
  │
  ├─ core::cacheControl::setCache
  │
  ├─ [plugins]::onEcho            (page is now a string)
  │
  ├─ Run performance/evaluation hit counters
  │
  └─ Echo page (Gzip if possible)
```

**Summary flow:**

```
domainLoad → dbconnect → loadMetadata (onMeta) → parseRequest
  → loadIntlControl → checkActions (onCheckActions, your code)
  → cronCheck (onCron) → renderPage (onRender, your code, onShow)
  → showTemplate → (onEcho)
```

---

## 2. Making Cached Content

Use `cacheControl` to avoid rebuilding expensive content on every request.

```php
// Check for an existing cache entry named "tag" that is no older than 60 seconds
$temp = $this->cacheControl->getCachedContent('tag', 60);

if ($temp === false) {
    // No cache found — build the content
    $temp = $this->runContent(/* ... */);

    // Store in cache:
    //   true  = shared cache for all users
    //   false = per-user cache (same for all guests, but separate per logged-in user)
    $this->cacheControl->addCachedContent('tag', $temp, true);
}

// Use $temp (template object or string)
$this->template->assign('content', $temp);
```

---

## 3. Plugins

Plugins extend the framework with optional features. Load them in your `config.php` after `dbconnect`.

### BI_STATS

Gathers statistics for every page hit. Suggested as the **last** plugin loaded.

```php
// config.php
$this->addPlugin('bi_stats');
```

Statistics are stored in the tables defined in the plugin's `meta.xml`. Use **BI_ADM** to inspect them. BI_STATS is i18n-aware and avoids logging known bots.

---

### BI_DEV

Developer tools/helpers — best used only on your local machine.

```php
// Inside the "local database" IF block in config.php
$dev = $this->addPlugin('bi_dev');
$dev->administrativePage = "/adm/"; // optional: where the admin area is
```

Adds a time/MySQL data bar to each page. Call any page with `?dev_help=true` for more options.

---

### BI_FM

Adds a "protected" folder in the file system, accessible only by authorized users.

```php
$this->addPlugin('bi_fm');
```

---

### BI_CMS

Manages editable content areas (`{CONTENTMAN}`) and virtual pages (pages that do not exist as template files).

```php
$this->addPlugin('bi_cms'); // suggested: load last, so it handles 404 last
```

- Virtual pages use the `_cms.html` template.
- `CONTENTMAN1` has no use — use `{CONTENTMAN}` or `{CONTENTMAN#}` (where `#` ≥ 2).
- i18n-aware.

---

### BI_AUTH and BI_GROUPS

Authentication and group/permission management. BI_AUTH requires BI_GROUPS.

```php
$this->addPlugin('bi_groups');           // required for AUTH
$auth = $this->addPlugin('bi_auth');     // user/auth system
```

Group `100` is the **master group** (ignores all permission checks — full access).

---

### BI_SEO

Creates URL aliases for pages (which may or may not exist as template files). Alias pages can also have custom titles and meta tags.

```php
$this->addPlugin('bi_seo');
```

---

### BI_UNDO

Saves a copy of every data change so you can undo them. Data is retained for 7 days by default.

```php
$this->addPlugin('bi_undo');
```

---

### BI_ADM

Creates an administrative area (usually `/adm/`) that reads `meta.xml` to generate all necessary editors.

```php
$this->addPlugin('bi_adm');
```

Two password levels:
- **Soft password** — set in the database (normal admin password)
- **Hard password** — set in `config.php` for the `"master"` account (level 100, can access all sites)

---

### BI_BB

A highly configurable Forum/Blog/Article plugin. Can run all three modes simultaneously.

```php
$bb = $this->addPlugin('bi_bb');
$bb->bbfolder          = "bb";          // base folder for all BB content
$bb->registrationGroup = 4;             // group for newly registered users
$bb->areaname          = "community";   // name of this area
$bb->homename          = "Prescia";     // homepage link label
$bb->noregistration    = false;         // allow/disallow public registration
$bb->blockforumlist    = false;         // show/hide full content list at index
$bb->showlastthreads   = 0;             // threads to show at index (0 = none)
$bb->mainthreadsAsBB   = true;          // treat index threads as forum or blog/article
$bb->ignorefolders     = "";            // comma-delimited folders NOT part of BI_BB
```

Override the default templates by setting:

```php
$bb->bbpage      = "thread";   // forum (list of threads)
$bb->blogpage    = "blog";     // blog thread list
$bb->articlepage = "article";  // article thread list
```

---

## 4. Templating

### Layout Tags

Some content tags are automatically removed in certain layout modes:

| Tag | Layout 0 (normal) | Layout 1 (popup) | Layout 2 (ajax) | Mobile (`CONS_BROWSER_ISMOB`) |
|---|---|---|---|---|
| `_ajaxonly` | REMOVE | REMOVE | KEEP | ← as layout |
| `_removeonpopup` | KEEP | REMOVE | REMOVE | ← as layout |
| `_removeonajax` | KEEP | KEEP | REMOVE | ← as layout |
| `_removemob` | KEEP | KEEP | KEEP | REMOVE |
| `_onserver` | NEST | NEST | NEST | NEST (removes on LOCAL) |

### Always-Present Tags

These tags are always available in your templates:

| Tag | Value |
|---|---|
| `PAGE_TITLE` | The page title (inside `<title>`) |
| `IMG_PATH` | Path to the site's files folder |
| `FMANAGER_PATH` | Path to the file manager folder |
| `BASE_PATH` | Root of the installation (`CONS_INSTALL_ROOT`) |
| `JS_PATH` | Path to common JavaScript libraries |
| `SESSION_LANG` | Current i18n language |
| `CHARSET` | Character set |
| `DOMAIN_NAME` | Domain being served |
| `METAKEYS` | Meta keywords |
| `METADESC` | Meta description |
| `CANONICAL` | Canonical URL of the current page |
| `HEADCSSTAGS` | CSS `<link>` tags for the `<head>` |
| `HEADJSTAGS` | JavaScript `<script src>` tags for the `<head>` |
| `HEADUSERTAGS` | User-defined meta tags appended to `<head>` |
| `METATAGS` | Full built-out meta tags block |

Filled only at `checkActions`:

| Tag | Value |
|---|---|
| `ACTION` | Current page/action (without extension) |
| `CONTEXT` | Current folder/context |
| `ORIGINAL_ACTION` | Original action call (with extension) |

Also see [Section 12](#12-meta-tags-filled-by-core).

---

## 5. Template Core Parameters

Template Core automatically inserts a `{endbody}` tag just before any `</body>` element (used by some plugins).

### Tag Output Functions

When outputting a tag like `{something}`, you can chain processing functions with `|`:

```
{tagname|function|param1|param2}
```

| Function | Usage | Description |
|---|---|---|
| `integer` | `{val\|integer}` | Show only an integer (rounds) |
| `float` | `{val\|float}` | Alias for `number` |
| `number` | `{val\|number\|2\|.\|,}` | Format: decimals, decimal sep, thousands sep |
| `truncate` | `{val\|truncate\|100\|…\|true\|true}` | Truncate with trail; 1st `true` strips HTML; 2nd `true` keeps `<br/>`/`</p>` |
| `nl2br` | `{val\|nl2br}` | Convert newlines to `<br/>` |
| `onnull` | `{val\|onnull\|default}` | Show `default` if blank/null |
| `toplain` | `{val\|toplain}` | Show HTML as plain text (`<` → `&lt;`) |
| `html` | `{val\|html}` | Prepare text for use inside an `input`/`title` attribute |
| `htmlentities` | `{val\|htmlentities}` | Apply `htmlentities` |
| `url` | `{val\|url}` | Prepend `http://` if missing |
| `deutf` | `{val\|deutf}` | Remove UTF-8 encoding |
| `uc` / `ucwords` | `{val\|ucwords}` | Capitalize words (also handles Portuguese characters) |
| `up` | `{val\|up}` | Uppercase |
| `noenvelope` | `{val\|noenvelope}` | Trim and remove wrapping tags |
| `nohtml` | `{val\|nohtml}` | Strip all HTML |
| `each` | `{val\|each\|2=odd\|even}` | On each loop iteration: alternates values |
| `map` | `{val\|map\|0=zero,1=one\|other}` | Convert values; `%%1` returns the raw variable |
| `select` / `selected` | `{val\|selected}` | Add `selected='selected'` if set |
| `check` / `checked` | `{val\|checked}` | Add `checked='checked'` if set |
| `month` | `{val\|month}` | Show the month label of a date/datetime |
| `date` | `{val\|date\|Y-m-d}` | Output in date format (default = site standard) |
| `datetime` | `{val\|datetime\|Y-m-d H:i}` | Same as `date`, but includes time |

**Examples:**

```html
<!-- Format a number -->
Price: {price|number|2|.|,}

<!-- Truncate with HTML strip -->
{summary|truncate|150|…|true}

<!-- Conditional display using map -->
{status|map|1=Active,0=Inactive|Unknown}

<!-- Mark current item in a list -->
<option value="{id}" {selected|selected}>{label}</option>
```

---

## 6. Other Useful Tag Replacements

### Auto-Injected Tags

- `{endbody}` — automatically inserted just before `</body>`

### Loop/List Tags (auto-filled by `runContent` or template system)

| Tag | Description |
|---|---|
| `#` | Iteration number starting at 1 |
| `islast` | `1` if this is the last item, `0` otherwise |
| `isfirst` | `1` if this is the first item, `0` otherwise |

```html
{isfirst|map|1=This is the first item|Not the first item}
```

### Classes

| Class | Usage | Description |
|---|---|---|
| `vdir` | `{path\|vdir\|/base}` | Remove redundant paths and make relative to current page |
| `query_strings` | `{qs\|query_strings\|exclude1,exclude2}` | Build query string from `$_REQUEST` (excluding specified keys) |
| `seo` | `{file\|seo\|fallback}` | Return SEO alias for the file (requires BI_SEO) |

### Image / Upload File Tags

For any upload field named `file`:

| Tag | Description |
|---|---|
| `{file}` | `y`/`n` — whether a file is uploaded |
| `{file_1}` | Raw URL for the original file (thumbnail `_1`) |
| `{file_1w}` | Width |
| `{file_1h}` | Height |
| `{file_1t}` | Full `<img>` (or Flash) HTML tag |
| `{file_1s}` | File size (human-readable) |
| `{file_1filename}` | Filename only (no path) |

> Remember to prepend `/` or `CONS_INSTALL_ROOT` before `{file_#}` in your HTML.

### Video Field Tags (when `special = onlinevideo`)

| Tag | Description |
|---|---|
| `{file_url}` | Direct link (YouTube or Vimeo) |
| `{file_embed}` | Embed iframe URL |

Vimeo embed: `https://player.vimeo.com/video/xxxxxx`  
YouTube embed: `https://www.youtube.com/embed/xxxx`  
YouTube thumbnail: `https://img.youtube.com/vi/{link}/0.jpg`

### Toggle Tags

In any loop using `runContent`, if a database field is empty its `_toggle_` tag is added to the exclude list:

```
{_toggle_[dbitem]}
```

If you retrieve `$data` from `runContent` and echo it later, toggles won't fire automatically. In that case, manually loop through `$params['excludes']`.

### Grouping in Lists

To group items under a header template:

```php
$this->templateParams['grouping'] = 'header:_header';
```

```html
{_header}<h1>{header}</h1>{/header}
{_list}
  <p>{title}</p>
{/list}
```

> The SQL **must** be ordered by the grouping field.

---

## 7. Metadata XML

Define your modules and their database structure in `meta.xml` (and optionally `custom.xml`).

### Module-Level Attributes

| Attribute | Description |
|---|---|
| `dbname` | Database table name |
| `Keys` | Comma-delimited primary keys |
| `Title` | Which field represents the item's title |
| `Volatile` | `true` — no foreign key control or I/O monitoring |
| `Multikeys` | Keys that auto-restart when the main key changes |
| `Parent` | Field that links to the parent item (e.g. `id_parent`) |
| `Plugins` | Comma-delimited plugin list |
| `Order` | Default sort field(s), e.g. `date -` |
| `permissionoverride` | 9-char string: `A`=allow, `C`=custom, `D`=deny for USER(r,w,c), GROUP(r,w,c), ALL(r,w,c) |
| `Linker` | `true` — this module is a join/link table |
| `Systemmodule` | `true` — hidden from the user |
| `autoclean` | SQL `WHERE` clause for cron auto-prune |
| `backup` | `no` — exclude from monthly auto backup |
| `meta` | Free-form metadata (not used by the core) |

**BI_ADM-specific:**

| Attribute | Description |
|---|---|
| `listing` | Comma-delimited fields to show in admin list. Use `#module` to show a related module's count |
| `warning` | Warning message shown in list/edit panes |
| `merge` | Another module to show as part of this one (must have a link field) |
| `tabs` | Pre-configured searches shown as tabs in the list pane, separated by `\|` |

### Field-Level Attributes

| Attribute | Description |
|---|---|
| `mandatory` | `true` |
| `join` | `left` or `inner` (default) |
| `unique` | `true` |
| `html` | `true` — allow HTML. Also required for serialized data |
| `size` | For text fields |
| `timestamp` | `true` — auto-set on insert |
| `updatestamp` | `true` — auto-set on update |
| `filetypes` | Comma-delimited allowed extensions |
| `filemaxsize` | Max file size in bytes |
| `thumbnails` | Thumbnail sizes, delimited by `\|`: each is `W,H` |
| `filepath` | Path for uploaded files |
| `restrict` | Restrict to a certain user level |
| `default` | Default value; use `%UID%` for current user ID in user links |
| `ignorenedit` | Do not set to null/blank if nothing is submitted |
| `forcesimple` | Use simpler HTML text field |
| `condthumbnails` | Conditional thumbnail cut by enum: `enum_name:[x,y;][x,y;]…` |
| `tweakimages` | Per-thumbnail image processing, delimited by `\|` (see below) |
| `noimg` | Default image if no upload, relative to `files/` |
| `special` | Special field type: `urla`, `login`, `mail`, `ucase`, `lcase`, `path`, `onlinevideo`, `time`, `date` |
| `urlaformat` / `furlformat` | How to build the friendly URL (put before timestamp fields if using dates) |
| `autoprune` | Comma-delimited limits per ENUM value (`0` = unlimited) |
| `isowner` | Sets the user as owner (default: first user link field) |
| `meta` | `masked` = password input; `password` = also hides from non-master output |
| `filteredBy` | Comma-delimited fields that filter this one |
| `readonly` | Field is not editable in admin (controlled by modules) |
| `custom` | `true` — disable all text parsing |
| `hashkey` | `true` — add an index. Recommended for frequently queried foreign keys |
| `conditional` | bi_adm: `field=value` or `field!=value` (field must be ENUM) |

**`tweakimages` options:**

```
stamp:over(filename@x,y)[r]
stamp:under(filename@x,y)[r]
croptofit[:top left right bottom]
```

### Field Types

| Type | Notes |
|---|---|
| `INT` | |
| `TINYINT` | |
| `BIGINT` | |
| `SMALLINT` | |
| `FLOAT` | |
| `VARCHAR` | |
| `VC` | |
| `BOL` | |
| `BOOLEAN` | Stores `'y'`/`'n'` |
| `ENUM` | Same format as SQL `ENUM` |
| `TXT` | |
| `TEXT` | 64 KB |
| `MEDIUMTEXT` | 16 MB |
| `LONGTEXT` | 4 GB |
| `DATE` | |
| `DATETIME` | |
| `OPT` / `OPTIONS` | Same format as ENUM |
| `FILE` | |
| `UPLOAD` | |
| `SERIALIZED` | Allows nesting of types; stored as array in one field |
| `[module]` | Foreign key to another module |

### Example `meta.xml` Snippet

```xml
<module>
  <dbname>bi_news</dbname>
  <Keys>id</Keys>
  <Title>title</Title>
  <Order>date -</Order>
  <listing>title,date,status</listing>

  <fields>
    <id type="INT" />
    <title type="VARCHAR" size="200" mandatory="true" />
    <content type="TEXT" html="true" mandatory="true" />
    <image type="UPLOAD" filetypes="jpg,png,gif" thumbnails="800,600|200,150" filepath="news/" />
    <date type="DATETIME" timestamp="true" />
    <status type="ENUM" default="draft">
      <values>'draft','published','archived'</values>
    </status>
  </fields>
</module>
```

---

## 8. Monitor XML

Defines notification icons/counters shown in the **BI_ADM** administrative pane.

Each item can have any name (ignored). Attributes:

| Attribute | Required | Description |
|---|---|---|
| `MODULE` | ✓ | Module being monitored |
| `SQL` | ✓ | `WHERE` clause to filter monitored items |
| `MONITOR_LEVEL` | | Display level: `low`, `high`, or any value with a matching `notify[__].png` in the admin folder |
| `MONITOR_TEXT` | | i18n text label for a single item |
| `MONITOR_TEXT_PLURAL` | | i18n text label for 0, 2, or more items |

---

## 9. Building Tree Structures

Use `getTreeTemplate` to display nested/recursive data.

**Template:**

```html
{_dirs}
  <li>
    {chields|map|0=last node|have children}
    <a href="#" onclick="menuClick('{id}','{chields}','{link}');">{title}</a>
    {_insubdirs}
      <ul>{subdirs}</ul>
    {/insubdirs}
  </li>
{/dirs}

{_dirs_subdirs}
  <li>
    <a href="#" onclick="menuClick('{id}','{chields}','{link}');">{title}</a>
    {_insubdirs}
      <ul>{subdirs}</ul>
    {/insubdirs}
  </li>
{/dirs_subdirs}
```

**PHP:**

```php
$tree = $categoryObj->getContents('', '', '', '', $sql);
$this->template->getTreeTemplate('_dirs', '_dirs_subdirs', $tree, 0);
```

Tag reference inside tree templates:

| Tag | Description |
|---|---|
| `{chields}` | Number of child nodes |
| `{id}` | Item ID (may be a string) |
| `{nid}` | Unique item number |
| `{title}` | Item title |
| `{_insubdirs}{subdirs}{/insubdirs}` | Renders child nodes inside `{subdirs}` |

---

## 10. CMS Tags

Available only when **BI_CMS** is enabled.

| Tag | Description |
|---|---|
| `{CONTENTMAN_TITLE}` | Editable title managed by CMS |
| `{CONTENTMAN}` | Main editable content area |
| `{CONTENTMAN#}` | Additional editable areas (replace `#` with a number ≥ 2) |

**Breadcrumbs template:**

```html
{_breadcrubs}
  <a href="{page}.html">[{title}]</a>
  {_hasnext} \ {/hasnext}
{/breadcrubs}
```

Pages that do not exist as template files but are added to the CMS use the `_cms.html` template for output.

---

## 11. Template Callbacks

Callbacks are called on each item during `runContent` or `fullPage`.

**Signature:**

```php
function myCallback(&$template, &$params, $data, $processed = false) {
    // $processed = true when called a second time on the SAME item

    // To exclude a tag, add to excludes (do NOT set $data['_something'] = ''):
    if (empty($data['image'])) {
        $params['excludes'][] = '_image_block';
    }

    // $params also has 'core' and 'module' references:
    $core   = $params['core'];
    $module = $params['module'];

    // ... transform $data as needed ...

    return $data; // MUST return $data
}
```

Pass the callback to `runContent`:

```php
$this->runContent($newsModule, $this->template, $sql, '_news_item', true, false, 'myCallback');
```

---

## 12. Meta Tags Filled by Core

### Title & Meta Priority (highest wins last)

| Source | Tags locked | Timing |
|---|---|---|
| `config.php` | title, meta keywords, meta description | At index, no lock |
| **BI_SEO** | title, meta keywords, meta description | At `checkAction`, sets `LOCKTITLE` |
| **BI_CMS** | title, meta keywords, meta description | At `onShow`, if not locked, sets `LOCKTITLE` |

Lock constants: `LOCKTITLE`, `LOCKDESC`, `LOCKKEYS`

To change the title from your content script and prevent CMS from overriding it:

```php
$this->template->assign('PAGE_TITLE', 'My Custom Title');
$this->storage['LOCKTITLE'] = true;
```

### Automatically Filled Meta Tags

| Tag | Source |
|---|---|
| `meta description` | `$template->constants['METADESC']` |
| `meta keywords` | `$template->constants['METAKEYS']` |
| `link canonical` | `$template->constants['CANONICAL']` — set by `showTemplate` |
| `link rel shortcut icon` | From `favicon` file in `files/` |
| `meta robots` | Based on `CONS_SESSION_NOROBOTS` (true when visiting a `CONS_NOROBOTDOMAINS` domain) |

Also see [Section 4](#4-templating).

---

## 13. Quick Function Reference

Most of the time `$core` is referenced as `$this` inside action/content scripts.

```php
// Build a SQL filter, order, and limit
$sql = $module->get_base_sql($where, $order, $limit);

// Run content (core shorthand)
$core->runContent($module, $tp, $sql, $tag, $usePaging, $cacheTag, $callback);

// Run content (module shorthand)
$module->runContent($tp, $sql, $tag, $usePaging, $cacheTag, $callback);

// Fetch all rows as array
$rows = $module->getContents($order, $treeTitle, $where, $treeSeparator, $sql);

// Check the result of the last action
$ok = $core->lastReturnCode; // true/false

// Create a paging bar
$core->template->createPaging('_pagetag', $total, $pageStart, $itemsPerPage);
```

### Common Content Script Pattern

```php
// content/news.php

// Fetch the requested item
$id   = $this->checkHackAttempt($_REQUEST['id'] ?? 0);
$news = $this->loaded('news');

if (!$news) {
    $this->fastClose('', '');
    return;
}

// Load the template
$this->loadTemplate('news.html');

// Run the content query
$this->runContent($news, $this->template, "id = $id", '_news', false, "news_$id", false);

// Set the page title from the result
if ($this->lastReturnCode) {
    $this->template->assign('PAGE_TITLE', $this->template->gettxt('news_title'));
    $this->storage['LOCKTITLE'] = true;
} else {
    $this->fastClose('', '');
}
```
