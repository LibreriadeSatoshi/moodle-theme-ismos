<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_ismos;

use PHPUnit\Framework\Attributes\CoversMethod;

/**
 * Tests for the ISMOS renderer: favicon, mark URLs, icon map and the navbar and footer templates.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversMethod(\theme_ismos\output\core_renderer::class, 'favicon')]
#[CoversMethod(\theme_ismos\output\core_renderer::class, 'get_ismos_mark_url')]
#[CoversMethod(\theme_ismos\output\core_renderer::class, 'standard_head_html')]
#[CoversMethod(\theme_ismos\output\icon_system_fontawesome::class, 'get_icon_name_map')]
#[CoversMethod(\theme_ismos\output\core_renderer::class, 'ismos_darkmode_toggle')]
final class core_renderer_test extends \advanced_testcase {
    /**
     * Switch to the theme and return its core renderer.
     *
     * @return \theme_ismos\output\core_renderer
     */
    private function ismos_renderer(): \theme_ismos\output\core_renderer {
        global $PAGE, $SITE;
        $this->resetAfterTest();
        $PAGE->set_course($SITE);
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
        return $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
    }

    /**
     * The dashboard welcome line loses core's emoji and exclamation mark.
     */
    public function test_welcome_message_has_no_emoji_or_exclamation(): void {
        $renderer = $this->ismos_renderer();
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Sofia']);
        $this->setUser($user);

        $first = new \lang_string('welcometosite', 'core', (object) ['firstname' => 'Sofia']);
        $this->assertSame('Welcome, Sofia.', $renderer->ismos_welcome_message($first));
        $back = new \lang_string('welcomeback', 'core', (object) ['firstname' => 'Sofia']);
        $this->assertSame('Welcome back, Sofia.', $renderer->ismos_welcome_message($back));

        // The Spanish pack is not installed in tests, so read the file.
        $string = [];
        include(__DIR__ . '/../lang/es/theme_ismos.php');
        $this->assertSame('Hola de nuevo, {$a}.', $string['welcomeback']);
        $this->assertSame('Te damos la bienvenida, {$a}.', $string['welcome']);
    }

    /**
     * The welcome line is escaped when rendered through core/full_header, on both first visit and return.
     */
    public function test_welcome_message_is_escaped_in_full_header(): void {
        $renderer = $this->ismos_renderer();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        // Core takes the name from $USER, as the renderer does.
        $GLOBALS['USER']->firstname = '<b>x</b>';

        foreach (['welcometosite' => 'Welcome, ', 'welcomeback' => 'Welcome back, '] as $key => $prefix) {
            $context = (object) [
                'welcomemessage' => new \lang_string($key, 'core', (object) ['firstname' => '<b>x</b>']),
                'hasnavbar' => false,
            ];
            $html = $renderer->render_from_template('core/full_header', $context);
            $this->assertStringContainsString($prefix . '&lt;b&gt;x&lt;/b&gt;.', $html);
            $this->assertStringNotContainsString('<b>x</b>', $html);
        }
    }

    /**
     * The favicon is the theme's own SVG.
     */
    public function test_favicon_comes_from_theme(): void {
        $out = $this->ismos_renderer();
        $url = $out->favicon()->out(false);
        $this->assertStringContainsString('/theme/ismos/', $url);
        $this->assertStringEndsWith('favicon.svg', $url);
    }

    /**
     * Paper and ink variants are different files.
     */
    public function test_mark_url_variants(): void {
        $out = $this->ismos_renderer();
        $paper = $out->get_ismos_mark_url()->out(false);
        $ink = $out->get_ismos_mark_url('ink')->out(false);
        $this->assertNotEquals($paper, $ink);
        $this->assertStringEndsWith('ismos-mark-paper.svg', $paper);
        $this->assertStringEndsWith('ismos-mark-ink.svg', $ink);
        $this->assertStringEndsWith('ismos-mark-paper.svg', $out->get_ismos_mark_url('bogus')->out(false));
    }

    /**
     * The head carries the apple touch icon.
     */
    public function test_head_has_touch_icon(): void {
        $out = $this->ismos_renderer();
        $html = $out->standard_head_html();
        $this->assertStringContainsString('rel="apple-touch-icon"', $html);
        $this->assertStringContainsString('/theme/ismos/pix/apple-touch-icon.png', $html);
    }

    /**
     * The bundled image files exist.
     */
    public function test_pix_files_exist(): void {
        global $CFG;
        foreach (
            ['ismos-mark-paper.svg', 'ismos-mark-ink.svg', 'favicon.svg', 'favicon.ico', 'apple-touch-icon.png',
                'email-logo.png'] as $file
        ) {
            $this->assertFileExists("$CFG->dirroot/theme/ismos/pix/$file");
        }
    }

    /**
     * No icon of the theme maps to a banned Font Awesome glyph.
     */
    public function test_banned_icons_are_remapped(): void {
        $out = $this->ismos_renderer();
        $system = \core\output\icon_system::instance(\theme_ismos\output\icon_system_fontawesome::class);
        $map = $system->get_icon_name_map();
        $this->assertNotEmpty($map);
        $banned = ['graduation-cap', 'trophy', 'medal', 'crown', 'star', 'rocket', 'award'];
        foreach ($map as $key => $value) {
            foreach ($banned as $name) {
                $this->assertDoesNotMatchRegularExpression(
                    '/(?<![\w-])fa-' . $name . '(?![\w-])/',
                    $value,
                    "$key maps to banned icon $name"
                );
            }
        }
        $this->assertStringContainsString('fa-book', $map['core:i/course']);
        $this->assertStringNotContainsString('fa-star', $map['mod_forum:t/star']);
        $this->assertStringContainsString('fa-bookmark', $map['mod_forum:t/star']);
        $this->assertStringContainsString('fa-circle-dot', $map['core:i/badge']);
        $this->assertStringContainsString('fa-regular fa-bookmark', $map['core:i/star-o']);
        $this->assertEquals('\theme_ismos\output\icon_system_fontawesome', $out->get_page()->theme->get_icon_system());
    }

    /**
     * Guests and logged in people get the right toggle state; the logged out navbar has a language menu slot.
     */
    public function test_darkmode_toggle_context(): void {
        $out = $this->ismos_renderer();
        $html = $out->ismos_darkmode_toggle();
        $this->assertStringContainsString('aria-checked="false"', $html);
        $this->assertStringContainsString('data-persist="0"', $html);
        $this->assertStringContainsString('role="switch"', $html);
        $this->assertStringContainsString('aria-label="Dark mode"', $html);
        $this->assertStringContainsString('title="Dark mode"', $html);
        $this->assertMatchesRegularExpression('/<svg class="ismos-darkmode-icon ismos-darkmode-icon-moon" aria-hidden="true"/', $html);
        $this->assertMatchesRegularExpression('/<svg class="ismos-darkmode-icon ismos-darkmode-icon-sun" aria-hidden="true"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<\/svg>\s*Dark mode\s*<\/button>/', $html);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('darkmode-on', 1, $user);
        $html = $out->ismos_darkmode_toggle();
        $this->assertStringContainsString('aria-checked="true"', $html);
        $this->assertStringContainsString('data-persist="1"', $html);
    }

    /**
     * The footer keeps the required Moodle output and has no copyright or social links.
     */
    public function test_footer_template(): void {
        $out = $this->ismos_renderer();
        $html = $out->render_from_template('theme_boost/footer', ['output' => $out]);
        $this->assertStringContainsString('ISMOS.XYZ', $html);
        $this->assertStringContainsString('ismos-mark-paper.svg', $html);
        $this->assertStringContainsString('id="page-footer"', $html);
        $this->assertStringNotContainsString('©', $html);
    }

    /**
     * The navbar has the wordmark with an accessible name, the hidden mark and the toggle.
     */
    public function test_navbar_template(): void {
        global $CFG;
        $out = $this->ismos_renderer();
        $html = $out->render_from_template('theme_boost/navbar', [
            'output' => $out,
            'config' => (object) ['homeurl' => $CFG->wwwroot],
            'sitename' => 'ISMOS',
            'langmenu' => ['title' => 'English', 'items' => []],
        ]);
        $this->assertStringContainsString('ismos-wordmark-text">ISMOS<', $html);
        $this->assertStringContainsString('alt="" aria-hidden="true"', $html);
        $this->assertStringContainsString('ismos-mark-paper.svg', $html);
        $this->assertStringContainsString('ismos-mark-ink.svg', $html);
        // No stray template text (a broken Mustache comment leaks its example context into the bar).
        $this->assertStringNotContainsString('}}', $html);
        $this->assertStringNotContainsString('] }', $html);
        $this->assertDoesNotMatchRegularExpression('/\]\s*\}/', $html);
        $this->assertStringContainsString('data-action="theme_ismos/toggle-darkmode"', $html);
    }

    /**
     * The login form has no first-time instructions; one "New here?" line links to the signup page.
     */
    public function test_loginform_signup_line(): void {
        $out = $this->ismos_renderer();
        $context = [
            'cansignup' => true,
            'signupurl' => 'https://example.com/login/signup.php',
            'hasinstructions' => true,
            'instructions' => 'FIRSTTIME-INSTRUCTIONS',
            'hasidentityproviders' => false,
            'loginurl' => 'https://example.com/login/index.php',
        ];
        $html = $out->render_from_template('core/loginform', $context);
        $this->assertStringContainsString('New here?', $html);
        $this->assertStringContainsString('href="https://example.com/login/signup.php">Create an account</a>', $html);
        $this->assertStringNotContainsString('FIRSTTIME-INSTRUCTIONS', $html);
        $this->assertStringNotContainsString('login-instructions', $html);

        $context['cansignup'] = false;
        $html = $out->render_from_template('core/loginform', $context);
        $this->assertStringNotContainsString('New here?', $html);
    }

    /**
     * The signup page has an "Already have an account? Log in" link.
     */
    public function test_signup_layout_login_link(): void {
        global $CFG;
        $out = $this->ismos_renderer();
        $html = $out->render_from_template('core/signup_form_layout', ['formhtml' => '<form></form>']);
        $this->assertStringContainsString('Already have an account?', $html);
        $this->assertStringContainsString($CFG->wwwroot . '/login/index.php">Log in</a>', $html);
    }

    /**
     * The maintenance layout shows the mark through direct static URLs and the message area.
     */
    public function test_maintenance_template(): void {
        global $CFG;
        $out = $this->ismos_renderer();
        $html = $out->render_from_template('theme_ismos/maintenance', [
            'output' => $out,
            'markink' => $CFG->wwwroot . '/theme/ismos/pix/ismos-mark-ink.svg',
            'markpaper' => $CFG->wwwroot . '/theme/ismos/pix/ismos-mark-paper.svg',
            'homeurl' => $CFG->wwwroot . '/',
            'wordmark' => 'ISMOS',
        ]);
        $this->assertStringContainsString('ismos-maintenance__bar', $html);
        $this->assertStringContainsString($CFG->wwwroot . '/theme/ismos/pix/ismos-mark-ink.svg', $html);
        $this->assertStringContainsString('ismos-wordmark-text">ISMOS<', $html);
        $this->assertStringNotContainsString('image.php', $html);
    }

    /**
     * The directory of the fake language pack; skips the test if a real one is already there.
     *
     * @return string
     */
    private function fake_language_dir(): string {
        global $CFG;
        $dir = $CFG->dataroot . '/lang/es';
        if (file_exists($dir)) {
            $this->markTestSkipped('A language pack already exists in the data directory; not overwriting it.');
        }
        return $dir;
    }

    /**
     * Install a fake second language pack in the data directory and reset the string manager.
     *
     * @param string $dir from fake_language_dir()
     */
    private function install_fake_language(string $dir): void {
        check_dir_exists($dir);
        file_put_contents(
            $dir . '/langconfig.php',
            "<?php\n\$string['thislanguage'] = 'Español';\n\$string['thislanguageint'] = 'Spanish';\n"
        );
        purge_all_caches();
        get_string_manager(true);
    }

    /**
     * The language menu is built for logged-in visitors too, and is empty with a single language.
     */
    public function test_lang_menu_for_logged_in_users(): void {
        global $CFG;
        $this->resetAfterTest(true);
        $out = $this->ismos_renderer();
        $this->setUser($this->getDataGenerator()->create_user());
        $CFG->langmenu = 1;

        // Controlled single language: the language list is restricted to English.
        $CFG->langlist = 'en';
        purge_all_caches();
        get_string_manager(true);
        $this->assertSame('', $out->ismos_lang_menu());

        $dir = $this->fake_language_dir();
        try {
            $this->install_fake_language($dir);
            $CFG->langlist = '';
            purge_all_caches();
            get_string_manager(true);
            $html = $out->ismos_lang_menu();
            $this->assertStringContainsString('class="langmenu"', $html);
            $this->assertStringContainsString('Español', $html);
            $this->assertStringContainsString('<span class="langbutton">EN</span>', $html);
            $this->assertStringContainsString('aria-label="Language: EN, English', $html);
            $this->assertStringNotContainsString('}}', $html);

            $navbar = $out->render_from_template('theme_boost/navbar', [
                'output' => $out,
                'config' => (object) ['homeurl' => $CFG->wwwroot],
                'sitename' => 'ISMOS',
            ]);
            $this->assertStringContainsString('class="langmenu"', $navbar);
            $this->assertStringNotContainsString('}}', $navbar);
            $this->assertDoesNotMatchRegularExpression('/\]\s*\}/', $navbar);
        } finally {
            remove_dir($dir);
            purge_all_caches();
            get_string_manager(true);
        }
    }

    /**
     * Turning the language menu off on the site wins over installed packs.
     */
    public function test_lang_menu_off_returns_nothing(): void {
        global $CFG;
        $this->resetAfterTest(true);
        $out = $this->ismos_renderer();
        $dir = $this->fake_language_dir();
        try {
            $this->install_fake_language($dir);
            $CFG->langlist = '';
            $CFG->langmenu = 0;
            purge_all_caches();
            get_string_manager(true);
            $this->assertSame('', $out->ismos_lang_menu());
        } finally {
            remove_dir($dir);
            purge_all_caches();
            get_string_manager(true);
        }
    }
}
