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

/**
 * Theme ismos lib.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the main SCSS content: Boost's default preset plus the ISMOS entry file.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_ismos_get_main_scss_content(theme_config $theme): string {
    global $CFG;

    $scss = file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    $scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/ismos/scss/ismos.scss');
    return $scss;
}

/**
 * Returns SCSS to prepend: Bootstrap variables set from the ISMOS tokens.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_ismos_get_pre_scss(theme_config $theme): string {
    $vars = [
        'primary' => '#000000',
        'link-color' => '#4D01E0',
        'body-color' => '#000000',
        'body-bg' => '#FFFFFF',
        'border-color' => '#DFD8FB',
        'danger' => '#8B3D00',
        'border-radius' => '4px',
        'border-radius-lg' => '8px',
        'border-radius-sm' => '4px',
        'enable-shadows' => 'false',
        'font-family-sans-serif' => '"IBM Plex Sans", system-ui, sans-serif',
        'font-size-base' => '1.0625rem',
        'line-height-base' => '1.647',
        'headings-font-family' => '"Literata", Georgia, serif',
    ];
    $scss = '';
    foreach ($vars as $name => $value) {
        $scss .= "\${$name}: {$value};\n";
    }
    return $scss;
}

/**
 * Returns SCSS to append after the main SCSS. Filled in by later tasks.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_ismos_get_extra_scss(theme_config $theme): string {
    return '';
}

/**
 * Registers the dark mode preference (shared with theme_scholastica; the definitions are identical).
 *
 * @return array
 */
function theme_ismos_user_preferences(): array {
    return [
        'darkmode-on' => [
            'type' => PARAM_BOOL,
            'null' => NULL_NOT_ALLOWED,
            'default' => false,
            'permissioncallback' => [core_user::class, 'is_current_user'],
        ],
    ];
}

/**
 * Adds exactly one mode class to the body: light, dark, or auto (follow the device).
 *
 * @param moodle_page $page The page being initialised.
 */
function theme_ismos_page_init(moodle_page $page): void {
    $mode = 'auto';
    if (isloggedin() && !isguestuser()) {
        $pref = get_user_preferences('darkmode-on', null);
        if ($pref !== null) {
            $mode = $pref ? 'dark' : 'light';
        }
    }
    $page->add_body_class('ismos-mode-' . $mode);
}
