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
 * Site home layout: public brand page for visitors, Boost drawers for logged-in users.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Logged-in users normally get redirected to the dashboard (defaulthomepage);
// if they reach site home anyway (e.g. ?redirect=0 or editing), show the normal layout.
if (isloggedin() && !isguestuser()) {
    require($CFG->dirroot . '/theme/boost/layout/drawers.php');
    return;
}

$home = new \theme_readmaster\output\frontpage(
    \theme_readmaster\local\brand::load(),
    \theme_readmaster\local\blog_feed::cached(),
);

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(['rm-home-body']),
    'home' => $home->export_for_template($OUTPUT),
];

echo $OUTPUT->render_from_template('theme_readmaster/frontpage', $templatecontext);
