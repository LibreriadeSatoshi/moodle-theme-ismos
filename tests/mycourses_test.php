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
use theme_ismos\local\mycourses;

/**
 * Tests for the My courses page: the heading and the block templates that keep the block's JavaScript working.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(mycourses::class)]
final class mycourses_test extends \advanced_testcase {
    /**
     * The theme renderer for the page.
     *
     * @return \theme_ismos\output\core_renderer
     */
    private function ismos_renderer(): \theme_ismos\output\core_renderer {
        global $PAGE, $SITE;
        $this->resetAfterTest();
        $PAGE->set_course($SITE);
        $PAGE->set_url('/my/courses.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
        return $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
    }

    public function test_lead_counts_the_visible_courses_the_user_is_enrolled_in(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);
        $this->assertSame(0, mycourses::count_enrolled());
        $this->assertSame(get_string('mycoursesempty', 'theme_ismos'), mycourses::export_header()['lead']);
        $this->assertSame(get_string('herocta', 'theme_ismos'), mycourses::export_header()['browse']);

        $a = $gen->create_course();
        $gen->enrol_user($user->id, $a->id);
        $this->assertSame("You're enrolled in 1 course. Pick up where you left off.", mycourses::export_header()['lead']);

        $b = $gen->create_course();
        $hidden = $gen->create_course(['visible' => 0]);
        $suspended = $gen->create_course();
        $gen->enrol_user($user->id, $b->id);
        $gen->enrol_user($user->id, $hidden->id);
        $gen->enrol_user($user->id, $suspended->id, null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $header = mycourses::export_header();
        $this->assertSame(2, $header['count']);
        $this->assertSame("You're enrolled in 2 courses. Pick up where you left off.", $header['lead']);
        $this->assertSame('', $header['browse']);
        $this->assertSame('My courses', $header['title']);
    }

    public function test_manage_link_needs_the_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(mycourses::export_header()['canmanage']);
        $this->setAdminUser();
        $header = mycourses::export_header();
        $this->assertTrue($header['canmanage']);
        $this->assertStringEndsWith('/course/management.php', $header['manageurl']);
    }

    public function test_the_page_header_replaces_core_s_title_and_buttons(): void {
        global $PAGE;
        $out = $this->ismos_renderer();
        $this->setAdminUser();
        $PAGE->set_pagelayout('mycourses');
        $html = $out->full_header();
        $this->assertStringContainsString('class="ismos-catalogue ismos-catalogue--head"', $html);
        $this->assertStringContainsString('<h1>My courses</h1>', $html);
        $this->assertStringContainsString('ismos-catalogue__manage', $html);
        $this->assertStringNotContainsString('Create course', $html);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertStringNotContainsString('ismos-catalogue__manage', $out->full_header());
    }

    public function test_grouping_tabs_keep_the_blocks_data_attributes(): void {
        $out = $this->ismos_renderer();
        $html = $out->render_from_template('block_myoverview/nav-grouping-selector', [
            'uniqid' => 'u1',
            'displaygroupingselector' => true,
            'displaygroupingall' => true,
            'displaygroupinginprogress' => true,
            'displaygroupingfuture' => true,
            'displaygroupingpast' => false,
            'displaygroupingfavourites' => true,
            'displaygroupinghidden' => true,
            'inprogress' => true,
        ]);
        // The container is what the page script uses to keep aria-current; the links are what the block's script reads.
        $this->assertStringContainsString('class="ismos-catalogue__tabs ismos-mc__tabs"', $html);
        $this->assertStringContainsString('data-show-active-item', $html);
        foreach (['all', 'inprogress', 'future', 'favourites', 'hidden'] as $value) {
            $this->assertStringContainsString('data-filter="grouping" data-value="' . $value . '" data-pref="' . $value . '"', $html);
        }
        $this->assertSame(5, substr_count($html, 'class="dropdown-item'));
        $this->assertSame(5, substr_count($html, 'aria-controls="courses-view-u1"'));
        // A grouping the admin turned off is not shown; the current one is marked; "removed from view" is the quiet one.
        $this->assertStringNotContainsString('data-value="past"', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="true"'));
        $this->assertMatchesRegularExpression('/data-value="inprogress"[^>]*aria-current="true"/', $html);
        $this->assertMatchesRegularExpression('/class="dropdown-item ismos-mc__quiet" href="#" data-filter="grouping" data-value="hidden"/', $html);
        $this->assertStringNotContainsString('dropdown-toggle', $html);
    }

    public function test_sort_and_view_dropdowns_keep_their_actions(): void {
        $out = $this->ismos_renderer();
        $sort = $out->render_from_template('block_myoverview/nav-sort-selector', ['uniqid' => 'u1', 'title' => true]);
        $this->assertStringContainsString('id="sortingdropdown"', $sort);
        $this->assertStringContainsString('class="btn dropdown-toggle ismos-mc__drop"', $sort);
        $this->assertStringContainsString('data-filter="sort" data-pref="lastaccessed"', $sort);
        $this->assertStringContainsString('data-active-item-text', $sort);
        $view = $out->render_from_template('block_myoverview/nav-display-selector', [
            'uniqid' => 'u1', 'card' => true,
            'layouts' => [['id' => 'card', 'name' => 'Card', 'arialabel' => 'Card', 'active' => true], ['id' => 'list', 'name' => 'List', 'arialabel' => 'List']],
        ]);
        $this->assertStringContainsString('id="displaydropdown"', $view);
        $this->assertStringContainsString('data-display-option="display" data-value="list" data-pref="list"', $view);
        $this->assertStringContainsString('View:', $view);
    }

    public function test_main_template_puts_every_selector_inside_the_filter_region(): void {
        $out = $this->ismos_renderer();
        $html = $out->render_from_template('block_myoverview/main', [
            'uniqid' => 'u1', 'displaydropdown' => true,
            'displaygroupingselector' => true, 'displaygroupingall' => true, 'all' => true,
            'layouts' => [['id' => 'card', 'name' => 'Card', 'arialabel' => 'Card', 'active' => true]],
            'title' => true, 'card' => true,
        ]);
        $start = strpos($html, 'data-region="filter"');
        $this->assertNotFalse($start);
        $end = strpos($html, 'data-region="courses-view"');
        $filter = substr($html, $start, $end - $start);
        foreach (['data-filter="grouping"', 'data-action="search"', 'id="sortingdropdown"', 'id="displaydropdown"'] as $needle) {
            $this->assertStringContainsString($needle, $filter, $needle);
        }
        $this->assertStringContainsString('role="search" data-region="filter"', $html);
        $this->assertStringContainsString('data-region="myoverview"', $html);
    }

    public function test_strings_exist_in_every_language(): void {
        foreach (['en', 'es', 'pt_br'] as $lang) {
            $string = [];
            include(__DIR__ . '/../lang/' . $lang . '/theme_ismos.php');
            foreach (['mycourseslead', 'mycourseslead1', 'mycoursesempty', 'mcview'] as $key) {
                $this->assertArrayHasKey($key, $string, "$lang $key");
            }
        }
    }
}
