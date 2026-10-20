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
 * Blog RSS parsing and the cached copy shown on the public home page.
 *
 * The page never fetches the feed itself; the scheduled task does, so a slow
 * or unavailable blog host cannot slow down or break the home page.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class blog_feed {

    /** Plugin config key holding the cached items as JSON. */
    private const CACHE_KEY = 'blogfeedcache';

    /**
     * Parse RSS 2.0 XML into display items, newest first as given by the feed.
     *
     * @param string $xml raw RSS
     * @param int $limit maximum items
     * @return array[] each with 'title', 'url', 'date' (unix time) and 'image' (string|null)
     * @throws \moodle_exception when the XML cannot be parsed or has no channel
     */
    public static function parse(string $xml, int $limit = 3): array {
        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($doc === false || !isset($doc->channel)) {
            throw new \moodle_exception('invalidxml', 'error');
        }

        $items = [];
        foreach ($doc->channel->item as $item) {
            if (count($items) >= $limit) {
                break;
            }
            $url = self::clean_url((string) $item->link);
            $title = trim((string) $item->title);
            if ($url === null || $title === '') {
                continue;
            }
            $items[] = [
                'title' => $title,
                'url' => $url,
                'date' => (int) strtotime((string) $item->pubDate),
                'image' => self::first_image((string) $item->description),
            ];
        }
        return $items;
    }

    /**
     * Save items as the current cache.
     *
     * @param array[] $items output of parse()
     */
    public static function store(array $items): void {
        set_config(self::CACHE_KEY, json_encode(array_values($items), JSON_UNESCAPED_UNICODE), 'theme_readmaster');
    }

    /**
     * Cached items, or an empty array when nothing usable is cached.
     *
     * @return array[]
     */
    public static function cached(): array {
        $json = get_config('theme_readmaster', self::CACHE_KEY);
        $items = is_string($json) ? json_decode($json, true) : null;
        return is_array($items) ? $items : [];
    }

    /**
     * Drop Naver's RSS tracking parameters and reject non-http(s) links.
     *
     * @param string $url
     * @return string|null
     */
    private static function clean_url(string $url): ?string {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) {
            return null;
        }
        return preg_replace('/\?fromRss=true&trackingCode=rss$/', '', $url);
    }

    /**
     * First <img src> in an item description, https only.
     *
     * Naver's RSS thumbnails use type=s3 (a 365px square crop that cuts off text in
     * cover images); type=w2 is the same image at its original 16:9-ish ratio.
     *
     * @param string $html
     * @return string|null
     */
    private static function first_image(string $html): ?string {
        if (!preg_match('/<img[^>]+src="(https:\/\/[^"]+)"/i', $html, $m)) {
            return null;
        }
        $url = html_entity_decode($m[1]);
        if (str_contains($url, '.pstatic.net/')) {
            $url = preg_replace('/([?&]type=)s3\b/', '${1}w2', $url);
        }
        return $url;
    }
}
