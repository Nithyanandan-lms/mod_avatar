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
 * Avatar overview datasource.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\user;
use avataraddon_pro\local\entities\avatar_list;
use avataraddon_pro\local\entities\avatar_user;
use avataraddon_pro\local\entities\avatar_usage;

/**
 * Avatar overview datasoure definitions.
 */
class avatar_overview extends datasource {

    /**
     * Return user friendly name of the report source.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('avatar_overview', 'mod_avatar');
    }

    /**
     * Initialise report.
     */
    protected function initialise(): void {
        $avatarlist = new avatar_list();
        $this->add_entity($avatarlist);

        $avatarusage = new avatar_usage();

        $this->add_entity($avatarusage);

        // Initialize all entities.
        $avatarlist->initialise();
        $avatarlistalias = $avatarlist->get_table_alias('avatar_list');

        $this->set_main_table('avatar_list', $avatarlistalias);

        if (method_exists($this, 'add_all_from_entities')) {
            $this->add_all_from_entities();
        } else {
            // Add all columns from avatar_list entity.
            $this->add_columns_from_entity($avatarlist->get_entity_name());

            // Add all filters from avatar_list entity.
            $this->add_filters_from_entity($avatarlist->get_entity_name());

            // Instead of adding all conditions, we'll add specific ones.
            $this->add_conditions_from_entity($avatarlist->get_entity_name());
        }
    }

    /**
     *  List of the default columns.
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'avatar_list:name',
            'avatar_list:status',
            'avatar_list:idnumber',
            'avatar_list:coursecategories',
            'avatar_list:cohorts',
            'avatar_list:variants',
            'avatar_list:timecreated',
        ];
    }

    /**
     *  List of the default filters.
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'avatar_list:name',
            'avatar_list:status',
            'avatar_list:coursecategories',
            'avatar_list:cohorts',
            'avatar_list:variants',
        ];
    }

    /**
     *  List of the default conditions.
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return ['avatar_list:status'];
    }
}
