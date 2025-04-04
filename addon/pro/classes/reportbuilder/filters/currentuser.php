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
 * Avatar report current user filter. Filter the avatars list collected by the current user.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\reportbuilder\filters;

use core_reportbuilder\local\helpers\database;

/**
 * Current user filter. Filter the avatars list collected by the current user.
 */
class currentuser extends \core_reportbuilder\local\filters\boolean_select {

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $USER;

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $paramname = database::generate_param_name();

        $operator = $values["{$this->name}_operator"] ?? self::ANY_VALUE;
        switch ($operator) {
            case self::CHECKED:
                $fieldsql .= " = :{$paramname}";
                $params[$paramname] = $USER->id;
                break;
            default:
                // Invalid or inactive filter.
                return ['', []];
        }

        return [$fieldsql, $params];
    }
}
