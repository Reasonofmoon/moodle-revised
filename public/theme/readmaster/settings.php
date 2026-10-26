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

/**
 * Admin settings: optional overrides of brand/profile.json values.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_readmaster\local\brand;

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $brand = brand::load();

    $settings->add(new admin_setting_heading(
        'theme_readmaster/overrides',
        get_string('settings_overrides', 'theme_readmaster'),
        get_string('settings_overrides_desc', 'theme_readmaster'),
    ));

    foreach (brand::OVERRIDABLE as $key) {
        $name = brand::setting_name($key);
        $fallback = (string) ($brand->profile_value($key) ?? '');
        $settings->add(new admin_setting_configtext(
            "theme_readmaster/{$name}",
            get_string($name, 'theme_readmaster'),
            get_string('setting_fallback', 'theme_readmaster', $fallback === '' ? '—' : s($fallback)),
            '',
            str_starts_with($key, 'links.') ? PARAM_URL : PARAM_TEXT,
        ));
    }
}
