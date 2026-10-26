# Prescia Framework — FAQ

> More questions? Use the forum or email **prescia@prescia.net**.

## Table of Contents

- [CMS](#cms)
- [Modules & Plugins](#modules--plugins)
- [404 Page Not Found](#404-page-not-found)
- [Maintenance Pages](#maintenance-pages)
- [Miscellaneous](#miscellaneous)

---

## CMS

### My Content Manager returns 404 but the page exists

Enable the **BI_DEV** plugin. It adds a bar at the bottom of every page (click the `∧` symbol) that shows the exact reason for any 404. Common causes:

| Reason | Explanation |
|---|---|
| *404 because of untreated virtualFolder at actions* | The page is not valid. If it were valid, `onCheckActions` of BI_CMS would have set `$core->ignore404`. |
| *404 because of path not found at renderPage* | — |
| *404 because file not found* | Your `default.php` (in either `actions/` or `content/`) changed behaviour and disabled `$core->ignore404`. |
| *404 because template not found: `_cms.html`* | You have no `_cms.html` in your site root. |

---

### CMS changes the metadata/title after I set it in my content script

The CMS `onShow` hook runs **after** your content script, so it will overwrite the title unless you lock it:

```php
// In your content script, after setting the title:
$this->storage['LOCKTITLE'] = true;
```

Also see [Usage Reference — Section 12](Usage_reference.md#12-meta-tags-filled-by-core).

---

### Loads the wrong frame

The framework loads the frame specified in your root `content/default.php`. To use a different frame for CMS pages:

- Add conditionals to `content/default.php` for your CMS pages, **or**
- Create a subfolder in `content/` and add a new `default.php` that loads the alternative frame.

---

### Loads an empty page

The `default.php` running for your CMS page is not calling `$this->loadTemplate()`. This call **must always** be present in your content scripts.

```php
// content/default.php (minimum required)
$this->loadTemplate('default.html');
```

---

### I have many virtual CMS folders and want some to load different frames

You must handle this manually in your `content/default.php`, for example:

```php
if (strpos($this->context, 'special-section') !== false) {
    $this->frame('layouts/special.html:CONTENT', '', '', '');
} else {
    $this->frame('layouts/main.html:CONTENT', '', '', '');
}
```

Alternatively, extend your CMS module to store which frame each section uses.

---

## Modules & Plugins

### My plugin function (e.g. `notifyEvent`) is not being called

Make sure the plugin is **connected to the module from both sides**:

1. The **module's `meta.xml`** must list the plugin in the `plugins` attribute — even if you load the plugin manually in PHP.
2. The **plugin** must be loaded (via `addPlugin`) before the action that triggers the event.

```xml
<!-- meta.xml -->
<module>
  <dbname>bi_news</dbname>
  <Plugins>bi_stats,bi_undo</Plugins>
  ...
</module>
```

```php
// config.php
$this->addPlugin('bi_stats');
$this->addPlugin('bi_undo');
```

---

## 404 Page Not Found

### A page is returning 404, but it should exist!

Enable the **BI_DEV** plugin. It adds a debug bar to every page. Click the `∧` symbol to see the exact reason for the 404.

```php
// config.php (local only, inside the local-database IF block)
$dev = $this->addPlugin('bi_dev');
$dev->administrativePage = "/adm/";
```

---

## Maintenance Pages

### I get a maintenance warning on my page

A soft-maintenance mode is active. Delete or rename the file `maint.txt` in the site root when you are done with maintenance.

### I get a 503 maintenance page

A hard-maintenance mode is active. Delete or rename the file `heavymaint.html` in the site root when you are done with maintenance.

| File | Behaviour |
|---|---|
| `maint.txt` | Shows a maintenance warning while the server is still running |
| `heavymaint.html` | Serves a 503 page; no PHP execution |

---

## Miscellaneous

### What does EconomicMode do?

EconomicMode disables several high-level features to improve performance. Changes you may notice:

| Feature | Effect |
|---|---|
| `nocache` and `forcecron` request params | Disabled |
| `CONS_BOTPROTECT` | Disabled |
| Cache control times | Doubled (quadrupled for detected bots) |
| Action logging | Disabled |
| Bot logging | Disabled |
| Benchmarking (statistics) | Disabled |
| `CONS_HONEYPOT` trap | Disabled (but captured honeypot hits are still recorded) |
