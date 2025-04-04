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
 * Additional features for the active avatars table
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\table;

use mod_avatar\avatar;

/**
 * Additional features for the active avatars table. Included addtional columns for categories and cohorts.
 */
trait trait_active_avatars_table {

    /**
     * Define the columns for course categories and cohorts.
     *
     * @param array $columns
     * @return array
     */
    public function define_columns($columns) {
        $columns = ['name', 'idnumber', 'description', 'timecreated',
            'usermodified', 'coursecategories', 'cohorts', 'usage', 'actions'];
        parent::define_columns($columns);
    }

    /**
     * Define the headers for course categories and cohorts.
     *
     * @param array $headers
     * @return array
     */
    public function define_headers($headers) {
        // Define the titles of columns to show in header.
        $proheaders = [
            get_string('name'),
            get_string('idnumber'),
            get_string('description'),
            get_string('timecreated'),
            get_string('createdby', 'mod_avatar'),
            get_string('coursecategories', 'mod_avatar'),
            get_string('cohorts', 'mod_avatar'),
            get_string('usage', 'mod_avatar'),
            get_string('actions'),
        ];

        parent::define_headers($proheaders);
    }

    /**
     * Set the SQL query to fetch data for the table.
     *
     * @param string $select
     * @param string $from
     * @param string $where
     * @param array $params
     */
    public function set_sql($select, $from, $where, array $params = []) {
        global $DB;

        $select .= ", ap.coursecategories, ap.cohorts";

        $from .= ' LEFT JOIN {avataraddon_pro_avatar} ap ON ap.avatarid = al.id';

        parent::set_sql($select, $from, $where);
    }

    /**
     * Course categories of the avatar only available.
     *
     * @param object $values
     * @return string Return formatted course categories.
     */
    public function col_coursecategories($values) {
        $categories = ($values->coursecategories) ? json_decode($values->coursecategories, true) : [];
        if (empty($categories)) {
            return get_string('none');
        }
        $categorynames = array_map(function($categoryid) {
            $category = \core_course_category::get($categoryid, IGNORE_MISSING);
            return $category ? $category->get_formatted_name() : '';
        }, $categories);
        return implode(', ', array_filter($categorynames));
    }

    /**
     * Cohorts included for avatars.
     *
     * @param object $values
     * @return string Return formatted cohorts.
     */
    public function col_cohorts($values) {
        global $DB;

        $cohorts = ($values->cohorts) ? json_decode($values->cohorts, true) : [];
        if (empty($cohorts)) {
            return get_string('none');
        }

        $cohortsdata = $DB->get_records_list('cohort', 'id', $cohorts);

        $cohortnames = array_map(function($cohort) {
            return $cohort ? $cohort->name : '';
        }, $cohortsdata);

        return implode(', ', array_filter($cohortnames));
    }

    /**
     * Export avatar action for active avatar table.
     *
     * @param object $row Table row
     * @param object $context Context
     * @param object $url URL
     * @return string HTML
     */
    public function avataraddon_avatar_table_action($row, $context, $url): string {
        global $OUTPUT;

        if (has_capability('avataraddon/pro:export', $context)) {
            $url->param('action', 'export');
            return $OUTPUT->action_icon($url, new \pix_icon('i/export', get_string('export', 'mod_avatar')));
        }

        return '';
    }
}
