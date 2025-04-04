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
 * User avatars datasource.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use core_cohort\reportbuilder\local\entities\cohort;
use core_course\reportbuilder\local\entities\course_category;
use avataraddon_pro\local\entities\avatar_list;
use avataraddon_pro\local\entities\avatar_user;

/**
 * User avatars datasource definitions.
 */
class user_avatars extends datasource {

    /**
     * Return user friendly name of the report source.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('user_avatars', 'mod_avatar');
    }

    /**
     * Initialise report.
     */
    protected function initialise(): void {

        $avataruser = new avatar_user();
        $this->add_entity($avataruser);

        $avatarlist = new avatar_list();
        $this->add_entity($avatarlist);

        $user = new user();
        $this->add_entity($user);

        $cohort = new cohort();
        $this->add_entity($cohort);

        $coursecategory = new course_category();
        $this->add_entity($coursecategory);

        // Initialize all entities.
        $avataruser->initialise();
        $avatarlist->initialise();

        $avataruseralias = $avataruser->get_table_alias('avatar_user');
        $avatarlistalias = $avatarlist->get_table_alias('avatar_list');
        $ualias = $user->get_table_alias('user');

        $this->set_main_table('avatar_user', "{$avataruseralias}");

        // Define SQL joins.
        $this->add_join("LEFT JOIN {user} {$ualias} ON {$ualias}.id = {$avataruseralias}.userid");
        $this->add_join("LEFT JOIN {avatar_list} {$avatarlistalias} ON {$avatarlistalias}.id = {$avataruseralias}.avatarid");

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
     * List of the default columns.
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return ['user:fullname', 'avatar_list:name', 'avatar_user:timecollected', 'avatar_user:variant'];
    }

    /**
     * List of the default filters.
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return ['user:fullname', 'avatar_list:name'];
    }

    /**
     * List of the default conditions.
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return ['user:fullname', 'avatar_list:name'];
    }
}
