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
 * Avatar report table class.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/user/lib.php');

use html_writer;
use core_table\dynamic as dynamic_table;

/**
 * Avatar report table.
 */
class avatar_report_table extends \core_user\table\participants {

    /**
     * The context instance.
     * @var \context
     */
    protected $context;

    /**
     * List of avatars.
     *
     * @var array
     */
    protected $avatars;

    /**
     * Cache for user avatars.
     * @var array
     */
    protected $useravatars;

    /** @var \stdClass[] $viewableroles */
    protected $viewableroles;

    /**
     * Confirm the user has capability to view the table.
     *
     * @return bool
     */
    public function has_capability(): bool {
        return has_capability('avataraddon/pro:viewreport', $this->context);
    }

    /**
     * Get the context of the avatar report table.
     *
     * @return \context
     */
    public function get_context(): \context {
        $cmcontext = $this->context;
        $context = $cmcontext->get_course_context();
        return $context;
    }

    /**
     * Constructor
     *
     * @param string $uniqueid Unique id of table
     * @param \context $context The context instance
     */
    public function __construct($uniqueid, $context=null) {
        parent::__construct($uniqueid);

        if ($context === null) {
            $cmid = (int) str_replace('avatar_report_', '', $uniqueid);
            $context = \context_module::instance($cmid);
        }

        $this->context = $context;
        $context = $context->get_course_context();
        $this->courseid = $context->instanceid;
        $this->avatars = $this->get_avatars();

         // Set download option to reports.
        $this->downloadable = true;
        $this->showdownloadbuttonsat = [TABLE_P_BOTTOM];

        $this->baseurl = new \moodle_url('/mod/avatar/report.php', ['id' => $this->context->instanceid]);
    }

    /**
     * Query the db.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     */
    public function query_db($pagesize, $useinitialsbar = true) {

        parent::query_db($pagesize, $useinitialsbar);
        $users = array_column($this->rawdata, 'id');
        $this->useravatars = $this->get_user_avatars($users);

    }

    /**
     * Setup and Render the menus table.
     *
     * @param int $pagesize Size of page for pagination.
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $CFG;

        // Define table headers and columns.
        $columns = [];
        $headers = [];

        $columns[] = 'fullname';
        $headers[] = get_string('fullname');

        $extrafields = \core_user\fields::get_identity_fields($this->context);
        foreach ($extrafields as $field) {
            $headers[] = \core_user\fields::get_display_name($field);
            $columns[] = $field;
        }

        $columns[] = 'lastaccess';
        $headers[] = get_string('lastaccess');

        // Get the list of fields we have to hide.
        $hiddenfields = [];
        if (!has_capability('moodle/course:viewhiddenuserfields', $this->context)) {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        // Add column for groups if the user can view them.
        $canseegroups = !isset($hiddenfields['groups']);
        if ($canseegroups) {
            $headers[] = get_string('groups');
            $columns[] = 'groups';
            $this->groups = groups_get_all_groups($this->courseid, 0, 0, 'g.*', true);
            $this->no_sorting('groups');
        }

        $headers[] = get_string('roles');
        $columns[] = 'roles';

        $columns[] = 'actions';
        $headers[] = get_string('actions');

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Remove sorting for some fields.
        $this->no_sorting('actions');
        $this->no_sorting('roles');
        $this->set_attribute('id', 'avatar_report');

        $this->extrafields = $extrafields;
        $this->pagesize = $pagesize;

        // If user has capability to review enrol, show them both role names.
        $canreviewenrol = has_capability('moodle/course:enrolreview', $this->context);
        $allrolesnamedisplay = ($canreviewenrol ? ROLENAME_BOTH : ROLENAME_ALIAS);

        $this->allroles = role_fix_names(get_all_roles($this->context), $this->context, $allrolesnamedisplay);
        $this->assignableroles = get_assignable_roles($this->context, ROLENAME_BOTH, false);
        $this->profileroles = get_profile_roles($this->context);
        $this->viewableroles = get_viewable_roles($this->context);

        $this->guess_base_url();
        $this->setup();

        $this->query_db($pagesize, $useinitialsbar);

        \table_sql::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Get all active avatars
     *
     * @return array
     */
    protected function get_avatars() {
        global $DB;
        return $DB->get_records('avatar_list', ['archived' => 0, 'status' => 1], 'name ASC');
    }

