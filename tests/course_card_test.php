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
use theme_ismos\local\card;

/**
 * Tests for the ISMOS course card.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(card::class)]
#[CoversMethod(\theme_ismos\output\core\course_renderer::class, 'coursecat_coursebox')]
final class course_card_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // The theme must be forced before anything (filters included) initialises the page theme.
        global $PAGE, $SITE;
        $PAGE->set_course($SITE);
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
    }

    /**
     * Return the theme's general core renderer.
     *
     * @return \core_renderer
     */
    private function ismos_output(): \core_renderer {
        global $PAGE;
        return $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
    }

    /**
     * Wrap a course record as the listing does.
     *
     * @param \stdClass $course
     * @return \core_course_list_element
     */
    private function element(\stdClass $course): \core_course_list_element {
        return new \core_course_list_element(get_course($course->id));
    }

    /**
     * Render a course through the card template.
     *
     * @param \stdClass $course
     * @return string
     */
    private function render(\stdClass $course): string {
        $out = $this->ismos_output();
        return $out->render_from_template('theme_ismos/course_card', card::export($this->element($course)));
    }

    /**
     * Create a user enrolled as editing teacher.
     *
     * @param \stdClass $course
     * @param array $record user fields
     * @return \stdClass
     */
    private function teacher(\stdClass $course, array $record): \stdClass {
        $user = $this->getDataGenerator()->create_user($record);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        return $user;
    }

    public function test_card_has_category_label_title_and_link(): void {
        $cat = $this->getDataGenerator()->create_category(['name' => 'Privacy']);
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Running a node', 'category' => $cat->id, 'summary' => '<p>Run your <b>own</b> node.</p>',
        ]);
        $data = card::export($this->element($course));
        $this->assertSame('Privacy', $data['label']);
        $this->assertSame('Running a node', $data['title']);
        $this->assertSame('Run your own node.', $data['summary']);
        $this->assertStringContainsString('/course/view.php?id=' . $course->id, $data['url']);

        $html = $this->render($course);
        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('href="' . $data['url'] . '"', $html);
        $this->assertStringContainsString('Privacy', $html);
        $this->assertStringContainsString('<h3', $html);
        $this->assertStringContainsString('ismos-card__title', $html);
    }

    public function test_long_title_is_not_truncated(): void {
        $title = str_repeat('Éducation à la souveraineté ', 5);
        $title = trim(mb_substr($title, 0, 120));
        $this->assertSame(120, mb_strlen($title));
        $course = $this->getDataGenerator()->create_course(['fullname' => $title]);
        $data = card::export($this->element($course));
        $this->assertSame($title, $data['title']);
        $this->assertStringContainsString($title, $this->render($course));
    }

    public function test_teachers_limited_to_two_with_initials_fallback(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $users = [
            $this->teacher($course, ['firstname' => 'Ada', 'lastname' => 'Lovelace']),
            $this->teacher($course, ['firstname' => 'Grace', 'lastname' => 'Hopper']),
            $this->teacher($course, ['firstname' => 'alan', 'lastname' => 'turing']),
        ];
        // Only Grace has no picture.
        $DB->set_field('user', 'picture', 5, ['id' => $users[0]->id]);
        $DB->set_field('user', 'picture', 5, ['id' => $users[2]->id]);
        $expected = [
            fullname($users[0]) => ['AL', true],
            fullname($users[1]) => ['GH', false],
            fullname($users[2]) => ['AT', true],
        ];

        $teachers = card::teachers($this->element($course));
        $this->assertCount(2, $teachers);
        $this->assertCount(2, card::export($this->element($course))['teachers']);
        foreach ($teachers as $teacher) {
            $this->assertSame(['name', 'pictureurl', 'initials'], array_keys($teacher));
            [$initials, $haspicture] = $expected[$teacher['name']];
            $this->assertSame($initials, $teacher['initials']);
            $this->assertSame($haspicture, $teacher['pictureurl'] !== null);
        }
        $this->assertCount(1, card::teachers($this->element($course), 1));
        $this->assertCount(3, card::teachers($this->element($course), 5));
    }

    public function test_teacher_without_picture_has_null_url_and_two_letter_initials(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->teacher($course, ['firstname' => 'Grace', 'lastname' => 'Hopper']);
        $teachers = card::teachers($this->element($course));
        $this->assertCount(1, $teachers);
        $this->assertNull($teachers[0]['pictureurl']);
        $this->assertSame('GH', $teachers[0]['initials']);
        $this->assertStringContainsString('ismos-avatar--initials', $this->render($course));
    }

    public function test_course_without_contacts_has_no_teacher_row(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->assertSame([], card::teachers($this->element($course)));
        $this->assertStringNotContainsString('ismos-card__teacher', $this->render($course));
    }

    public function test_teacher_names_are_plain_text(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->teacher($course, ['firstname' => 'Ada', 'lastname' => 'Lovelace']);
        $html = $this->render($course);
        $this->assertStringContainsString('ismos-card__teacher', $html);
        $this->assertSame(1, substr_count($html, '<a '));
    }

    public function test_fee_course_shows_price(): void {
        $enabled = enrol_get_plugins(true);
        $enabled['fee'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
        $course = $this->getDataGenerator()->create_course(['startdate' => time() - DAYSECS]);
        $studentrole = \core\di::get(\moodle_database::class)->get_record('role', ['shortname' => 'student']);
        enrol_get_plugin('fee')->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED, 'roleid' => $studentrole->id, 'cost' => 10, 'currency' => 'USD',
        ]);
        $data = card::export($this->element($course));
        $this->assertSame([\core_payment\helper::get_cost_as_string(10, 'USD')], $data['facts']);
        $this->assertStringContainsString('ismos-card__facts', $this->render($course));
    }

    public function test_future_start_shows_date(): void {
        $start = time() + 10 * DAYSECS;
        $course = $this->getDataGenerator()->create_course(['startdate' => $start]);
        $data = card::export($this->element($course));
        $this->assertSame(
            [get_string('cardstarts', 'theme_ismos', userdate($start, get_string('strftimedaydate')))],
            $data['facts']
        );
    }

    public function test_no_facts_hides_hairline(): void {
        $course = $this->getDataGenerator()->create_course(['startdate' => time() - DAYSECS]);
        $this->assertSame([], card::export($this->element($course))['facts']);
        $this->assertStringNotContainsString('ismos-card__facts', $this->render($course));
    }

    public function test_card_has_no_course_image(): void {
        $course = $this->getDataGenerator()->create_course();
        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => \context_course::instance($course->id)->id, 'component' => 'course',
            'filearea' => 'overviewfiles', 'itemid' => 0, 'filepath' => '/', 'filename' => 'cover.png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $this->assertStringNotContainsString('<img', $this->render($course));
    }

    public function test_listing_renders_cards_in_courses_container(): void {
        $out = $this->ismos_output();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Listed course']);
        global $PAGE;
        $courserenderer = $PAGE->get_renderer('core', 'course');
        $this->assertInstanceOf(\theme_ismos\output\core\course_renderer::class, $courserenderer);
        $html = $courserenderer->frontpage_available_courses();
        $this->assertStringContainsString('class="courses ', $html);
        $this->assertStringContainsString('coursebox', $html);
        $this->assertStringContainsString('data-courseid="' . $course->id . '"', $html);
        $this->assertStringContainsString('ismos-card', $html);
        $this->assertStringContainsString('Listed course', $html);
        unset($out);
    }
}
