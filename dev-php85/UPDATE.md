# PHP 8.5.7 Upgrade — `ctw/ctw-middleware-phpconfig`

- **Branch:** `php85` (cut from `master`)
- **Runtime:** PHP 8.3.31 → **8.5.7**
- **Date:** 2026-06-25

This is a **TODO list** of the changes required for this package to run cleanly
under PHP 8.5.7. Boxes are intentionally left unchecked.

---

## ✅ Applied on `php85` (diactoros blocker resolved) — ⚠️ one first-party item remains

> Supersedes the "❌ FAILS" analysis in §1.

- [x] `composer.json`: `ctw/ctw-middleware` `^4.0` → **`dev-php85`** — `composer
  update -W` is now green; the five `middlewares/utils` deprecations (§2a) are
  **cleared** by middlewares-utils v4.
- [ ] **Still open — §2b:** `phpunit --no-coverage` still reports **2
  deprecations** from the deprecated `assert.*` INI settings exercised by
  `test/PhpConfigMiddlewareTest.php`. This is a first-party test fix (replace the
  `assert.*` example directives with non-deprecated ones) and is **independent of
  the diactoros blocker** — see §2b below.

Also residual: the shared PHPStan `missingType.*` unmatched-ignore (§3, owned by
`ctw/ctw-qa`). Re-tag `ctw/ctw-middleware` to stable before merge.

> ⚠️ **This package has a first-party PHP 8.5 finding** (the `assert.*` INI
> deprecation in §2b), in addition to the shared third-party ones.

Detection commands used:

```bash
composer update -W
php vendor/bin/phpunit --no-coverage --display-deprecations --display-warnings --display-notices --display-errors
composer rector      # rector --dry-run
composer phpstan
```

---

## 1. `composer update -W` — ❌ FAILS (inherited blocker)

```
Problem 1
  - Root composer.json requires ctw/ctw-middleware ^4.0
  - ctw/ctw-middleware[4.0.0 ... 4.0.6] require laminas/laminas-diactoros ^2.11
  - laminas/laminas-diactoros[2.11 ... 2.26] require php ~8.0 || ~8.1 || ~8.2 || ~8.3
    -> your php version (8.5.7) does not satisfy that requirement.
```

No direct `laminas-diactoros` dependency; blocked transitively through
`ctw/ctw-middleware ^4.0` (its 4.0.x releases pin Diactoros 2.x, PHP ≤ 8.3).

- [ ] **Blocked on `ctw/ctw-middleware`.** Fix & publish the Diactoros 3 bump
  there first (`ctw-middleware/dev-php85/UPDATE.md` §1), then bump this package's
  `ctw/ctw-middleware` constraint and re-run `composer update -W`.

> §2 was captured against the existing (master) lockfile because the update
> aborts.

---

## 2a. PHP 8.5 runtime deprecations — third-party (`middlewares/utils`)

The "implicitly nullable parameter" deprecation. **Not fixable in this repo's
`src/`.**

| Location | Method / parameter |
| --- | --- |
| `vendor/middlewares/utils/src/Dispatcher.php:21` | `Dispatcher::run()` `$request` |
| `vendor/middlewares/utils/src/Factory.php:88` | `Factory::createUploadedFile()` `$size` |
| `vendor/middlewares/utils/src/Factory.php:90` | `Factory::createUploadedFile()` `$filename` |
| `vendor/middlewares/utils/src/Factory.php:91` | `Factory::createUploadedFile()` `$mediaType` |
| `vendor/middlewares/utils/src/CallableHandler.php:25` | `CallableHandler::__construct()` `$responseFactory` |

- [ ] Resolved by updating `middlewares/utils` once §1 is cleared; escalate
  upstream if the latest release still emits them.

## 2b. PHP 8.5 runtime deprecations — **first-party (must fix here)**

`PhpConfigMiddleware::process()` applies arbitrary php.ini directives via
`ini_set($option, $value)` (`src/PhpConfigMiddleware.php:20`). The test suite
feeds it the `assert.*` directives, which **PHP 8.5 deprecates**:

```
src/PhpConfigMiddleware.php:20  ini_set(): assert.warning INI setting is deprecated
  (CtwTest\...\PhpConfigMiddlewareTest::testBooleanFalseIsNormalizedToOff, test:107)
src/PhpConfigMiddleware.php:20  ini_set(): assert.active  INI setting is deprecated
  (CtwTest\...\PhpConfigMiddlewareTest::testIntegerZeroIsNormalizedToString, test:315)
```

In PHP 8.5 the runtime-configurable `assert.*` INI settings (`assert.active`,
`assert.warning`, `assert.bail`, `assert.callback`, `assert.exception`) are
deprecated.

- [ ] **`test/PhpConfigMiddlewareTest.php`** — the fixtures at lines 26-28, 66,
  93, 101, 110, 119, 129, 137 use `assert.warning` / `assert.active` /
  `assert.callback` as example directives. Replace them with non-deprecated
  php.ini options (e.g. `precision`, `serialize_precision`, `default_charset`)
  so the suite exercises the middleware without tripping the PHP 8.5 deprecation.
- [ ] **`src/PhpConfigMiddleware.php`** (optional, decide in step 2) — consider
  having the middleware skip or warn on directives PHP has deprecated, so a
  consumer's config can't surface the deprecation at runtime. The current code
  is a generic pass-through, so this is a design choice, not strictly required.

---

## 3. QA tooling issues

- [ ] **PHPStan unmatched ignore pattern** (`missingType.generics`) — fix
  centrally in **`ctw/ctw-qa`** (`ctw-qa/dev-php85/UPDATE.md` §3). PHPStan
  currently reports **1 error**, this spurious one only.

---

## 4. Notes (non-blocking)

- Run locally with `--no-coverage` (no Xdebug/PCOV here). Not a PHP 8.5 issue.

---

## 5. Verification snapshot (current state on `php85`)

| Check | Result |
| --- | --- |
| `composer update -W` | ❌ fails — transitive `laminas-diactoros` 2.x (§1) |
| PHPUnit (`--no-coverage`, stale deps) | 28 tests, 34 assertions, **7 deprecations** (5× `middlewares/utils` §2a + 2× `assert.*` §2b) |
| Rector (dry-run) | ✅ no changes proposed |
| PHPStan | ❌ 1 error (shared unmatched-ignore, §3) |

**First-party work needed here:** §2b. Everything else is gated on upstream
`ctw/ctw-middleware` + `ctw/ctw-qa` fixes.
