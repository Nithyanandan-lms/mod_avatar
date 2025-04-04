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
 * Avatar addon subplugin definitions.
 *
 * @package   mod_avatar
 * @copyright 2025 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_avatar\plugininfo;

use avataraddon_pro\avatar;
use core\plugininfo\base;

/**
 * Avatar addon subplugin info class.
 */
class avataraddon extends base {

    /**
     * Returns the information about plugin availability
     *
     * @return bool
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Is the uninstallation allowed.
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Loads plugin settings to the settings tree.
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;

        $ADMIN = $adminroot;
        $plugininfo = $this;

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig || !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new \admin_settingpage('avataraddon', $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php'));

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Returns the Pro features utility instance.
     *
     * @return \avataraddon_pro\util|null
     */
    public static function pro_util() {

        if (self::has_avataraddon_pro()) {
            return new \avataraddon_pro\util();
        }

        return null;
    }

    /**
     * Confirm the avataraddon_pro plugin is installed.
     *
     * @return bool
     */
    public static function has_avataraddon_pro() {
        // ...TODO: use plugin manager to check the plugin installed.
        return class_exists('avataraddon_pro\avatar');
    }

}
