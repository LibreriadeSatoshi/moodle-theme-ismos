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
        foreach (['ismos-mark-paper.svg', 'ismos-mark-ink.svg', 'favicon.svg', 'favicon.ico', 'apple-touch-icon.png',
                'email-logo.png'] as $file) {
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
        $this->assertStringContainsString('data-action="theme_ismos/toggle-darkmode"', $html);
    }

    /**
     * The language menu is built for logged-in visitors too, and is empty with a single language.
     */
    public function test_lang_menu_for_logged_in_users(): void {
        global $CFG;
        $out = $this->ismos_renderer();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame('', $out->ismos_lang_menu());

        // Install a second language pack in the data directory.
        $CFG->langmenu = 1;
        purge_all_caches();
        $dir = $CFG->dataroot . '/lang/es';
        check_dir_exists($dir);
        file_put_contents($dir . '/langconfig.php', "<?php\n\$string['thislanguage'] = 'Español';\n\$string['thislanguageint'] = 'Spanish';\n");
        get_string_manager(true);
        $html = $out->ismos_lang_menu();
        $this->assertStringContainsString('class="langmenu"', $html);
        $this->assertStringContainsString('Español', $html);

        $navbar = $out->render_from_template('theme_boost/navbar', [
            'output' => $out,
            'config' => (object) ['homeurl' => $CFG->wwwroot],
            'sitename' => 'ISMOS',
        ]);
        $this->assertStringContainsString('class="langmenu"', $navbar);
    }
}
