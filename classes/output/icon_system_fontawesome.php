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

defined('MOODLE_INTERNAL') || die();

/**
 * Font Awesome icon system with the icons the ISMOS guide bans swapped for neutral ones.
 *
 * The guide bans graduation caps, trophies, medals, crowns, stars, rockets and awards (gamified, off-voice).
 * The meaning is kept: a course is a book, a badge or award is a confirmed check, a rating is a bookmark.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class icon_system_fontawesome extends \core\output\icon_system_fontawesome {
    /** @var string[] Banned Font Awesome icon names (without the fa- prefix) and their neutral replacement. */
    private const REPLACEMENTS = [
        'graduation-cap' => 'book',
        'trophy' => 'circle-check',
        'medal' => 'circle-check',
        'award' => 'circle-check',
        'crown' => 'bookmark',
        'star' => 'bookmark',
        'rocket' => 'arrow-up',
    ];

    #[\Override]
    public function get_core_icon_map() {
        $map = parent::get_core_icon_map();
        $pattern = '/(?<![\w-])fa-(' . implode('|', array_keys(self::REPLACEMENTS)) . ')(?![\w-])/';
        foreach ($map as $key => $value) {
            $map[$key] = preg_replace_callback(
                $pattern,
                fn(array $m): string => 'fa-' . self::REPLACEMENTS[$m[1]],
                $value
            );
        }
        return $map;
    }
}
