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
 * Live data for the ISMOS landing page: the next cohort, the facts, the three paths and the calendar.
 *
 * Everything comes from Moodle: visible courses in visible categories, their start dates, languages and
 * enrolments. One query reads the visible courses; the rest works on that list, so a card costs no query.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class landing {
    /** @var int Characters kept from a summary or a category description. */
    private const EXCERPT = 140;

    /** @var int Rows in the calendar. */
    private const CALENDAR_MAX = 6;

    /** @var int Course names listed in a path card. */
    private const PATH_COURSES = 3;

    /** @var int Testimonials on the landing page. */
    private const QUOTES = 3;

    /** @var int Start of today for the viewer: courses starting from here on are upcoming. */
    private int $today;

    /** @var array[]|null Visible courses (not the site course) in visible categories, with their top-level category. */
    private ?array $courses = null;

    /**
     * Constructor.
     *
     * @param int|null $now the current time, for tests
     */
    public function __construct(?int $now = null) {
        $this->today = usergetmidnight($now ?? time());
    }

    /**
     * The visible courses, each with its top-level category id, the category name and its language code.
     *
     * @return array[]
     */
    public function get_courses(): array {
        global $DB, $SITE;
        if ($this->courses !== null) {
            return $this->courses;
        }
        $sql = "SELECT c.id, c.category, c.fullname, c.summary, c.summaryformat, c.startdate, c.lang, cc.path, cc.name AS catname
                  FROM {course} c
                  JOIN {course_categories} cc ON cc.id = c.category
                 WHERE c.id <> :site AND c.visible = 1 AND cc.visible = 1
              ORDER BY c.startdate ASC, c.id ASC";
        $this->courses = [];
        foreach ($DB->get_records_sql($sql, ['site' => $SITE->id]) as $course) {
            $parts = array_values(array_filter(explode('/', $course->path)));
            $course->topid = (int) ($parts[0] ?? $course->category);
            $course->code = self::language_code((string) $course->lang);
            $this->courses[] = (array) $course;
        }
        return $this->courses;
    }

    /**
     * Upper-case two letter language code of a course language ("pt_br" gives "PT"), empty when none is set.
     *
     * @param string $lang
     * @return string
     */
    public static function language_code(string $lang): string {
        $lang = trim($lang);
        if ($lang === '') {
            return '';
        }
        return \core_text::strtoupper(substr(explode('_', $lang)[0], 0, 2));
    }

    /**
     * Visible courses starting today or later, soonest first.
     *
     * @return array[]
     */
    public function get_upcoming(): array {
        $upcoming = array_filter($this->get_courses(), fn(array $c): bool => (int) $c['startdate'] >= $this->today);
        return array_values($upcoming);
    }

    /**
     * The number of active enrolments in a course.
     *
     * @param int $courseid
     * @return int
     */
    private function count_enrolled(int $courseid): int {
        global $DB;
        $sql = "SELECT COUNT(DISTINCT ue.userid)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid AND e.status = :enabled AND ue.status = :active
                       AND (ue.timeend = 0 OR ue.timeend > :now)";
        return (int) $DB->count_records_sql($sql, [
            'courseid' => $courseid,
            'enabled' => ENROL_INSTANCE_ENABLED,
            'active' => ENROL_USER_ACTIVE,
            'now' => time(),
        ]);
    }

    /**
     * A course name or summary formatted in its context.
     *
     * @param array $course
     * @return string the formatted name
     */
    private function course_name(array $course): string {
        return format_string($course['fullname'], true, ['context' => \context_course::instance($course['id']), 'escape' => false]);
    }

    /**
     * The "Next cohort" card: the upcoming course with the nearest start date, or null when there is none.
     *
     * @return array|null
     */
    public function get_next_cohort(): ?array {
        $upcoming = $this->get_upcoming();
        if (!$upcoming) {
            return null;
        }
        $course = $upcoming[0];
        $context = \context_course::instance($course['id']);
        $summary = format_text($course['summary'], $course['summaryformat'], ['context' => $context, 'noclean' => false]);
        $summary = shorten_text(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($summary)))), self::EXCERPT);
        $enrolled = $this->count_enrolled((int) $course['id']);
        return [
            'startslabel' => get_string('cardstarts', 'theme_ismos', self::short_date((int) $course['startdate'])),
            'name' => $this->course_name($course),
            'summary' => $summary,
            'hassummary' => $summary !== '',
            'category' => format_string($course['catname'], true, ['context' => \context_course::instance($course['id']), 'escape' => false]),
            // A zero count reads as "nobody is coming", so the card only shows it once someone has enrolled.
            'enrolled' => $enrolled > 0 ? get_string('enrolledcount', 'theme_ismos', $enrolled) : null,
            'code' => $course['code'],
            'url' => (new \moodle_url('/course/view.php', ['id' => $course['id']]))->out(false),
        ];
    }

    /**
     * A short date in the viewer's language: "3 Nov".
     *
     * @param int $time
     * @return string
     */
    public static function short_date(int $time): string {
        return (int) userdate($time, '%d') . ' ' . rtrim(userdate($time, '%b'), '.');
    }

    /**
     * The four facts: students, courses, languages (when courses set any) and the static "live" cell.
     *
     * @return array[] each with value and label
     */
    public function get_facts(): array {
        global $CFG, $DB;
        $students = (int) $DB->count_records_select(
            'user',
            'deleted = 0 AND confirmed = 1 AND suspended = 0 AND id <> :guest AND username <> :guestname',
            ['guest' => (int) $CFG->siteguest, 'guestname' => 'guest']
        );
        $courses = $this->get_courses();
        $codes = array_unique(array_filter(array_column($courses, 'code')));
        sort($codes);
        $facts = [
            ['value' => (string) $students, 'label' => get_string('factstudents', 'theme_ismos')],
            ['value' => (string) count($courses), 'label' => get_string('factcourses', 'theme_ismos')],
        ];
        if ($codes) {
            $facts[] = ['value' => implode(' · ', $codes), 'label' => get_string('factlanguages', 'theme_ismos')];
        }
        $facts[] = ['value' => get_string('factlive', 'theme_ismos'), 'label' => get_string('factlivelabel', 'theme_ismos')];
        return $facts;
    }

    /**
     * The paths: one card per visible top-level category, in the site's order. Any number of categories works the
     * same way: no card depends on its position or on how many there are.
     *
     * @return array[]
     */
    public function get_paths(): array {
        $courses = $this->get_courses();
        // Only categories with at least one visible course become a path: an empty card promises courses that aren't there.
        $categories = [];
        $owncourses = [];
        foreach (\core_course_category::top()->get_children() as $category) {
            $own = array_values(array_filter($courses, fn(array $c): bool => $c['topid'] === (int) $category->id));
            if ($own) {
                $categories[] = $category;
                $owncourses[] = $own;
            }
        }
        $paths = [];
        foreach ($categories as $index => $category) {
            $own = $owncourses[$index];
            $names = [];
            foreach ($this->order_for_path($own) as $course) {
                $names[] = ['name' => $this->course_name($course), 'url' => (new \moodle_url('/course/view.php', ['id' => $course['id']]))->out(false)];
            }
            $description = '';
            if (!empty($category->description)) {
                $description = format_text($category->description, $category->descriptionformat, ['context' => $category->get_context()]);
                $description = shorten_text(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($description)))), self::EXCERPT);
            }
            $paths[] = [
                'name' => $category->get_formatted_name(['escape' => false]),
                'description' => $description,
                'hasdescription' => $description !== '',
                'courses' => array_slice($names, 0, self::PATH_COURSES),
                'hascourses' => !empty($names),
                'url' => (new \moodle_url('/course/index.php', ['categoryid' => $category->id]))->out(false),
            ];
        }
        return $paths;
    }

    /**
     * Courses of a path in display order: upcoming first by start date, then the most recent ones.
     *
     * @param array[] $courses
     * @return array[]
     */
    private function order_for_path(array $courses): array {
        $upcoming = array_filter($courses, fn(array $c): bool => (int) $c['startdate'] >= $this->today);
        $past = array_filter($courses, fn(array $c): bool => (int) $c['startdate'] < $this->today);
        usort($upcoming, fn(array $a, array $b): int => [$a['startdate'], $a['id']] <=> [$b['startdate'], $b['id']]);
        usort($past, fn(array $a, array $b): int => [$b['startdate'], $b['id']] <=> [$a['startdate'], $a['id']]);
        return array_merge($upcoming, $past);
    }

    /**
     * The calendar: upcoming courses, soonest first, at most six.
     *
     * @return array[]
     */
    public function get_calendar(): array {
        $rows = [];
        foreach (array_slice($this->get_upcoming(), 0, self::CALENDAR_MAX) as $course) {
            $time = (int) $course['startdate'];
            $rows[] = [
                'day' => userdate($time, '%d'),
                'month' => \core_text::strtoupper(rtrim(userdate($time, '%b'), '.')),
                'year' => userdate($time, '%Y'),
                'name' => $this->course_name($course),
                'category' => format_string($course['catname'], true, ['context' => \context_course::instance($course['id']), 'escape' => false]),
                'code' => $course['code'],
                'hascode' => $course['code'] !== '',
                'url' => (new \moodle_url('/course/view.php', ['id' => $course['id']]))->out(false),
            ];
        }
        return $rows;
    }

    /**
     * The first testimonials, three at most.
     *
     * @param array[] $testimonials all of them
     * @return array[]
     */
    public static function pick_testimonials(array $testimonials): array {
        return array_slice($testimonials, 0, self::QUOTES);
    }

    /**
     * Split a title on its first comma: the part up to the comma, and the rest for the italic line.
     *
     * @param string $title
     * @return string[] main part and italic part (empty without a comma)
     */
    public static function split_title(string $title): array {
        $pos = strpos($title, ',');
        if ($pos === false) {
            return [$title, ''];
        }
        return [trim(substr($title, 0, $pos + 1)), trim(substr($title, $pos + 1))];
    }
}
