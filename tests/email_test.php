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

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Tests for the ISMOS HTML email template.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class email_test extends \advanced_testcase {
    /**
     * An HTML email sent through email_to_user is wrapped in the ISMOS template.
     */
    public function test_email_uses_ismos_template(): void {
        global $CFG, $PAGE, $SITE;
        $this->resetAfterTest();
        $CFG->theme = 'ismos';
        \theme_reset_all_caches();
        $PAGE->set_course($SITE);
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');

        $to = $this->getDataGenerator()->create_user(['mailformat' => 1]);
        $from = \core_user::get_noreply_user();
        $sink = $this->redirectEmails();
        email_to_user($to, $from, 'Subject line', 'Plain text', '<p>Hello <a href="https://ismos.xyz">there</a></p>');
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $html = quoted_printable_decode($messages[0]->body);

        $this->assertStringContainsString('/theme/ismos/pix/email-logo.png', $html);
        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('ISMOS.XYZ', $html);
        $this->assertStringContainsString($SITE->fullname, $html);
        $this->assertStringContainsString('max-width:600px', str_replace(' ', '', $html));
        $this->assertStringNotContainsString('theme_scholastica', $html);
        $this->assertStringNotContainsString('scholastica', strtolower($html));
    }
}
