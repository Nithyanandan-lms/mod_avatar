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
 * Avatar report cohort filter.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\reportbuilder\filters;

/**
 * Cohort filter. Filter the avatars list by cohort.
 */
class cohort extends \core_reportbuilder\local\filters\cohort {

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $cohortids = $values["{$this->name}_values"] ?? [];
        if (empty($cohortids)) {
            return ['', []];
        }

        foreach ($cohortids as $key => $cohortid) {
            $likesql[] = $DB->sql_like("{$fieldsql}", ":cohortid$key", false, false);
            $cohortparams["cohortid$key"] = '%"' . $cohortid . '"%';
        }

        $cohortselect = '(' . implode(' OR ', $likesql) . ')';

        return ["$cohortselect", array_merge($params, $cohortparams)];
    }
}
