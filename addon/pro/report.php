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
 * Avatar usage report
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;

$cmid = optional_param('cmid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

// Set up page parameters.
if ($cmid) {
    list($course, $cm) = get_course_and_cm_from_cmid($cmid);
    require_login($course);

    $context = \context_module::instance($cm->id);
    $PAGE->set_context($context);
    $PAGE->set_cm($cm);
    $PAGE->set_course($course);

    $PAGE->set_url(new \moodle_url('/mod/avatar/addon/pro/report.php', ['cmid' => $cmid]));
    $PAGE->set_title(get_string('avatarreport', 'mod_avatar'));
    $PAGE->set_heading($cm->name);
} else {
    admin_externalpage_setup('avatarreport');
    $context = context_system::instance();
    $course = get_course(SITEID);
    $PAGE->set_course($course);
    $PAGE->set_url(new \moodle_url('/mod/avatar/addon/pro/report.php'));
}

// Capability checks.
require_capability('avataraddon/pro:viewreport', $context);

// Set up the table.
$table = new \avataraddon_pro\table\avatar_report_table('avatar_report_' . $cmid, $context);

$filterset = new \core_user\table\participants_filterset();
$filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$course->id]));

$table->set_filterset($filterset);

// Download if requested.
if ($download) {
    $table->is_downloading($download, 'avatar_report');
}

if (!$table->is_downloading()) {
    // Only output headers if not downloading.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('avatarreport', 'mod_avatar'));
}

// Display the table.
$table->out(25, true); // Show 25 per page.

if (!$table->is_downloading()) {

    $PAGE->requires->js_call_amd('avataraddon_pro/avatar_reports', 'init', [$cmid]);
    echo $OUTPUT->footer();
}
