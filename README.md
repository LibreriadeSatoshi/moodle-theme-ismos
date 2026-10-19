# theme_ismos

ISMOS theme for Moodle 5.1: a Boost child theme (`parents = ['boost']`) with the ISMOS
design language (quiet, high contrast, no card shadows, self-hosted Literata, IBM Plex
Sans and IBM Plex Mono).

## Where the tokens come from

Colour, type and spacing tokens come from the ISMOS design artifact (`03 Specification`,
`03.1-states.md` themes table, `03.2-hacker-mode.md` roles). They are encoded as SCSS
variables in `scss/` (added in later tasks) and prepended through
`theme_ismos_get_pre_scss()`. Fonts are woff2 files served from `fonts/` (no Google Fonts).
All visible text lives in `lang/en/theme_ismos.php` and `lang/es/theme_ismos.php`.

## Running the tests

Moodle runs only in Docker (no PHP on the host). One-off setup from the worktree root:

```sh
docker start moodle-github-db
docker run -d --name moodle-ismos-theme-php --network moodle-github-test \
  -v "$PWD":/var/www/html -v moodle-github-vendor:/var/www/html/vendor \
  moodlehq/moodle-php-apache:8.3 sleep infinity
```

Copy a working `config.php` to the worktree root (gitignored) and set
`$CFG->phpunit_prefix = 'ismos_'` and `$CFG->phpunit_dataroot = '/tmp/phpunitdata-ismos'`
so other checkouts' PHPUnit tables are never touched. Then:

```sh
docker exec moodle-ismos-theme-php mkdir -p /tmp/moodledata /tmp/phpunitdata-ismos
docker exec -w /var/www/html moodle-ismos-theme-php php public/admin/tool/phpunit/cli/init.php
docker exec -w /var/www/html moodle-ismos-theme-php vendor/bin/phpunit --testsuite theme_ismos_testsuite
```

Re-run `init.php` after adding or changing plugins (version or file list) when PHPUnit
reports the environment was initialised for a different version.

To install the theme into the site database (CLI lives in `admin/cli`, outside `public/`):

```sh
docker exec -w /var/www/html moodle-ismos-theme-php php admin/cli/upgrade.php --non-interactive
```
