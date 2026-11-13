---
name: PHP built-in server router chdir pattern
description: When using PHP's built-in server with a router.php, legacy relative require() calls fail unless chdir and include_path are set in the router.
---

# PHP Router chdir Pattern

## Problem
PHP's built-in server serves files from the document root, but when `router.php` includes a file (e.g., `index.php`), and that file does `require("inc/bd.php")`, the include resolves relative to the **including file's directory**, not the project root. This breaks for files in subdirectories.

## Solution
At the top of `router.php`:
```php
$root = __DIR__;
chdir($root);  // set cwd to project root
set_include_path(get_include_path() . PATH_SEPARATOR . $root);
```

**Why:** `chdir()` changes the process working directory; PHP's `require()` with relative paths resolves against cwd. `set_include_path()` also helps for `include_path`-aware lookups.

**How to apply:** Always add this to the router when using PHP built-in server with a legacy project that uses relative `require()` paths.
