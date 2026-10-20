# theme_ismos

ISMOS theme for Moodle 5.1: a Boost child theme (`parents = ['boost']`) with the ISMOS
design language (quiet, high contrast, no card shadows, self-hosted Literata, IBM Plex
Sans and IBM Plex Mono).

## Where the tokens come from

Colour, type and spacing tokens come from the ISMOS design artifact (`03 Specification`,
`03.1-states.md` themes table, `03.2-hacker-mode.md` roles). They are CSS custom properties
in `scss/ismos/_tokens.scss`: light values on `:root`, Hacker mode (dark) values in a mixin applied to
`.ismos-mode-dark` and, under `prefers-color-scheme: dark`, `.ismos-mode-auto`. Components read the
tokens (`var(--ismos-...)`), so a mode switch only swaps the values. The pre-SCSS
(`theme_ismos_get_pre_scss()`) sets only the Bootstrap variables Boost bakes in at compile time
(primary, body colours, radii, font families). Fonts are woff2 files served from `fonts/` (no Google
Fonts). All visible text lives in `lang/en/theme_ismos.php` and `lang/es/theme_ismos.php`.

## Overrides

Templates in `templates/` that shadow a core or Boost template. Re-diff these after each Moodle
upgrade.

| Template | Core file it shadows | What changed and why |
| --- | --- | --- |
| `core/loginform` | `lib/templates/loginform.mustache` | The divider before the identity providers is an "or" label with hairlines (`login-divider-or`); the providers heading is visually hidden. Matches the ISMOS login design. The core site logo (`logourl`) is no longer rendered above the form, since the login layout already carries the ISMOS mark and wordmark; the visible "Log in to site" heading is always used. |
| `core/signup_form_layout` | `lib/templates/signup_form_layout.mustache` | Heading reads "Create your account" (theme string) instead of core's "New account". |
| `core/email_html` | `lib/templates/email_html.mustache` | Branded HTML email frame (table layout, inline styles, ISMOS wordmark, address footer) around `{{{body}}}`. Email clients ignore the theme CSS, so it is all inline. |
| `core_payment/gateway` | `payment/templates/gateway.mustache` | Gateway choice as an ISMOS option row (radio, icon badge, name, description) instead of Bootstrap's `bg-light border` card. Keeps the `payby` radio and `data-*` attributes the payment JS reads. |
| `enrol_fee/enrol_page` | `enrol/fee/templates/enrol_page.mustache` | Shows the cost and, when given, the gateway badge and name. Depends on the team's change in `public/enrol/fee/classes/plugin.php` (commit 76c357e) that passes the gateway data to the template. |
| `theme_boost/navbar` | `theme/boost/templates/navbar.mustache` | Ink top bar: mark and wordmark brand, language menu always visible, dark mode toggle. Same user menu, notification popovers and edit switch. |
| `theme_boost/footer` | `theme/boost/templates/footer.mustache` | Static Ink footer (mark, address, language menu, links) instead of Boost's footer popover. Keeps the footer output hooks (doc link, support links, `standard_footer_html`). |

The theme's own templates (not overrides): `login` and `frontpage` (page shells, used by
`layout/login.php` and `layout/frontpage.php`), `frontpage_main` (hero, browse row and testimonials,
rendered by `course_renderer::frontpage()` inside the main landmark), `course_card`, `course_info`
and `darkmode_toggle`.

## Running the tests

Moodle runs only in Docker (no PHP on the host). One-off setup from the worktree root:

```sh
docker start moodle-github-db
docker run -d --name moodle-ismos-theme-php --network moodle-github-test \
  -v "$PWD":/var/www/html -v moodle-github-vendor:/var/www/html/vendor \
  moodlehq/moodle-php-apache:8.3 sleep infinity
```

Copy a working `config.php` to the worktree root (gitignored). It must read the PHPUnit prefix and
data directory from the environment, so other checkouts' PHPUnit tables are never touched:

```php
$CFG->phpunit_prefix = getenv('PHPUNIT_PREFIX') ?: 'ismos_';
$CFG->phpunit_dataroot = getenv('PHPUNIT_DATAROOT') ?: '/tmp/phpunitdata-ismos';
```

Then pass the same two variables on every `docker exec`:

```sh
T="-e PHPUNIT_PREFIX=ismt6_ -e PHPUNIT_DATAROOT=/tmp/phpunitdata-ismt6"
docker exec $T moodle-ismos-theme-php mkdir -p /tmp/phpunitdata-ismt6
docker exec $T moodle-ismos-theme-php sh -c 'cd /var/www/html && php public/admin/tool/phpunit/cli/init.php'
docker exec $T moodle-ismos-theme-php sh -c 'cd /var/www/html && vendor/bin/phpunit --testsuite theme_ismos_testsuite'
```

Re-run `init.php` after bumping the theme version (PHPUnit reports the environment was initialised for
a different version) and `php public/admin/tool/phpunit/cli/util.php --buildcomponentconfigs` after adding
a test file.

Do not run `admin/cli/upgrade.php` from this container: the site DB is shared with the main checkout.

Lint: `./node_modules/.bin/stylelint "public/theme/ismos/scss/**/*.scss" --config public/theme/ismos/scss/ismos/.stylelintrc`
for the SCSS, and `phpcs --standard=moodle public/theme/ismos` (moodle-cs) for PHP.
