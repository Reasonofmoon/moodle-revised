# ReadMaster Theme Design (theme_readmaster)

Date: 2026-10-20 · Status: approved

## Goal

Rebrand the Moodle 6.0 site as **리드마스터 입시학원 | 초중고 영어 전문** (부천 옥길동) and make
the result repeatable for other academies.

- Public home page for visitors (parents, prospective students)
- Ordinary Moodle LMS for logged-in users
- All academy-specific data in one brand profile, so a new academy = clone + edit profile + swap logo

## Sources

- Blog: https://blog.naver.com/readmaster2642 (RSS: https://rss.blog.naver.com/readmaster2642.xml)
- Study platform: https://study.moonlang.com (uses the same Lectio Libera logo)
- Logo: 1254×1254 PNG, "Lectio Libera" wordmark. Sampled colors: forest `#10301D`, amber `#F3A92D`

Facts not found in these sources (phone, address, hours) are **not invented**. They are admin
settings that default to empty, and an empty value hides its UI element.

## Architecture

Boost child theme at `public/theme/readmaster/` (`$THEME->parents = ['boost']`).
Anything not overridden is inherited from Boost.

| Path | Role |
|---|---|
| `brand/profile.json` | **Only academy-specific file** (plus `pix/`): names, tagline, colors, links, programs, RSS URL |
| `classes/local/brand.php` | Loads and validates the profile; merges admin setting overrides |
| `classes/local/blog_feed.php` | Parses RSS XML into items; reads/writes the cached copy |
| `classes/task/refresh_blog_feed.php` | Hourly scheduled task that downloads RSS and caches it |
| `classes/output/core_renderer.php` | Logo/compact logo/favicon fall back to theme `pix/` when no admin logo is set |
| `layout/frontpage.php` | Visitors: public home template. Logged-in users: Boost `drawers.php` |
| `templates/frontpage.mustache` | Public home page |
| `templates/core/login_panel.mustache` | Left panel of Boost's split login layout |
| `scss/` | Brand variables (pre-SCSS) and page styles |
| `fonts/` | Self-hosted Pretendard (body) and Noto Serif KR (headings) |
| `settings.php` | Admin overrides: phone, address, hours, consult/level-test/blog/platform URLs |
| `cli/clone.php` | Copies the theme to `theme_<newname>` with a blank profile |

### Visual system

| Token | Value | Use |
|---|---|---|
| primary | profile `colors.primary` (`#10301D`) | navbar, buttons, links, headings |
| accent | profile `colors.accent` (`#F3A92D`) | highlights, badges, rules. Never as text on light backgrounds |
| paper | profile `colors.paper` (`#F7F5EF`) | public home background |
| heading font | Noto Serif KR | echoes the serif wordmark |
| body font | Pretendard | Korean legibility |

Logged-in pages keep Boost layouts and change only colors and fonts.

## Public home page

1. Header: logo + academy name. Links: blog, study platform, login
2. Hero: tagline from profile. CTAs: consult booking, level test (hidden if URL empty)
3. Program cards from `profile.programs`
4. Latest news: 3 newest cached blog posts (hidden when there is no cache)
5. Footer: academy name, non-empty contact fields, channel links

Moodle requires `main_content()` in every layout, so the template renders it in a section below
the cards. Site home course lists are turned off (`frontpage = none`) for visitors.
Logged-in users are redirected to the dashboard by `defaulthomepage = HOMEPAGE_MY`.

## Data flow and failure handling

- `refresh_blog_feed` runs hourly. On success it stores the parsed items as JSON in plugin config.
  On HTTP or parse failure it keeps the previous cache and logs via `mtrace`.
- The page never calls Naver at request time.
- `brand::load()` throws `coding_exception` with the missing key if the profile is invalid,
  so a broken clone fails loudly at install time, not silently on the page.

## Setup script changes (`setup_readmaster.php`)

- Set `theme = readmaster`, `defaulthomepage = HOMEPAGE_MY`, `frontpage = none`
- Remove navy SCSS, hero HTML injection, and the fake phone footer
- Clear the old hero HTML from the site summary/section 1
- Run the blog feed task once

Demo categories/courses/users are out of scope (separate content task).

## Testing

- PHPUnit (theme only): profile validation, setting override merge, RSS parsing (fixture),
  cache fallback on parse failure
- Manual: Playwright screenshots of home (desktop + mobile), login, and a logged-in course page

## Replication workflow

1. `php public/theme/readmaster/cli/clone.php --name=<academy>`
2. Edit `public/theme/<academy>/brand/profile.json`, replace `pix/logo.png` and `pix/favicon.png`
3. Copy `setup_readmaster.php` to `setup_<academy>.php` and change the theme name
4. Install/upgrade, purge caches, run the feed task, take screenshots
