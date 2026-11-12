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
 * XML sitemap for search engines: the catalogue and each visible course's information page.
 *
 * Public, like the pages it lists; robots.txt points to it.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- a sitemap of public pages, read without signing in.
define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
echo \theme_ismos\local\sitemap::xml();
