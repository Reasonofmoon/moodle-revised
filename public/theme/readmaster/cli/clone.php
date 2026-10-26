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
 * Copy this theme to a new theme for another academy.
 *
 * Usage (inside the web container, from the Moodle root):
 *   php public/theme/<this theme>/cli/clone.php --name=sunrise --displayname="선라이즈 영어학원"
 *
 * The copy keeps all code, fonts and templates, renames the component, and resets
 * brand/profile.json from brand/profile.template.json. Logo files are copied as
 * placeholders and must be replaced.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    ['name' => '', 'displayname' => '', 'help' => false],
    ['h' => 'help'],
);

$self = basename(dirname(__DIR__));
$usage = "Copy theme_{$self} to a new theme for another academy.

Options:
  --name=NAME            New theme name: lowercase letters and digits, starts with a letter (e.g. sunrise)
  --displayname=TEXT     Name shown in the theme selector (defaults to NAME)
  -h, --help             Show this help

Example:
  php public/theme/{$self}/cli/clone.php --name=sunrise --displayname=\"선라이즈 영어학원\"
";

if ($options['help'] || $unrecognized) {
    cli_writeln($usage);
    exit($unrecognized ? 1 : 0);
}

$name = (string) $options['name'];
if (!preg_match('/^[a-z][a-z0-9]{1,29}$/', $name)) {
    cli_error("--name must be 2-30 lowercase letters/digits starting with a letter.\n\n" . $usage);
}
$displayname = trim((string) $options['displayname']) ?: $name;

$source = dirname(__DIR__);
$sourcename = basename($source);
$target = dirname($source) . '/' . $name;
if ($name === $sourcename || file_exists($target)) {
    cli_error("Theme directory already exists: {$target}");
}

/** Files whose contents are rewritten. Everything else (fonts, images) is copied as-is. */
const CLONE_TEXT_EXTENSIONS = ['php', 'mustache', 'scss', 'json', 'xml', 'md', 'txt', 'js'];

$replacements = [
    "theme_{$sourcename}" => "theme_{$name}",
    'THEME_' . strtoupper($sourcename) . '_' => 'THEME_' . strtoupper($name) . '_',
    "\$THEME->name = '{$sourcename}';" => "\$THEME->name = '{$name}';",
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);

$copied = 0;
foreach ($iterator as $file) {
    $relative = substr($file->getPathname(), strlen($source) + 1);
    $relative = str_replace("theme_{$sourcename}", "theme_{$name}", $relative);
    $destination = $target . '/' . $relative;

    if ($file->isDir()) {
        check_dir_exists($destination, true, true);
        continue;
    }

    $extension = strtolower($file->getExtension());
    if (in_array($extension, CLONE_TEXT_EXTENSIONS, true)) {
        $content = strtr(file_get_contents($file->getPathname()), $replacements);
        file_put_contents($destination, $content);
    } else {
        copy($file->getPathname(), $destination);
    }
    $copied++;
}

// Fresh, obviously-unfinished brand profile for the new academy.
copy("{$source}/brand/profile.template.json", "{$target}/brand/profile.json");

// Theme selector names.
foreach (glob("{$target}/lang/*/theme_{$name}.php") as $langfile) {
    $content = preg_replace(
        "/\\\$string\\['pluginname'\\] = '[^']*';/",
        "\$string['pluginname'] = '" . addcslashes($displayname, "'\\") . "';",
        file_get_contents($langfile),
    );
    file_put_contents($langfile, $content);
}

// New plugin starts at today's version.
$versionfile = "{$target}/version.php";
$content = preg_replace('/\$plugin->version\s*=\s*\d+;/', '$plugin->version   = ' . date('Ymd') . '00;', file_get_contents($versionfile));
$content = preg_replace("/\\\$plugin->release\s*=\s*'[^']*';/", "\$plugin->release   = '0.1.0';", $content);
file_put_contents($versionfile, $content);

cli_writeln("Created theme_{$name} ({$copied} files) at {$target}");
cli_writeln('');
cli_writeln('Next steps:');
cli_writeln("  1. Edit {$target}/brand/profile.json (every value marked CHANGE ME).");
cli_writeln("  2. Replace {$target}/pix/logo.png, pix/logomark.png and pix/favicon.ico.");
cli_writeln('  3. php admin/cli/upgrade.php --non-interactive --allow-unstable');
cli_writeln("  4. Set theme = {$name} (setup script or Site administration > Appearance > Themes).");
cli_writeln("  5. php admin/cli/scheduled_task.php --execute='\\theme_{$name}\\task\\refresh_blog_feed'");
cli_writeln('  6. php admin/cli/purge_caches.php');
