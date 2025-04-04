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
 * Defines restore_avataraddon_pro_subplugin class
 *
 * @package     avataraddon_pro
 * @copyright   2025 bdecent GmbH <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to restore pro addon data
 */
class restore_avataraddon_pro_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the subplugin at avatar level
     * @return array of restore_path_element
     */
    protected function define_avatar_subplugin_structure() {
        $paths = [];

        $elepath = $this->get_pathfor('/avataraddon_pro');
        $paths[] = new restore_path_element('avataraddon_pro', $elepath);

        $elepath = $this->get_pathfor('/avataraddon_pro_avatar');
        $paths[] = new restore_path_element('avataraddon_pro_avatar', $elepath);

        return $paths;
    }

    /**
     * Process the avataraddon_pro element
     *
     * @param array $data
     * @return void
     */
    public function process_avataraddon_pro($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // The cmavatarid needs to be mapped to the new avatar id.
        $data->cmavatarid = $this->task->get_activityid();

        // Check if a record already exists for this avatar.
        $existing = $DB->get_record('avataraddon_pro', ['cmavatarid' => $data->cmavatarid]);
        if (!$existing) {
            $newitemid = $DB->insert_record('avataraddon_pro', $data);
            $this->set_mapping('avataraddon_pro', $oldid, $newitemid);
        } else {
            // Update existing record.
            $data->id = $existing->id;
            $DB->update_record('avataraddon_pro', $data);
            $this->set_mapping('avataraddon_pro', $oldid, $existing->id);
        }
    }

    /**
     * Process the avataraddon_pro_avatar element
     * This handles both avatar-level and avatar_item-level elements
     *
     * @param array $data
     * @return void
     */
    public function process_avataraddon_pro_avatar($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Map the avatarid to the new avatar id.
        $data->avatarid = $this->get_mappingid('avatar_item', $data->avatarid);

        if (!$data->avatarid) {
            // Skip if we don't have a valid avatar ID.
            return;
        }

        // Check if a record already exists for this avatar.
        $existing = $DB->get_record('avataraddon_pro_avatar', ['avatarid' => $data->avatarid]);
        if (!$existing) {
            $newitemid = $DB->insert_record('avataraddon_pro_avatar', $data);
            $this->set_mapping('avataraddon_pro_avatar', $oldid, $newitemid);
        } else {
            // Update existing record.
            $data->id = $existing->id;
            $DB->update_record('avataraddon_pro_avatar', $data);
            $this->set_mapping('avataraddon_pro_avatar', $oldid, $existing->id);
        }
    }
}
