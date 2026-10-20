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

/**
 * Core renderer: the bundled brand logo and favicon are used unless an admin uploads others.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Admin-uploaded logo, else the theme logo.
     *
     * @param int|null $maxwidth
     * @param int $maxheight
     * @return \core\url
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        return parent::get_logo_url($maxwidth, $maxheight) ?: $this->image_url('logo', 'theme');
    }

    /**
     * Admin-uploaded compact logo, else the theme logo (shown in the navbar).
     *
     * @param int $maxwidth
     * @param int $maxheight
     * @return \core\url
     */
    public function get_compact_logo_url($maxwidth = 300, $maxheight = 300) {
        return parent::get_compact_logo_url($maxwidth, $maxheight) ?: $this->image_url('logomark', 'theme');
    }

    /**
     * Admin-uploaded favicon, else the theme favicon.
     *
     * @return \core\url
     */
    public function favicon() {
        if (get_config('core_admin', 'favicon')) {
            return parent::favicon();
        }
        return $this->image_url('favicon', 'theme');
    }
}
