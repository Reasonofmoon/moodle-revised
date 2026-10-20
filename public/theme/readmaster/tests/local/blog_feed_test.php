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
 * Tests for blog feed parsing and caching.
 *
 * @package    theme_readmaster
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\theme_readmaster\local\blog_feed::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\theme_readmaster\task\refresh_blog_feed::class)]
final class blog_feed_test extends \advanced_testcase {

    /**
     * Fixture XML.
     *
     * @return string
     */
    private function fixture(): string {
        return file_get_contents(__DIR__ . '/../fixtures/rss.xml');
    }

    public function test_parse_returns_first_three_valid_items(): void {
        $items = blog_feed::parse($this->fixture());
        $this->assertSame(
            ['First post｜with a full-width bar', 'Second post', 'Third post'],
            array_column($items, 'title'),
        );
    }

    public function test_parse_strips_tracking_params_and_reads_date(): void {
        $first = blog_feed::parse($this->fixture())[0];
        $this->assertSame('https://blog.test/academy/1', $first['url']);
        $this->assertSame(strtotime('2026-10-20 19:07:08 +0900'), $first['date']);
    }

    public function test_parse_keeps_only_https_images(): void {
        $items = blog_feed::parse($this->fixture());
        $this->assertSame('https://img.test/1.png?type=s3', $items[0]['image']);
        $this->assertNull($items[1]['image']);
        $this->assertNull($items[2]['image']);
    }

    public function test_parse_uses_uncropped_naver_thumbnail(): void {
        $xml = str_replace('https://img.test/1.png?type=s3', 'https://blogthumb.pstatic.net/a/b.png?type=s3', $this->fixture());
        $this->assertSame('https://blogthumb.pstatic.net/a/b.png?type=w2', blog_feed::parse($xml)[0]['image']);
    }

    public function test_parse_respects_limit(): void {
        $this->assertCount(1, blog_feed::parse($this->fixture(), 1));
    }

    public function test_parse_rejects_invalid_xml(): void {
        $this->expectException(\moodle_exception::class);
        blog_feed::parse('<html>not rss');
    }

    public function test_cached_is_empty_before_first_store(): void {
        $this->resetAfterTest();
        $this->assertSame([], blog_feed::cached());
    }

    public function test_store_and_cached_round_trip(): void {
        $this->resetAfterTest();
        $items = blog_feed::parse($this->fixture());
        blog_feed::store($items);
        $this->assertSame($items, blog_feed::cached());
    }

    public function test_task_keeps_cache_when_download_fails(): void {
        $this->resetAfterTest();
        $items = blog_feed::parse($this->fixture());
        blog_feed::store($items);
        // Port 9 on localhost is closed, so the download fails fast.
        set_config('links_rss', 'http://127.0.0.1:9/rss.xml', 'theme_readmaster');

        $task = new \theme_readmaster\task\refresh_blog_feed();
        $this->expectOutputRegex('/keeping cache/');
        $task->execute();
        $this->assertDebuggingCalled();

        $this->assertSame($items, blog_feed::cached());
    }
}
