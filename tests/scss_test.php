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

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for the compiled ISMOS stylesheet.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('theme_ismos_get_pre_scss')]
#[CoversFunction('theme_ismos_get_main_scss_content')]
final class scss_test extends \advanced_testcase {
    /**
     * The light top bar, the full-bleed footer, the login block, the Nostr button, maintenance and category rules.
     */
    public function test_bar_footer_login_and_page_rules(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $css = \theme_config::load('ismos')->get_css_content();
        $flat = strtolower(preg_replace('/\s+/', '', $css));

        // Top bar: nav ground, 6 px band, hairline; Ink in light and white in Hacker mode through the tokens.
        $this->assertMatchesRegularExpression('/\.navbar\.fixed-top\.ismos-navbar\{background-color:var\(--ismos-nav-bg\);' .
            'border-block-end:1pxsolidvar\(--ismos-hairline\);border-block-start:6pxsolidvar\(--ismos-bar\)/', $flat);
        $this->assertMatchesRegularExpression('/:root\{[^}]*--ismos-nav-bg:var\(--ismos-logwood-50\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-nav-bg:var\(--ismos-ink\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-navbar\.primary-navigation\.navigation\.nav-link\.active[^{]*\{[^}]*' .
            'border:0;[^}]*font-weight:600;text-decoration:underline2px;text-decoration-color:currentcolor;text-underline-offset:8px/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-navbar\.primary-navigation\.dropdown-toggle::after\{[^}]*' .
            'border-block-end:1\.5pxsolidcurrentcolor/', $flat);
        $this->assertMatchesRegularExpression('/\.navbar\.fixed-top\.ismos-navbar\.ismos-mark-paper\{display:none/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\.navbar\.fixed-top\.ismos-navbar\.ismos-mark-paper\{display:block/', $flat);
        // The Ink-bar overrides are gone: no Paper text forced on bar controls.
        $this->assertDoesNotMatchRegularExpression('/\.navbar\.fixed-top\.ismos-navbar\{[^}]*background-color:var\(--ismos-ink\)/', $flat);

        // Footer: full-bleed band that does not depend on the padded #page.
        $this->assertMatchesRegularExpression('/#page-footer\.ismos-footer\{[^}]*box-shadow:000100vmaxvar\(--ismos-ink\),0-1px0100vmax' .
            'var\(--ismos-logwood-700\);clip-path:inset\(-1px-100vmax0\)/', $flat);

        // Login: the block cell stretches, its content is the sticky full-height part; Nostr like the other providers.
        $this->assertMatchesRegularExpression('/\.ismos-login__block-inner\{[^}]*block-size:100dvh[^}]*position:sticky/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-login__form#auth-nostr-btn\.btn\{[^}]*background:(?:transparent|#fff0);' .
            'border:1\.5pxsolidvar\(--ismos-text\);border-radius:4px/', $flat);
        $this->assertMatchesRegularExpression('/\.loginform:has\(\.login-divider-or\)\.auth-nostr-sep\{display:none/', $flat);
        $this->assertMatchesRegularExpression('/#page-login-signup\.ismos-login__form\.mform\[data-groupname="buttonar"\]#id_cancel\{display:none/', $flat);

        // Maintenance and category pages.
        $this->assertMatchesRegularExpression('/\.ismos-maintenance\.maintenance_message[^{]*\{[^}]*' .
            'background-color:var\(--ismos-info-bg\)[^}]*border-radius:4px/', $flat);
        $this->assertMatchesRegularExpression('/#region-main\.box\.generalbox\.info\{[^}]*background:none[^}]*' .
            'max-inline-size:680px/', $flat);
        $this->assertMatchesRegularExpression('/#action_bar\.tertiary-navigation\{padding-inline:0/', $flat);
    }

