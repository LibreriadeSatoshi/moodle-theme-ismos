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
 * Basic tests for the ISMOS theme skeleton.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class theme_test extends \advanced_testcase {
    /**
     * The theme loads and inherits from Boost.
     *
     * @covers \theme_config::load
     */
    public function test_theme_loads_with_boost_parent(): void {
        $this->resetAfterTest();
        $theme = \theme_config::load('ismos');
        $this->assertSame('ismos', $theme->name);
        $this->assertSame(['boost'], $theme->parents);
    }

    /**
     * Required strings exist in English and Spanish.
     *
     * @covers \core_string_manager::get_string
     */
    public function test_strings_exist_in_english_and_spanish(): void {
        $this->resetAfterTest();
        foreach (['pluginname', 'choosereadme'] as $key) {
            $this->assertNotEmpty(get_string($key, 'theme_ismos'));
        }
        $this->assertNotEmpty(get_string_manager()->get_string('pluginname', 'theme_ismos', null, 'es'));
    }
}
