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

namespace theme_ismos;

use PHPUnit\Framework\Attributes\CoversClass;
use theme_ismos\local\sitemap;

/**
 * The sitemap search engines are pointed to: the catalogue and each course's public information page.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(sitemap::class)]
final class sitemap_test extends \advanced_testcase {
    /**
     * The sitemap's URLs, read with an XML parser as a search engine would.
     *
     * @return string[]
     */
    private function urls(): array {
        $xml = simplexml_load_string(sitemap::xml());
        $this->assertNotFalse($xml, 'the sitemap is well-formed XML');
        $this->assertSame('urlset', $xml->getName());
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getNamespaces()['']);
        return array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
    }

    public function test_lists_the_catalogue_and_the_information_page_of_each_visible_course(): void {
        global $CFG;
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category();
        $one = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $two = $this->getDataGenerator()->create_course(['category' => $category->id]);

        $urls = $this->urls();

        $this->assertContains($CFG->wwwroot . '/course/index.php', $urls);
        $this->assertContains($CFG->wwwroot . '/course/info.php?id=' . $one->id, $urls);
        $this->assertContains($CFG->wwwroot . '/course/info.php?id=' . $two->id, $urls);
    }

    public function test_hidden_courses_and_courses_in_hidden_categories_are_left_out(): void {
        global $CFG;
        $this->resetAfterTest();
        $shown = $this->getDataGenerator()->create_category();
        $hiddencategory = $this->getDataGenerator()->create_category(['visible' => 0]);
        $visible = $this->getDataGenerator()->create_course(['category' => $shown->id]);
        $hidden = $this->getDataGenerator()->create_course(['category' => $shown->id, 'visible' => 0]);
        $inhidden = $this->getDataGenerator()->create_course(['category' => $hiddencategory->id]);

        $urls = $this->urls();

        $this->assertContains($CFG->wwwroot . '/course/info.php?id=' . $visible->id, $urls);
        $this->assertNotContains($CFG->wwwroot . '/course/info.php?id=' . $hidden->id, $urls);
        $this->assertNotContains($CFG->wwwroot . '/course/info.php?id=' . $inhidden->id, $urls);
    }

    public function test_a_signed_in_admin_requesting_it_sees_the_same_public_list(): void {
        global $CFG;
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category();
        $hidden = $this->getDataGenerator()->create_course(['category' => $category->id, 'visible' => 0]);
        $this->setAdminUser();

        $this->assertNotContains($CFG->wwwroot . '/course/info.php?id=' . $hidden->id, $this->urls());
    }

    public function test_the_front_page_course_is_not_listed(): void {
        global $CFG, $SITE;
        $this->resetAfterTest();

        $this->assertNotContains($CFG->wwwroot . '/course/info.php?id=' . $SITE->id, $this->urls());
    }
}
