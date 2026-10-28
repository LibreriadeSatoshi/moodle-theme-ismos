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
use theme_ismos\local\catalogue;

/**
 * Tests for the course catalogue data and page.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(catalogue::class)]
final class catalogue_test extends \advanced_testcase {
    /** @var int One day in seconds. */
    private const DAY = 86400;

    /**
     * Create a course starting the given number of days from now.
     *
     * @param string $name
     * @param \core_course_category|null $category
     * @param int $days
     * @param array $extra
     * @return \stdClass
     */
    private function course(string $name, ?\core_course_category $category, int $days, array $extra = []): \stdClass {
        $record = ['fullname' => $name, 'startdate' => time() + $days * self::DAY] + $extra;
        if ($category) {
            $record['category'] = $category->id;
        }
        return $this->getDataGenerator()->create_course($record);
    }

    /**
     * Hide Moodle's default category so the tests control the levels.
     */
    private function setup_levels(): array {
        \core_course_category::get_default()->hide();
        $gen = $this->getDataGenerator();
        return [
            $gen->create_category(['name' => 'One', 'description' => '<p>First <b>level</b></p>']),
            $gen->create_category(['name' => 'Two', 'description' => '']),
            $gen->create_category(['name' => 'Three', 'description' => '']),
        ];
    }

    public function test_cards_are_upcoming_first_then_past_newest_first(): void {
        $this->resetAfterTest();
        [$one] = $this->setup_levels();
        $this->course('Old', $one, -90);
        $this->course('Later', $one, 30);
        $this->course('Recent', $one, -5);
        $this->course('Soon', $one, 3);
        $this->course('Hidden', $one, 1, ['visible' => 0]);
        $data = (new catalogue())->export();
        $names = array_column($data['sections'][0]['cards'], 'name');
        $this->assertSame(['Soon', 'Later', 'Recent', 'Old'], $names);
        $cards = $data['sections'][0]['cards'];
        $this->assertTrue($cards[0]['open']);
        $this->assertTrue($cards[2]['past']);
        $this->assertStringStartsWith('Starts ', $cards[0]['when']);
    }

    public function test_levels_without_visible_courses_are_left_out_and_counts_are_right(): void {
        $this->resetAfterTest();
        [$one, $two, $three] = $this->setup_levels();
        $this->course('A', $one, 1);
        $this->course('B', $one, 2);
        $this->course('C', $three, 3);
        $this->course('Hidden', $two, 1, ['visible' => 0]);
        $data = (new catalogue())->export();
        $this->assertSame(['All', 'One', 'Three'], array_column($data['tabs'], 'name'));
        $this->assertSame([3, 2, 1], array_column($data['tabs'], 'count'));
        $this->assertSame(['One', 'Three'], array_column($data['sections'], 'name'));
        $this->assertSame('All courses', $data['title']);
        $this->assertSame('3 courses in 2 categories, live with a teacher. Start with the one that fits you.', $data['lead']);
        $this->assertTrue($data['tabs'][0]['active']);
        $this->assertTrue($data['sections'][0]['showonly']);
        $this->assertTrue($data['sections'][0]['showname']);
    }

    public function test_three_levels_have_no_level_bars_or_kickers(): void {
        $this->resetAfterTest();
        [$one, $two, $three] = $this->setup_levels();
        foreach ([$one, $two, $three] as $cat) {
            $this->course('Course ' . $cat->name, $cat, 1);
        }
        $sections = (new catalogue())->export()['sections'];
        $this->assertArrayNotHasKey('level', $sections[0]);
        $this->assertArrayNotHasKey('kicker', $sections[0]);
        $this->assertSame('First level', $sections[0]['description']);
    }

    public function test_a_level_page_filters_to_the_level_and_its_subcategories(): void {
        $this->resetAfterTest();
        [$one, $two] = $this->setup_levels();
        $child = $this->getDataGenerator()->create_category(['name' => 'Child', 'parent' => $one->id, 'description' => '<p>Child text</p>']);
        $this->course('Direct', $one, 1);
        $this->course('In child', $child, 2);
        $this->course('Other level', $two, 3);
        $data = (new catalogue())->export((int) $one->id);
        $this->assertSame('One', $data['title']);
        $this->assertSame('First level', $data['lead']);
        $this->assertCount(1, $data['sections']);
        $this->assertSame(['Direct', 'In child'], array_column($data['sections'][0]['cards'], 'name'));
        $this->assertFalse($data['sections'][0]['showonly']);
        // The level's own page has its name as the title, so the section heading is only for screen readers.
        $this->assertFalse($data['sections'][0]['showname']);
        // The level's description is the lead, so the section does not repeat it.
        $this->assertFalse($data['sections'][0]['hasdescription']);
        $this->assertFalse($data['tabs'][0]['active']);
        $this->assertTrue($data['tabs'][1]['active']);
    }

    public function test_a_subcategory_page_filters_to_that_subcategory_under_its_level_tab(): void {
        $this->resetAfterTest();
        [$one] = $this->setup_levels();
        $child = $this->getDataGenerator()->create_category(['name' => 'Child', 'parent' => $one->id, 'description' => '<p>Child text</p>']);
        $this->course('Direct', $one, 1);
        $this->course('In child', $child, 2);
        $data = (new catalogue())->export((int) $child->id);
        $this->assertSame('Child', $data['title']);
        $this->assertSame('Child text', $data['lead']);
        $this->assertSame('One', $data['sections'][0]['name']);
        $this->assertSame(['In child'], array_column($data['sections'][0]['cards'], 'name'));
        $this->assertTrue($data['tabs'][1]['active']);
    }

    public function test_enrolled_count_shows_from_five(): void {
        $this->resetAfterTest();
        [$one] = $this->setup_levels();
        $few = $this->course('Few', $one, 1);
        $many = $this->course('Many', $one, 2);
        $gen = $this->getDataGenerator();
        for ($i = 0; $i < catalogue::ENROLLED_MIN; $i++) {
            $gen->enrol_user($gen->create_user()->id, $many->id);
            if ($i < catalogue::ENROLLED_MIN - 1) {
                $gen->enrol_user($gen->create_user()->id, $few->id);
            }
        }
        $gen->enrol_user($gen->create_user()->id, $many->id, null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $cards = array_column((new catalogue())->export()['sections'][0]['cards'], null, 'name');
        $this->assertFalse($cards['Few']['hasenrolled']);
        $this->assertSame('', $cards['Few']['enrolled']);
        $this->assertTrue($cards['Many']['hasenrolled']);
        $this->assertSame('5 enrolled', $cards['Many']['enrolled']);
    }

    public function test_cover_image_comes_from_the_overview_files_and_the_placeholder_keeps_the_frame(): void {
        $this->resetAfterTest();
        [$one] = $this->setup_levels();
        $with = $this->course('With picture', $one, 1);
        $this->course('Without picture', $one, 2);
        $context = \context_course::instance($with->id);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'course', 'filearea' => 'overviewfiles', 'itemid' => 0,
            'filepath' => '/', 'filename' => 'cover.png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        \cache::make('core', 'course_image')->purge();
        $cards = array_column((new catalogue())->export()['sections'][0]['cards'], null, 'name');
        $this->assertTrue($cards['With picture']['hasimage']);
        $this->assertStringContainsString('/course/overviewfiles/cover.png', $cards['With picture']['image']);
        $this->assertFalse($cards['Without picture']['hasimage']);
        $this->assertSame('', $cards['Without picture']['image']);
    }

    public function test_catalogue_card_markup_has_a_frame_with_or_without_a_picture(): void {
        global $PAGE, $SITE;
        $this->resetAfterTest();
        $PAGE->set_course($SITE);
        $PAGE->set_url('/course/index.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
        [$one] = $this->setup_levels();
        $with = $this->course('With picture', $one, 1);
        $this->course('Without picture', $one, 2);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_course::instance($with->id)->id, 'component' => 'course', 'filearea' => 'overviewfiles',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'cover.png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        \cache::make('core', 'course_image')->purge();
        $out = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
        $html = $out->render_from_template('theme_ismos/catalogue', (new catalogue())->export());
        // Two cards, two frames with the mark, one picture with empty alt text, lazily loaded.
        $this->assertSame(2, substr_count($html, 'class="ismos-ccard__image" aria-hidden="true"'));
        $this->assertSame(2, substr_count($html, 'class="ismos-ccard__mark"'));
        $this->assertSame(1, substr_count($html, 'class="ismos-ccard__photo"'));
        $this->assertMatchesRegularExpression('/<img class="ismos-ccard__photo" src="[^"]*cover\.png" alt="" loading="lazy"/', $html);
        $this->assertSame(2, substr_count($html, 'class="ismos-ccard__body"'));
    }

    public function test_the_first_course_contact_is_the_teacher(): void {
        global $DB;
        $this->resetAfterTest();
        [$one] = $this->setup_levels();
        $course = $this->course('Taught', $one, 1);
        $this->course('Untaught', $one, 2);
        $teacher = $this->getDataGenerator()->create_user(['firstname' => 'Yuri', 'lastname' => 'Delgado']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $cards = array_column((new catalogue())->export()['sections'][0]['cards'], null, 'name');
        $this->assertTrue($cards['Taught']['hasteacher']);
        $this->assertSame('Yuri Delgado', $cards['Taught']['teacher']['name']);
        $this->assertSame('YD', $cards['Taught']['teacher']['initials']);
        $this->assertFalse($cards['Taught']['teacher']['haspicture']);
        $this->assertFalse($cards['Untaught']['hasteacher']);
        $this->assertFalse($cards['Untaught']['hasfoot']);
        $this->assertTrue($cards['Taught']['hasfoot']);
        $this->assertNotNull($DB);
    }

    public function test_initials(): void {
        $this->assertSame('YD', catalogue::initials('Yuri Delgado'));
        $this->assertSame('C', catalogue::initials('Catrya'));
        $this->assertSame('AB', catalogue::initials(' ana maria b '));
        $this->assertSame('', catalogue::initials(''));
    }

    public function test_language_chip_and_summary_excerpt(): void {
        $this->resetAfterTest();
        [$one] = $this->setup_levels();
        $this->course('Lang', $one, 1, ['lang' => 'pt_br', 'summary' => '<p>' . str_repeat('word ', 80) . '</p>']);
        $this->course('Plain', $one, 2);
        $cards = array_column((new catalogue())->export()['sections'][0]['cards'], null, 'name');
        $this->assertSame('PT', $cards['Lang']['code']);
        $this->assertTrue($cards['Lang']['hascode']);
        $this->assertFalse($cards['Plain']['hascode']);
        $this->assertLessThanOrEqual(203, \core_text::strlen($cards['Lang']['summary']));
        $this->assertStringNotContainsString('<', $cards['Lang']['summary']);
    }

    public function test_manage_link_needs_the_capability(): void {
        $this->resetAfterTest();
        $this->setup_levels();
        $this->setGuestUser();
        $this->assertFalse((new catalogue())->export()['canmanage']);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse((new catalogue())->export()['canmanage']);
        $this->setAdminUser();
        $data = (new catalogue())->export();
        $this->assertTrue($data['canmanage']);
        $this->assertStringEndsWith('/course/management.php', $data['manageurl']);
    }

    public function test_the_course_index_renders_the_catalogue_without_core_chrome(): void {
        global $PAGE, $SITE;
        $this->resetAfterTest();
        $PAGE->set_course($SITE);
        $PAGE->set_url('/course/index.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_pagetype('course-index-category');
        $PAGE->force_theme('ismos');
        $this->setGuestUser();
        [$one] = $this->setup_levels();
        $this->course('Visible', $one, 1);
        $renderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);
        $html = $renderer->course_category(0);
        $this->assertStringContainsString('class="ismos-catalogue"', $html);
        $this->assertStringContainsString('<h1>All courses</h1>', $html);
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString('/course/search.php', $html);
        $this->assertStringContainsString('Visible', $html);
        $this->assertStringNotContainsString('categoryselect', $html);
        $this->assertStringNotContainsString('collapseexpand', $html);
        $this->assertStringNotContainsString('Manage courses', $html);
        $this->assertStringContainsString('ismos-catalog', $PAGE->bodyclasses);
        $this->assertSame('', $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL)->full_header());
    }

    public function test_search_lists_the_matching_visible_courses_in_catalogue_order(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        [$one, $two] = $this->setup_levels();
        $this->course('Bitcoin later', $one, 5);
        $this->course('Bitcoin sooner', $two, 1);
        $this->course('Nostr', $one, 2);
        $this->course('Bitcoin hidden', $one, 3, ['visible' => 0]);
        $data = (new catalogue())->export_search('bitcoin');
        $this->assertSame('Results for “bitcoin”', $data['title']);
        $this->assertSame('2 courses found.', $data['lead']);
        $this->assertSame('bitcoin', $data['search']);
        $this->assertStringEndsWith('/course/index.php', $data['browseurl']);
        $this->assertCount(1, $data['sections']);
        $this->assertSame(['Bitcoin sooner', 'Bitcoin later'], array_column($data['sections'][0]['cards'], 'name'));
        $this->assertSame([false, false, false], array_column($data['tabs'], 'active'));
        $this->assertSame(3, $data['tabs'][0]['count']);
    }

    public function test_search_without_results_says_so_and_links_to_all_courses(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        [$one] = $this->setup_levels();
        $this->course('Nostr', $one, 2);
        $data = (new catalogue())->export_search('cashu');
        $this->assertFalse($data['hassections']);
        $this->assertSame('No course matches your search.', $data['lead']);
        $this->assertSame('', $data['empty']);
        $this->assertSame('1 course found.', (new catalogue())->export_search('nostr')['lead']);
    }

    public function test_course_search_renders_the_catalogue_and_other_searches_keep_core(): void {
        global $PAGE, $SITE;
        $this->resetAfterTest();
        $PAGE->set_course($SITE);
        $PAGE->set_url('/course/search.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_pagetype('course-search');
        $PAGE->force_theme('ismos');
        $this->setAdminUser();
        [$one] = $this->setup_levels();
        $this->course('Bitcoin <b>basics</b>', $one, 1);
        $renderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);

        $_GET = [];
        $this->assertTrue(catalogue::is_empty_search($PAGE));
        $this->assertFalse(catalogue::is_search_page($PAGE));

        $_GET = ['search' => '"><b>bitcoin'];
        $this->assertTrue(catalogue::is_search_page($PAGE));
        $html = $renderer->search_courses(['search' => '"><b>bitcoin']);
        $this->assertStringContainsString('class="ismos-catalogue"', $html);
        $this->assertStringContainsString('value="&quot;&gt;bitcoin"', $html);
        $this->assertStringNotContainsString('"><b>bitcoin', $html);
        $this->assertSame('', $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL)->full_header());

        $_GET = ['search' => 'bitcoin', 'tagid' => 3];
        $this->assertFalse(catalogue::is_search_page($PAGE));
        $this->assertFalse(catalogue::is_empty_search($PAGE));
        $this->assertStringNotContainsString('ismos-catalogue', $renderer->search_courses(['search' => 'bitcoin', 'tagid' => 3]));
        $_GET = [];
    }
}
