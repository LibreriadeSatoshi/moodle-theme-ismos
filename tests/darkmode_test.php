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
 * Tests for Hacker mode (dark mode) body classes and preference.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class darkmode_test extends \advanced_testcase {
    /**
     * Run page_init and return the body classes.
     *
     * @return string
     */
    private function body_classes(): string {
        global $CFG;
        require_once($CFG->dirroot . '/theme/ismos/lib.php');
        $page = new \moodle_page();
        \theme_ismos_page_init($page);
        return $page->bodyclasses;
    }

    /**
     * Assert exactly one mode class is present.
     *
     * @param string $expected Mode suffix: light, dark or auto.
     */
    private function assert_mode(string $expected): void {
        $classes = $this->body_classes();
        foreach (['light', 'dark', 'auto'] as $mode) {
            if ($mode === $expected) {
                $this->assertStringContainsString("ismos-mode-$mode", $classes);
            } else {
                $this->assertStringNotContainsString("ismos-mode-$mode", $classes);
            }
        }
    }

    /**
     * Logged out visitors follow the device.
     *
     * @covers ::theme_ismos_page_init
     */
    public function test_logged_out_is_auto(): void {
        $this->resetAfterTest();
        $this->setUser(0);
        $this->assert_mode('auto');
    }

    /**
     * Guests follow the device.
     *
     * @covers ::theme_ismos_page_init
     */
    public function test_guest_is_auto(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $this->assert_mode('auto');
    }

    /**
     * No stored preference follows the device.
     *
     * @covers ::theme_ismos_page_init
     */
    public function test_logged_in_without_preference_is_auto(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        unset_user_preference('darkmode-on');
        $this->assert_mode('auto');
    }

    /**
     * Preference 1 is Hacker mode.
     *
     * @covers ::theme_ismos_page_init
     */
    public function test_preference_one_is_dark(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('darkmode-on', 1);
        $this->assert_mode('dark');
    }

    /**
     * Preference 0 is light, and differs from absent.
     *
     * @covers ::theme_ismos_page_init
     */
    public function test_preference_zero_is_light(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_user_preference('darkmode-on', 0);
        $this->assert_mode('light');
    }

    /**
     * The preference is registered as a boolean.
     *
     * @covers ::theme_ismos_user_preferences
     */
    public function test_preference_is_registered(): void {
        $this->resetAfterTest();
        $definition = \core_user::get_preference_definition('darkmode-on');
        $this->assertSame(PARAM_BOOL, $definition['type']);
    }
}