    /**
     * Bar states, blocks and course overview cards.
     */
    public function test_nav_states_blocks_and_course_cards(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $css = \theme_config::load('ismos')->get_css_content();
        $flat = strtolower(preg_replace('/\s+/', '', $css));

        // The more menu has no box over the hairline.
        $this->assertMatchesRegularExpression('/\.navbar\.fixed-top\.ismos-navbar\.moremenu,[^{]*\{[^}]*' .
            'background-color:(?:transparent|#fff0)!important;block-size:auto;border:0/', $flat);
        // Hover: underline in the hairline colour, no background box.
        $this->assertMatchesRegularExpression('/\.navigation\.nav-link:hover,[^{]*\{background-color:#fff0;color:var\(--ismos-text\);' .
            'text-decoration:underline2px;text-decoration-color:var\(--ismos-hairline\)/', $flat);
        // The user menu keeps a tint, no border or shadow.
        $this->assertMatchesRegularExpression('/\.usermenu\.dropdown-toggle:hover,[^{]*\{background-color:var\(--ismos-nav-hover\);' .
            'border-color:#fff0;box-shadow:none/', $flat);
        // Blocks.
        $this->assertMatchesRegularExpression('/\.block\.card\{[^}]*border-radius:4px/', $flat);
        $this->assertMatchesRegularExpression('/\.block\.card>\.card-body\{padding:24px!important/', $flat);
        $this->assertMatchesRegularExpression('/\.block\.card>\.card-body>\.d-flex:first-child\{border-block-end:1pxsolidvar\(--ismos-hairline\)/', $flat);
        $this->assertMatchesRegularExpression('/\.block\.card\.card-title\{[^}]*font-family:var\(--ismos-font-serif\)/', $flat);
        $this->assertMatchesRegularExpression('/\[data-region="filter"\]\.btn,[^{]*\{[^}]*border:1pxsolidvar\(--ismos-border-input\)/', $flat);
        // Course cards: edge, radius, no generated pattern, a bar, no box on the actions button.
        $this->assertMatchesRegularExpression('/\.course-card\.card\{[^}]*border:1pxsolidvar\(--ismos-hairline\);border-radius:4px;box-shadow:none/', $flat);
        $this->assertMatchesRegularExpression('/\.course-card\.card\.card-img-top\[style\*="data:image"\]\{display:none/', $flat);
        $this->assertMatchesRegularExpression('/\.course-card\.card\.ismos-course-card__bar\{[^}]*height:6px;width:32px/', $flat);
        $this->assertMatchesRegularExpression('/\.course-card\.card\.coursemenubtn\{[^}]*background:#fff0;border:0[^}]*min-block-size:44px;min-inline-size:44px/', $flat);
        // Secondary and tertiary tabs: no box, a hairline underline on hover, a current underline.
        $this->assertMatchesRegularExpression('/:is\(\.secondary-navigation,\.tertiary-navigation\)\.moremenu\.nav-link:hover,[^{]*\{' .
            'background-color:(?:transparent|#fff0)!important;color:var\(--ismos-text\);text-decoration:underline2px;' .
            'text-decoration-color:var\(--ismos-hairline\)/', $flat);
        $this->assertMatchesRegularExpression('/:is\(\.secondary-navigation,\.tertiary-navigation\)\.moremenu\.nav-link\.active,[^{]*\{' .
            '[^}]*font-weight:600;text-decoration:underline2px/', $flat);
        $this->assertMatchesRegularExpression('/\.nav-tabs\.nav-link:focus-visible\{outline:2pxsolidvar\(--ismos-focus\)/', $flat);
        // Tables read their Bootstrap variables from the tokens.
        $this->assertMatchesRegularExpression('/\.table,\.generaltable\{--bs-table-bg:var\(--ismos-surface\);--bs-table-color:var\(--ismos-text\);' .
            '--bs-table-border-color:var\(--ismos-hairline\);--bs-table-striped-bg:var\(--ismos-surface-raised\)/', $flat);
        $this->assertMatchesRegularExpression('/--bs-table-hover-bg:var\(--ismos-surface-raised\)/', $flat);
        $this->assertMatchesRegularExpression('/\.table-light,\.thead-lightth[^{]*\{[^}]*background-color:var\(--ismos-surface-raised\);color:var\(--ismos-text\)/', $flat);
        // Completion badges: square, hairline, readable.
        $this->assertMatchesRegularExpression('/\.activity-information\.badge,[^{]*\{background-color:(?:transparent|#fff0)!important;' .
            'border:1pxsolidvar\(--ismos-hairline\);border-radius:4px!important;color:var\(--ismos-text\)!important;[^}]*font-size:13px/', $flat);
        // Light utilities and outline buttons.
        $this->assertMatchesRegularExpression('/\.alert-light\{background-color:var\(--ismos-surface-raised\)/', $flat);
        $this->assertMatchesRegularExpression('/\.btn-light\{--bs-btn-bg:var\(--ismos-surface-raised\)/', $flat);
        $this->assertMatchesRegularExpression('/\.btn-outline-secondary\{border-color:var\(--ismos-border-input\)/', $flat);
        // Help and action icons follow the ink.
        $this->assertMatchesRegularExpression('/\.icon\.text-info,[^{]*\{color:var\(--ismos-text\)!important/', $flat);
        $this->assertMatchesRegularExpression('/\.icon\.fa:not\(\.text-danger,\.text-warning\)\{color:var\(--ismos-text-muted\)/', $flat);
        // Signup legend.
        $this->assertMatchesRegularExpression('/#page-login-signup\.ismos-login__form\.mform\.fdescription\.required\{display:none/', $flat);
    }

