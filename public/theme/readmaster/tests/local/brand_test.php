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

namespace theme_readmaster\local;

/**
 * Tests for the brand profile loader.
 *
 * @package    theme_readmaster
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\theme_readmaster\local\brand::class)]
final class brand_test extends \advanced_testcase {

    /**
     * A minimal valid profile.
     *
     * @return array
     */
    private function valid_profile(): array {
        return [
            'name' => 'Test Academy',
            'shortname' => 'TA',
            'tagline' => 'We read books.',
            'description' => 'Small classes.',
            'colors' => ['primary' => '#10301D', 'accent' => '#F3A92D', 'paper' => '#F7F5EF'],
            'contact' => ['phone' => '', 'address' => 'Somewhere 1', 'hours' => ''],
            'links' => [
                'blog' => 'https://blog.test',
                'rss' => 'https://blog.test/rss.xml',
                'platform' => '',
                'consult' => 'https://consult.test',
                'leveltest' => '',
                'parent' => '',
            ],
            'programs' => [
                ['title' => 'Reading', 'description' => 'Read a book a month.'],
            ],
        ];
    }

    public function test_bundled_profile_is_valid(): void {
        $brand = brand::load();
        $this->assertNotEmpty($brand->get('name'));
    }

    public function test_from_array_reads_values(): void {
        $brand = brand::from_array($this->valid_profile());
        $this->assertSame('Test Academy', $brand->get('name'));
        $this->assertSame('#10301D', $brand->get('colors.primary'));
        $this->assertSame([['title' => 'Reading', 'description' => 'Read a book a month.']], $brand->programs());
    }

    public function test_missing_required_key_names_the_key(): void {
        $profile = $this->valid_profile();
        unset($profile['colors']['primary']);
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessageMatches('/colors\.primary/');
        brand::from_array($profile);
    }

    public function test_invalid_color_is_rejected(): void {
        $profile = $this->valid_profile();
        $profile['colors']['accent'] = 'orange';
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessageMatches('/colors\.accent/');
        brand::from_array($profile);
    }

    public function test_admin_setting_overrides_profile(): void {
        $this->resetAfterTest();
        set_config('links_consult', 'https://override.test', 'theme_readmaster');
        $brand = brand::from_array($this->valid_profile());
        $this->assertSame('https://override.test', $brand->get('links.consult'));
    }

    public function test_empty_admin_setting_falls_back_to_profile(): void {
        $this->resetAfterTest();
        set_config('links_consult', '', 'theme_readmaster');
        $brand = brand::from_array($this->valid_profile());
        $this->assertSame('https://consult.test', $brand->get('links.consult'));
    }

    public function test_contact_skips_empty_fields(): void {
        $brand = brand::from_array($this->valid_profile());
        $contact = $brand->contact();
        $this->assertCount(1, $contact);
        $this->assertSame('Somewhere 1', $contact[0]['value']);
        $this->assertSame('address', $contact[0]['type']);
    }

    public function test_unknown_key_returns_null(): void {
        $brand = brand::from_array($this->valid_profile());
        $this->assertNull($brand->get('nope.nothing'));
    }
}
