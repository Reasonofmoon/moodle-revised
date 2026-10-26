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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use theme_readmaster\local\brand;

/**
 * Template context for the public home page (theme_readmaster/frontpage_content).
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontpage implements renderable, templatable {

    /** Links shown on the page; empty values become null so the template hides them. */
    private const LINKS = ['blog', 'platform', 'parent', 'consult', 'leveltest'];

    /**
     * Constructor.
     *
     * @param brand $brand academy profile
     * @param array[] $news cached blog items from blog_feed::cached()
     */
    public function __construct(
        /** @var brand academy profile */
        private readonly brand $brand,
        /** @var array[] cached blog items */
        private readonly array $news,
    ) {
    }

    /**
     * Build the template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $links = [];
        foreach (self::LINKS as $key) {
            $value = trim((string) ($this->brand->get("links.{$key}") ?? ''));
            $links[$key] = $value === '' ? null : $value;
        }

        $programs = [];
        foreach ($this->brand->programs() as $i => $program) {
            $programs[] = $program + ['number' => sprintf('%02d', $i + 1)];
        }

        $news = array_map(fn(array $item): array => [
            'title' => $item['title'],
            'url' => $item['url'],
            'image' => $item['image'] ?? null,
            'datestr' => userdate($item['date'], '%Y.%m.%d'),
        ], $this->news);

        $contact = $this->brand->contact();

        return [
            'name' => $this->brand->get('name'),
            'shortname' => $this->brand->get('shortname'),
            'tagline' => $this->brand->get('tagline'),
            'description' => $this->brand->get('description') ?? '',
            'logourl' => $output->image_url('logo', 'theme')->out(false),
            'markurl' => $output->image_url('logomark', 'theme')->out(false),
            'loginurl' => (new \core\url('/login/index.php'))->out(false),
            'links' => $links,
            'hasctas' => $links['consult'] !== null || $links['leveltest'] !== null,
            'programs' => $programs,
            'hasprograms' => !empty($programs),
            'news' => $news,
            'hasnews' => !empty($news),
            'contact' => $contact,
            'hascontact' => !empty($contact),
            'year' => userdate(time(), '%Y'),
        ];
    }
}
