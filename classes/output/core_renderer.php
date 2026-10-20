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

use moodle_url;

/**
 * Core renderer for the ISMOS theme: bundled mark, favicon and the dark mode toggle.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Returns the URL of the ISMOS mark file bundled in the theme.
     *
     * @param string $variant 'paper' (white, for dark grounds) or 'ink' (black, for light grounds).
     * @return moodle_url
     */
    public function get_ismos_mark_url(string $variant = 'paper'): moodle_url {
        $variant = $variant === 'ink' ? 'ink' : 'paper';
        return $this->ismos_pix_url("ismos-mark-$variant.svg");
    }

    /**
     * Returns the favicon: the theme's own SVG, not the shared core favicon setting.
     *
     * @return moodle_url
     */
    public function favicon() {
        return $this->ismos_pix_url('favicon.svg');
    }

    /**
     * Adds the apple touch icon and an .ico fallback to the standard head output.
     *
     * @return string
     */
    public function standard_head_html() {
        $html = parent::standard_head_html();
        $html .= \html_writer::empty_tag('link', [
            'rel' => 'alternate icon',
            'href' => $this->ismos_pix_url('favicon.ico')->out(false),
        ]) . "\n";
        $html .= \html_writer::empty_tag('link', [
            'rel' => 'apple-touch-icon',
            'href' => $this->ismos_pix_url('apple-touch-icon.png')->out(false),
        ]) . "\n";
        return $html;
    }

    /**
     * Renders the language menu for the top bar, for logged-in visitors as well as logged-out ones.
     *
     * Boost only puts the menu in the bar for logged-out visitors and moves it into the user menu
     * otherwise. ISMOS keeps the switch always visible.
     *
     * @return string empty when the site offers a single language
     */
    public function ismos_lang_menu(): string {
        $menu = (new \core\output\language_menu($this->page))->export_for_template($this);
        if (empty($menu) || empty($menu['items'])) {
            return '';
        }
        return $this->render_from_template('theme_boost/language_menu', $menu);
    }

    /**
     * Renders the dark mode toggle for the top bar.
     *
     * @return string
     */
    public function ismos_darkmode_toggle(): string {
        $persist = isloggedin() && !isguestuser();
        $checked = $persist && (bool) get_user_preferences('darkmode-on', 0);
        return $this->render_from_template('theme_ismos/darkmode_toggle', [
            'checked' => $checked,
            'persist' => $persist,
        ]);
    }

    /**
     * Static file in the theme's pix folder, served directly (image_url() would turn favicon into .ico).
     *
     * @param string $file File name in pix/.
     * @return moodle_url
     */
    private function ismos_pix_url(string $file): moodle_url {
        return new moodle_url("/theme/ismos/pix/$file");
    }
}
