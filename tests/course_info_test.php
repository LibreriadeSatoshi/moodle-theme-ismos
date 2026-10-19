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

namespace theme_ismos;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the public course information page.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\theme_ismos\output\core\course_renderer::class)]
final class course_info_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        global $PAGE, $SITE;
        $PAGE->set_course($SITE);
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
    }

    /**
     * Render the info box for a course on the given page type.
     *
     * @param \stdClass $course
     * @param string $pagetype
     * @return string
     */
    private function info(\stdClass $course, string $pagetype): string {
        global $PAGE;
        $PAGE->set_pagetype($pagetype);
        $renderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);
        return $renderer->course_info_box(get_course($course->id));
    }

    public function test_course_info_page_renders_summary_contacts_and_link(): void {
        $summary = str_repeat('A long sentence about the course. ', 40);
        $course = $this->getDataGenerator()->create_course(['summary' => '<p>' . $summary . '</p>', 'startdate' => 1793000000]);
        $teacher = $this->getDataGenerator()->create_user(['firstname' => 'Ada', 'lastname' => 'Lovelace']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $html = $this->info($course, 'course-info');
        $this->assertStringContainsString(trim($summary), $html);
        $this->assertStringContainsString('Ada Lovelace', $html);
        $this->assertStringContainsString('course/view.php?id=' . $course->id, $html);
        $this->assertStringContainsString(get_string('gotocourse', 'theme_ismos'), $html);
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString(userdate(1793000000, get_string('strftimedaydate', 'core_langconfig')), $html);
    }

    public function test_other_pagetypes_use_core_output(): void {
        $course = $this->getDataGenerator()->create_course();
        $html = $this->info($course, 'course-index');
        $this->assertStringContainsString('generalbox info', $html);
        $this->assertStringContainsString('ismos-coursebox', $html);
        $this->assertStringNotContainsString('ismos-courseinfo', $html);
    }

    public function test_cover_has_dimensions(): void {
        global $USER;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAYAAAC56t6BAAAAEklEQVR4nGP4z8Dwn4GBgQEAFAAC/ZyRbgAAAABJRU5ErkJggg==');
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'course', 'filearea' => 'overviewfiles',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'cover.png',
        ], $png);

        $html = $this->info($course, 'course-info');
        $this->assertMatchesRegularExpression('/<img[^>]*cover\.png/', $html);
        $this->assertMatchesRegularExpression('/<img[^>]*width="2"/', $html);
        $this->assertMatchesRegularExpression('/<img[^>]*height="3"/', $html);
        $this->assertStringContainsString('loading="eager"', $html);
    }

    public function test_no_cover_means_no_image_and_no_dates_means_no_date_list(): void {
        $course = $this->getDataGenerator()->create_course(['startdate' => 0, 'enddate' => 0]);
        $html = $this->info($course, 'course-info');
        $this->assertStringNotContainsString('cover', $html);
        $this->assertStringNotContainsString('ismos-courseinfo__dates', $html);
    }
}
