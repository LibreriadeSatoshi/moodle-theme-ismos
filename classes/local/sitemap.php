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
 * The XML sitemap (sitemaps.org 0.9) that robots.txt points search engines to.
 *
 * Lists the pages a visitor who is not signed in can open and that belong in search results: the landing, the
 * catalogue and its level pages, and each listable course's public information page (/course/info.php). A
 * visitor who is not signed in is sent from /course/view.php to the login page, so the information page is
 * where search engines learn about a course. The list is the same whoever requests it: it is computed for a
 * visitor who is not signed in, not for the current user.
 *
 * Served from the site root (public/sitemap.php in the campus repository), so it may list any URL on the site.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sitemap {
    /** @var string Capability /course/info.php checks in the course's category. */
    private const LISTCAPABILITY = 'moodle/category:viewcourselist';

    /**
     * Whether the site has public pages to list: not when everyone must sign in, nor during maintenance.
     *
     * @return bool
     */
    public static function available(): bool {
        global $CFG;
        return empty($CFG->forcelogin) && empty($CFG->maintenance_enabled);
    }

    /**
     * Send the sitemap, or 404 when the site requires login and 503 during maintenance.
     */
    public static function serve(): void {
        global $CFG;
        if (!self::available()) {
            if (!empty($CFG->maintenance_enabled)) {
                http_response_code(503);
                header('Retry-After: 3600');
            } else {
                http_response_code(404);
            }
            return;
        }
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo self::xml();
    }

    /**
     * The sitemap document.
     *
     * @return string XML
     */
    public static function xml(): string {
        $courses = self::courses();
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        self::url($xml, new \moodle_url('/'));
        self::url($xml, new \moodle_url('/course/index.php'));
        foreach (self::categories($courses) as $categoryid) {
            self::url($xml, new \moodle_url('/course/index.php', ['categoryid' => $categoryid]));
        }
        foreach ($courses as $course) {
            self::url($xml, new \moodle_url('/course/info.php', ['id' => $course->id]), (int) $course->timemodified);
        }
        $xml->endElement();
        $xml->endDocument();
        return $xml->outputMemory();
    }

    /**
     * Courses whose information page a visitor who is not signed in can open: visible, in a visible category,
     * and listable there. Hiding a category hides its subcategories too (their visible flag is cleared), so
     * checking the course's own category is enough.
     *
     * @return \stdClass[] id, category, category path and timemodified, by course id
     */
    private static function courses(): array {
        global $DB;
        $courses = $DB->get_records_sql(
            'SELECT c.id, c.category, c.timemodified, cc.path
               FROM {course} c
               JOIN {course_categories} cc ON cc.id = c.category
              WHERE c.id <> :siteid AND c.visible = 1 AND cc.visible = 1
           ORDER BY c.id',
            ['siteid' => SITEID]
        );
        $listable = [];
        return array_filter($courses, function (\stdClass $course) use (&$listable): bool {
            // User 0 is the visitor who is not signed in (the not-logged-in role).
            return $listable[$course->category] ??= has_capability(self::LISTCAPABILITY,
                \context_coursecat::instance($course->category), 0);
        });
    }

    /**
     * Catalogue pages worth listing: the categories holding listed courses and the levels above them.
     *
     * @param \stdClass[] $courses as from courses()
     * @return int[] category ids
     */
    private static function categories(array $courses): array {
        $ids = [];
        foreach ($courses as $course) {
            foreach (explode('/', trim((string) $course->path, '/')) as $id) {
                $ids[(int) $id] = (int) $id;
            }
        }
        ksort($ids);
        return array_values($ids);
    }

    /**
     * One <url> entry.
     *
     * @param \XMLWriter $xml
     * @param \moodle_url $url
     * @param int $modified last modification time, 0 when unknown
     */
    private static function url(\XMLWriter $xml, \moodle_url $url, int $modified = 0): void {
        $xml->startElement('url');
        $xml->writeElement('loc', $url->out(false));
        if ($modified > 0) {
            $xml->writeElement('lastmod', gmdate('Y-m-d', $modified));
        }
        $xml->endElement();
    }
}
