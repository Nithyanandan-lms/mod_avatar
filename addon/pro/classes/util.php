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
 * Pro features for the avatar module.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro;

use mod_avatar\avatar as modavatar;
use mod_avatar\helper;
use mod_avatar\plugininfo\avataraddon;
use moodle_exception;

/**
 * Pro features for the avatar module.
 */
class util {

    /**
     * Singleton instance of the class.
     *
     * @var \mod_avatar\avatar
     */
    public static function instance() {
        return new self();
    }

    /**
     * Load the avatar course module, pro features data for the given cmavatar ID.
     *
     * @param object $data The avatar course module data.
     * @return object The loaded avatar data.
     */
    public function load_cmavatar_data(&$data) {
        global $DB;

        if ($record = (array) $DB->get_record('avataraddon_pro', ['cmavatarid' => $data->id])) {
            unset($record['id']);
            $data = (object) array_merge((array) $data, $record);
        }

        return $data;
    }

    /**
     * Load the pro features avatar data for the given avatar ID.
     *
     * @param object $data The avatar data.
     * @return object The loaded avatar data.
     */
    public function load_avatar_data(&$data) {
        global $DB;

        if ($record = (array) $DB->get_record('avataraddon_pro_avatar', ['avatarid' => $data->id])) {
            unset($record['id']);
            $data = (object) array_merge((array) $data, $record);
        }

        return $data;
    }

    /**
     * Generate the avatar activity data, availability info and message to the template data for render.
     *
     * @param array $data The template data.
     * @param object $avataractivity The avatar activity object.
     */
    public function avatar_activity_templatedata(&$data, $avataractivity) {

        $cmavatar = $avataractivity->cmavatar;
        $this->load_cmavatar_data($cmavatar);

        $avatar = new \avataraddon_pro\avatar($cmavatar, $avataractivity->cm);
        $availabilityinfo = $avatar->get_availability_info();
        $hasrestriction = !empty($availabilityinfo);
        $data['has_availability_restriction'] = $hasrestriction;
        if ($hasrestriction) {
            $data['availability_message'] = $avatar->get_availability_message($availabilityinfo);
        }
    }

    /**
     * Check if an avatar is available to the user.
     *
     * @param object $avatar The avatar record
     * @param int $userid The user ID
     * @param object|null $cm The course module (optional)
     * @return bool True if the avatar is available, false otherwise
     */
    public function verify_avatar_available($avatar, $userid, $cm = null) {
        global $DB;

        // Load pro avatar data.
        $this->load_avatar_data($avatar);

        // Check course categories.
        if (!empty($avatar->coursecategories)) {
            $categories = json_decode($avatar->coursecategories);
            $usercourses = enrol_get_users_courses($userid);
            $usercategories = [];
            foreach ($usercourses as $course) {
                $usercategories[] = $course->category;
                if ($avatar->includesubcategories) {
                    $parentcategories = explode('/', $course->category);
                    $usercategories = array_merge($usercategories, $parentcategories);
                }
            }
            $usercategories = array_unique($usercategories);
            $intersect = array_intersect($categories, $usercategories);
            if (empty($intersect)) {
                return false;
            }
        }

        // Check cohorts.
        if (!empty($avatar->cohorts)) {
            $cohorts = json_decode($avatar->cohorts);
            $usercohorts = $DB->get_records_menu('cohort_members', ['userid' => $userid], '', 'cohortid, id');
            $intersect = array_intersect($cohorts, array_keys($usercohorts));
            if (empty($intersect)) {
                return false;
            }
        }

        // Check total capacity.
        if (!empty($avatar->totalcapacity)) {
            $count = $DB->count_records('avatar_user', ['avatarid' => $avatar->id]);
            if ($count >= $avatar->totalcapacity) {
                return false;
            }
        }

        // Check activity-specific limits.
        if ($cm) {
            return $this->cm_avatar_available($cm, $userid);
        }

        return true;
    }

