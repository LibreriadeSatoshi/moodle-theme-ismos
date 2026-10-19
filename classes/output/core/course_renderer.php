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

namespace theme_ismos\output\core;

/**
 * Course listing renderer: every course in a listing is an ISMOS card.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends \core_course_renderer {
    /**
     * Render one course of a listing as a card.
     *
     * Keeps the .coursebox wrapper and data attributes core scripts and behat look for.
     *
     * Deliberate departure from core: every display mode (collapsed, auto, expanded) renders the same
     * full card. The collapsed "more info" toggle and the enrolment icons are dropped, because a card
     * is a single link with no nested interactive elements, and the card already carries the summary,
     * teachers and fee. The category expander only listens on .coursebox .moreinfo, which is absent, so
     * it keeps working for categories and simply never toggles a course.
     *
     * @param \coursecat_helper $chelper
     * @param \core_course_list_element|\stdClass $course
     * @param string $additionalclasses
     * @return string
     */
    protected function coursecat_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return '';
        }
        if ($course instanceof \stdClass) {
            $course = new \core_course_list_element($course);
        }
        $classes = trim('coursebox ismos-coursebox ' . $additionalclasses);
        return \html_writer::tag(
            'div',
            $this->output->render_from_template('theme_ismos/course_card', \theme_ismos\local\card::export($course)),
            ['class' => $classes, 'data-courseid' => $course->id, 'data-type' => self::COURSECAT_TYPE_COURSE]
        );
    }

    /**
     * Front page part: the available courses get the ISMOS label and heading (an h2, the hero is the h1).
     *
     * @param string $skipdivid
     * @param string $contentsdivid
     * @param string $header
     * @param string $contents
     * @return string
     */
    protected function frontpage_part($skipdivid, $contentsdivid, $header, $contents) {
        if ($contentsdivid !== 'frontpage-available-course-list' || strval($contents) === '') {
            return parent::frontpage_part($skipdivid, $contentsdivid, $header, $contents);
        }
        $heading = get_string('coursesheading', 'theme_ismos');
        $output = \html_writer::link(
            '#' . $skipdivid,
            get_string('skipa', 'access', \core_text::strtolower($heading)),
            ['class' => 'skip-block skip aabtn']
        );
        $output .= \html_writer::start_tag('div', ['id' => $contentsdivid]);
        $output .= \html_writer::span(get_string('courses', 'theme_ismos'), 'ismos-label d-block');
        $output .= $this->heading($heading);
        $output .= $contents;
        $output .= \html_writer::end_tag('div');
        $output .= \html_writer::tag('span', '', ['class' => 'skip-block-to', 'id' => $skipdivid]);
        return $output;
    }
}
