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
     * Returns the URL of the Ink (black) ISMOS mark, for light grounds.
     *
     * @return moodle_url
     */
    public function get_ismos_mark_url_ink(): moodle_url {
        return $this->get_ismos_mark_url('ink');
    }

    /**
     * The page header with the title, the context and the actions. The course catalogue prints its own heading,
     * so core's (the site name and a settings menu) is left out there.
     *
     * @return string
     */
    public function full_header() {
        if (in_array($this->page->pagetype, ['course-index', 'course-index-category'], true)) {
            return '';
        }
        return parent::full_header();
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
     * Swaps the dashboard welcome line for the theme's own wording (no emoji, no exclamation mark).
     *
     * Core builds the line from 'welcometosite' (first visit) or 'welcomeback'. Both carry an emoji and
     * the first an exclamation mark, so the theme renders its own strings and leaves core untouched.
     *
     * @param string $templatename
     * @param array|\stdClass $context
     * @return string
     */
    public function render_from_template($templatename, $context) {
        if ($templatename === 'core/full_header' && is_object($context) && !empty($context->welcomemessage)) {
            $context->welcomemessage = $this->ismos_welcome_message($context->welcomemessage);
        }
        return parent::render_from_template($templatename, $context);
    }

    /**
     * Returns the theme's welcome line matching core's welcome message.
     *
     * @param \lang_string|string $core the message core prepared
     * @return string
     */
    public function ismos_welcome_message($core): string {
        global $USER;
        $a = (object) ['firstname' => $USER->firstname];
        $first = get_string('welcometosite', 'core', $a);
        $key = (string) $core === $first ? 'welcome' : 'welcomeback';
        return get_string($key, 'theme_ismos', $USER->firstname);
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
        $menu['code'] = strtoupper(explode('_', current_language())[0]);
        return $this->render_from_template('theme_ismos/language_menu', $menu);
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
