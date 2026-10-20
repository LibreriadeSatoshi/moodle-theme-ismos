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

use core_privacy\local\request\writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Privacy provider tests for theme_ismos.
 *
 * @package    theme_ismos
 * @category   test
 * @copyright  2026 ISMOS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Data provider for {@see test_export_user_preferences}.
     *
     * @return array[]
     */
    public static function export_user_preference_provider(): array {
        return [
            'Dark mode on' => [true, 'privacy:darkmodeon'],
            'Dark mode off' => [false, 'privacy:darkmodeoff'],
        ];
    }

    /**
     * The dark mode preference is exported with a description.
     *
     * @param bool $value
     * @param string $expectdescription
     */
    #[DataProvider('export_user_preference_provider')]
    public function test_export_user_preferences(bool $value, string $expectdescription): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference(provider::DARK_MODE_ON, $value, $user);

        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_user::instance($user->id));
        $this->assertTrue($writer->has_any_data());

        $exported = $writer->get_user_preferences('theme_ismos');
        $this->assertCount(1, (array) $exported);
        $this->assertEquals($value, (bool) $exported->{provider::DARK_MODE_ON}->value);
        $this->assertSame(get_string($expectdescription, 'theme_ismos'), $exported->{provider::DARK_MODE_ON}->description);
    }

    /**
     * Nothing is exported when the person never chose a mode.
     */
    public function test_nothing_exported_without_preference(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        provider::export_user_preferences($user->id);
        $this->assertFalse(writer::with_context(\context_user::instance($user->id))->has_any_data());
    }

    /**
     * The preference is declared in the metadata, with a string in both languages.
     */
    public function test_metadata_declares_the_preference(): void {
        $items = provider::get_metadata(new \core_privacy\local\metadata\collection('theme_ismos'))->get_collection();
        $this->assertCount(1, $items);
        $this->assertSame('darkmode-on', $items[0]->get_name());
        $string = [];
        require(__DIR__ . '/../../lang/es/theme_ismos.php');
        foreach (['privacy:darkmodeon', 'privacy:darkmodeoff', 'privacy:metadata:preference:darkmodeon'] as $key) {
            $this->assertNotEmpty($string[$key] ?? '', $key);
        }
    }
}
