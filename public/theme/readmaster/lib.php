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
 * Theme callbacks.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/theme/boost/lib.php');

/** Theme SCSS partials, in load order. */
const THEME_READMASTER_SCSS_FILES = ['fonts', 'brand', 'frontpage', 'login'];

/**
 * Brand variables, prepended before Bootstrap so they replace its defaults.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_readmaster_get_pre_scss($theme): string {
    global $CFG;

    $brand = \theme_readmaster\local\brand::load();
    $fontroot = $CFG->wwwroot . '/theme/' . $theme->name . '/fonts';

    $scss = theme_boost_get_pre_scss($theme);
    $scss .= '$primary: ' . $brand->get('colors.primary') . ";\n";
    $scss .= '$rm-accent: ' . $brand->get('colors.accent') . ";\n";
    $scss .= '$rm-paper: ' . $brand->get('colors.paper') . ";\n";
    $scss .= '$rm-fontroot: "' . $fontroot . "\";\n";
    $scss .= '$font-family-sans-serif: "Noto Sans KR", "Pretendard Variable", Pretendard, -apple-system, BlinkMacSystemFont, '
        . "\"Apple SD Gothic Neo\", \"Malgun Gothic\", sans-serif;\n";
    $scss .= '$rm-font-serif: "Noto Sans KR", sans-serif;' . "\n";
    $scss .= '$headings-font-family: "Noto Sans KR", sans-serif;' . "\n";
    $scss .= '$headings-font-weight: 700;' . "\n";
    return $scss;
}

/**
 * Boost's preset followed by this theme's partials.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_readmaster_get_main_scss_content($theme): string {
    $scss = theme_boost_get_main_scss_content($theme);
    foreach (THEME_READMASTER_SCSS_FILES as $name) {
        $scss .= "\n" . file_get_contents(__DIR__ . "/scss/_{$name}.scss");
    }
    return $scss;
}

/**
 * Extra SCSS, appended after Boost's (Moodle runs every parent's callback first).
 *
 * Boost puts a stock photo and an "AI-generated image" caption on the login panel;
 * the brand panel replaces both, so they are reset here with the same selectors.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_readmaster_get_extra_scss($theme): string {
    $loginbgurl = $theme->image_url('login_background', 'theme');
    $scss = "body.pagelayout-login #page .login-layout-left { background-image: url('$loginbgurl'); background-size: cover; background-position: center; }\n";
    $scss .= 'body.pagelayout-login #page .login-layout-left::after { content: none; }' . "\n";
    $scss .= 'body.pagelayout-login .login-layout-left-content.rm-login-panel { background-color: rgba(16, 48, 29, 0.78); backdrop-filter: blur(8px); padding: 2.25rem; border-radius: 18px; border: 1px solid rgba(243, 169, 45, 0.35); }' . "\n";
    return $scss . (string) ($theme->settings->scss ?? '');
}
