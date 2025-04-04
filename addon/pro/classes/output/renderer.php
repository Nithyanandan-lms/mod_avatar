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
 * Avatar renderer.
 *
 * @package   avataraddon_pro
 * @copyright 2025 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\output;

defined('MOODLE_INTERNAL') || die();

use mod_avatar\output\renderer as avatar_renderer;
use moodle_url;
use html_writer;

require_once($CFG->dirroot . '/mod/avatar/lib.php');

/**
 * Renderer for mod_avatar.
 */
class renderer extends avatar_renderer {

    /**
     * Render the avatar popover.
     *
     * @param int $userid
     * @return string HTML
     */
    public function render_avatar_popover($userid) {
        global $DB;

        $avatars = \mod_avatar\util::get_user_avatars($userid);

        $avatardata = [];
        $islefthalf = true;
        foreach ($avatars as $avatar) {

            $avatardata[] = [
                'id' => $avatar->id,
                'name' => $avatar->name,
                'variantimage' => $avatar->info->get_variant_image_url(),
                'viewurl' => new moodle_url('/mod/avatar/view_avatar.php', ['avatarid' => $avatar->id]),
                'primary' => $avatar->info->isprimary(),
                'additionalclass' => $islefthalf ? 'left-side' : 'right-side',
            ];

            $islefthalf = $avatar->info->isprimary();
        }

        $data = [
            'avatars' => $avatardata,
            'has_avatars' => !empty($avatars),
        ];
        return $this->render_from_template('avataraddon_pro/avatar_popover', $data);

    }

    /**
     * Render the additional buttons for avatar management page.
     *
     * @param \context $context
     * @param string $buttons
     *
     * @return string HTML
     */
    public function render_additional_manage_avatar_buttons($context, $buttons) {
        // Button to import the avatar from zip files.
        if (has_capability('avataraddon/pro:import', $context)) {
            $importurl = new moodle_url('/mod/avatar/import.php');
            $buttons .= $this->single_button($importurl, get_string('importavatar', 'mod_avatar'), 'get');
        }
        return $buttons;
    }

}
