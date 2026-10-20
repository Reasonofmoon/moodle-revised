# ReadMaster Theme Implementation Plan

> Execution: inline (executing-plans style), task by task, commit after each task.

**Goal:** Ship `theme_readmaster`, a brand-profile-driven Boost child theme with a public home page, and a clone CLI for other academies.

**Architecture:** Boost child theme. All academy data lives in `brand/profile.json` + `pix/`. Pure PHP classes (`brand`, `blog_feed`) carry the logic and are unit-tested. Layout/templates only render what those classes return.

**Tech Stack:** Moodle 6.0dev, PHP 8.3, Mustache, SCSS (Moodle's scssphp), PHPUnit (`advanced_testcase`), Docker.

**Spec:** `docs/superpowers/specs/2026-10-20-readmaster-theme-design.md`

## Global Constraints

- Theme path: `public/theme/readmaster/`, component `theme_readmaster`, `$plugin->requires = 2026100200`
- Never invent academy facts. Unknown phone/address/hours stay empty and hide their UI
- Accent `#F3A92D` is never used as text color on light backgrounds
- Code comments in English. UI strings in `lang/ko` and `lang/en`
- Tests run inside the container: `docker exec -u www-data readmaster-moodle-web vendor/bin/phpunit --filter <name>` (theme tests only)
- Conventional Commits, one logical change per commit

---

### Task 1: Plugin skeleton + brand profile

**Files:** `version.php`, `config.php`, `lib.php` (stub), `lang/en/theme_readmaster.php`, `lang/ko/theme_readmaster.php`, `brand/profile.json`, `brand/profile.template.json`, `classes/local/brand.php`, `tests/local/brand_test.php`, `pix/logo.png`, `pix/favicon.png`

**Interfaces (produces):**
- `\theme_readmaster\local\brand::load(?string $path = null): brand` — throws `\coding_exception` naming the missing/invalid key
- `->get(string $key): mixed` — dot path (`colors.primary`, `links.consult`); admin setting `theme_readmaster/<key with . → _>` wins when it is a non-empty string
- `->programs(): array` of `['title' => string, 'description' => string]`
- `->contact(): array` of `['label' => string, 'value' => string]`, only non-empty fields

**Tests (brand_test):**
1. Valid profile loads; `get('name')` returns the profile name
2. Missing `colors.primary` → `coding_exception` whose message contains `colors.primary`
3. Color not matching `/^#[0-9a-f]{6}$/i` → `coding_exception`
4. Admin setting `links_consult = 'https://x.test'` overrides profile value
5. `contact()` skips empty phone

### Task 2: Blog feed parsing, cache, scheduled task

**Files:** `classes/local/blog_feed.php`, `classes/task/refresh_blog_feed.php`, `db/tasks.php`, `tests/local/blog_feed_test.php`, `tests/fixtures/rss.xml`

**Interfaces (produces):**
- `blog_feed::parse(string $xml, int $limit = 3): array` of `['title','url','date' (int ts),'image' (?string)]`; throws `\moodle_exception('invalidxml')` on bad XML
- `blog_feed::store(array $items): void`, `blog_feed::cached(): array` (empty array when nothing cached)
- `refresh_blog_feed::execute()` — downloads `brand->get('links.rss')`, on any failure keeps cache and `mtrace`s

**Tests:** parse fixture → 3 items in order with title/url/date/image; invalid XML throws; store→cached round trip; task with unreachable URL leaves existing cache intact.

### Task 3: SCSS, fonts, renderer (logo/favicon)

**Files:** `lib.php` (pre/main SCSS callbacks), `scss/_brand.scss`, `scss/_frontpage.scss`, `scss/_login.scss`, `fonts/…`, `classes/output/core_renderer.php`

- Pre-SCSS emits `$primary`, `$rm-accent`, `$rm-paper`, `$rm-wwwroot` from the brand profile
- Fonts: Pretendard Variable dynamic subset + Noto Serif KR 600/700 subsets, self-hosted
- Renderer: `get_logo_url`/`get_compact_logo_url` fall back to `pix/logo`; `favicon()` to `pix/favicon`

**Verify:** purge caches, `curl` theme CSS contains `#10301d`; navbar shows logo.

### Task 4: Public home + login panel + settings

**Files:** `layout/frontpage.php`, `templates/frontpage.mustache`, `templates/core/login_panel.mustache`, `settings.php`, `classes/output/frontpage.php` (templatable, builds context from `brand` + `blog_feed`)

**Tests:** `frontpage_test`: export hides level-test CTA when URL empty; news section absent when cache empty; contact list matches `brand->contact()`.

**Verify:** Playwright screenshots desktop 1440 + mobile 390 of `/`, `/login/index.php`, and a logged-in course page.

### Task 5: Setup script + clone CLI + playbook

**Files:** modify `setup_readmaster.php`; create `public/theme/readmaster/cli/clone.php`, `docs/academy-playbook.md`

- Setup: theme=readmaster, defaulthomepage=HOMEPAGE_MY, frontpage none, remove navy SCSS/hero/fake footer, clear old hero HTML, run feed task once
- Clone: `--name=<a-z0-9_>`; copies dir, rewrites `readmaster`→name in file contents (component strings only), resets `profile.json` from template, refuses to overwrite an existing theme
- Verify clone: run with `--name=demoacademy`, check `php -l` on all files, install via upgrade, then delete the demo theme
