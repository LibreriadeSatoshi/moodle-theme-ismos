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
 * Data for the ISMOS landing page: hero with the next cohort, facts, paths, calendar, steps, testimonials, final band.
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
        $landing = new \theme_ismos\local\landing();
        $testimonials = \theme_ismos\local\landing::pick_testimonials(
            self::parse_testimonials((string) get_config('theme_ismos', 'testimonials'))
        );
        $signupurl = self::get_signup_url();
        [$title, $titleem] = \theme_ismos\local\landing::split_title(get_string('herotitle', 'theme_ismos'));
        $nextcohort = $landing->get_next_cohort();
        $paths = $landing->get_paths();
        $calendar = $landing->get_calendar();
        $courseindex = (new \moodle_url('/course/index.php'))->out(false);
        return [
            'hero' => [
                'label' => get_string('herolabel', 'theme_ismos'),
                'title' => $title,
                'titleem' => $titleem,
                'hastitleem' => $titleem !== '',
                'lead' => get_string('heroleadlive', 'theme_ismos'),
                'cta' => get_string('herocta', 'theme_ismos'),
                'ctaurl' => $courseindex,
                'signup' => get_string('herosignup', 'theme_ismos'),
                'signupurl' => $signupurl,
                'next' => $nextcohort,
                'hasnext' => $nextcohort !== null,
            ],
            'facts' => $landing->get_facts(),
            'paths' => $paths,
            'haspaths' => !empty($paths),
            'calendar' => $calendar,
            'hascalendar' => !empty($calendar),
            'steps' => [
                ['number' => 1, 'title' => get_string('step1title', 'theme_ismos'), 'text' => get_string('step1text', 'theme_ismos')],
                ['number' => 2, 'title' => get_string('step2title', 'theme_ismos'), 'text' => get_string('step2text', 'theme_ismos')],
                ['number' => 3, 'title' => get_string('step3title', 'theme_ismos'), 'text' => get_string('step3text', 'theme_ismos')],
            ],
            'testimonials' => $testimonials,
            'hastestimonials' => !empty($testimonials),
            'final' => [
                'heading' => get_string('finalheading', 'theme_ismos'),
                'cta' => $signupurl ? get_string('finalcta', 'theme_ismos') : get_string('herocta', 'theme_ismos'),
                'url' => $signupurl ?? $courseindex,
            ],
        ];
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
