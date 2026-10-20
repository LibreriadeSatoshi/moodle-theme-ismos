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
 * ISMOS maintenance layout. Runs without the database, so it uses direct static URLs for the mark.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$templatecontext = [
    // The context cannot be passed to format_string: this layout is also used during installation.
    'sitename' => format_string($SITE->shortname, true, ['escape' => false]),
    'output' => $OUTPUT,
    'markink' => $CFG->wwwroot . '/theme/ismos/pix/ismos-mark-ink.svg',
    'markpaper' => $CFG->wwwroot . '/theme/ismos/pix/ismos-mark-paper.svg',
    'homeurl' => $CFG->wwwroot . '/',
    'wordmark' => get_string('wordmark', 'theme_ismos'),
];

echo $OUTPUT->render_from_template('theme_ismos/maintenance', $templatecontext);
