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

namespace theme_ismos\output;

/**
 * Data for the ISMOS front page: hero, category browse row and testimonials.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontpage implements \renderable, \templatable {
    /**
     * Export the front page data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $categories = self::get_categories();
        $testimonials = self::parse_testimonials((string) get_config('theme_ismos', 'testimonials'));
        $signupurl = self::get_signup_url();
        return [
            'hero' => [
                'label' => get_string('herolabel', 'theme_ismos'),
                'title' => get_string('herotitle', 'theme_ismos'),
                'lead' => get_string('herolead', 'theme_ismos'),
                'cta' => get_string('herocta', 'theme_ismos'),
                'ctaurl' => (new \moodle_url('/course/index.php'))->out(false),
                'signup' => get_string('herosignup', 'theme_ismos'),
                'signupurl' => $signupurl,
            ],
            'categories' => $categories,
            'hascategories' => !empty($categories),
            'testimonials' => $testimonials,
            'hastestimonials' => !empty($testimonials),
        ];
    }

    /**
     * Top-level categories the viewer can see.
     *
     * @return array list of name and url
     */
    private static function get_categories(): array {
        $categories = [];
        foreach (\core_course_category::top()->get_children() as $category) {
            $categories[] = [
                'name' => $category->get_formatted_name(['escape' => false]),
                'url' => (new \moodle_url('/course/index.php', ['categoryid' => $category->id]))->out(false),
            ];
        }
        return $categories;
    }

    /**
     * The signup URL, or null when the viewer is logged in or self-registration is off.
     *
     * @return string|null
     */
    private static function get_signup_url(): ?string {
        global $CFG;
        require_once($CFG->libdir . '/authlib.php');
        if ((isloggedin() && !isguestuser()) || !signup_is_enabled()) {
            return null;
        }
        return (new \moodle_url('/login/signup.php'))->out(false);
    }

    /**
     * Parse the testimonials setting: one "quote | name" per line, split on the last separator.
     *
     * @param string $raw
     * @return array list of quote and name; lines without a separator or with an empty part are skipped
     */
    public static function parse_testimonials(string $raw): array {
        $items = [];
        foreach (preg_split('/\R/', $raw) as $line) {
            $pos = strrpos($line, '|');
            if ($pos === false) {
                continue;
            }
            $quote = trim(substr($line, 0, $pos));
            $name = trim(substr($line, $pos + 1));
            if ($quote === '' || $name === '') {
                continue;
            }
            $items[] = ['quote' => $quote, 'name' => $name];
        }
        return $items;
    }
}
