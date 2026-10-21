# New Academy Playbook (Moodle 6 + brand theme)

How to stand up a branded Moodle for another academy by cloning `theme_readmaster`.
Proven on ReadMaster (리드마스터 입시학원), 2026-10-21.

## 0. Gather brand facts (30 min)

Sources, in order of trust: the academy's own blog/site, then RSS, then the people.

| Need | Where it came from for ReadMaster |
|---|---|
| Official name, tagline, region | Naver blog RSS `<channel><description>` (`https://rss.blog.naver.com/<id>.xml`) |
| Logo | Blog profile image (`<channel><image><url>`), 1254px PNG |
| Colors | Sampled from the logo with PHP GD inside the container |
| Programs | Blog category names + post titles |
| Consult / level test / parent links | `href`s on the academy's other sites |

**Never invent** phone, address or hours. Leave them `""` in the profile; empty fields are hidden.

## 1. Clone the theme

```bash
docker exec -u www-data readmaster-moodle-web \
  php public/theme/readmaster/cli/clone.php --name=<name> --displayname="<학원 이름>"
```

`<name>` = lowercase letters/digits, e.g. `sunrise`. The clone renames the component,
resets `brand/profile.json` from the template, and copies fonts/templates unchanged.

## 2. Fill the brand

- `public/theme/<name>/brand/profile.json`: replace every `CHANGE ME`. Colors must be `#RRGGBB`.
  `primary` = dark brand color (buttons, headings). `accent` = highlight, never text on light backgrounds.
- `pix/logo.png` (full logo, used large), `pix/logomark.png` (square mark, used small),
  `pix/favicon.ico` (Moodle only looks for `.ico` under the name `favicon`; a PNG wrapped in ICO is fine).
  Crop the mark from the logo with PHP GD in the container (no Pillow on the host).

## 3. Install and switch

```bash
docker exec -u www-data readmaster-moodle-web php admin/cli/upgrade.php --non-interactive --allow-unstable
```

Copy `setup_readmaster.php` to `setup_<name>.php`, change `READMASTER_THEME` to `<name>`, and run it.
It sets the theme, `defaulthomepage = HOMEPAGE_MY`, `frontpage = none`, clears old Boost branding,
fetches the blog feed once, and resets theme caches.

## 4. Verify

1. Theme tests: `docker exec -u www-data readmaster-moodle-web vendor/bin/phpunit --testsuite theme_<name>_testsuite` (about 10 min; a directory path runs nothing)
2. Precompile CSS **before** opening a browser (see pitfalls), then screenshot `/` at 1440 and 390 wide,
   `/login/index.php`, and a course page while logged in. Check: no horizontal scroll at 390px.

## Pitfalls we hit

| Symptom | Cause | Fix |
|---|---|---|
| CSS change not visible after purge | `usefallback`: the first `styles.php` request serves old CSS and the browser caches it under the new revision URL | After purging, `curl` the `styles.php` URL from the page until it contains your rule, then open the browser |
| PHP change ignored for a minute | `opcache.revalidate_freq = 60` | Dockerfile now uses `0` |
| Login panel shows stock photo + "AI-generated image" | Moodle runs **every parent's** `extrascsscallback`; Boost's adds them | The theme's own extra SCSS resets them with the same selectors |
| Paper background stops after one screen | Boost sticky footer sets `body` and `#page-wrapper` to `height: 100%` | `height: auto` on the public home body/wrapper |
| Login inputs narrower than the button | YUI `cssbase` sets `input[type=text] { width: 12.25em }` | `.login-container .login-form .form-control { width: 100% }` |
| Naver thumbnails cropped | RSS gives `type=s3` (365px square) | Parser swaps to `type=w2` (original ratio) |
| `phpunit/cli/init.php` fails | composer self-update, then missing `en_AU.UTF-8` locale | Run with `--disable-composer`; Dockerfile now generates `en_AU` and `ko_KR` |
| Setup script recreated the level-test course | Existing rule: course with fewer than 4 modules is rebuilt | Expected for demo data; remove that rule before real use |

## Class log (local_classlog)

Attendance + lesson log lives in `public/local/classlog/` and has no academy-specific data,
so clones need no change. It installs with the same `upgrade.php --allow-unstable` run.
Teachers see 수업일지 / 출결 현황 in the course "More" menu. Spec:
`docs/superpowers/specs/2026-10-21-classlog-design.md`.

## Out of scope for the theme

Demo categories, courses and users in the setup script are still sample data, as are
`noreplyaddress`/`supportemail`. Replace them per academy as a separate content task.
