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
 * Academy brand profile: the single source of academy-specific data.
 *
 * Values come from brand/profile.json. Keys listed in OVERRIDABLE can be replaced
 * by a non-empty admin setting named after the key with dots turned into underscores.
 *
 * @package    theme_readmaster
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class brand {

    /** Keys that must be present and non-empty. */
    private const REQUIRED = [
        'name', 'shortname', 'tagline',
        'colors.primary', 'colors.accent', 'colors.paper',
        'links.blog',
    ];

    /** Keys that must be #RRGGBB colors. */
    private const COLORS = ['colors.primary', 'colors.accent', 'colors.paper'];

    /** Keys an admin can override from the theme settings page. */
    public const OVERRIDABLE = [
        'contact.phone', 'contact.address', 'contact.hours',
        'links.consult', 'links.leveltest', 'links.parent', 'links.platform', 'links.blog', 'links.rss',
    ];

    /** Contact fields, in display order. */
    private const CONTACT_FIELDS = ['phone', 'address', 'hours'];

    /**
     * Use load() or from_array().
     *
     * @param array $data validated profile data
     */
    private function __construct(
        /** @var array validated profile data */
        private readonly array $data,
    ) {
    }

    /**
     * Path of the profile bundled with this theme.
     *
     * @return string
     */
    public static function default_path(): string {
        return dirname(__DIR__, 2) . '/brand/profile.json';
    }

    /**
     * Load and validate a profile file.
     *
     * @param string|null $path defaults to the bundled profile
     * @return self
     * @throws \coding_exception when the file is missing or invalid
     */
    public static function load(?string $path = null): self {
        $path ??= self::default_path();
        if (!is_readable($path)) {
            throw new \coding_exception("Brand profile not found: {$path}");
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            throw new \coding_exception("Brand profile is not valid JSON: {$path} (" . json_last_error_msg() . ')');
        }
        return self::from_array($data);
    }

    /**
     * Validate profile data.
     *
     * @param array $data decoded profile
     * @return self
     * @throws \coding_exception naming the first invalid key
     */
    public static function from_array(array $data): self {
        foreach (self::REQUIRED as $key) {
            $value = self::lookup($data, $key);
            if (!is_string($value) || trim($value) === '') {
                throw new \coding_exception("Brand profile key '{$key}' is missing or empty");
            }
        }
        foreach (self::COLORS as $key) {
            if (!preg_match('/^#[0-9a-f]{6}$/i', self::lookup($data, $key))) {
                throw new \coding_exception("Brand profile key '{$key}' must be a #RRGGBB color");
            }
        }
        foreach ($data['programs'] ?? [] as $i => $program) {
            if (!is_array($program) || empty($program['title'])) {
                throw new \coding_exception("Brand profile key 'programs.{$i}.title' is missing or empty");
            }
        }
        return new self($data);
    }

    /**
     * Read a value by dot path. Admin overrides win for OVERRIDABLE keys.
     *
     * @param string $key e.g. 'colors.primary'
     * @return mixed null when the key does not exist
     */
    public function get(string $key): mixed {
        if (in_array($key, self::OVERRIDABLE, true)) {
            $override = get_config('theme_readmaster', self::setting_name($key));
            if (is_string($override) && trim($override) !== '') {
                return trim($override);
            }
        }
        return self::lookup($this->data, $key);
    }

    /**
     * Value from profile.json, ignoring admin overrides.
     *
     * @param string $key dot path
     * @return mixed
     */
    public function profile_value(string $key): mixed {
        return self::lookup($this->data, $key);
    }

    /**
     * Admin setting name for a profile key.
     *
     * @param string $key dot path
     * @return string
     */
    public static function setting_name(string $key): string {
        return str_replace('.', '_', $key);
    }

    /**
     * Program cards.
     *
     * @return array[] each with 'title' and 'description'
     */
    public function programs(): array {
        return array_map(fn(array $p): array => [
            'title' => (string) $p['title'],
            'description' => (string) ($p['description'] ?? ''),
        ], $this->data['programs'] ?? []);
    }

    /**
     * Non-empty contact fields.
     *
     * @return array[] each with 'type', 'label' and 'value'
     */
    public function contact(): array {
        $result = [];
        foreach (self::CONTACT_FIELDS as $field) {
            $value = (string) ($this->get("contact.{$field}") ?? '');
            if (trim($value) !== '') {
                $result[] = [
                    'type' => $field,
                    'label' => get_string("contact_{$field}", 'theme_readmaster'),
                    'value' => $value,
                ];
            }
        }
        return $result;
    }

    /**
     * Walk a dot path through nested arrays.
     *
     * @param array $data
     * @param string $key
     * @return mixed
     */
    private static function lookup(array $data, string $key): mixed {
        foreach (explode('.', $key) as $part) {
            if (!is_array($data) || !array_key_exists($part, $data)) {
                return null;
            }
            $data = $data[$part];
        }
        return $data;
    }
}
