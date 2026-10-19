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

/**
 * Tests for the compiled ISMOS stylesheet.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scss_test extends \advanced_testcase {
    /**
     * The compiled CSS carries the tokens and self-hosted fonts, and none of the forbidden things.
     *
     * @covers ::theme_ismos_get_pre_scss
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
}