    /**
     * The compiled CSS carries the tokens and self-hosted fonts, and none of the forbidden things.
     */
    public function test_compiled_css_contains_tokens_and_fonts(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $css = \theme_config::load('ismos')->get_css_content();
        $flat = strtolower(preg_replace('/\s+/', '', $css));

        $this->assertStringContainsString('--ismos-mauveine:#4d01e0', $flat);
        $this->assertStringContainsString('--ismos-surface:', $flat);
        $this->assertStringContainsString('--ismos-focus:', $flat);
        $this->assertStringContainsString('font-family:"literata"', $flat);
        $this->assertStringContainsString('ibmplexsans-latin.woff2', $flat);
        $this->assertMatchesRegularExpression('/\.invalid-feedback::?before[^{}]*\{[^}]*mask:/', $flat);
        $this->assertStringContainsString('--ismos-indigo:#2a2550', $flat);
        $this->assertStringContainsString('--ismos-success-text:var(--ismos-indigo)', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-surface:var\(--ismos-ink\)/', $flat);
        $this->assertMatchesRegularExpression('/prefers-color-scheme:dark\)\{\.ismos-mode-auto\{[^}]*--ismos-focus:var\(--ismos-weld\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--bs-body-bg:var\(--ismos-surface\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark#region-main[^{]*\{[^}]*background-color:var\(--ismos-surface\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\.bg-white[^{]*\{[^}]*background-color:var\(--ismos-surface\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\.main-inner[^{]*\{[^}]*background-color:var\(--ismos-surface\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark#topofscroll[^{]*\{[^}]*background-color:var\(--ismos-surface\)/', $flat);
        $toggle = 'button\.btn\[data-action="theme_ismos\/toggle-darkmode"\]';
        $this->assertMatchesRegularExpression('/' . $toggle . '\{[^}]*background-color:(?:transparent|#fff0);' .
            '[^}]*border:0;[^}]*border-radius:4px;[^}]*color:var\(--ismos-text\)[^}]*min-block-size:44px;[^}]*min-inline-size:44px/', $flat);
        $this->assertMatchesRegularExpression('/' . $toggle . '\.ismos-darkmode-icon\{[^}]*block-size:20px[^}]*inline-size:20px[^}]*stroke:currentcolor/', $flat);
        $this->assertMatchesRegularExpression('/' . $toggle . '\.ismos-darkmode-icon-sun\{display:none/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\.navbar[^{]*' . $toggle . '\.ismos-darkmode-icon-moon\{display:none\}' .
            '[^{]*\.ismos-darkmode-icon-sun\{display:block/', $flat);
        $this->assertMatchesRegularExpression('/prefers-color-scheme:dark\)\{\.ismos-mode-auto\.navbar[^{]*' . $toggle .
            '\.ismos-darkmode-icon-moon\{display:none\}[^{]*\.ismos-darkmode-icon-sun\{display:block/', $flat);
        $this->assertMatchesRegularExpression('/' . $toggle . ':focus-visible\{[^}]*outline:2pxsolidvar\(--ismos-focus\)/', $flat);
        $this->assertDoesNotMatchRegularExpression('/' . $toggle . '\[aria-checked/', $flat);
        foreach (['#frontpage-available-course-list', '#frontpage-course-list', '.course-search-result'] as $scope) {
            $this->assertMatchesRegularExpression('/' . preg_quote($scope, '/') . '\.ismos-coursebox\.coursebox[^{]*\{[^}]*border:0;' .
                '[^}]*border-radius:0;[^}]*margin:0;[^}]*padding:0/', $flat);
        }
        $this->assertMatchesRegularExpression('/\.ismos-mode-auto#region-main[^{]*\{[^}]*background-color:var\(--ismos-surface\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\.list-group-item\.active\{[^}]*border-inline-start:2pxsolidvar\(--ismos-bar\)[^}]*font-weight:600/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--bs-body-bg-rgb:var\(--ismos-surface-rgb\)/', $flat);
        $this->assertStringContainsString('html:has(body.ismos-mode-dark){color-scheme:dark', $flat);
        $this->assertMatchesRegularExpression('/\.alert-success\{[^}]*color:var\(--ismos-success-text\)/', $flat);
        $this->assertMatchesRegularExpression('/\.btn\.btn:focus-visible\{[^}]*outline:2pxsolidvar\(--ismos-focus\)/', $flat);
        $this->assertMatchesRegularExpression('/\.btn\.btn:not\(\.btn-link\)(,[^{]*)?\{[^}]*text-decoration:none/', $flat);
        $this->assertMatchesRegularExpression('/\.btn\.btn:not\(\.btn-link\):hover[^{]*\{[^}]*text-decoration:none/', $flat);
        $this->assertMatchesRegularExpression('/\.btn\.btn-link\{[^}]*color:var\(--ismos-link\);text-decoration:underline/', $flat);
        $this->assertMatchesRegularExpression('/:where\(a:not\(\.btn\):hover\)\{[^}]*color:var\(--ismos-link-hover\)/', $flat);
        $this->assertMatchesRegularExpression('/:where\(a:not\(\.btn\)\)\{[^}]*text-decoration:underline/', $flat);
        $this->assertMatchesRegularExpression('/:where\(a:not\(\.btn\)\)\{[^}]*color:var\(--ismos-link\)/', $flat);
        $this->assertStringNotContainsString('fonts.googleapis.com', $css);

        // The compiled sheet also carries other plugins' own styles.css (some use gradients), so the
        // no-gradient rule is checked on the theme's own SCSS sources.
        $sources = glob(__DIR__ . '/../scss/{,ismos/}*.scss', GLOB_BRACE);
        $this->assertNotEmpty($sources);
        foreach ($sources as $source) {
            $this->assertStringNotContainsString('gradient(', file_get_contents($source), $source);
        }
    }

    /**
     * The Ink bar colours do not leak into menus and popovers opened from the bar.
     */
    public function test_navbar_colours_do_not_leak_into_menus(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $flat = strtolower(preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content()));

        // Menus get the overlay text colour, link colour and the normal focus ring back.
        $this->assertMatchesRegularExpression(
            '/\.ismos-navbar:is\(#usernavigation,\.primary-navigation\):is\(\.dropdown-menu,\.popover,\.popover-region-container\)\{[^}]*color:var\(--ismos-text\)/',
            $flat
        );
        $this->assertMatchesRegularExpression(
            '/\.ismos-navbar:is\(#usernavigation,\.primary-navigation\):is\(\.dropdown-menu,[^{]*\):focus-visible[^{]*\{[^}]*outline:2pxsolidvar\(--ismos-focus\)/',
            $flat
        );
        // The restore rule that names .dropdown-menu sets the overlay text colour, and links keep their underline.
        $this->assertMatchesRegularExpression('/\.dropdown-menu,[^{]*\{color:var\(--ismos-text\)\}/', $flat);
        $this->assertMatchesRegularExpression('/\.dropdown-menu,[^{]*\.popover-region-container\)a\{[^}]*text-decoration:underline/', $flat);
        $this->assertMatchesRegularExpression('/\.dropdown-menu,[^{]*\)\.btn-primary[^{]*\{color:var\(--ismos-on-action\)/', $flat);
        $this->assertMatchesRegularExpression(
            '/\.ismos-navbar#usernavigation\.popover-region-container\{[^}]*background-color:var\(--ismos-overlay-bg\)/',
            $flat
        );
        $this->assertMatchesRegularExpression(
            '/\.popover-region-container\.popover-region-footer-container[^{]*\{[^}]*background-color:var\(--ismos-overlay-bg\)/',
            $flat
        );
        // Selected row and notch use tokens, not Boost's blue and white.
        $this->assertMatchesRegularExpression(
            '/\.content-item-container\.selected\{[^}]*background-color:var\(--ismos-surface-raised\)[^}]*border-inline-start:2pxsolidvar\(--ismos-bar\)/',
            $flat
        );
        $this->assertMatchesRegularExpression('/\.popover-region-toggle::after\{[^}]*border-block-end-color:var\(--ismos-overlay-bg\)/', $flat);
        $this->assertMatchesRegularExpression('/\.a\.content-item-container|a\.content-item-container[^{]*\{[^}]*text-decoration:none/', $flat);
        // The bar-level Weld ring is not applied to menu contents without that restore.
        $this->assertStringContainsString('.ismos-navbar:focus-visible', str_replace('.navbar.fixed-top', '', $flat));
    }

