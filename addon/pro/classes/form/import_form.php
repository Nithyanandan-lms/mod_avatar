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
 * Avatar import form
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Import avatar form.
 */
class import_form extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'avatarfile', get_string('avatarfile', 'mod_avatar'), null,
            ['maxbytes' => 0, 'accepted_types' => '.zip']);
        $mform->addRule('avatarfile', null, 'required');

        $this->add_action_buttons(true, get_string('import', 'mod_avatar'));
    }

    /**
     * Validation.
     *
     * @param object $data
     * @param object $files
     *
     * @return void
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['avatarfile'])) {
            $errors['avatarfile'] = get_string('required');
        }

        return $errors;
    }
}
