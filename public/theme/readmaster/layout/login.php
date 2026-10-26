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
 * Login layout: Boost's split layout plus brand data for the left panel.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$brand = \theme_readmaster\local\brand::load();

$leftinstructions = !empty($CFG->auth_instructions)
    ? format_text($CFG->auth_instructions, FORMAT_MOODLE, ['context' => context_system::instance()])
    : null;

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'leftinstructions' => $leftinstructions,
    'brand' => [
        'name' => $brand->get('name'),
        'tagline' => $brand->get('tagline'),
        'description' => $brand->get('description') ?? '',
        'logourl' => $OUTPUT->image_url('logomark', 'theme')->out(false),
    ],
];

echo $OUTPUT->render_from_template('theme_boost/login', $templatecontext);
