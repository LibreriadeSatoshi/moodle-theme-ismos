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

namespace theme_ismos\local;

/**
 * The sign-in buttons of the login and signup pages, in the order students use them.
 *
 * Google first (most students sign in with it), then GitHub, then any other provider. The first button is the
 * wide "Continue with …" one; the rest share a row, each with its name.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class identity_providers {
    /** @var string[] Provider name fragments, most used first. */
    private const ORDER = ['google', 'github'];

    /**
     * Order identity providers (as prepared for output by core) and label the first one.
     *
     * @param array $providers Providers from \auth_plugin_base::prepare_identity_providers_for_output().
     * @return array The providers, each with `label` (what the button says) and `primary` (the first one).
     */
    public static function ordered(array $providers): array {
        $rank = static function (array $provider): int {
            $name = \core_text::strtolower((string) ($provider['name'] ?? ''));
            foreach (self::ORDER as $index => $fragment) {
                if (str_contains($name, $fragment)) {
                    return $index;
                }
            }
            return count(self::ORDER);
        };
        // PHP's sort is stable, so providers of equal rank keep the site's order.
        usort($providers, static fn(array $a, array $b): int => $rank($a) <=> $rank($b));
        foreach ($providers as $index => &$provider) {
            $provider['primary'] = $index === 0;
            $provider['label'] = $index === 0
                ? get_string('continuewith', 'theme_ismos', $provider['name'])
                : $provider['name'];
        }
        return $providers;
    }

    /**
     * The site's identity providers, ordered, for pages that are not the login page (signup).
     *
     * @param \renderer_base $output
     * @return array
     */
    public static function for_page(\renderer_base $output): array {
        $providers = \auth_plugin_base::get_identity_providers(get_enabled_auth_plugins());
        return self::ordered(\auth_plugin_base::prepare_identity_providers_for_output($providers, $output));
    }
}
