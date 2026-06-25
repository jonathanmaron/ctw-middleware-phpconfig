# PHP 8.5 Migration — `ctw/ctw-middleware-phpconfig`

- **Branch:** `php85` (cut from `master`)
- **Runtime:** PHP 8.3.31 → **8.5.7**
- **PHPUnit:** 12 → **13.2.1**
- **Status:** ✅ done

PSR-15 middleware that applies `php.ini` directives at runtime from a config
array. Under PHP 8.5 the original `composer update -W` failed because
`laminas/laminas-diactoros` 2.x (pulled in transitively via
`ctw/ctw-middleware ^4.0`) caps PHP at `~8.3.0`. The fix path is
`ctw/ctw-middleware: dev-php85` (diactoros → ^3, middlewares/utils → ^4, which
clears five vendor "implicitly nullable parameter" deprecations). This package
also carries one **first-party** PHP 8.5 finding: the test suite exercised the
middleware with the `assert.*` INI directives, which PHP 8.5 deprecates as INI
settings.

---

## Audit checklist

### `test/PhpConfigMiddlewareTest.php` — first-party

- [x] **(deprecation) `test/PhpConfigMiddlewareTest.php`** — `ini_set(): assert.warning INI setting is deprecated` / `assert.active` / `assert.callback`. The suite fed the middleware the `assert.*` directives as example config; PHP 8.5 deprecates the runtime-configurable `assert.*` INI settings (`assert.active`, `assert.warning`, `assert.bail`, `assert.callback`, `assert.exception`), so `ini_set()` emitted a deprecation through `PhpConfigMiddleware::process()`.
  **Fix:** replaced the `assert.*` directives with non-deprecated, string-typed INI directives — `error_prepend_string`, `default_mimetype`, `user_agent` — that round-trip the middleware's `normalize()` output identically (bool → `'On'`/`'Off'`, int → string, null → `''`). `default_charset` was tried first but it validates its value as an encoding (emitted a warning), so an inert string directive was used instead. The `testBooleanFalseIsNormalizedToOff` assertion tightened from a `'' || 'Off'` either-or check to an exact `self::assertSame('Off', …)` now that the directive round-trips deterministically.

### Vendor (cleared by `ctw/ctw-middleware: dev-php85`)

- [x] **(deprecation) `vendor/middlewares/utils`** — five "implicitly nullable parameter" deprecations (`Dispatcher::run()` `$request`; `Factory::createUploadedFile()` `$size`/`$filename`/`$mediaType`; `CallableHandler::__construct()` `$responseFactory`).
  **Fix:** not fixable in this repo's `src/`. Cleared by `middlewares/utils` v4 (declares explicit `?type` parameters), pulled in via `ctw/ctw-middleware: dev-php85`.

### Tooling

- [x] **(tooling) PHPUnit 12 → 13.** Suite runs green on PHPUnit 13.2.1; no first-party test-double changes were required for this package.
  **Fix:** `phpunit/phpunit ^12 → ^13`, `ctw/ctw-qa → dev-php85`, `phpunit.xml.dist` schema bumped to 13.2.
- [x] **(tooling) PHPStan `missingType.*` unmatched-ignore.** Resolved centrally in `ctw/ctw-qa` (`reportUnmatchedIgnoredErrors: false`), consumed via `ctw/ctw-qa: dev-php85`. PHPStan is clean.

---

## composer.json & CI

- [x] `require.php`: `^8.3` → **`^8.5`** — pins the runtime floor to the target major.
- [x] `ctw/ctw-middleware`: `^4.0` → **`dev-php85`** — brings diactoros ^3 (3.8.0) + middlewares/utils ^4 (4.0.2); unblocks `composer update -W`. Re-tag to a stable release before merge.
- [x] `ctw/ctw-qa`: `^5.0` → **`dev-php85`** (PHP 8.5 / PHPUnit 13 QA config). Re-tag before merge.
- [x] `phpunit/phpunit`: `^12.0` → **`^13.0`** (installs 13.2.1).
- [x] `phpunit.xml.dist`: schema → 13.2.
- [x] `.github/workflows/tests.yml`: matrix → **PHP 8.5 only** (`php: [ '8.5' ]`).

---

## Final audit (PHP 8.5.7)

- [x] `php -v` → **PHP 8.5.7** (cli).
- [x] `composer update -W` → **clean** (rc=0, nothing to modify; no security advisories).
- [x] `phpunit --no-coverage --display-deprecations --display-warnings --display-notices --display-errors` → **28 tests, 34 assertions, 0 issues** (PHPUnit 13.2.1 / PHP 8.5.7).
- [x] PHPStan → **clean** (no issues found).
