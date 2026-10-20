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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use theme_ismos\output\login;

/**
 * Tests for the ISMOS login and signup layout.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(login::class)]
#[CoversFunction('theme_ismos_get_main_scss_content')]
final class login_layout_test extends \advanced_testcase {
    /**
     * The login layout is the theme's own file and template.
     */
    public function test_login_layout_is_theme_layout(): void {
        $this->resetAfterTest();
        $theme = \theme_config::load('ismos');
        $this->assertSame('login.php', $theme->layouts['login']['file']);
        $this->assertFileExists(__DIR__ . '/../layout/login.php');
        $this->assertFileExists(__DIR__ . '/../templates/login.mustache');
        $this->assertSame([], $theme->layouts['login']['regions']);
    }

    /**
     * Data provider: page type and the expected line and lead.
     *
     * @return array
     */
    public static function pagetype_provider(): array {
        return [
            'login' => ['login-index', 'Welcome back. Pick up where you left off.', null],
            'forgot password' => ['login-forgot_password', 'Welcome back. Pick up where you left off.', null],
            'signup' => [
                'login-signup',
                'Free, in your own language.',
                'Create your account in a minute. You don\'t need to know anything yet.',
            ],
        ];
    }

    /**
     * The block line (and lead, for signup) depends on the page type.
     *
     * @param string $pagetype
     * @param string $line
     * @param string|null $lead
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pagetype_provider')]
    public function test_line_per_pagetype(string $pagetype, string $line, ?string $lead): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/login/index.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_pagetype($pagetype);
        $data = (new login())->export_for_template($PAGE->get_renderer('core'));
        $this->assertSame($line, $data['line']);
        if ($lead === null) {
            $this->assertFalse($data['haslead']);
        } else {
            $this->assertTrue($data['haslead']);
            $this->assertSame($lead, $data['lead']);
        }
        $this->assertSame('ISMOS.XYZ', $data['address']);
    }

    /**
     * Spanish drafts exist for the new strings.
     */
    public function test_spanish_strings_exist(): void {
        $string = [];
        require(__DIR__ . '/../lang/es/theme_ismos.php');
        foreach (['loginline', 'signupline', 'signuplead', 'signupheading', 'loginor'] as $key) {
            $this->assertNotEmpty($string[$key] ?? '', $key);
        }
    }

    /**
     * Compiled CSS: select chevron in both modes, RTL counterpart, multi-selects excluded.
     */
    public function test_select_chevron_css(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $css = \theme_config::load('ismos')->get_css_content();
        $flat = strtolower(preg_replace('/\s+/', '', $css));

        $this->assertMatchesRegularExpression(
            '/select:not\(\[multiple\]\)[^{]*\{[^}]*appearance:none[^}]*padding-inline-end:44px/',
            $flat
        );
        $this->assertMatchesRegularExpression('/select:not\(\[multiple\]\)[^{]*\{[^}]*background-image:url\("data:image\/svg\+xml[^}]*%23544281/', $flat);
        $this->assertMatchesRegularExpression('/ismos-mode-dark[^{]*select:not\(\[multiple\]\)[^{]*\{[^}]*background-image:url\("data:image\/svg\+xml[^}]*%23dfd8fb/', $flat);
        $this->assertMatchesRegularExpression('/ismos-mode-auto[^{]*select:not\(\[multiple\]\)[^{]*\{[^}]*%23dfd8fb/', $flat);
        $this->assertMatchesRegularExpression('/select:not\(\[multiple\]\)[^{]*\{[^}]*background-position:right12pxcenter/', $flat);
        $this->assertMatchesRegularExpression(
            '/\.dir-rtlselect:not\(\[multiple\]\)[^{]*\{[^}]*background-position:left12pxcenter/',
            $flat
        );

        // The right-to-left build keeps the chevron on the inline end.
        $theme = \theme_config::load('ismos');
        $theme->set_rtl_mode(true);
        $rtl = strtolower(preg_replace('/\s+/', '', $theme->get_css_content()));
        $this->assertMatchesRegularExpression(
            '/\.dir-rtlselect:not\(\[multiple\]\)[^{]*\{[^}]*background-position:left12pxcenter/',
            $rtl
        );
        $this->assertDoesNotMatchRegularExpression('/select\[multiple\][^{]*\{[^}]*background-image:url\("data:image\/svg/', $flat);
    }

    /** @var bool Whether the page was already pointed at the theme in this test. */
    private bool $pageready = false;

    /**
     * Point the page at the ismos theme, once.
     */
    private function setup_page(): void {
        global $PAGE, $SITE;
        if ($this->pageready) {
            return;
        }
        $this->pageready = true;
        $PAGE->set_course($SITE);
        $PAGE->set_url('/login/index.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
    }

    /**
     * Render a template through the ismos theme.
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    private function render(string $template, array $context): string {
        global $PAGE;
        $this->setup_page();
        return $PAGE->get_renderer('core')->render_from_template($template, $context);
    }

    /**
     * The signup heading is the theme's, not core's "New account".
     */
    public function test_signup_heading_override(): void {
        $this->resetAfterTest();
        $html = $this->render('core/signup_form_layout', ['formhtml' => '<p>form</p>']);
        $this->assertStringContainsString('Create your account', $html);
        $this->assertStringNotContainsString(get_string('newaccount'), $html);
        $this->assertStringContainsString('<p>form</p>', $html);
    }

    /**
     * The login layout keeps the standard footer output (site policies and the like) under the form.
     */
    public function test_login_layout_renders_standard_footer_output(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setup_page();
        $output = new class($PAGE, RENDERER_TARGET_GENERAL) extends \theme_ismos\output\core_renderer {
            #[\Override]
            public function standard_footer_html() {
                return '<a id="ismos-footer-marker" href="#">Policies marker</a>';
            }
        };
        $html = $this->render('theme_ismos/login', [
            'output' => $output,
            'bodyattributes' => '',
            'line' => 'x',
            'wordmark' => 'ISMOS',
            'address' => 'ISMOS.XYZ',
        ]);
        $this->assertMatchesRegularExpression(
            '/ismos-login__footer[^>]*>.*ismos-footer-marker.*<\/footer>/s',
            $html
        );
    }

    /**
     * The block's sticky height and overflow rules exist only in the desktop media block, so the small
     * screen header is never clipped.
     */
    public function test_block_height_rules_are_desktop_only(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $flat = preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content());

        // Outside any media block: no height or overflow limits on the block.
        $this->assertMatchesRegularExpression('/\.ismos-login__block\{[^}]*overflow:visible/', $flat);
        $outside = preg_replace('/@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', '', $flat);
        $base = preg_match_all('/(?<![\w-])\.ismos-login__block\{[^}]*\}/', $outside, $m) ? implode('', $m[0]) : '';
        $this->assertStringNotContainsString('max-block-size', $base);
        $this->assertStringNotContainsString('overflow-y', $base);
        $this->assertStringNotContainsString('position:sticky', $base);

        $this->assertMatchesRegularExpression(
            '/@media\(min-width:768px\)\{[^@]*\.ismos-login__block\{[^}]*max-block-size:100dvh[^}]*overflow-y:auto[^}]*position:sticky/',
            $flat
        );
    }
}
