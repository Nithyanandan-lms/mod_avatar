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
 * Event observer for avataraddon_pro.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro;

use mod_avatar\event\avatar_collected;
use mod_avatar\event\avatar_upgraded;
use mod_avatar\event\avatar_assigned;
use context_user;
use mod_avatar\avatar;

/**
 * Event observer for avataraddon_pro.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Observer for user_created event.
     *
     * @param \core\event\user_created $event
     * @return bool
     */
    public static function user_created(\core\event\user_created $event) {
        return self::assign_avatar($event->objectid, true);
    }

    /**
     * Observer for user_updated event.
     *
     * @param \core\event\user_updated $event
     * @return bool
     */
    public static function user_updated(\core\event\user_updated $event) {
        return self::assign_avatar($event->objectid, false);
    }

    /**
     * Assign avatar to user based on profile field or initial avatar setting.
     *
     * @param int $userid
     * @param bool $initial
     * @return bool
     */
    protected static function assign_avatar(int $userid, bool $initial=false) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/gdlib.php');

        $profilefieldid = get_config('avataraddon_pro', 'userprofilefield');
        $setinitialavatar = get_config('avataraddon_pro', 'setinitialavatar');

        if (!$profilefieldid) {
            return false;
        }

        $avatar = null;
        if ($profilefieldid) {
            $fielddata = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $profilefieldid]);
            if ($fielddata) {
                $fieldvalue = $fielddata->data;
                $avatar = $DB->get_record_select('avatar_list', 'name = :fieldvalue OR idnumber = :fieldvalue2',
                    ['fieldvalue' => $fieldvalue, 'fieldvalue2' => $fieldvalue], "*", IGNORE_MULTIPLE);
            }
        }

        if (!empty($avatar)) {
            // Check if the user already has an avatar.
            if ($DB->record_exists('avatar_user', ['userid' => $userid, 'avatarid' => $avatar->id])) {
                return false;
            }

            // Confirm the user not have a profile picture.
            // Set initial picture.
            $user = \core_user::get_user($userid);
            if (empty($user->profile)) {
                $initial = true;
            }

            // Add the avatar to the user collection.
            // If the set initial avatar setting is checked, update the users profile picture.
            if ($initial && $setinitialavatar) {
                if (self::add_avatar_to_user($userid, $avatar->id)) {
                    return self::update_user_profile_picture($userid, $avatar->id);
                }
            }
        }

        return false;
    }

    /**
     * Add avatar to user.
     *
     * @param int $userid
     * @param int $avatarid
     * @return bool
     */
    public static function add_avatar_to_user($userid, $avatarid) {
        global $DB, $USER;

        // If the avatar is not available for the user, return false.
        $avatar = new avatar($avatarid);
        if (!$avatar->is_avatar_available($userid)) {
            return false;
        }

        $record = new \stdClass();
        $record->userid = $userid;
        $record->avatarid = $avatarid;
        $record->variant = 1;
        $record->isprimary = 1;
        $record->timecollected = time();
        $record->timemodified = time();

        // Remove the primary flag from any existing primary avatars for the user.
        $DB->set_field_select('avatar_user', 'isprimary', 0, 'userid=:userid', ['userid' => $userid]);

        $result = $DB->insert_record('avatar_user', $record) ? true : false;

        // Trigger avatar collected event.
        $params = [
            'context' => context_user::instance($userid),
            'objectid' => $avatar->id,
            'relateduserid' => $userid,
            'other' => [
                'avatarid' => $avatar->id,
            ],
        ];

        $event = ($userid == $USER->id) ? avatar_collected::create($params) : avatar_assigned::create($params);
        $event->trigger();

        return $result;
    }

    /**
     * Update users profile picture with the assigned avatar.
     *
     * @param int $userid
     * @param int $avatarid
     * @return bool
     */
    protected static function update_user_profile_picture($userid, $avatarid) {
        return \mod_avatar\util::update_user_profile_picture($userid, $avatarid);
    }

}
