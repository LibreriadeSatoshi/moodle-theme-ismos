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
        $classes = trim('coursebox ' . $additionalclasses);
        return \html_writer::tag(
            'div',
            $this->output->render_from_template('theme_ismos/course_card', \theme_ismos\local\card::export($course)),
            ['class' => $classes, 'data-courseid' => $course->id, 'data-type' => self::COURSECAT_TYPE_COURSE]
        );
    }
}
