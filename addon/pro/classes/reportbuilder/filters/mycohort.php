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
 * Avatar report my cohort filter.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\reportbuilder\filters;

defined('MOODLE_INTERNAL') || die();

use core_reportbuilder\local\helpers\database;
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * My Cohort filter. Filters the avatar list to include only the cohorts to which the current user belongs.
 */
class mycohort extends \core_reportbuilder\local\filters\boolean_select {

    /**
     * Return filter SQL.
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $USER, $DB;

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $paramname = database::generate_param_name();

        $usercohorts = cohort_get_user_cohorts($USER->id);

        $operator = $values["{$this->name}_operator"] ?? self::ANY_VALUE;

        if (empty($usercohorts) || $operator != self::CHECKED) {
            return ['', []];
        }

        foreach ($usercohorts as $key => $cohort) {
            $cohortid = $cohort->id;
            $likesql[] = $DB->sql_like("{$fieldsql}", ":cohortid$key", false, false);
            $cohortparams["cohortid$key"] = '%"' . $cohortid . '"%';
        }

        $cohortselect = '(' . implode(' OR ', $likesql) . ')';

        return ["$cohortselect", array_merge($params, $cohortparams)];
    }
}
