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

use core\output\mustache_template_finder;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Tests for the payment gateway and enrolment fee templates.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class payment_templates_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        global $PAGE, $SITE;
        $PAGE->set_course($SITE);
        $PAGE->set_url('/');
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('ismos');
    }

    /**
     * Render a template through the ismos theme.
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    private function render(string $template, array $context): string {
        global $PAGE;
        return $PAGE->get_renderer('core')->render_from_template($template, $context);
    }

    public function test_theme_overrides_are_chosen(): void {
        foreach (['core_payment/gateway', 'enrol_fee/enrol_page'] as $template) {
            $this->assertStringContainsString(
                '/theme/ismos/templates/',
                mustache_template_finder::get_template_filepath($template, 'ismos')
            );
        }
    }

    public function test_enrol_page_with_named_gateway(): void {
        $html = $this->render('enrol_fee/enrol_page', [
            'cost' => 'USD 10.00',
            'gateway' => ['shortname' => 'btcpay', 'name' => 'BTCPay', 'showname' => true, 'wordmark' => false],
        ]);
        $this->assertStringContainsString('USD 10.00', $html);
        $this->assertStringContainsString('BTCPay', $html);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('paygw-icon-badge', $html);
        $this->assertStringNotContainsString('paygw-icon-badge--wordmark', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function test_enrol_page_wordmark_has_no_visible_name(): void {
        $html = $this->render('enrol_fee/enrol_page', [
            'cost' => 'USD 10.00',
            'gateway' => ['shortname' => 'btcpay', 'name' => 'BTCPay', 'showname' => false, 'wordmark' => true],
        ]);
        $this->assertStringContainsString('paygw-icon-badge--wordmark', $html);
        $this->assertStringNotContainsString('BTCPay', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function test_enrol_page_without_gateway(): void {
        $html = $this->render('enrol_fee/enrol_page', ['cost' => 'USD 10.00', 'gateway' => null]);
        $this->assertStringContainsString('USD 10.00', $html);
        $this->assertStringNotContainsString('paygw-icon-badge', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_gateway_option_keeps_core_hooks(): void {
        $html = $this->render('core_payment/gateway', [
            'shortname' => 'btcpay',
            'name' => 'BTCPay',
            'description' => 'Pay with <b>bitcoin</b>',
            'surcharge' => '3',
            'cost' => '10.00',
            'uniqid' => 'abc',
            'checked' => true,
        ]);
        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('name="payby"', $html);
        $this->assertStringContainsString('id="id-payby-abc-btcpay"', $html);
        $this->assertStringContainsString('for="id-payby-abc-btcpay"', $html);
        $this->assertStringContainsString('data-cost="10.00"', $html);
        $this->assertStringContainsString('data-surcharge="3"', $html);
        $this->assertStringContainsString('value="btcpay"', $html);
        $this->assertStringContainsString('checked="checked"', $html);
        $this->assertStringContainsString('Pay with <b>bitcoin</b>', $html);
        $this->assertStringContainsString('BTCPay', $html);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('ismos-gateway', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('shadow', $html);

        $unchecked = $this->render('core_payment/gateway', ['shortname' => 'btcpay', 'name' => 'BTCPay', 'uniqid' => 'abc']);
        $this->assertStringNotContainsString('checked', $unchecked);
    }

    public function test_stylesheet_is_flat_and_token_driven(): void {
        theme_reset_all_caches();
        $flat = strtolower(preg_replace('/\s+/', '', \theme_config::load('ismos')->get_css_content()));
        $this->assertMatchesRegularExpression('/\.paygw-icon-badge--wordmark\{[^}]*inline-size:110px/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-gateway__radio:checked\+\.ismos-gateway__label\{[^}]*border-color:var\(--ismos-border-strong\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-gateway__radio:focus-visible\+\.ismos-gateway__label\{[^}]*outline:2pxsolidvar\(--ismos-focus\)/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-gateway__label\{[^}]*border-radius:4px/', $flat);
        $this->assertMatchesRegularExpression('/\.ismos-gateway__label\{[^}]*min-block-size:44px/', $flat);
    }
}
