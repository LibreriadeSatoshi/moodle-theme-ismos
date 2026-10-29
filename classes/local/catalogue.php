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
 * Data for the ISMOS course catalogue (course/index.php and its category pages) and its course search
 * (course/search.php).
 *
 * Levels are the visible top-level categories that hold at least one visible course. One query reads the visible
 * courses with their context, one reads the enrolment counts and one the first course contact of each course, so a
 * card costs no query of its own.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class catalogue {
    /** @var int Enrolments shown on a card from this number on. */
    public const ENROLLED_MIN = 5;

    /** @var int Characters kept from a summary. */
    private const EXCERPT = 200;

    /** @var int Start of today: courses starting from here on are upcoming. */
    private int $today;

    /**
     * Constructor.
     *
     * @param int|null $now the current time, for tests
     */
    public function __construct(?int $now = null) {
        $this->today = usergetmidnight($now ?? time());
    }

    /**
     * Visible courses in visible categories with the data a card needs; contexts are preloaded.
     *
     * @return \stdClass[] by course id
     */
    private function get_courses(): array {
        global $DB, $SITE;
        $ctxfields = \context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT c.id, c.category, c.fullname, c.summary, c.summaryformat, c.startdate, c.lang, cc.path, cc.name AS catname,
                       $ctxfields
                  FROM {course} c
                  JOIN {course_categories} cc ON cc.id = c.category
                  JOIN {context} ctx ON ctx.contextlevel = :ctxlevel AND ctx.instanceid = c.id
                 WHERE c.id <> :site AND c.visible = 1 AND cc.visible = 1";
        $courses = $DB->get_records_sql($sql, ['ctxlevel' => CONTEXT_COURSE, 'site' => $SITE->id]);
        foreach ($courses as $course) {
            \context_helper::preload_from_record($course);
            $parts = array_values(array_filter(explode('/', $course->path)));
            $course->topid = (int) ($parts[0] ?? $course->category);
            $course->pathids = array_map('intval', $parts);
        }
        return $courses;
    }

    /**
     * Active enrolments per course.
     *
     * @return int[] by course id
     */
    private function get_enrolled_counts(): array {
        global $DB;
        $sql = "SELECT e.courseid, COUNT(DISTINCT ue.userid) AS total
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.status = :enabled AND ue.status = :active AND (ue.timeend = 0 OR ue.timeend > :now)
              GROUP BY e.courseid";
        $counts = [];
        foreach ($DB->get_records_sql($sql, ['enabled' => ENROL_INSTANCE_ENABLED, 'active' => ENROL_USER_ACTIVE, 'now' => time()]) as $row) {
            $counts[(int) $row->courseid] = (int) $row->total;
        }
        return $counts;
    }

    /**
     * The first course contact of each course: the first of the contact roles in their sort order.
     *
     * @return \stdClass[] users by course id
     */
    private function get_teachers(): array {
        global $CFG, $DB;
        $roleids = array_filter(array_map('intval', explode(',', (string) ($CFG->coursecontact ?? ''))));
        if (!$roleids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
        $userfields = 'u.id, u.picture, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, ' .
            'u.alternatename, u.imagealt, u.email';
        $sql = "SELECT ra.id AS raid, ctx.instanceid AS courseid, $userfields
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :ctxlevel
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {user} u ON u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0
                 WHERE ra.roleid $insql
              ORDER BY r.sortorder ASC, ra.id ASC";
        $teachers = [];
        $rows = $DB->get_recordset_sql($sql, $params + ['ctxlevel' => CONTEXT_COURSE]);
        foreach ($rows as $row) {
            if (!isset($teachers[$row->courseid])) {
                $teachers[$row->courseid] = $row;
            }
        }
        $rows->close();
        return $teachers;
    }

    /**
     * The cover image of each course: the first image in its overview files, as core finds it. Core keeps the answer in
     * its course_image cache, so one call reads all of them.
     *
     * @param int[] $courseids
     * @return string[] image URL by course id; courses without an image are left out
     */
    private function get_images(array $courseids): array {
        if (!$courseids) {
            return [];
        }
        $images = [];
        foreach (\cache::make('core', 'course_image')->get_many($courseids) as $id => $path) {
            if (is_string($path) && $path !== '') {
                $images[(int) $id] = (new \moodle_url($path))->out(false);
            }
        }
        return $images;
    }

    /**
     * A plain text excerpt of formatted text.
     *
     * @param string $text
     * @param int $format
     * @param \context $context
     * @param int $max
     * @return string
     */
    private function excerpt(string $text, int $format, \context $context, int $max): string {
        if (trim($text) === '') {
            return '';
        }
        $text = format_text($text, $format, ['context' => $context, 'noclean' => false]);
        return shorten_text(card::plain_text($text), $max);
    }

    /**
     * The initials of a name: first letters of the first and the last word.
     *
     * @param string $name
     * @return string
     */
    public static function initials(string $name): string {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return '';
        }
        $first = \core_text::strtoupper(\core_text::substr($words[0], 0, 1));
        return count($words) > 1 ? $first . \core_text::strtoupper(\core_text::substr(end($words), 0, 1)) : $first;
    }

    /**
     * Build the catalogue.
     *
     * @param int $categoryid 0 for all courses; a top-level category for one level; a subcategory for its courses
     * @return array
     */
    public function export(int $categoryid = 0): array {
        $courses = $this->get_courses();
        $counts = $this->get_enrolled_counts();
        $teachers = $this->get_teachers();
        $images = $this->get_images(array_keys($courses));
        [$levels, $bylevel] = $this->levels($courses);

        // The selected category: its top-level level, and the filter inside it.
        $selected = null;
        $activetop = 0;
        if ($categoryid > 0) {
            $selected = \core_course_category::get($categoryid, IGNORE_MISSING);
            if ($selected) {
                $activetop = (int) (array_values(array_filter(explode('/', $selected->path)))[0] ?? $selected->id);
            }
        }

        $showlevels = $selected ? array_values(array_filter($levels, fn($c) => (int) $c->id === $activetop)) : $levels;
        $tabs = $this->tabs($levels, $bylevel, count($courses), $selected ? $activetop : null);
        $sections = [];
        foreach ($levels as $cat) {
            if (!in_array($cat, $showlevels, true)) {
                continue;
            }
            $pool = $bylevel[(int) $cat->id];
            if ($selected && (int) $selected->id !== (int) $cat->id) {
                $pool = array_filter($pool, fn($c) => in_array((int) $selected->id, $c->pathids, true));
            }
            $cards = [];
            foreach ($this->order($pool) as $course) {
                $cards[] = $this->card($course, $counts, $teachers, $images);
            }
            // On the level's own page the description is the lead under the title; it is not repeated.
            $description = ($selected && (int) $selected->id === (int) $cat->id) ? '' :
                $this->excerpt((string) $cat->description, (int) $cat->descriptionformat, $cat->get_context(), 300);
            $sections[] = [
                'name' => $cat->get_formatted_name(['escape' => false]),
                'plain' => false,
                // The level's own page already has its name as the page title; a subcategory page keeps the level name.
                'showname' => !($selected && (int) $selected->id === (int) $cat->id),
                'description' => $description,
                'hasdescription' => $description !== '',
                'url' => (new \moodle_url('/course/index.php', ['categoryid' => $cat->id]))->out(false),
                'showonly' => !$selected,
                'cards' => $cards,
                'hascards' => !empty($cards),
            ];
        }

        $total = count($courses);
        if ($selected) {
            $title = $selected->get_formatted_name(['escape' => false]);
            $lead = $this->excerpt((string) $selected->description, (int) $selected->descriptionformat, $selected->get_context(), 300);
        } else {
            $title = get_string('catalogtitle', 'theme_ismos');
            $lead = get_string('cataloglead', 'theme_ismos', ['courses' => $total, 'categories' => count($levels)]);
        }
        return [
            'title' => $title,
            'lead' => $lead,
            'haslead' => $lead !== '',
            'tabs' => $tabs,
            'hastabs' => count($levels) > 0,
            'sections' => $sections,
            'hassections' => !empty($sections),
            'empty' => get_string('catalogempty', 'theme_ismos'),
            'search' => '',
        ] + $this->common();
    }

    /**
     * Build the search results: the catalogue's heading, tabs (none active) and search box with the query, and one grid
     * of the catalogue's courses that core's course search finds for it, in the catalogue's order.
     *
     * @param string $search the search words
     * @return array
     */
    public function export_search(string $search): array {
        $courses = $this->get_courses();
        [$levels, $bylevel] = $this->levels($courses);
        $found = \core_course_category::search_courses(['search' => $search], ['idonly' => true]);
        $matches = array_intersect_key($courses, array_flip($found));
        $cards = [];
        if ($matches) {
            $counts = $this->get_enrolled_counts();
            $teachers = $this->get_teachers();
            $images = $this->get_images(array_keys($matches));
            foreach ($this->order($matches) as $course) {
                $cards[] = $this->card($course, $counts, $teachers, $images);
            }
        }
        $name = get_string('searchresults');
        $sections = $cards ? [[
            'name' => $name,
            'plain' => true,
            'showname' => false,
            'description' => '',
            'hasdescription' => false,
            'url' => '',
            'showonly' => false,
            'cards' => $cards,
            'hascards' => true,
        ]] : [];
        $count = count($cards);
        $lead = $count ? get_string($count === 1 ? 'catalogsearchone' : 'catalogsearchmany', 'theme_ismos', $count)
            : get_string('catalogsearchnone', 'theme_ismos');
        return [
            'title' => get_string('catalogsearchtitle', 'theme_ismos', $search),
            'lead' => $lead,
            'haslead' => true,
            'browse' => get_string('catalogseeall', 'theme_ismos'),
            'browseurl' => (new \moodle_url('/course/index.php'))->out(false),
            'tabs' => $this->tabs($levels, $bylevel, count($courses), -1),
            'hastabs' => count($levels) > 0,
            'sections' => $sections,
            'hassections' => !empty($sections),
            'empty' => '',
            'search' => $search,
        ] + $this->common();
    }

    /**
     * Whether this request is a plain course search that the catalogue shows: course/search.php with search words and
     * no other criteria. Searches by tag, block or activity keep core's page.
     *
     * @param \moodle_page $page
     * @return bool
     */
    public static function is_search_page(\moodle_page $page): bool {
        if ($page->pagetype !== 'course-search') {
            return false;
        }
        return self::search_words() !== '' && !self::other_criteria();
    }

    /**
     * Whether this request is course/search.php without any criteria, which core shows as a bare search form.
     *
     * @param \moodle_page $page
     * @return bool
     */
    public static function is_empty_search(\moodle_page $page): bool {
        return $page->pagetype === 'course-search' && self::search_words() === '' && !self::other_criteria();
    }

    /**
     * The search words of course/search.php, read and cleaned as it does.
     *
     * @return string
     */
    public static function search_words(): string {
        $search = optional_param('q', '', PARAM_RAW) ?: optional_param('search', '', PARAM_RAW);
        return trim(strip_tags($search));
    }

    /**
     * Whether course/search.php has criteria besides the search words.
     *
     * @return bool
     */
    private static function other_criteria(): bool {
        return (bool) (optional_param('blocklist', 0, PARAM_INT) || optional_param('modulelist', '', PARAM_PLUGIN) ||
            optional_param('tagid', 0, PARAM_INT));
    }

    /**
     * Levels: visible top-level categories with at least one visible course, and the courses of each.
     *
     * @param \stdClass[] $courses
     * @return array [\core_course_category[] levels, \stdClass[][] courses by level id]
     */
    private function levels(array $courses): array {
        $bylevel = [];
        foreach ($courses as $course) {
            $bylevel[$course->topid][] = $course;
        }
        $levels = [];
        foreach (\core_course_category::top()->get_children() as $cat) {
            if (!empty($bylevel[(int) $cat->id])) {
                $levels[] = $cat;
            }
        }
        return [$levels, $bylevel];
    }

    /**
     * The tabs: "All", then one per level.
     *
     * @param \core_course_category[] $levels
     * @param \stdClass[][] $bylevel
     * @param int $total
     * @param int|null $active the active level id; null for "All", -1 for none
     * @return array
     */
    private function tabs(array $levels, array $bylevel, int $total, ?int $active): array {
        $tabs = [['name' => get_string('catalogall', 'theme_ismos'), 'count' => $total,
            'url' => (new \moodle_url('/course/index.php'))->out(false), 'active' => $active === null]];
        foreach ($levels as $cat) {
            $tabs[] = [
                'name' => $cat->get_formatted_name(['escape' => false]),
                'count' => count($bylevel[(int) $cat->id]),
                'url' => (new \moodle_url('/course/index.php', ['categoryid' => $cat->id]))->out(false),
                'active' => (int) $cat->id === $active,
            ];
        }
        return $tabs;
    }

    /**
     * What the catalogue and the search share: the search URL and the "Manage courses" link.
     *
     * @return array
     */
    private function common(): array {
        $syscontext = \context_system::instance();
        $canmanage = has_capability('moodle/category:manage', $syscontext) || has_capability('moodle/course:create', $syscontext);
        return [
            'searchurl' => (new \moodle_url('/course/search.php'))->out(false),
            'canmanage' => $canmanage,
            'manageurl' => (new \moodle_url('/course/management.php'))->out(false),
        ];
    }

    /**
     * Upcoming courses first (start date ascending), then the past ones (start date descending).
     *
     * @param \stdClass[] $courses
     * @return \stdClass[]
     */
    private function order(array $courses): array {
        $upcoming = array_filter($courses, fn($c) => (int) $c->startdate >= $this->today);
        $past = array_filter($courses, fn($c) => (int) $c->startdate < $this->today);
        usort($upcoming, fn($a, $b) => [$a->startdate, $a->id] <=> [$b->startdate, $b->id]);
        usort($past, fn($a, $b) => [$b->startdate, $b->id] <=> [$a->startdate, $a->id]);
        return array_merge($upcoming, $past);
    }

    /**
     * The data of one course card.
     *
     * @param \stdClass $course
     * @param int[] $counts enrolments by course id
     * @param \stdClass[] $teachers first contact by course id
     * @param string[] $images cover image URL by course id
     * @return array
     */
    private function card(\stdClass $course, array $counts, array $teachers, array $images): array {
        global $PAGE;
        $context = \context_course::instance($course->id);
        $upcoming = (int) $course->startdate >= $this->today;
        $when = '';
        if ($course->startdate) {
            $when = $upcoming
                ? get_string('cardstarts', 'theme_ismos', landing::short_date((int) $course->startdate))
                : \core_text::strtoupper(rtrim(userdate($course->startdate, '%b'), '.')) . ' ' . userdate($course->startdate, '%Y');
        }
        $teacher = null;
        if (isset($teachers[$course->id])) {
            $user = $teachers[$course->id];
            $name = fullname($user);
            $picture = null;
            if (!empty($user->picture)) {
                $picture = (new \user_picture($user))->get_url($PAGE)->out(false);
            }
            $teacher = ['name' => $name, 'initials' => self::initials($name), 'picture' => $picture, 'haspicture' => $picture !== null];
        }
        $enrolled = $counts[(int) $course->id] ?? 0;
        $image = $images[(int) $course->id] ?? '';
        $code = landing::language_code((string) $course->lang);
        return [
            'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'image' => $image,
            'hasimage' => $image !== '',
            'name' => format_string($course->fullname, true, ['context' => $context, 'escape' => false]),
            'summary' => $this->excerpt((string) $course->summary, (int) $course->summaryformat, $context, self::EXCERPT),
            'when' => $when,
            'haswhen' => $when !== '',
            'open' => $upcoming && $course->startdate,
            'past' => !$upcoming,
            'code' => $code,
            'hascode' => $code !== '',
            'teacher' => $teacher,
            'hasteacher' => $teacher !== null,
            'enrolled' => $enrolled >= self::ENROLLED_MIN ? get_string('enrolledcount', 'theme_ismos', $enrolled) : '',
            'hasenrolled' => $enrolled >= self::ENROLLED_MIN,
            'hasfoot' => $teacher !== null || $enrolled >= self::ENROLLED_MIN,
        ];
    }
}
