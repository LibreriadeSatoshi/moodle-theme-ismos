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
use PHPUnit\Framework\Attributes\CoversMethod;

/**
 * Tests for the front page parts rendered by the theme course renderer.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\theme_ismos\output\core\course_renderer::class)]
#[CoversMethod(\theme_ismos\output\frontpage::class, 'export_for_template')]
final class frontpage_parts_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        global $PAGE, $SITE;
        $PAGE->set_course($SITE);
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_pagetype('site-index');
        $PAGE->force_theme('ismos');
    }

    /**
     * Render the front page parts for the given layout setting.
     *
     * @param string $layout
     * @return string
     */
    private function render(string $layout): string {
        global $CFG, $PAGE;
        $CFG->frontpage = $layout;
        $CFG->frontpageloggedin = $layout;
        $this->getDataGenerator()->create_category(['name' => 'Bitcoin & LN']);
        $this->getDataGenerator()->create_course();
        return $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL)->frontpage();
    }

    public function test_course_list_and_category_tree_are_replaced_by_the_landing(): void {
        $this->setGuestUser();
        set_config('testimonials', '', 'theme_ismos');
        $html = $this->render('6,4,7');
        // The landing shows paths and a calendar instead of the course list and the category tree.
        $this->assertStringNotContainsString('frontpage-available-course-list', $html);
        $this->assertStringNotContainsString('frontpage-category-combo', $html);
        // The search box stays, as a part with a label and an h2.
        $this->assertSame(1, substr_count($html, 'ismos-frontpage-part'));
        $this->assertSame(1, substr_count($html, 'ismos-label d-block'));
        $this->assertStringContainsString('>' . get_string('searchcourses') . '</h2>', $html);
    }

    public function test_front_page_main_content_holds_the_landing_sections(): void {
        $this->setGuestUser();
        set_config('testimonials', "Great | Ana\nFine | Joni", 'theme_ismos');
        $html = $this->render('6');
        // One h1 in the hero, then facts, paths, steps, testimonials and the final band, in that order.
        $this->assertSame(1, preg_match_all('/<h1[ >]/', $html));
        $order = ['ismos-hero', 'ismos-facts', 'ismos-paths', 'ismos-steps', 'ismos-quotes', 'ismos-final'];
        $last = -1;
        foreach ($order as $class) {
            $pos = strpos($html, 'class="' . $class);
            $this->assertNotFalse($pos, $class);
            $this->assertGreaterThan($last, $pos, $class);
            $last = $pos;
        }
        $this->assertStringContainsString('Learn to run your own money,', $html);
        $this->assertStringContainsString('<em>one lesson at a time.</em>', $html);
        // Nothing is left of the old hero buttons or the old testimonials list.
        $this->assertStringNotContainsString('ismos-browse', $html);
        $this->assertStringNotContainsString('ismos-testimonials__list', $html);
    }

    public function test_front_page_layout_template_leaves_the_main_content_to_the_renderer(): void {
        $template = file_get_contents(__DIR__ . '/../templates/frontpage.mustache');
        $this->assertStringNotContainsString('ismos-hero', $template);
        $this->assertStringNotContainsString('ismos-testimonials', $template);
        $this->assertStringContainsString('output.main_content', $template);
    }

    /**
     * Give the site section 1 a summary.
     *
     * @param string $summary
     */
    private function set_section1_summary(string $summary): void {
        global $DB, $SITE;
        course_create_sections_if_missing($SITE, 1);
        $DB->set_field('course_sections', 'summary', $summary, ['course' => $SITE->id, 'section' => 1]);
        rebuild_course_cache($SITE->id, true);
    }

    public function test_section1_is_inside_the_content_column_after_the_steps(): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/course/lib.php');
        $this->setGuestUser();
        $this->set_section1_summary('<p>Site summary marker</p>');
        $html = $this->render('6');
        $this->assertSame(1, substr_count($html, 'Site summary marker'));
        $hero = strpos($html, '<h1');
        $section1 = strpos($html, 'Site summary marker');
        $this->assertNotFalse($hero);
        $this->assertLessThan($section1, $hero);
        // Inside the content column, not before it.
        $this->assertLessThan($section1, strpos($html, 'ismos-frontpage-column ismos-frontpage-courses'));
        $this->assertLessThan($section1, strpos($html, 'ismos-steps'));
        // Core's own call from index.php adds nothing on the site index.
        $renderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);
        $this->assertSame('', $renderer->frontpage_section1());
    }

    public function test_section1_is_left_alone_outside_the_site_index(): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/course/lib.php');
        $this->setGuestUser();
        $this->set_section1_summary('<p>Site summary marker</p>');
        $PAGE->set_pagetype('course-index');
        $renderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);
        $this->assertStringContainsString('Site summary marker', $renderer->frontpage_section1());
    }

    public function test_empty_section1_renders_nothing_unless_editing(): void {
        $this->setGuestUser();
        $html = $this->render('6');
        $this->assertStringNotContainsString('frontpage-section', $html);
    }

    public function test_other_pages_keep_the_plain_front_page_parts(): void {
        global $PAGE;
        $this->setGuestUser();
        $PAGE->set_pagetype('course-index');
        $html = $this->render('6');
        $this->assertStringNotContainsString('ismos-hero', $html);
    }

    public function test_empty_extra_parts_leave_no_empty_column(): void {
        $this->setGuestUser();
        $html = $this->render('6');
        $this->assertStringNotContainsString('ismos-frontpage-courses', $html);
    }

    public function test_search_form_is_plain_outside_the_front_page(): void {
        global $PAGE;
        $PAGE->set_pagetype('course-search');
        $html = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL)->course_search_form('');
        $this->assertStringNotContainsString('ismos-frontpage-part', $html);
    }

    public function test_category_names_are_not_double_escaped(): void {
        $this->setGuestUser();
        $this->getDataGenerator()->create_category(['name' => 'Bitcoin & LN']);
        $data = (new \theme_ismos\output\frontpage())->export_for_template($this->get_renderer());
        $names = array_column($data['paths'], 'name');
        $this->assertContains('Bitcoin & LN', $names);
    }

    /**
     * The page core renderer.
     *
     * @return \renderer_base
     */
    private function get_renderer(): \renderer_base {
        global $PAGE;
        return $PAGE->get_renderer('core');
    }
}
