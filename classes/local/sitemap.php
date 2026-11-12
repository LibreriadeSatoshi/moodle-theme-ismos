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
 * Lists the catalogue and each visible course's public information page (/course/info.php). The catalogue
 * links courses to /course/view.php, which sends a visitor who is not signed in to the login page, so this is
 * how search engines find the course descriptions. The list is the same whoever requests it: it reads
 * visibility from the database, not from the current user's view.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sitemap {
    /**
     * The sitemap document.
     *
     * @return string XML
     */
    public static function xml(): string {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        self::url($xml, new \moodle_url('/course/index.php'));
        foreach (self::courses() as $course) {
            self::url($xml, new \moodle_url('/course/info.php', ['id' => $course->id]), (int) $course->timemodified);
        }
        $xml->endElement();
        $xml->endDocument();
        return $xml->outputMemory();
    }

    /**
     * Visible courses in visible categories, the front page course excluded. Hiding a category hides its
     * subcategories too (their visible flag is cleared), so checking the course's own category is enough.
     *
     * @return \stdClass[] id and timemodified
     */
    private static function courses(): array {
        global $DB;
        return $DB->get_records_sql(
            'SELECT c.id, c.timemodified
               FROM {course} c
               JOIN {course_categories} cc ON cc.id = c.category
              WHERE c.id <> :siteid AND c.visible = 1 AND cc.visible = 1
           ORDER BY c.id',
            ['siteid' => SITEID]
        );
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
