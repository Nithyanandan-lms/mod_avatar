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
 * Defines backup_avataraddon_pro_subplugin class
 *
 * @package     avataraddon_pro
 * @copyright   2025 bdecent GmbH <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to backup pro addon data
 */
class backup_avataraddon_pro_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to avatar element
     */
    protected function define_avatar_subplugin_structure() {

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subpluginprodata = new backup_nested_element('avataraddon_pro', ['id'], [
            'cmavatarid', 'collectiontotallimit', 'collectionlimitperuser',
            'collectionlimitperinterval', 'collectioninterval',
        ]);

        // Connect XML elements into the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginprodata);

        // Set source to populate the data.
        $subpluginprodata->set_source_table('avataraddon_pro', ['cmavatarid' => backup::VAR_PARENTID]);

        // Define avatar pro data.
        $proavatardata = new backup_nested_element('avataraddon_pro_avatar', ['id'], [
            'avatarid', 'coursecategories', 'includesubcategories', 'cohorts', 'totalcapacity',
        ]);

        // Add avatar pro data to the structure.
        $subpluginwrapper->add_child($proavatardata);

        // Set source to populate the data.
        $proavatardata->set_source_sql(
            "SELECT apa.*
               FROM {avataraddon_pro_avatar} apa
               JOIN {avatar_list} al ON apa.avatarid = al.id
              WHERE al.archived = 0",
            []
        );

        return $subplugin;
    }

    /**
     * Returns the subplugin information to attach to avatar_item element
     */
    protected function define_avatar_item_subplugin_structure() {
        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subpluginprodata = new backup_nested_element('avataraddon_pro_avatar', ['id'], [
            'avatarid', 'coursecategories', 'includesubcategories', 'cohorts', 'totalcapacity',
        ]);

        // Connect XML elements into the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginprodata);

        // Set source to populate the data.
        $subpluginprodata->set_source_table('avataraddon_pro_avatar', ['avatarid' => backup::VAR_PARENTID]);

        return $subplugin;
    }
}
