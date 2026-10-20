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
use theme_ismos\output\frontpage;

/**
 * Tests for the ISMOS front page data.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(frontpage::class)]
final class frontpage_test extends \advanced_testcase {
    /**
     * Export the front page data with the page set up for the theme.
     *
     * @return array
     */
    private function export(): array {
        global $PAGE;
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        $output = $PAGE->get_renderer('core');
        return (new frontpage())->export_for_template($output);
    }

    public function test_categories_only_visible_ones(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $visible = $this->getDataGenerator()->create_category(['name' => 'Bitcoin']);
        $this->getDataGenerator()->create_category(['name' => 'Secret', 'visible' => 0]);
        $data = $this->export();
        $names = array_column($data['paths'], 'name');
        $this->assertContains('Bitcoin', $names);
        $this->assertNotContains('Secret', $names);
        $this->assertTrue($data['haspaths']);
        $found = array_values(array_filter($data['paths'], fn($c) => $c['name'] === 'Bitcoin'))[0];
        $this->assertStringContainsString('categoryid=' . $visible->id, $found['url']);
    }

    public function test_no_categories_means_no_row(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        \core_course_category::get_default()->hide();
        $data = $this->export();
        $this->assertSame([], $data['paths']);
        $this->assertFalse($data['haspaths']);
    }

    public function test_signup_button_only_when_logged_out_and_signup_enabled(): void {
        global $CFG;
        $this->resetAfterTest();

        $CFG->registerauth = 'email';
        $this->setUser(0);
        $this->assertStringEndsWith('/login/signup.php', $this->export()['hero']['signupurl']);

        $this->setGuestUser();
        $this->assertNotNull($this->export()['hero']['signupurl']);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertNull($this->export()['hero']['signupurl']);

        $this->setUser(0);
        $CFG->registerauth = '';
        $this->assertNull($this->export()['hero']['signupurl']);
    }

    public function test_hero_links_to_course_index(): void {
        $this->resetAfterTest();
        $hero = $this->export()['hero'];
        $this->assertStringEndsWith('/course/index.php', $hero['ctaurl']);
        foreach (['label', 'title', 'titleem', 'lead', 'cta', 'signup'] as $key) {
            $this->assertNotSame('', $hero[$key]);
        }
    }

    public function test_testimonials_from_setting(): void {
        $this->resetAfterTest();
        set_config('testimonials', "A | Ana\nB | Joni\nC | Eva\nD | Luz", 'theme_ismos');
        $data = $this->export();
        // The landing shows the first three.
        $this->assertCount(3, $data['testimonials']);
        $this->assertSame('Eva', $data['testimonials'][2]['name']);
        $this->assertTrue($data['hastestimonials']);
        set_config('testimonials', '', 'theme_ismos');
        $data = $this->export();
        $this->assertFalse($data['hastestimonials']);
    }

    public function test_parse_testimonials(): void {
        $items = frontpage::parse_testimonials("Quote one | Ana\n\n  Quote two|Joni \nno separator here");
        $this->assertSame([
            ['quote' => 'Quote one', 'name' => 'Ana'],
            ['quote' => 'Quote two', 'name' => 'Joni'],
        ], $items);
    }

    public function test_parse_testimonials_splits_on_last_separator(): void {
        $items = frontpage::parse_testimonials('Use a | b to pipe | Ana, from Chile');
        $this->assertSame([['quote' => 'Use a | b to pipe', 'name' => 'Ana, from Chile']], $items);
    }

    public function test_default_testimonials_setting_is_parsed_verbatim(): void {
        global $CFG;
        require_once($CFG->dirroot . '/theme/ismos/lib.php');
        $items = frontpage::parse_testimonials(theme_ismos_default_testimonials());
        $this->assertCount(6, $items);
        $this->assertSame('Yuri Delgado, from Cuba', $items[0]['name']);
        $this->assertSame('anónimo, from the global south', $items[5]['name']);
    }
}
