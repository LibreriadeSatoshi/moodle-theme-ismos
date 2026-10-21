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
        foreach (['loginline', 'signupline', 'signuplead', 'signupheading', 'loginwithemail', 'logintitle', 'continuewith'] as $key) {
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
     * The signup form keeps no core heading ("New account"): the layout draws "Create your account" above it.
     */
    public function test_signup_heading_override(): void {
        $this->resetAfterTest();
        $html = $this->render('core/signup_form_layout', ['formhtml' => '<p>form</p>']);
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
        $output = new class ($PAGE, RENDERER_TARGET_GENERAL) extends \theme_ismos\output\core_renderer {
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
     * Core's main_content already provides the main landmark: the layout adds no second one.
     */
    public function test_login_layout_has_no_main_element(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setup_page();
        $html = $this->render('theme_ismos/login', [
            'output' => $PAGE->get_renderer('core'),
            'bodyattributes' => '',
            'line' => 'x',
            'wordmark' => 'ISMOS',
            'address' => 'ISMOS.XYZ',
        ]);
        $this->assertStringNotContainsString('<main', $html);
        $this->assertStringContainsString('ismos-login__side', $html);
    }

    /**
     * The "or" divider is plain text, not a separator role (which would hide the word).
     */
    public function test_or_divider_has_no_separator_role(): void {
        $this->resetAfterTest();
        $html = $this->render('core/loginform', [
            'sitename' => 'ISMOS',
            'showloginform' => true,
            'hasidentityproviders' => true,
            'identityproviders' => [['url' => '#', 'name' => 'Provider', 'label' => 'Continue with Provider', 'primary' => true]],
        ]);
        $this->assertStringContainsString('login-divider-or', $html);
        $this->assertStringContainsString('or with your email', $html);
        // Buttons first, then the divider, then the email form.
        $this->assertLessThan(strpos($html, 'login-divider-or'), strpos($html, 'Continue with Provider'));
        $this->assertLessThan(strpos($html, 'id="login"'), strpos($html, 'login-divider-or'));
        $this->assertStringNotContainsString('role="separator"', $html);
    }

    /**
     * The block content's sticky height and overflow rules exist only in the desktop media block, so the small
     * screen header is never clipped.
     */
    public function test_block_height_rules_are_desktop_only(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $flat = preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content());

        // Outside any media block: the cell has no height or overflow limits, and the inner part is not sticky.
        $outside = preg_replace('/@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', '', $flat);
        $base = preg_match_all('/(?<![\w-])\.ismos-login__block(?:-inner)?\{[^}]*\}/', $outside, $m) ? implode('', $m[0]) : '';
        $this->assertStringNotContainsString('max-block-size', $base);
        $this->assertStringNotContainsString('overflow-y', $base);
        $this->assertStringNotContainsString('position:sticky', $base);
        $this->assertStringNotContainsString('block-size:100', $base);

        $this->assertMatchesRegularExpression(
            '/@media\(min-width:768px\)\{[^@]*\.ismos-login__block-inner\{[^}]*block-size:100dvh[^}]*overflow-y:auto[^}]*position:sticky/',
            $flat
        );
    }

    /**
     * The core site logo is not rendered above the login form, even when one is configured.
     */
    public function test_login_form_does_not_show_the_core_logo(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/login/index.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
        set_config('logo', '/old-brand.png', 'core_admin');
        $output = $PAGE->get_renderer('core');
        $logourl = 'https://example.com/pluginfile.php/1/core_admin/logo/old-brand.png';
        $html = $output->render_from_template('core/loginform', [
            'logourl' => $logourl,
            'sitename' => 'Test site',
            'logintoken' => 'x',
            'username' => '',
            'loginurl' => '/login/index.php',
        ]);
        $this->assertStringContainsString('class="loginform"', $html);
        $this->assertStringNotContainsString($logourl, $html);
        $this->assertStringNotContainsString('old-brand', $html);
        $this->assertStringNotContainsString('id="logoimage"', $html);
        $this->assertSame(1, substr_count($html, '<h1 class="login-heading">'));
    }

    /**
     * Show/hide password: the layout's script wraps every password field by moving it (so what was typed or
     * autofilled survives), and the login form no longer asks core/togglesensitive, which re-renders the field.
     */
    public function test_password_toggle_keeps_typed_value(): void {
        $layout = file_get_contents(__DIR__ . '/../templates/login.mustache');
        $this->assertStringContainsString("wrapper.appendChild(input)", $layout);
        $this->assertStringNotContainsString('outerHTML', $layout);
        $this->assertStringContainsString('showpassword, theme_ismos', $layout);
        $this->assertStringNotContainsString('core/togglesensitive', file_get_contents(__DIR__ . '/../templates/core/loginform.mustache'));
    }
}
