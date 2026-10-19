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

    public function test_every_part_has_a_label_and_one_h2_and_keeps_the_skip_link(): void {
        $this->setGuestUser();
        $html = $this->render('6,4,7');
        foreach (['skipavailablecourses', 'skipcourses'] as $skip) {
            $this->assertStringContainsString('href="#' . $skip . '"', $html);
            $this->assertStringContainsString('id="' . $skip . '"', $html);
        }
        // Available courses, the category tree and the search box: three parts, three labels, three h2.
        $this->assertSame(3, substr_count($html, 'ismos-frontpage-part'));
        $this->assertSame(3, preg_match_all('/<h2[ >]/', $html));
        $this->assertSame(3, substr_count($html, 'ismos-label d-block'));
        $this->assertStringContainsString(get_string('coursesheading', 'theme_ismos'), $html);
    }

    public function test_combo_list_is_headed_with_categories(): void {
        $this->setGuestUser();
        $html = $this->render('4');
        $this->assertStringContainsString('id="frontpage-category-combo"', $html);
        $this->assertStringContainsString('>' . get_string('categories') . '</h2>', $html);
        $this->assertSame(1, preg_match_all('/<h2[ >]/', $html));
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
        $names = array_column($data['categories'], 'name');
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
