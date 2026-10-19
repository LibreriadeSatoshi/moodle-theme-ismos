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
 * Builds the data for the ISMOS course card (template theme_ismos/course_card).
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class card {
    /**
     * Export a course for the card template.
     *
     * @param \core_course_list_element $course
     * @return array url, title, label, summary, teachers and facts
     */
    public static function export(\core_course_list_element $course): array {
        $context = \context_course::instance($course->id);
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
        $teachers = self::teachers($course);
        $facts = self::facts($course);

        return [
            'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'title' => format_string($course->fullname, true, ['context' => $context]),
            'label' => $category ? $category->get_formatted_name() : '',
            'summary' => self::summary($course, $context),
            'hasteachers' => !empty($teachers),
            'teachers' => $teachers,
            'facts' => $facts,
            'hasfacts' => !empty($facts),
        ];
    }

    /**
     * The course contacts to show on a card or course page.
     *
     * Honours the coursecontact setting through the course list element.
     *
     * @param \core_course_list_element $course
     * @param int $limit maximum number of teachers
     * @return array of [name => string, pictureurl => ?string, initials => string]
     */
    public static function teachers(\core_course_list_element $course, int $limit = 2): array {
        global $DB, $PAGE;

        $contacts = $course->get_course_contacts();
        $ids = [];
        foreach ($contacts as $contact) {
            $ids[$contact['user']->id] = $contact['user']->id;
        }
        if (!$ids || $limit < 1) {
            return [];
        }
        $ids = array_slice($ids, 0, $limit, true);
        $fields = \core_user\fields::for_userpic()->get_sql('u', false, '', '', false)->selects;
        [$insql, $params] = $DB->get_in_or_equal(array_keys($ids), SQL_PARAMS_NAMED);
        $users = $DB->get_records_sql("SELECT $fields FROM {user} u WHERE u.id $insql", $params);

        $result = [];
        foreach ($contacts as $contact) {
            $id = $contact['user']->id;
            if (!isset($ids[$id]) || !isset($users[$id])) {
                continue;
            }
            $user = $users[$id];
            unset($ids[$id]);
            $pictureurl = null;
            if ($user->picture > 0) {
                $pictureurl = (new \core\output\user_picture($user))->get_url($PAGE)->out(false);
            }
            $result[] = [
                'name' => $contact['username'],
                'pictureurl' => $pictureurl,
                'initials' => self::initials($user),
            ];
        }
        return $result;
    }

    /**
     * Two uppercase letters: first letter of the first and last name.
     *
     * @param \stdClass $user
     * @return string
     */
    private static function initials(\stdClass $user): string {
        $first = mb_substr(trim((string) $user->firstname), 0, 1);
        $last = mb_substr(trim((string) $user->lastname), 0, 1);
        $initials = mb_strtoupper($first . $last);
        if (mb_strlen($initials) < 2) {
            $initials = mb_strtoupper(mb_substr(trim($user->firstname . ' ' . $user->lastname), 0, 2));
        }
        return $initials;
    }

    /**
     * Plain text summary, whitespace collapsed.
     *
     * @param \core_course_list_element $course
     * @param \context $context
     * @return string
     */
    private static function summary(\core_course_list_element $course, \context $context): string {
        if (!$course->has_summary()) {
            return '';
        }
        $html = format_text($course->summary, $course->summaryformat, ['context' => $context, 'para' => false]);
        $text = html_entity_decode(strip_tags(preg_replace('~</(p|div|li|h\d)>~i', ' ', $html)), ENT_QUOTES);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Facts line: a fee price and/or a future start date. Nothing else is invented.
     *
     * @param \core_course_list_element $course
     * @return string[]
     */
    private static function facts(\core_course_list_element $course): array {
        $facts = [];
        foreach (enrol_get_instances($course->id, true) as $instance) {
            if ($instance->enrol === 'fee' && (float) $instance->cost > 0) {
                $facts[] = \core_payment\helper::get_cost_as_string((float) $instance->cost, $instance->currency);
                break;
            }
        }
        if ($course->startdate > time()) {
            $facts[] = get_string(
                'cardstarts',
                'theme_ismos',
                userdate($course->startdate, get_string('strftimedaydate'))
            );
        }
        return $facts;
    }
}
