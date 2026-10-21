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

namespace theme_ismos\local;

/**
 * Data for the heading of the My courses page (/my/courses.php).
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mycourses {
    /**
     * The number of visible courses the user is enrolled in.
     *
     * @param int|null $userid defaults to the current user
     * @return int
     */
    public static function count_enrolled(?int $userid = null): int {
        global $USER;
        $userid = $userid ?? (int) $USER->id;
        if (!$userid || isguestuser($userid)) {
            return 0;
        }
        $courses = enrol_get_all_users_courses($userid, true, ['visible']);
        return count(array_filter($courses, fn($c) => !empty($c->visible)));
    }

    /**
     * The heading: label, title, the lead with the number of courses, and the manage link for people who can.
     *
     * @return array
     */
    public static function export_header(): array {
        $count = self::count_enrolled();
        $browse = '';
        if ($count === 0) {
            $lead = get_string('mycoursesempty', 'theme_ismos');
            $browse = get_string('herocta', 'theme_ismos');
        } else if ($count === 1) {
            $lead = get_string('mycourseslead1', 'theme_ismos');
        } else {
            $lead = get_string('mycourseslead', 'theme_ismos', $count);
        }
        $syscontext = \context_system::instance();
        return [
            'label' => get_string('mycourses'),
            'title' => get_string('mycourses'),
            'lead' => $lead,
            'haslead' => true,
            'browse' => $browse,
            'browseurl' => (new \moodle_url('/course/index.php'))->out(false),
            'canmanage' => has_capability('moodle/category:manage', $syscontext) || has_capability('moodle/course:create', $syscontext),
            'manageurl' => (new \moodle_url('/course/management.php'))->out(false),
            'count' => $count,
        ];
    }
}
