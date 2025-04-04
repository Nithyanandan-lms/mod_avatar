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
 * Avatar addon pro - Library functions.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Renders the avatar popover in the navbar.
 *
 * @param \renderer_base $renderer The renderer instance.
 * @return string HTML for the avatar popover.
 */
function avataraddon_pro_render_navbar_output(\renderer_base $renderer) {
    global $PAGE, $USER;

    if (!\mod_avatar\plugininfo\avataraddon::has_avataraddon_pro()) {
        return '';
    }

    if (get_config('avataraddon_pro', 'displayavatarpopover') != \avataraddon_pro\avatar::POPOVERENABLED) {
        return '';
    }

    // Render the popover content.
    $avatarrenderer = $PAGE->get_renderer('avataraddon_pro');
    $popovercontent = $avatarrenderer->render_avatar_popover($USER->id);
    return $popovercontent;
}

/**
 * Plugin files and data sources to be used in report builder.
 *
 * @return array
 */
function avataraddon_pro_reportbuilder_reports(): array {
    return [
        'datasources' => [
            \avataraddon_pro\reportbuilder\datasource\avatar_overview::class,
            \avataraddon_pro\reportbuilder\datasource\user_avatars::class,
        ],
    ];
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $node The node to add module settings to
 */
function avataraddon_pro_extend_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE;

    if (has_capability('mod/avatar:addinstance', $PAGE->cm->context)) {
        $url = new \moodle_url('/mod/avatar/addon/pro/report.php', ['cmid' => $PAGE->cm->id, 'sesskey' => sesskey()]);
        $node->add(
            get_string('report', 'core'), $url, navigation_node::TYPE_SETTING, null, 'avatarreport', null
        );
    }
}

/**
 * Renders the available avatars table.
 *
 * @param array $args The arguments passed to the function.
 * @return string HTML for the available avatars table.
 */
function avataraddon_pro_output_fragment_available_avatars($args) {
    global $OUTPUT;

    $cmid = $args['cmid'];
    $userid = $args['userid'];
    $filterset = new \avataraddon_pro\table\available_avatars_table_filterset();
    $filterset->add_filter(
        new \core_table\local\filter\integer_filter('userid', \core_table\local\filter\filter::JOINTYPE_DEFAULT, [(int)$userid]));
    $table = new \avataraddon_pro\table\available_avatars_table(
        'available_avatars_' . $cmid,
        $cmid
    );

    $table->set_filterset($filterset);
    ob_start();
    $table->out(25, true);
    $tablecontent = ob_get_clean();

    return $tablecontent;
}
