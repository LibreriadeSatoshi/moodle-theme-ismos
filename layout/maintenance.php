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
    // The footer mark: this layout's output is not always the theme renderer, so the URL is passed in.
    'footermark' => $CFG->wwwroot . '/theme/ismos/pix/ismos-mark-paper.svg',
];

// The maintenance message page gets its own calm layout; other pages on this layout (installation, upgrade
// progress) keep the core content. Nothing here may touch the database.
if ($PAGE->pagetype === 'maintenance-message') {
    $templatecontext['ismaintenance'] = true;
    $templatecontext['label'] = get_string('maintenancelabel', 'theme_ismos');
    $templatecontext['heading'] = get_string('maintenanceheading', 'theme_ismos');
    if (isset($CFG->maintenance_message) && !html_is_blank($CFG->maintenance_message)) {
        $templatecontext['message'] = $CFG->maintenance_message;
    }
    if (!empty($CFG->supportemail)) {
        $email = s($CFG->supportemail);
        $templatecontext['questions'] = get_string(
            'maintenancequestions',
            'theme_ismos',
            \html_writer::link('mailto:' . $email, $email)
        );
    }
}

echo $OUTPUT->render_from_template('theme_ismos/maintenance', $templatecontext);
