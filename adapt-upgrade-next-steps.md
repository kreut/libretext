# ADAPT upgrade: what's left to test and do

Project: `~/adapt_laravel_8`, branch `php_upgrade`
Now on: **Laravel 12.70.1, PHP 8.4.17** (MAMP PRO 7.4, site `local.adapt`)

Automated tests: **1,836 run, 0 errors, 4 failures**. The 4 failures are the outdated
`QueryTest` tests that call the removed `getQuestionsByTags` endpoint, and they were
already broken before the upgrade.

---

## 1. Manual testing at https://local.adapt:8891

These areas changed during the upgrade. The test suite covers most of them, but click
through each one.

- [ ] **Log in** and move between a few pages
- [ ] **WeBWorK question**: open one, submit an answer, request a hint
      *(JWT secret swapping, and answer submission)*
- [ ] **Upload a PDF** for a question, and for a solution
      *(file storage, Flysystem 3)*
- [ ] **Create cutups** from a PDF solution *(file storage)*
- [ ] **Shift dates** for a course's assignments *(Carbon 3 fix)*
- [ ] **Canvas-linked course**: load courses and assignments, if one is set up locally
      *(CanvasAPI constructor)*
- [ ] **Framework**: export one, and download the template *(file storage)*
- [ ] **Scores**: view a course's scores page *(ScoreController time limit)*
- [ ] **Audio or video discussion comment**: record or upload one
      *(S3 client and bucket access)*
- [ ] **QTI import**: upload a QTI zip *(local file paths)*

If something breaks:

```bash
cd ~/adapt_laravel_8
tail -40 storage/logs/laravel.log
```

---

## 2. Before deploying

- [ ] Command-line `php.ini`: hide deprecation notices, if not done yet
  ```bash
  sed -i '' 's/^error_reporting = .*/error_reporting = E_ALL \& ~E_DEPRECATED \& ~E_USER_DEPRECATED/' /Applications/MAMP/bin/php/php8.4.17/conf/php.ini
  ```
- [ ] Do the same in MAMP PRO's web `php.ini`: **File → Edit Template → PHP → 8.4.17**
- [ ] `vapor.yml`: set `runtime: 'php-8.4:al2'` for each environment
      (`php-8.4:al2-arm` if you use Arm)
- [ ] Commit everything: `git add -A && git commit -m "..."`
- [ ] Deploy to **staging** first, then repeat the manual tests there
- [ ] Check the staging logs
- [ ] Deploy to production

---

## 3. Follow-up work (after the upgrade ships)

- [ ] Replace the 12 abandoned `web-token/*` packages with `web-token/jwt-library`.
      This needs the `JWT` trait (`app/Traits/JWT.php`) rewritten, since `jwt-easy`
      doesn't exist in the new library. It also removes the `Safe\...` deprecation
      notices, which come from `thecodingmachine/safe` 1.x.
- [ ] Delete or rewrite the 4 obsolete `QueryTest` tests (`getQuestionsByTags`)
- [ ] Fix the 6 "No tests found" classes: `JwtTest`,
      `LearningTreesInAssignmentsTest`, `FlashCardTest`,
      `QuestionsViewLearningTreesTest`, `StudentLearningTreesInAssignmentsTest`,
      `VerificationTest`
- [ ] Convert `/** @test */` doc-comments to `#[Test]` attributes (Rector),
      before PHPUnit 12
- [ ] Fix PHP 8.4 implicit-nullable parameters (`Type $x = null` → `?Type $x = null`)
      with Rector
- [ ] Convert legacy factories to class-based factories, then remove
      `laravel/legacy-factories`
- [ ] Fix the PSR-4 test class-name mismatches that Composer warns about
- [ ] Plan the next upgrade: Laravel 12 security support ends **Feb 24, 2027**;
      Laravel 13 needs PHP 8.3+, which you already have

---

## Useful commands

Full test suite, without stopping at the first failure:

```bash
cd ~/adapt_laravel_8
sed 's/stopOnFailure="true"/stopOnFailure="false"/' phpunit.xml > phpunit.full.xml
vendor/bin/phpunit -c phpunit.full.xml --testdox 2>&1 | tee ~/test-run.log
rm phpunit.full.xml
tail -1 ~/test-run.log
```

Security and abandoned-package check:

```bash
composer audit
```

---

## What changed in the upgrade (for reference)

| Area | Change |
|---|---|
| Framework | Laravel 7 → 12, PHP 7.4 → 8.4 |
| Removed packages | `fideloper/proxy`, `fruitcake/laravel-cors`, `laravel/vapor-ui`, `doctrine/dbal`, `beyondcode/laravel-dump-server`, `facade/ignition` |
| Migrations | `->change()` calls now list their `nullable()` / `default()` (Laravel 11+ no longer keeps them); `unsignedDecimal` → `decimal()->unsigned()`; Doctrine calls removed |
| `DB::raw` | `DB::select(DB::raw(...))` → `DB::select((...))` |
| File storage | `getAdapter()->getPathPrefix()` → `path('')`; `getBucket()` → `getConfig()['bucket']` |
| JWT | `App\Providers\JWT\SwappableSecretLcobucci` makes `setSecret()` work again with `tymon/jwt-auth` 2.x |
| WeBWorK answers | `JWTController` puts the answer JWT object into the request through a new `InputBag` (Symfony 6) |
| Canvas | `CanvasAPI` constructor arguments are optional (Laravel 11+ creates models with no arguments) |
| Dates | `AssignmentController::shiftDates()` uses `subMinutes()` (Carbon 3) |
| Time limit | `ScoreController` sets its 300-second limit for web requests only |
| Factories | Kept as-is via `laravel/legacy-factories` |
| Security | `symfony/yaml` 8.1.6 (CVE-2026-109413); Composer platform set to `8.4.17` |
