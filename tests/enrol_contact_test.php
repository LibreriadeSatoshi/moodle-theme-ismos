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
 * Tests for the enrolment page of a course whose only way in is manual enrolment.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\theme_ismos\output\core\course_renderer::class)]
final class enrol_contact_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        global $PAGE, $SITE;
        $PAGE->set_course($SITE);
        $PAGE->set_url('/enrol/index.php');
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_pagetype('enrol-index');
        $PAGE->force_theme('ismos');
        set_config('enrolcontactemail', 'cursos@ismos.xyz', 'theme_ismos');
        $this->setUser($this->getDataGenerator()->create_user());
    }

    /**
     * The enrolment options of a course, as the enrolment page renders them without forms.
     *
     * @param \stdClass $course
     * @param string[] $widgets
     * @return string
     */
    private function options(\stdClass $course, array $widgets = []): string {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);
        return $renderer->enrolment_options(get_course($course->id), $widgets, new \core\url('/'));
    }

    /**
     * A course whose only enabled enrolment method is manual enrolment.
     *
     * @return \stdClass
     */
    private function manual_only_course(): \stdClass {
        global $DB;
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Basic rules & prompts']);
        $DB->set_field_select('enrol', 'status', ENROL_INSTANCE_DISABLED, 'courseid = ? AND enrol <> ?', [$course->id, 'manual']);
        return $course;
    }

    public function test_manual_only_course_shows_the_contact_email_with_the_course_as_subject(): void {
        $html = $this->options($this->manual_only_course());
        $this->assertStringContainsString('href="mailto:cursos@ismos.xyz?subject=Basic%20rules%20%26%20prompts"', $html);
        $this->assertStringContainsString('Write to cursos@ismos.xyz', $html);
        $this->assertStringContainsString(get_string('enrolcontact', 'theme_ismos'), $html);
        $this->assertStringNotContainsString(get_string('notenrollable', 'enrol'), $html);
    }

    public function test_a_course_with_another_enabled_method_keeps_cores_message(): void {
        global $DB;
        $course = $this->manual_only_course();
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['courseid' => $course->id, 'enrol' => 'self']);
        $html = $this->options($course);
        $this->assertStringContainsString(get_string('notenrollable', 'enrol'), $html);
        $this->assertStringNotContainsString('mailto:', $html);
    }

    public function test_forms_from_enrolment_methods_are_shown_as_they_are(): void {
        $html = $this->options($this->manual_only_course(), ['<form id="request-access"></form>']);
        $this->assertStringContainsString('<form id="request-access"></form>', $html);
        $this->assertStringNotContainsString('mailto:', $html);
    }

    public function test_an_empty_setting_or_a_guest_keeps_cores_message(): void {
        $course = $this->manual_only_course();
        set_config('enrolcontactemail', '', 'theme_ismos');
        $this->assertStringNotContainsString('mailto:', $this->options($course));

        set_config('enrolcontactemail', 'cursos@ismos.xyz', 'theme_ismos');
        $this->setGuestUser();
        $html = $this->options($course);
        $this->assertStringNotContainsString('mailto:', $html);
        $this->assertStringContainsString(get_string('noguestaccess', 'enrol'), $html);
    }
}
