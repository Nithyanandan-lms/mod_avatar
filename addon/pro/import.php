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
 * Import avatar from ZIP
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

$context = context_system::instance();

require_login();
require_capability('avataraddon/pro:import', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/avatar/addon/pro/import.php'));
$PAGE->set_title(get_string('importavatar', 'mod_avatar'));
$PAGE->set_heading(get_string('importavatar', 'mod_avatar'));

$form = new \avataraddon_pro\form\import_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/avatar/manage.php'));
} else if ($formdata = $form->get_data()) {

    $filename = $form->get_new_filename('avatarfile');
    $tempdir = make_temp_directory('mod_avatar_import');
    $tempfile = $tempdir . '/' . $filename;

    $form->save_file('avatarfile', $tempfile);

    // Process the uploaded file.
    $success = \avataraddon_pro\helper::import_avatar($tempfile, $filename);

    if ($success) {
        \core\notification::success(get_string('avatarimported', 'mod_avatar'));
    } else {
        \core\notification::error(get_string('avatarimportfailed', 'mod_avatar'));
    }

    redirect(new moodle_url('/mod/avatar/manage.php'));
}

$PAGE->navbar->add(get_string('pluginname', 'mod_avatar'), new moodle_url('/mod/avatar/index.php'));
$PAGE->navbar->add(get_string('manageavatars', 'mod_avatar'), new moodle_url('/mod/avatar/manage.php'));
$PAGE->navbar->add(get_string('importavatar', 'mod_avatar'));

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
