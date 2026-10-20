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

/**
 * Tests for the "Courses" item in the primary navigation.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(hook_callbacks::class)]
final class navigation_test extends \advanced_testcase {
    /**
     * Build the primary navigation for a page of the given type, as an admin on the ISMOS theme.
     *
     * @param string $pagetype
     * @param string $url
     * @return \core\navigation\views\primary
     */
    private function primary(string $pagetype, string $url): \core\navigation\views\primary {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url($url);
        $PAGE->set_pagetype($pagetype);
        $PAGE->force_theme('ismos');
        if ($pagetype === 'course-index') {
            // As course/index.php does.
            $PAGE->set_primary_active_tab('home');
        }
        $primary = new \core\navigation\views\primary($PAGE);
        $primary->initialise();
        return $primary;
    }

    public function test_courses_sits_right_after_home(): void {
        $keys = $this->primary('site-index', '/')->get_children_key_list();
        $this->assertSame(['home', hook_callbacks::COURSES_KEY], array_slice($keys, 0, 2));
        $this->assertContains('myhome', $keys);
        $this->assertContains('siteadminnode', $keys);
    }

    public function test_courses_is_the_current_item_on_the_catalogue(): void {
        $primary = $this->primary('course-index', '/course/index.php');
        $this->assertTrue($primary->find(hook_callbacks::COURSES_KEY, null)->isactive);
        $this->assertFalse($primary->find('home', null)->isactive);
    }

    public function test_home_stays_current_on_the_front_page(): void {
        $primary = $this->primary('site-index', '/');
        $this->assertFalse($primary->find(hook_callbacks::COURSES_KEY, null)->isactive);
    }
}
