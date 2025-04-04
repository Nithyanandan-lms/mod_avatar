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
 * Avatar task for collect avatar.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\task;

/**
 * Collect avatar task.
 */
class collectavatar extends \core\task\adhoc_task {

    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute() {
        global $DB;

        list($sql, $params) = self::get_listsql();
        if (!$sql) {
            return;
        }

        $avatars = $DB->get_records_sql($sql, $params, 0, 2);
        foreach ($avatars as $avatar) {
            \avataraddon_pro\observer::add_avatar_to_user($avatar->userid, $avatar->avatarid);
        }

        $customdata = $this->get_custom_data();
        // Looking for next set of users to assign avatars.
        return self::init_task($customdata->count);

    }

    /**
     * Get the sql to fetch the list of users and avatars to assign.
     *
     * @return array
     */
    public static function get_listsql() {
        global $DB;

        $profilefieldid = get_config('avataraddon_pro', 'userprofilefield');
        if (!$profilefieldid) {
            return false; // No profile field set.
        }

        $uniqueid = $DB->sql_concat('u.id', "'#'", 'a.id') . ' AS uniqueid';
        // Users with the profile field set to the avatar idnumber or name.
        $listsql = "SELECT $uniqueid, u.id AS userid, a.id AS avatarid
                FROM {user} u
                JOIN {user_info_data} uid ON uid.userid = u.id
                JOIN {user_info_field} uif ON uif.id = uid.fieldid
                JOIN {avatar_list} a ON a.idnumber = uid.data OR a.name = uid.data
                WHERE uif.id = :profilefieldid AND a.id != 0 AND a.id != ''
                AND a.id NOT IN (SELECT avatarid FROM {avatar_user} WHERE userid = u.id)
            ";

        $params = ['profilefieldid' => $profilefieldid];

        return [$listsql, $params];
    }

    /**
     * Initialize the task to collect avatar, find the count of avatars and setup a task.
     *
     * @param int $previouscount
     * @return void
     */
    public static function init_task($previouscount=0) {
        global $DB;

        $profilefieldid = get_config('avataraddon_pro', 'userprofilefield');
        if (!$profilefieldid) {
            return false; // No profile field set.
        }

        $countsql = "SELECT count(u.id)
                FROM {user} u
                JOIN {user_info_data} uid ON uid.userid = u.id
                JOIN {user_info_field} uif ON uif.id = uid.fieldid
                JOIN {avatar_list} a ON a.idnumber = uid.data OR a.name = uid.data
                WHERE uif.id = :profilefieldid AND a.id != 0 AND a.id != ''
                AND a.id NOT IN (SELECT avatarid FROM {avatar_user} WHERE userid = u.id)
            ";

        $params = ['profilefieldid' => $profilefieldid];

        if ($count = $DB->count_records_sql($countsql, $params)) {
            $task = new self();
            $task->set_custom_data((object) ['count' => $count]);

            // Use the count of records of initial records.
            // On each run the count will be decreased.
            // When the count is not reached, the task will be stopped.
            // This is to avoid the task to run forever for the users who are not available to collect avatars.
            if ($count == $previouscount) {
                return false;
            }
            \core\task\manager::queue_adhoc_task($task, true);
        }

        return true;
    }
}
