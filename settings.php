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
 * Theme ismos settings.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingismos', get_string('configtitle', 'theme_ismos'));

    $page = new admin_settingpage('theme_ismos_frontpage', get_string('frontpagesettings', 'theme_ismos'));
    $setting = new admin_setting_configtextarea(
        'theme_ismos/testimonials',
        get_string('testimonials', 'theme_ismos'),
        get_string('testimonials_desc', 'theme_ismos'),
        theme_ismos_default_testimonials(),
        PARAM_TEXT,
        80,
        12
    );
    $page->add($setting);
    $settings->add($page);

    $page = new admin_settingpage('theme_ismos_maintenance', get_string('maintenancesettings', 'theme_ismos'));
    $setting = new admin_setting_configcheckbox(
        'theme_ismos/neutralmaintenance',
        get_string('maintenanceneutral', 'theme_ismos'),
        get_string('maintenanceneutral_desc', 'theme_ismos'),
        0
    );
    $page->add($setting);
    $settings->add($page);
}
