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
        $this->assertMatchesRegularExpression('/button\.btn\[data-action="theme_ismos\/toggle-darkmode"\]\{[^}]*background-color:(?:transparent|#fff0);[^}]*color:var\(--ismos-paper\)/', $flat);
        foreach (['#frontpage-available-course-list', '#frontpage-course-list', '.course-search-result'] as $scope) {
            $this->assertMatchesRegularExpression('/' . preg_quote($scope, '/') . '\.ismos-coursebox\.coursebox[^{]*\{[^}]*border:0;[^}]*border-radius:0;[^}]*margin:0;[^}]*padding:0/', $flat);
        }
        $this->assertMatchesRegularExpression('/\.ismos-mode-auto#region-main[^{]*\{[^}]*background-color:var\(--ismos-surface\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\.list-group-item\.active\{[^}]*border-inline-start:2pxsolidvar\(--ismos-bar\)[^}]*font-weight:600/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-mode-dark\{[^}]*--bs-body-bg-rgb:var\(--ismos-surface-rgb\)/', $flat);
        $this->assertStringContainsString('html:has(body.ismos-mode-dark){color-scheme:dark', $flat);
        $this->assertMatchesRegularExpression('/\.alert-success\{[^}]*color:var\(--ismos-success-text\)/', $flat);
        $this->assertMatchesRegularExpression('/\.btn\.btn:focus-visible\{[^}]*outline:2pxsolidvar\(--ismos-focus\)/', $flat);
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
}
