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
 * Settings for the avatar pro addon.
 *
 * @package   avataraddon_pro
 * @copyright 2025 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Display avatar popover.
    $settings->add(new admin_setting_configselect('avataraddon_pro/displayavatarpopover',
        get_string('displayavatarpopover', 'mod_avatar'),
        get_string('displayavatarpopover_desc', 'mod_avatar'),
        'disabled',
        [
            avataraddon_pro\avatar::POPOVERDISABLED => get_string('disabled', 'mod_avatar'),
            avataraddon_pro\avatar::POPOVERENABLED => get_string('enabled', 'mod_avatar'),
        ])
    );

    // Availability settings.
    $settings->add(new admin_setting_heading('avatardefaultavailabilitysettings',
        get_string('availabilitysettings', 'mod_avatar'),
        get_string('availabilitysettings_desc', 'mod_avatar')));

    $settings->add(new admin_setting_configselect('avataraddon_pro/displaymode',
        get_string('displaymode', 'mod_avatar'),
        get_string('displaymode_help', 'mod_avatar'),
        0,
        [
            0 => get_string('displaymodepage', 'mod_avatar'),
            1 => get_string('displaymodeinline', 'mod_avatar'),
        ])
    );

    $settings->add(new admin_setting_configtext('avataraddon_pro/collectiontotallimit',
        get_string('collectiontotallimit', 'mod_avatar'),
        get_string('collectiontotallimit_desc', 'mod_avatar'),
        0,
        PARAM_INT));

    $settings->add(new admin_setting_configtext('avataraddon_pro/collectionlimitperuser',
        get_string('collectionlimitperuser', 'mod_avatar'),
        get_string('collectionlimitperuser_desc', 'mod_avatar'),
        0,
        PARAM_INT));

    $settings->add(new admin_setting_configtext('avataraddon_pro/collectionlimitperinterval',
        get_string('collectionlimitperinterval', 'mod_avatar'),
        get_string('collectionlimitperinterval_desc', 'mod_avatar'),
        0,
        PARAM_INT));

    $settings->add(new admin_setting_configduration('avataraddon_pro/collectionlimitinterval',
        get_string('collectionlimitinterval', 'mod_avatar'),
        get_string('collectionlimitinterval_help', 'mod_avatar'),
        0));

    // Automatic assignment settings.
    $settings->add(new admin_setting_heading('avatarautomaticassignment',
        get_string('automaticassignment', 'mod_avatar'),
        get_string('automaticassignment_desc', 'mod_avatar')));

    // User profile field setting.
    $profilefields = profile_get_custom_fields();
    $profilefieldoptions = [0 => get_string('none')];
    foreach ($profilefields as $field) {
        if ($field->datatype == 'text' || $field->datatype == 'menu') {
            $profilefieldoptions[$field->id] = $field->name;
        }
    }

    // Only show the setting if the user has the capability to auto assign avatars.
    if (!has_capability('avataraddon/pro:autoassign', context_system::instance())) {
        global $CFG;
        $PAGE->add_body_class('disable-avatar-autoassign-config');
        $CFG->forced_plugin_settings['avataraddon_pro']['setinitialavatar'] = get_config('avataraddon_pro', 'setinitialavatar');
        $CFG->forced_plugin_settings['avataraddon_pro']['userprofilefield'] = get_config('avataraddon_pro', 'userprofilefield');
    }

    $profilefield = new admin_setting_configselect('avataraddon_pro/userprofilefield',
        get_string('userprofilefield', 'mod_avatar'),
        get_string('userprofilefield_desc', 'mod_avatar'),
        0,
        $profilefieldoptions
    );

    // When update initiate the task to assign related avatars to users.
    $profilefield->set_updatedcallback(fn($name) => \avataraddon_pro\task\collectavatar::init_task());
    $settings->add($profilefield);

    // Set initial avatar setting.
    $setinitialavatar = new admin_setting_configcheckbox('avataraddon_pro/setinitialavatar',
        get_string('setinitialavatar', 'mod_avatar'),
        get_string('setinitialavatar_desc', 'mod_avatar'),
        0
    );
    $settings->add($setinitialavatar);

}