    /**
     * Check if the avatar is available for the course module.
     *
     * @param object $cm The course module
     * @param int|null $userid The user ID (optional)
     * @return bool True if the avatar is available, false otherwise
     */
    public function cm_avatar_available($cm, $userid = null) {
        global $DB;

        $activityrecord = $DB->get_record('avatar', ['id' => $cm->instance]);

        // Check collection total limit for this activity.
        if (!empty($activityrecord->collectiontotallimit)) {
            $count = $DB->count_records('avatar_user', ['cmid' => $cm->id]);
            if ($count >= $activityrecord->collectiontotallimit) {
                return false;
            }
        }

        // Check collection limit per user.
        if (!empty($activityrecord->collectionlimitperuser) && $userid) {
            $count = $DB->count_records('avatar_user', ['userid' => $userid, 'cmid' => $cm->id]);
            if ($count >= $activityrecord->collectionlimitperuser) {
                return false;
            }
        }

        // Check collection limit per interval.
        if (!empty($activityrecord->collectionlimitperinterval) && $userid) {
            $interval = $activityrecord->collectioninterval ?? 86400; // Default to daily.
            $timeframe = time() - $interval;
            $count = $DB->count_records_select('avatar_user',
                "userid = :userid AND cmid = :cmid AND timecollected > :timeframe",
                ['userid' => $userid, 'cmid' => $cm->id, 'timeframe' => $timeframe]);
            if ($count >= $activityrecord->collectionlimitperinterval) {
                return false;
            }
        }

        return true;
    }

    /**
     * Export an avatar. Include this export action in the avatar edit page.
     *
     * @param string $action
     * @param modavatar $avatar
     * @param object $context
     */
    public function manage_avatar_edit_action(string $action, modavatar $avatar, $context) {

        if ($action !== 'export') {
            return;
        }

        // Verify the export capability for the context.
        require_capability('avataraddon/pro:export', $context);

        $zipfile = helper::export_avatar($avatar->id);
        $filename = basename($zipfile);
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Length: " . filesize($zipfile));
        readfile($zipfile);
        unlink($zipfile); // Delete the temporary file.
        exit();
    }

    /**
     * Export avatar action for active avatar table.
     *
     * @param object $row Table row
     * @param object $context Context
     * @param object $url URL
     * @return string HTML
     */
    public function manage_avatar_table_action($row, $context, $url) {
        global $OUTPUT;

        if (has_capability('avataraddon/pro:export', $context)) {
            $url->param('action', 'export');
            return $OUTPUT->action_icon($url, new \pix_icon('i/export', get_string('export', 'mod_avatar')));
        }
    }

    /**
     * Moodle form definition after data.
     * @param \moodleform $mform The form object.
     * @return void
     */
    public function mod_form_definition_after_data($mform) {

        // Collection limits.
        $availability[] =& $mform->createElement('header', 'availability', get_string('availability', 'mod_avatar'));

        // Collection total limit.
        $availability[] =& $mform->createElement('text', 'collectiontotallimit', get_string('collectiontotallimit', 'mod_avatar'));
        $mform->setType('collectiontotallimit', PARAM_INT);
        // Set the default values.
        if ($limit = get_config('avataraddon_pro', 'collectiontotallimit')) {
            $mform->setDefault('collectiontotallimit', $limit);
        }

        // Collection limit per user.
        $availability[] =& $mform->createElement('text', 'collectionlimitperuser',
            get_string('collectionlimitperuser', 'mod_avatar'));
        $mform->setType('collectionlimitperuser', PARAM_INT);
        // Set the default values.
        if ($limit = get_config('avataraddon_pro', 'collectionlimitperuser')) {
            $mform->setDefault('collectionlimitperuser', $limit);
        }

        // Collection limit per interval.
        $availability[] =& $mform->createElement('text', 'collectionlimitperinterval',
            get_string('collectionlimitperinterval', 'mod_avatar'));
        $mform->setType('collectionlimitperinterval', PARAM_INT);
        // Set the default values.
        if ($limit = get_config('avataraddon_pro', 'collectionlimitperinterval')) {
            $mform->setDefault('collectionlimitperinterval', $limit);
        }

        // Collection interval.
        $availability[] =& $mform->createElement('duration', 'collectioninterval', get_string('collectioninterval', 'mod_avatar'));
        if ($collectioninterval = get_config('avataraddon_pro', 'collectioninterval')) {
            $mform->setDefault('collectioninterval', $collectioninterval);
        }

        foreach ($availability as &$field) {
            $mform->insertElementBefore($field, 'appearancehdr');
        }

        $mform->addHelpButton('collectiontotallimit', 'collectiontotallimit', 'mod_avatar');
        $mform->addHelpButton('collectionlimitperinterval', 'collectionlimitperinterval', 'mod_avatar');
        $mform->addHelpButton('collectionlimitperuser', 'collectionlimitperuser', 'mod_avatar');
        $mform->addHelpButton('collectioninterval', 'collectioninterval', 'mod_avatar');
    }

