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

namespace theme_ismos\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for theme_ismos.
 *
 * @package    theme_ismos
 * @copyright  2026 ISMOS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,
    // This plugin has a sitewide user preference to export.
    \core_privacy\local\request\user_preference_provider {
    /** The user preference for Hacker (dark) mode. */
    const DARK_MODE_ON = 'darkmode-on';

    /**
     * Returns meta data about this system.
     *
     * @param collection $items The initialised item collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_user_preference(self::DARK_MODE_ON, 'privacy:metadata:preference:darkmodeon');
        return $items;
    }

    /**
     * Store all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid): void {
        $darkmodepref = get_user_preferences(self::DARK_MODE_ON, null, $userid);
        if ($darkmodepref === null) {
            return;
        }
        $description = $darkmodepref ? get_string('privacy:darkmodeon', 'theme_ismos')
            : get_string('privacy:darkmodeoff', 'theme_ismos');
        writer::export_user_preference('theme_ismos', self::DARK_MODE_ON, $darkmodepref, $description);
    }
}
