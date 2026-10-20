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

namespace theme_readmaster\task;

use theme_readmaster\local\blog_feed;
use theme_readmaster\local\brand;

/**
 * Hourly download of the blog RSS feed into the home page cache.
 *
 * Any failure keeps the previous cache, so the page shows slightly old posts
 * instead of an empty section.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_blog_feed extends \core\task\scheduled_task {

    /**
     * Task name shown in the admin UI.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_refresh_blog_feed', 'theme_readmaster');
    }

    /**
     * Download, parse and cache the feed.
     */
    public function execute(): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = (string) (brand::load()->get('links.rss') ?? '');
        if ($url === '') {
            mtrace('No blog RSS URL configured; keeping cache.');
            return;
        }

        $xml = download_file_content($url, null, null, false, 10, 5);
        if (!is_string($xml) || $xml === '') {
            mtrace("Could not download {$url}; keeping cache.");
            return;
        }

        try {
            $items = blog_feed::parse($xml);
        } catch (\moodle_exception $e) {
            mtrace("Could not parse {$url}: {$e->getMessage()}; keeping cache.");
            return;
        }

        blog_feed::store($items);
        mtrace('Cached ' . count($items) . ' blog posts.');
    }
}
