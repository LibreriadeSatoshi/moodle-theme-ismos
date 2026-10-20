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
 * Basic tests for the ISMOS theme skeleton.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversMethod(\theme_config::class, 'load')]
#[CoversMethod(\core_string_manager::class, 'get_string')]
final class theme_test extends \advanced_testcase {
    /**
     * The theme loads and inherits from Boost.
     */
    public function test_theme_loads_with_boost_parent(): void {
        $this->resetAfterTest();
        $theme = \theme_config::load('ismos');
        $this->assertSame('ismos', $theme->name);
        $this->assertSame(['boost'], $theme->parents);
    }

    /**
     * Editor styles are inherited from Boost: the theme declares none of its own (no missing editor.scss).
     */
    public function test_editor_styles_come_from_boost(): void {
        $this->resetAfterTest();
        $this->assertFileDoesNotExist(__DIR__ . '/../scss/editor.scss');
        $theme = \theme_config::load('ismos');
        $this->assertEmpty($theme->editor_scss);
        $this->assertNotSame('', trim($theme->editor_scss_to_css()));
    }

    /**
     * Required strings exist in English and Spanish, and Spanish is translated.
     */
    public function test_strings_exist_in_english_and_spanish(): void {
        $this->resetAfterTest();
        $sm = get_string_manager();
        foreach (['pluginname', 'choosereadme'] as $key) {
            $this->assertTrue($sm->string_exists($key, 'theme_ismos'), "Missing string $key");
        }
        $en = 'The ISMOS theme: a quiet, high-contrast Boost child theme for study.';
        $this->assertSame($en, get_string('choosereadme', 'theme_ismos'));

        // Moodle only reads a plugin's non-English strings from an installed language pack, so check the
        // bundled Spanish file directly.
        $string = [];
        require(__DIR__ . '/../lang/es/theme_ismos.php');
        foreach (['pluginname', 'choosereadme'] as $key) {
            $this->assertArrayHasKey($key, $string);
            $this->assertNotSame('', $string[$key]);
            $this->assertStringNotContainsString('[[', $string[$key]);
        }
        $this->assertNotSame($en, $string['choosereadme']);
    }

    /**
     * Every bundled translation has exactly the English keys, with nothing empty, so no language falls back to
     * English for a single string.
     */
    public function test_bundled_translations_cover_every_english_string(): void {
        $string = [];
        require(__DIR__ . '/../lang/en/theme_ismos.php');
        $en = $string;
        foreach (['es', 'pt_br'] as $lang) {
            $string = [];
            require(__DIR__ . "/../lang/$lang/theme_ismos.php");
            $this->assertSame([], array_diff(array_keys($en), array_keys($string)), "Missing in $lang");
            $this->assertSame([], array_diff(array_keys($string), array_keys($en)), "Extra in $lang");
            foreach ($string as $key => $value) {
                $this->assertNotSame('', trim($value), "Empty $lang string $key");
            }
            $this->assertNotSame($en['herotitle'], $string['herotitle'], "$lang hero title is not translated");
        }
    }
}
