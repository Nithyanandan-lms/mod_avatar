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
 * Avatar cm instance with pro features
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro;

use mod_avatar\avatar as modavatar;

/**
 * Avatar course module with pro features.
 */
class avatar {

    /**
     * @var bool Popover disabled
     */
    public const POPOVERDISABLED = 0;

    /**
     * @var bool Popover enabled
     */
    public const POPOVERENABLED = 1;

    /**
     * @var bool avatar course module instance.
     */
    protected $cmavatar;

    /**
     * @var \cm_info Course module information.
     */
    protected $cm;

    /**
     * Constructor.
     *
     * @param \mod_avatar\avatar $cmavatar Avatar course module instance.
     * @param \cm_info|null $cm Course module information.
     */
    public function __construct($cmavatar, $cm=null) {
        $this->cmavatar = $cmavatar;
        $this->cm = $cm;
    }

    /**
     * Get availability information.
     *
     * @return array
     */
    public function get_availability_info() {
        global $DB, $USER;

        $totallimit = $this->cmavatar->collectiontotallimit;
        $userlimit = $this->cmavatar->collectionlimitperuser;
        $intervallimit = $this->cmavatar->collectionlimitperinterval;
        $interval = $this->cmavatar->collectioninterval;

        $info = [];

        if ($totallimit > 0) {
            $collectedtotal = $DB->count_records('avatar_user', ['cmid' => $this->cm->id]);
            $remainingtotal = max(0, $totallimit - $collectedtotal);
            $info['total'] = ['limit' => $totallimit, 'remaining' => $remainingtotal];
        }

        if ($userlimit > 0) {
            $collecteduser = $DB->count_records('avatar_user', ['userid' => $USER->id, 'cmid' => $this->cm->id]);
            $remaininguser = max(0, $userlimit - $collecteduser);
            $info['user'] = ['limit' => $userlimit, 'remaining' => $remaininguser];
        }

        if ($intervallimit > 0 && $interval > 0) {
            $timeframe = time() - $interval;
            $collectedinterval = $DB->count_records_select('avatar_user',
                "userid = :userid AND cmid = :cmid AND timecollected > :timeframe",
                ['userid' => $USER->id, 'cmid' => $this->cm->id, 'timeframe' => $timeframe]);
            $remaininginterval = max(0, $intervallimit - $collectedinterval);
            $info['interval'] = ['limit' => $intervallimit, 'remaining' => $remaininginterval, 'period' => $interval];
        }

        return $info;
    }

    /**
     * Get availability message based on the availability info.
     *
     * @param array $availabilityinfo
     * @return string
     */
    public function get_availability_message($availabilityinfo) {
        $messages = [];

        if (isset($availabilityinfo['total'])) {
            $messages[] = get_string('availabilitytotal', 'mod_avatar', $availabilityinfo['total']);
        }

        if (isset($availabilityinfo['user'])) {
            $messages[] = get_string('availabilityuser', 'mod_avatar', $availabilityinfo['user']);
        }

        if (isset($availabilityinfo['interval'])) {
            $interval = self::interval_options($availabilityinfo['interval']['period']);
            $intervalstring = get_string('interval' . $interval, 'mod_avatar');
            $messages[] = get_string('availabilityinterval', 'mod_avatar', [
                'limit' => $availabilityinfo['interval']['limit'],
                'remaining' => $availabilityinfo['interval']['remaining'],
                'interval' => $intervalstring,
            ]);
        }

        return implode(' ', $messages);
    }

    /**
     * Interval options.
     *
     * @param int $value
     * @return int|null
     */
    public static function interval_options($value) {
        // Collection interval.
        $intervaloptions = [
            3600 => 'hour',
            86400 => 'day',
            604800 => 'week',
            2592000 => 'month',
        ];

        return $intervaloptions[$value] ?? '';
    }

}
