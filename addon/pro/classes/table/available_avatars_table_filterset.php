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
 * Available avatars table filterset.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;

/**
 * Available avatars table filterset.
 */
class available_avatars_table_filterset extends filterset {

    /**
     * Get the required filters.
     *
     * @return array.
     */
    public function get_required_filters(): array {
        return [
            'cmid' => integer_filter::class,
            'userid' => integer_filter::class,
        ];
    }

    /**
     * Get the optional filters.
     *
     * @return array
     */
    public function get_optional_filters(): array {
        return [];
    }
}
