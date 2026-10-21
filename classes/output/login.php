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
 * Data for the ISMOS login and signup block: the line, the optional lead and the address.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class login implements \renderable, \templatable {
    /**
     * Export the block data. The signup page gets its own line and a lead, and the head of its form column: the
     * sign-in buttons (as on the login page) and the link to log in instead.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        global $PAGE;
        $issignup = $PAGE->pagetype === 'login-signup';
        $data = [
            'line' => get_string($issignup ? 'signupline' : 'loginline', 'theme_ismos'),
            'haslead' => $issignup,
            'lead' => $issignup ? get_string('signuplead', 'theme_ismos') : '',
            'address' => get_string('address', 'theme_ismos'),
            'wordmark' => get_string('wordmark', 'theme_ismos'),
            'issignup' => $issignup,
        ];
        if ($issignup) {
            $loginurl = (new \moodle_url('/login/index.php'))->out(false);
            $data['loginurl'] = $loginurl;
            $data['identityproviders'] = \theme_ismos\local\identity_providers::for_page($output);
            // auth_nostr signs in (and creates the account) from the login page only, so here it links there.
            $data['nostr'] = is_enabled_auth('nostr') ? [
                'url' => $loginurl,
                'iconurl' => $output->image_url('nostr', 'auth_nostr')->out(false),
                'label' => get_string('provider_name', 'auth_nostr'),
            ] : false;
            $data['hasidentityproviders'] = !empty($data['identityproviders']) || !empty($data['nostr']);
        }
        return $data;
    }
}