    /**
     * Link hover has its own rgb token in both scopes, and Bootstrap's hover rgb reads it.
     */
    public function test_link_hover_rgb_token(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $flat = strtolower(preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content()));

        $this->assertMatchesRegularExpression('/:root\{[^}]*--ismos-link-hover-rgb:84,66,129/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-link-hover-rgb:255,255,255/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-auto\{[^}]*--ismos-link-hover-rgb:255,255,255/', $flat);
        $this->assertSame(3, substr_count($flat, '--bs-link-hover-color-rgb:var(--ismos-link-hover-rgb)'));
        $this->assertStringNotContainsString('--bs-link-hover-color-rgb:var(--ismos-text-rgb)', $flat);
    }

    /**
     * Check boxes, radios and switches: token driven, per-mode glyphs, outline buttons visible in both modes.
     */
    public function test_form_controls_and_outline_buttons(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $flat = strtolower(preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content()));

        // Light: Logwood 800 border, Ink fill when checked, Paper check mark (%23ffffff).
        $this->assertMatchesRegularExpression('/:root\{[^}]*--ismos-control-border:var\(--ismos-logwood-800\)/', $flat);
        $this->assertMatchesRegularExpression('/:root\{[^}]*--ismos-control-checked-bg:var\(--ismos-ink\)/', $flat);
        $this->assertMatchesRegularExpression('/:root\{[^}]*--ismos-check-image:url\("data:image\/svg\+xml[^"]*stroke=\'%23ffffff\'/', $flat);
        // Hacker mode: Paper border, Mauveine 200 fill, Ink check mark (%23000000), light knob off.
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-control-border:var\(--ismos-paper\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-control-checked-bg:var\(--ismos-mauveine-200\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-check-image:url\("data:image\/svg\+xml[^"]*stroke=\'%23000000\'/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-radio-image:url\([^)]*fill=\'%23000000\'/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-knob-off-image:url\([^)]*fill=\'%23ffffff\'/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-auto\{[^}]*--ismos-control-checked-bg:var\(--ismos-mauveine-200\)/', $flat);

        // The control rules read the tokens and Bootstrap's image variables.
        $this->assertMatchesRegularExpression('/\.form-check-input\{[^}]*background-color:var\(--ismos-control-bg\)[^}]*border:1\.5pxsolidvar\(--ismos-control-border\)/', $flat);
        $this->assertMatchesRegularExpression('/\.form-check-input:checked[^{]*\{[^}]*background-color:var\(--ismos-control-checked-bg\)/', $flat);
        $this->assertMatchesRegularExpression('/\.form-check-input\[type="checkbox"\]:checked\{--bs-form-check-bg-image:var\(--ismos-check-image\)/', $flat);
        $this->assertMatchesRegularExpression('/\.form-check-input\[type="radio"\]:checked\{--bs-form-check-bg-image:var\(--ismos-radio-image\)/', $flat);
        $this->assertMatchesRegularExpression('/\.form-switch\.form-check-input:checked\{--bs-form-switch-bg:var\(--ismos-knob-on-image\)/', $flat);
        $this->assertMatchesRegularExpression('/\.form-switch\.form-check-input\{--bs-form-switch-bg:var\(--ismos-knob-off-image\)/', $flat);

        // The edit mode switch on the bar uses the controls of the current mode and a label in the text colour.
        $this->assertDoesNotMatchRegularExpression('/\.ismos-navbar\.editmode-switch-form\{[^}]*--ismos-control-border/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-navbar\.editmode-switch-formlabel[^{]*\{color:var\(--ismos-text\)!important/', $flat);

        // Outline buttons: text colour border and label, hover inverts onto the surface.
        $this->assertMatchesRegularExpression('/\.btn-outline-primary,\.btn-outline-secondary\{[^}]*--bs-btn-color:var\(--ismos-text\)' .
            '[^}]*--bs-btn-border-color:var\(--ismos-text\)[^}]*--bs-btn-hover-color:var\(--ismos-surface\)' .
            '[^}]*--bs-btn-hover-bg:var\(--ismos-text\)/', $flat);
    }

    /**
     * Cards: only links answer to hover; the course card takes the card background token; the login
     * block's focus ring is not Mauveine on Mauveine.
     */
    public function test_card_hover_card_background_and_login_block_focus(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $flat = strtolower(preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content()));

        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])\.card:hover/', $flat);
        $this->assertMatchesRegularExpression('/a\.card:hover\{border-color:var\(--ismos-text\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-card\{background:var\(--ismos-card-bg\)[^}]*border:1pxsolidvar\(--ismos-hairline\)/', $flat);
        // Hacker mode: Logwood card ground, Logwood 700 edge.
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-card-bg:var\(--ismos-logwood\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-hairline:var\(--ismos-logwood-700\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-login__block:focus-visible\{outline-color:var\(--ismos-on-block\)/', $flat);
    }

    /**
     * Course page: section titles, boxes, availability notes, icons, drawer toggles and placeholders use tokens.
     */
    public function test_course_page_styles(): void {
        $this->resetAfterTest();
        theme_reset_all_caches();
        $flat = strtolower(preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content()));

        $this->assertMatchesRegularExpression('/\.course-section\.sectionname>a,\.course-section\.sectionname\{color:var\(--ismos-text-heading\)/', $flat);
        $this->assertMatchesRegularExpression('/\.course-section\.section-item\{border-color:var\(--ismos-hairline\);border-radius:4px/', $flat);
        $this->assertMatchesRegularExpression('/\.course-section\.activity\{border-top-color:var\(--ismos-hairline\)/', $flat);
        $this->assertMatchesRegularExpression('/\.max-section-alert,[^{]*\.activity-afterlink[^{]*\{border-color:var\(--ismos-hairline\)/', $flat);
        $this->assertMatchesRegularExpression('/\.course-content\.dividerhr(,[^{]*)?\{border-top-color:var\(--ismos-hairline\)/', $flat);
        $this->assertMatchesRegularExpression('/\.course-section\.availabilityinfo,\.availabilityinfo\.availabilityinfo\{background-color:var\(--ismos-info-bg\);[^}]*border-radius:4px/', $flat);
        $this->assertMatchesRegularExpression('/\.changenumsections\.disabled\.dividerhr[^{]*\{border-top-color:var\(--ismos-hairline\)/', $flat);
        $this->assertMatchesRegularExpression('/\.sectionli\.moveherea\{border-color:var\(--ismos-text-muted\)/', $flat);
        $this->assertMatchesRegularExpression('/\.availabilityinfoa:not\(\.btn\)\{color:var\(--ismos-link\);text-decoration:underline/', $flat);
        $this->assertMatchesRegularExpression('/\.activityiconcontainer:not\(\.isbranded\)\.activityicon:not\(\.nofilter\)[^{]*\{filter:var\(--ismos-icon-filter\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-icon-filter:brightness\(0\)invert\(1\)/', $flat);
        $this->assertStringContainsString('--ismos-icon-filter:brightness(0);', $flat);
        $this->assertMatchesRegularExpression('/\.drawer-toggles\.drawer-toggler\.btn\{[^}]*border-radius:4px/', $flat);
        $this->assertStringContainsString('--ismos-placeholder:var(--ismos-logwood-100)', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--ismos-placeholder:var\(--ismos-logwood-700\)/', $flat);
        $this->assertMatchesRegularExpression('/\.bg-pulse-grey,\.placeholder\{background-color:var\(--ismos-placeholder\)/', $flat);
    }

    /**
     * The navbar and footer take fonts and sizes from the tokens, not hard-coded stacks or rem values.
     */
    public function test_navbar_and_footer_use_tokens(): void {
        foreach (['_navbar.scss', '_footer.scss'] as $file) {
            $source = file_get_contents(__DIR__ . '/../scss/ismos/' . $file);
            $this->assertStringNotContainsString('IBM Plex', $source, $file);
            $this->assertStringNotContainsString('Bricolage Grotesque",', $source, $file);
            $this->assertDoesNotMatchRegularExpression('/font-size:\s*[\d.]+rem/', $source, $file);
        }
    }
}
