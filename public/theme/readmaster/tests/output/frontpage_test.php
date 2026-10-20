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

namespace theme_readmaster\output;

use theme_readmaster\local\brand;

/**
 * Tests for the public home page template context.
 *
 * @package    theme_readmaster
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\theme_readmaster\output\frontpage::class)]
final class frontpage_test extends \advanced_testcase {

    /**
     * Brand with optional link overrides.
     *
     * @param array $links
     * @return brand
     */
    private function brand(array $links = []): brand {
        return brand::from_array([
            'name' => 'Test Academy',
            'shortname' => 'TA',
            'tagline' => 'We read books.',
            'description' => 'Small classes.',
            'colors' => ['primary' => '#10301D', 'accent' => '#F3A92D', 'paper' => '#F7F5EF'],
            'contact' => ['phone' => '010-0000-0000', 'address' => '', 'hours' => ''],
            'links' => $links + [
                'blog' => 'https://blog.test',
                'consult' => 'https://consult.test',
                'leveltest' => '',
            ],
            'programs' => [
                ['title' => 'Reading', 'description' => 'One book a month.'],
                ['title' => 'Exams'],
            ],
        ]);
    }

    /**
     * Export the page context.
     *
     * @param brand $brand
     * @param array $news
     * @return array
     */
    private function export(brand $brand, array $news = []): array {
        global $PAGE;
        return (new frontpage($brand, $news))->export_for_template($PAGE->get_renderer('core'));
    }

    public function test_empty_link_is_null_so_template_hides_it(): void {
        $this->resetAfterTest();
        $context = $this->export($this->brand());
        $this->assertSame('https://consult.test', $context['links']['consult']);
        $this->assertNull($context['links']['leveltest']);
        $this->assertNull($context['links']['platform']);
    }

    public function test_programs_are_numbered(): void {
        $this->resetAfterTest();
        $programs = $this->export($this->brand())['programs'];
        $this->assertSame(['01', '02'], array_column($programs, 'number'));
        $this->assertSame('', $programs[1]['description']);
    }

    public function test_news_section_absent_without_cache(): void {
        $this->resetAfterTest();
        $context = $this->export($this->brand());
        $this->assertFalse($context['hasnews']);
    }

    public function test_news_items_get_formatted_dates(): void {
        $this->resetAfterTest();
        $this->setTimezone('Asia/Seoul');
        $news = [['title' => 'Post', 'url' => 'https://blog.test/1', 'date' => strtotime('2026-10-20 19:07:08 +0900'), 'image' => null]];
        $context = $this->export($this->brand(), $news);
        $this->assertTrue($context['hasnews']);
        $this->assertSame('2026.10.20', $context['news'][0]['datestr']);
    }

    public function test_contact_comes_from_brand(): void {
        $this->resetAfterTest();
        $context = $this->export($this->brand());
        $this->assertTrue($context['hascontact']);
        $this->assertSame('010-0000-0000', $context['contact'][0]['value']);
    }

    public function test_template_renders(): void {
        $this->resetAfterTest();
        global $PAGE;
        $html = $PAGE->get_renderer('core')->render_from_template(
            'theme_readmaster/frontpage_content',
            $this->export($this->brand()),
        );
        $this->assertStringContainsString('Test Academy', $html);
        $this->assertStringContainsString('https://consult.test', $html);
        $this->assertStringNotContainsString('rm-news', $html);
    }
}
