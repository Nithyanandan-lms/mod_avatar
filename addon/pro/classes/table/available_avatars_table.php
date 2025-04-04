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
 * Table to display available avatars for assignment
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\table;

defined('MOODLE_INTERNAL') || die();

use mod_avatar\util;
use html_writer;
use moodle_url;
use core_table\dynamic as dynamic_table;
use mod_avatar\avatar;

require_once($CFG->libdir.'/tablelib.php');

/**
 * Table to display available avatars for assignment.
 */
class available_avatars_table extends \table_sql implements dynamic_table {

    /**
     * Course module ID.
     * @var int
     */
    protected $cmid;

    /**
     * User ID
     *
     * @var int
     */
    protected $userid;

    /**
     * Avatar cm instance.
     *
     * @var void
     */
    protected $cmavatar;

    /**
     * User avatar.
     *
     * @var stdclass
     */
    protected $useravatar;

    /**
     * Constructor for the available avatars table.
     *
     * @param string $uniqueid Unique id of table
     * @param int $cmid Course module ID
     */
    public function __construct($uniqueid, $cmid=null) {
        parent::__construct($uniqueid);

        if ($cmid == null) {
            $explode = array_reverse(explode('_', $uniqueid));
            $cmid = $explode[0];
        }

        $this->cmid = $cmid;
        $cm = get_coursemodule_from_id('avatar', $this->cmid, 0, false, MUST_EXIST);
        $this->cmavatar = util::get_avatar_cminstance($cm->instance);

        // Define columns.
        $columns = [
            'preview' => get_string('previewimage', 'mod_avatar'),
            'name' => get_string('avatarname', 'mod_avatar'),
            'variants' => get_string('variants', 'mod_avatar'),
            'actions' => get_string('actions'),
        ];
        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));

        // Table configuration.
        $this->collapsible(false);
        $this->sortable(true, 'name', SORT_ASC);
        $this->pageable(true);
        $this->is_downloadable(false);

        $this->no_sorting('actions');
    }

    /**
     * Setup the base url of the report.
     *
     * @return void
     */
    public function guess_base_url(): void {
        $this->baseurl = new \moodle_url('/mod/avatar/report.php', ['cmid' => $this->cmid, 'userid' => $this->userid]);
    }

    /**
     * Confirm the user has capability to view the report.
     *
     * @return \core\context
     */
    public function get_context(): \core\context {
        return \context_module::instance($this->cmid);
    }

    /**
     * Confirm the user has capabiltiy.
     *
     * @return bool
     */
    public function has_capability(): bool {
        return has_capability('avataraddon/pro:viewreport', $this->get_context());
    }

    /**
     * Setup the table records.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     *
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        if ($this->cmavatar->avatarselection == 0) {
            $fields = 'al.*';
            $from = '{avatar_list} al';
            $where = 'al.archived = 0 AND al.status = :status';
            $params = ['status' => 1];
        } else {
            // Specific tags.
            $tags = explode(',', $this->cmavatar->specifictags);
            $tags = array_map('trim', $tags);
            list($insql, $params) = $DB->get_in_or_equal($tags);

            $fields = 'al.*';
            $from = '{avatar_list} al
                    JOIN {tag_instance} ti ON ti.itemid = al.id
                    JOIN {tag} t ON t.id = ti.tagid';
            $where = "al.archived = 0 AND al.status = 1 AND t.name $insql";
        }

        // Set up the SQL for the table.
        $this->set_sql($fields, $from, $where, $params);

        parent::query_db($pagesize, $useinitialsbar);

        if ($this->filterset->has_filter('userid')) {
            $values = $this->filterset->get_filter('userid')->get_filter_values();
            $userid = isset($values[0]) ? current($values) : '';
            $this->userid = $userid;
            $this->useravatar = util::get_user_avatars($userid);
        }
    }

    /**
     * Format preview column.
     *
     * @param object $row Table row
     * @return string HTML
     */
    public function col_preview($row) {
        global $OUTPUT;

        $avatar = new avatar($row->id, $row);
        return html_writer::img($avatar->info->get_preview_image(), $row->name,
            ['class' => 'avatar-preview-image rounded-circle', 'width' => '50', 'height' => '50']
        );
    }

    /**
     * Format actions column.
     *
     * @param object $row Table row
     * @return string HTML
     */
    public function col_actions($row) {
        global $OUTPUT;

        $assignurl = new \moodle_url('/mod/avatar/report.php', [
            'cmid' => $this->cmid,
            'action' => 'assign',
            'userid' => $this->userid,
            'avatarid' => $row->id,
            'sesskey' => sesskey(),
        ]);

        $avatar = $this->useravatar[$row->id] ?? new \mod_avatar\avatar($row->id, $row);

        if (!$avatar->is_avatar_available($this->userid, $this->cmid)) {
            return get_string('notavailable', 'mod_avatar');
        }

        $progress = $avatar->info->get_progress();

        if ($progress['canupgrade']) {
            $string = get_string('upgrade', 'mod_avatar');
            $class = 'upgrade-avatar-btn';
        } else if ($progress['canpick']) {
            $string = get_string('assign', 'mod_avatar');
            $class = 'assign-avatar-btn';
        } else {
            return '';
        }

        return $OUTPUT->single_button($assignurl, $string, 'post', ['class' => $class]);
    }
}