    /**
     * Get all user avatars
     *
     * @param array $users
     * @return array
     */
    protected function get_user_avatars($users) {
        global $DB;

        if (empty($users)) {
            return [];
        }

        list($insql, $inparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'au');
        $insql = "userid $insql";
        $records = $DB->get_records_select('avatar_user', $insql, $inparams);

        $useravatars = [];
        foreach ($records as $record) {
            if (!isset($useravatars[$record->userid])) {
                $useravatars[$record->userid] = [];
            }
            $useravatars[$record->userid][$record->avatarid] = $record;
        }
        return $useravatars;
    }

    /**
     * Generate avatar header with preview image.
     *
     * @param object $avatar
     * @return string
     */
    protected function get_avatar_header($avatar) {
        global $OUTPUT;

        $avatarobj = new \mod_avatar\avatar($avatar->id);
        $preview = $avatarobj->info->get_preview_image();

        $header = html_writer::img($preview, $avatar->name, [
            'class' => 'avatar-preview rounded-circle', 'width' => '40', 'height' => '40']);
        $header .= html_writer::tag('div', $avatar->name, ['class' => 'avatar-name mt-2']);

        return $header;
    }

    /**
     * Generate the fullname column
     *
     * @param object $user
     * @return string
     */
    public function col_fullname($user) {
        global $OUTPUT;
        return $OUTPUT->user_picture($user) . ' ' . fullname($user);
    }

    /**
     * Actions of the report.
     *
     * @param stdclass $row
     * @return string
     */
    public function col_actions($row) {
        // Check if user has this avatar.
        $useravatars = $this->useravatars[$row->id] ?? [];

        $output = '';
        foreach ($useravatars as $avatarid => $useravatar) {
            $avatarobj = new \mod_avatar\avatar($avatarid);
            $preview = $avatarobj->info->get_preview_image();

            $image = html_writer::img($preview, $avatarobj->name, [
                'class' => 'avatar-preview rounded-circle', 'width' => '40', 'height' => '40']);
            $output .= $this->render_upgrade_button($row->id, $avatarid, $image);
        }

        // Assign button if user doesn't have the avatar.
        $output .= $this->render_assign_button($row->id);

        return $output;
    }

    /**
     * User roles column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_roles($data) {
         global $OUTPUT;

        $roles = isset($this->allroleassignments[$data->id]) ? $this->allroleassignments[$data->id] : [];
        $editable = new \core_user\output\user_roles_editable($this->course,
                                                              $this->context,
                                                              $data,
                                                              $this->allroles,
                                                              $this->assignableroles,
                                                              $this->profileroles,
                                                              $roles,
                                                              $this->viewableroles);

        return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));
    }

    /**
     * Render progress indicators.
     *
     * @param int $current Current variant
     * @param int $total Total variants
     * @return string
     */
    protected function render_progress_indicators($current, $total) {
        $indicators = '';
        for ($i = 1; $i <= $total; $i++) {
            $class = $i <= $current ? 'bg-success' : 'bg-secondary';
            $indicators .= html_writer::tag('div', '',
                ['class' => "progress-indicator $class me-1",
                 'style' => 'width: 8px; height: 8px; border-radius: 50%; display: inline-block;']);
        }
        return html_writer::div($indicators, 'avatar-progress mb-2');
    }

    /**
     * Render upgrade button.
     *
     * @param int $userid User ID
     * @param int $avatarid Avatar ID
     * @param string $avatarimg Avatar image
     * @return string
     */
    protected function render_upgrade_button($userid, $avatarid, $avatarimg) {

        $url = new \moodle_url('/mod/avatar/report.php', [
            'action' => 'upgrade',
            'userid' => $userid,
            'avatarid' => $avatarid,
            'sesskey' => sesskey(),
        ]);

        return html_writer::link('javascript:void();', $avatarimg, [
            'title' => get_string('upgrade', 'mod_avatar'), 'class' => 'avatar-upgrade-btn',
            'data-userid' => $userid, 'data-avatarid' => $avatarid]);
    }

    /**
     * Render assign button.
     *
     * @param int $userid User ID
     * @return string
     */
    protected function render_assign_button($userid) {

        $url = new \moodle_url('/mod/avatar/report.php', [
            'action' => 'assign',
            'userid' => $userid,
            'sesskey' => sesskey(),
        ]);

        return html_writer::link('javascript:void();', '<i class="fa fa-circle-plus"></i>',
            ['title' => get_string('assign', 'mod_avatar'),
            'class' => 'btn btn-outline-secondary avatar-assign-btn rounded-circle ml-2',
            'width' => '40', 'height' => '40', 'data-userid' => $userid]);
    }
}
