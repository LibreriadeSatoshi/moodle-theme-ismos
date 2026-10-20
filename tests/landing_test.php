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
use theme_ismos\local\landing;
use theme_ismos\output\frontpage;

/**
 * Tests for the live data of the landing page.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(landing::class)]
#[CoversClass(frontpage::class)]
final class landing_test extends \advanced_testcase {
    /** @var int One day in seconds. */
    private const DAY = 86400;

    /**
     * Create a course starting the given number of days from now.
     *
     * @param string $name
     * @param \stdClass|int|null $category
     * @param int $days
     * @param array $extra more course fields
     * @return \stdClass
     */
    private function course(string $name, $category, int $days, array $extra = []): \stdClass {
        $record = ['fullname' => $name, 'startdate' => time() + $days * self::DAY] + $extra;
        if ($category !== null) {
            $record['category'] = is_object($category) ? $category->id : $category;
        }
        return $this->getDataGenerator()->create_course($record);
    }

    public function test_next_cohort_is_the_nearest_visible_upcoming_course(): void {
        $this->resetAfterTest();
        $cat = $this->getDataGenerator()->create_category(['name' => 'Bitcoin Dev']);
        $this->course('Past', $cat, -3);
        $this->course('Later', $cat, 30);
        $hidden = $this->course('Hidden soon', $cat, 2, ['visible' => 0]);
        $hiddencat = $this->getDataGenerator()->create_category(['name' => 'Hidden', 'visible' => 0]);
        $this->course('Hidden category', $hiddencat, 1);
        $soon = $this->course('Soon', $cat, 5, ['lang' => 'pt_br', 'summary' => '<p>' . str_repeat('word ', 60) . '</p>']);
        $this->getDataGenerator()->enrol_user($this->getDataGenerator()->create_user()->id, $soon->id);
        $this->getDataGenerator()->enrol_user($this->getDataGenerator()->create_user()->id, $soon->id);
        $next = (new landing())->get_next_cohort();
        $this->assertSame('Soon', $next['name']);
        $this->assertSame('Bitcoin Dev', $next['category']);
        $this->assertSame('2 enrolled', $next['enrolled']);
        $this->assertSame('PT', $next['code']);
        $this->assertStringEndsWith('/course/view.php?id=' . $soon->id, $next['url']);
        $this->assertLessThanOrEqual(143, \core_text::strlen($next['summary']));
        $this->assertStringNotContainsString('<', $next['summary']);
        $this->assertNotEquals($hidden->id, $soon->id);
    }

    public function test_a_course_starting_today_counts_and_none_upcoming_hides_the_card(): void {
        $this->resetAfterTest();
        $this->course('Yesterday', null, -1);
        $this->assertNull((new landing())->get_next_cohort());
        $today = $this->course('Today', null, 0);
        $this->getDataGenerator()->create_course(['fullname' => 'Today', 'startdate' => usergetmidnight(time()) + 60]);
        $next = (new landing())->get_next_cohort();
        $this->assertSame('Today', $next['name']);
    }

    public function test_active_enrolments_only(): void {
        $this->resetAfterTest();
        $course = $this->course('Cohort', null, 4);
        $gen = $this->getDataGenerator();
        $gen->enrol_user($gen->create_user()->id, $course->id);
        $gen->enrol_user($gen->create_user()->id, $course->id, null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $this->assertSame('1 enrolled', (new landing())->get_next_cohort()['enrolled']);
    }

    public function test_facts_count_real_students_and_courses_and_languages(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $before = (new landing())->get_facts();
        $gen->create_user();
        $gen->create_user(['suspended' => 1]);
        $gen->create_user(['deleted' => 1]);
        $gen->create_user(['confirmed' => 0]);
        $this->course('A', null, 1, ['lang' => 'es']);
        $this->course('B', null, 1, ['lang' => 'pt_br']);
        $this->course('C', null, 1, ['lang' => 'es']);
        $this->course('Hidden', null, 1, ['lang' => 'fr', 'visible' => 0]);
        $facts = (new landing())->get_facts();
        $this->assertSame((string) ((int) $before[0]['value'] + 1), $facts[0]['value']);
        $this->assertSame('3', $facts[1]['value']);
        $this->assertSame('ES · PT', $facts[2]['value']);
        $this->assertSame(get_string('factlive', 'theme_ismos'), $facts[3]['value']);
        $this->assertCount(4, $facts);
    }

    public function test_language_cell_is_hidden_when_no_course_sets_one(): void {
        $this->resetAfterTest();
        $this->course('A', null, 1);
        $facts = (new landing())->get_facts();
        $this->assertCount(3, $facts);
        $this->assertSame(get_string('factlive', 'theme_ismos'), $facts[2]['value']);
    }

    public function test_language_code(): void {
        $this->assertSame('ES', landing::language_code('es'));
        $this->assertSame('PT', landing::language_code('pt_br'));
        $this->assertSame('', landing::language_code(''));
    }

    public function test_paths_with_three_categories_have_levels_kickers_and_picks(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $a = $gen->create_category(['name' => 'Bitcoin 4 Everyone', 'description' => '<p>Start <b>here</b></p>']);
        $b = $gen->create_category(['name' => 'Bitcoin Dev', 'description' => '']);
        $child = $gen->create_category(['name' => 'Child', 'parent' => $b->id]);
        $gen->create_category(['name' => 'Bitcoin Master']);
        $gen->create_category(['name' => 'Hidden', 'visible' => 0]);
        $this->course('Old', $b, -50);
        $this->course('Older', $b, -80);
        $this->course('Soon', $child, 5);
        $this->course('Sooner', $b, 2);
        $this->course('Latest past', $b, -10);
        $this->course('Hidden course', $b, 3, ['visible' => 0]);
        $paths = (new landing())->get_paths();
        $default = \core_course_category::get_default();
        $named = array_column($paths, null, 'name');
        // Moodle's default category counts as a fourth category unless hidden: only check the three named ones here.
        $this->assertSame('Start here', $named['Bitcoin 4 Everyone']['description']);
        $this->assertFalse($named['Bitcoin Dev']['hasdescription']);
        $this->assertArrayNotHasKey('Hidden', $named);
        $this->assertSame(['Sooner', 'Soon', 'Latest past'], array_column($named['Bitcoin Dev']['courses'], 'name'));
        $this->assertStringEndsWith('categoryid=' . $b->id, $named['Bitcoin Dev']['url']);
        $this->assertNotNull($default);
    }

    public function test_exactly_three_categories_get_level_bars_and_kickers(): void {
        $this->resetAfterTest();
        \core_course_category::get_default()->hide();
        $gen = $this->getDataGenerator();
        $gen->create_category(['name' => 'One']);
        $gen->create_category(['name' => 'Two']);
        $paths = (new landing())->get_paths();
        $this->assertCount(2, $paths);
        $this->assertFalse($paths[0]['haslevel']);
        $this->assertSame('', $paths[0]['kicker']);
        $gen->create_category(['name' => 'Three']);
        $paths = (new landing())->get_paths();
        $this->assertCount(3, $paths);
        $this->assertSame([true, false, false], array_column($paths[0]['level'], 'on'));
        $this->assertSame([true, true, true], array_column($paths[2]['level'], 'on'));
        $this->assertSame('To start', $paths[0]['kicker']);
        $this->assertSame('To build', $paths[1]['kicker']);
        $this->assertSame('To go deep', $paths[2]['kicker']);
    }

    public function test_calendar_is_upcoming_only_ascending_and_limited_to_six(): void {
        $this->resetAfterTest();
        $cat = $this->getDataGenerator()->create_category(['name' => 'Bitcoin Dev']);
        $this->course('Past', $cat, -5);
        foreach ([9, 3, 7, 1, 8, 2, 6, 4] as $days) {
            $this->course("In $days", $cat, $days, $days === 3 ? ['lang' => 'es'] : []);
        }
        $this->course('Hidden', $cat, 1, ['visible' => 0]);
        $rows = (new landing())->get_calendar();
        $this->assertCount(6, $rows);
        $this->assertSame(['In 1', 'In 2', 'In 3', 'In 4', 'In 6', 'In 7'], array_column($rows, 'name'));
        $this->assertSame('Bitcoin Dev', $rows[0]['category']);
        $this->assertFalse($rows[0]['hascode']);
        $this->assertTrue($rows[2]['hascode']);
        $this->assertSame('ES', $rows[2]['code']);
        $this->assertMatchesRegularExpression('/^\d{2}$/', $rows[0]['day']);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $rows[0]['year']);
    }

    public function test_no_upcoming_courses_hide_calendar_and_card(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $this->course('Past', null, -5);
        $data = (new frontpage())->export_for_template($this->get_renderer());
        $this->assertFalse($data['hascalendar']);
        $this->assertFalse($data['hero']['hasnext']);
    }

    public function test_split_title_on_the_first_comma(): void {
        $this->assertSame(['Learn,', 'then build, again.'], landing::split_title('Learn, then build, again.'));
        $this->assertSame(['Learn to run your own money,', 'one lesson at a time.'], landing::split_title('Learn to run your own money, one lesson at a time.'));
        $this->assertSame(['No comma here', ''], landing::split_title('No comma here'));
    }

    public function test_final_band_falls_back_to_the_course_list_without_signup(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->registerauth = 'email';
        $this->setUser(0);
        $final = (new frontpage())->export_for_template($this->get_renderer())['final'];
        $this->assertStringEndsWith('/login/signup.php', $final['url']);
        $this->assertSame(get_string('finalcta', 'theme_ismos'), $final['cta']);
        $this->setUser($this->getDataGenerator()->create_user());
        $final = (new frontpage())->export_for_template($this->get_renderer())['final'];
        $this->assertStringEndsWith('/course/index.php', $final['url']);
        $this->assertSame(get_string('herocta', 'theme_ismos'), $final['cta']);
    }

    public function test_steps_and_copy_strings(): void {
        $this->resetAfterTest();
        $data = (new frontpage())->export_for_template($this->get_renderer());
        $this->assertCount(3, $data['steps']);
        $this->assertSame([1, 2, 3], array_column($data['steps'], 'number'));
        $string = [];
        include(__DIR__ . '/../lang/es/theme_ismos.php');
        foreach (['heroleadlive', 'nextcohort', 'factlive', 'pathkicker1', 'calendarheading', 'step3text', 'finalcta'] as $key) {
            $this->assertArrayHasKey($key, $string);
        }
    }

    /**
     * The page core renderer.
     *
     * @return \renderer_base
     */
    private function get_renderer(): \renderer_base {
        global $PAGE;
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        return $PAGE->get_renderer('core');
    }
}
