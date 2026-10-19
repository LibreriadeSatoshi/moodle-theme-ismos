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
 * Returns SCSS to prepend (variables). Filled in by later tasks.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_ismos_get_pre_scss(theme_config $theme): string {
    return '';
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