    /**
     * Validate the avatar module form data.
     *
     * @param array $data The form data.
     * @param array $files The uploaded files.
     * @return array An array of errors, if any.
     */
    public function mod_form_data_validation($data, $files): array {
        $errors = [];

        // Check that the collection limits are valid.
        if (!empty($data['collectiontotallimit']) && $data['collectiontotallimit'] < 0) {
            $errors['collectiontotallimit'] = get_string('error:negativelimit', 'mod_avatar');
        }
        if (!empty($data['collectionlimitperuser']) && $data['collectionlimitperuser'] < 0) {
            $errors['collectionlimitperuser'] = get_string('error:negativelimit', 'mod_avatar');
        }
        if (!empty($data['collectionlimitperinterval']) && $data['collectionlimitperinterval'] < 0) {
            $errors['collectionlimitperinterval'] = get_string('error:negativelimit', 'mod_avatar');
        }

        return $errors;
    }

    /**
     * Update the avatar module data.
     *
     * @param object $data The form data.
     * @return void
     */
    public function update_cmavatar_data($data) {
        global $DB;

        $record = new \stdClass();
        $record->cmavatarid = $data->id;
        $record->collectiontotallimit = $data->collectiontotallimit;
        $record->collectionlimitperuser = $data->collectionlimitperuser;
        $record->collectionlimitperinterval = $data->collectionlimitperinterval;
        $record->collectioninterval = $data->collectioninterval;

        if ($pro = $DB->get_record('avataraddon_pro', ['cmavatarid' => $data->id])) {
            $record->id = $pro->id;
            $DB->update_record('avataraddon_pro', $record);
        } else {
            $DB->insert_record('avataraddon_pro', $record);
        }
    }

    /**
     * Add the pro definition in the avatar creation form, after data.
     *
     * @param \moodleform $mform The form object.
     * @return void
     */
    public function avatar_form_definition_after_data($mform) {
        // Availability.
        $element[] =& $mform->createElement('header', 'availability', get_string('availability', 'mod_avatar'));

        // Course categories.
        $categories = \core_course_category::make_categories_list();
        $element[] =& $mform->createElement('autocomplete', 'coursecategories',
            get_string('coursecategories', 'mod_avatar'), $categories, ['multiple' => true]);

        // Include subcategories.
        $element[] =& $mform->createElement('advcheckbox', 'includesubcategories',
            get_string('includesubcategories', 'mod_avatar'));

        // Cohorts.
        $cohorts = \cohort_get_all_cohorts();
        $cohortoptions = [];
        foreach ($cohorts['cohorts'] as $cohort) {
            $cohortoptions[$cohort->id] = $cohort->name;
        }
        $element[] =& $mform->createElement('autocomplete', 'cohorts',
            get_string('cohorts', 'mod_avatar'), $cohortoptions, ['multiple' => true]);

        // Total capacity.
        $element[] =& $mform->createElement('text', 'totalcapacity', get_string('totalcapacity', 'mod_avatar'));
        $mform->setType('totalcapacity', PARAM_INT);

        // Set the default capacity from global config.
        if ($limit = get_config('avataraddon_pro', 'collectiontotallimit')) {
            $mform->setDefault('totalcapacity', $limit);
        }

        foreach ($element as &$field) {
            $mform->insertElementBefore($field, 'tags');
        }

        $mform->addHelpButton('coursecategories', 'coursecategories', 'mod_avatar');
        $mform->addHelpButton('includesubcategories', 'includesubcategories', 'mod_avatar');
        $mform->addHelpButton('cohorts', 'cohorts', 'mod_avatar');
        $mform->addHelpButton('totalcapacity', 'totalcapacity', 'mod_avatar');

    }

    /**
     * Update the avatar data.
     *
     * @param object $data The form data.
     * @return void
     */
    public function update_avatar_data($data) {
        global $DB;

        $record = new \stdClass();
        $record->avatarid = $data->id;
        $record->includesubcategories = $data->includesubcategories;
        $record->totalcapacity = $data->totalcapacity;
        $record->coursecategories = $data->coursecategories ? json_encode($data->coursecategories) : '';
        $record->cohorts = $data->cohorts ? json_encode($data->cohorts) : '';

        if ($pro = $DB->get_record('avataraddon_pro_avatar', ['avatarid' => $data->id])) {
            $record->id = $pro->id;
            $DB->update_record('avataraddon_pro_avatar', $record);
        } else {
            $DB->insert_record('avataraddon_pro_avatar', $record);
        }
    }

    /**
     * Prepare the avatar edit data.
     *
     * @param object $avatardata The avatar data.
     * @param object $context The context.
     * @return void
     */
    public function prepare_avatar_edit_data(&$avatardata, $context) {
        $this->load_avatar_data($avatardata);
        $avatardata->coursecategories = $avatardata->coursecategories ? json_decode($avatardata->coursecategories, true) : [];
        $avatardata->cohorts = $avatardata->cohorts ? json_decode($avatardata->cohorts, true) : [];
    }
}
