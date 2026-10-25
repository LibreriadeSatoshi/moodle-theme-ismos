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

use core\hook\navigation\primary_extend;
use core\hook\output\before_http_headers;
use theme_ismos\local\catalogue;

/**
 * Hook callbacks for theme_ismos.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** Key of the catalogue item in the primary navigation. */
    public const COURSES_KEY = 'ismoscourses';

    /**
     * Put "Courses" (the catalogue) right after Home, and make it the current item on the catalogue and course search.
     *
     * Only on the ISMOS theme: another theme keeps Moodle's navigation unchanged.
     *
     * @param primary_extend $hook
     */
    public static function primary_extend(primary_extend $hook): void {
        global $PAGE;
        if ($PAGE->theme->name !== 'ismos') {
            return;
        }
        $primary = $hook->get_primaryview();
        $node = \navigation_node::create(get_string('courses', 'theme_ismos'), new \moodle_url('/course/index.php'),
            \navigation_node::TYPE_CUSTOM, null, self::COURSES_KEY);
        $children = $primary->get_children_key_list();
        $before = null;
        foreach ($children as $index => $key) {
            if ($key === 'home') {
                $before = $children[$index + 1] ?? null;
                break;
            }
        }
        $primary->add_node($node, $before);
        if (preg_match('/^course-(index|search)/', (string) $PAGE->pagetype)) {
            $PAGE->set_primary_active_tab(self::COURSES_KEY);
        }
    }

    /**
     * Course search in the catalogue's look: a search without words goes to the catalogue, which lists every course,
     * and the results page gets the catalogue's full-width layout (the body class must be set before the header).
     *
     * @param before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE;
        if ($PAGE->theme->name !== 'ismos') {
            return;
        }
        if (catalogue::is_empty_search($PAGE)) {
            redirect(new \moodle_url('/course/index.php'));
        }
        if (catalogue::is_search_page($PAGE)) {
            $PAGE->add_body_class('ismos-catalog');
        }
    }
}
