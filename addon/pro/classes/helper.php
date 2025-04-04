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
 * Pro features for the avatar module.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro;

/**
 * Class Avatar module export and import.
 */
class helper {

    /**
     * Export an avatar to XML format and include all files in a ZIP archive
     *
     * @param int $avatarid The ID of the avatar to export
     * @return string The path to the exported ZIP file
     */
    public static function export_avatar($avatarid) {
        global $DB, $CFG;

        $avatar = $DB->get_record('avatar_list', ['id' => $avatarid]);

        if (!$avatar) {
            return false;
        }

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><avatar></avatar>');

        foreach ($avatar as $key => $value) {
            if ($key !== 'id') {
                $xml->addChild($key, $value);
            }
        }

        $files = $xml->addChild('files');

        $fs = get_file_storage();
        $context = \context_system::instance();

        $fileareas = ['previewimage', 'additionalmedia', 'description'];
        for ($i = 1; $i <= $avatar->variants; $i++) {
            $fileareas[] = "avatarimage{$i}";
            $fileareas[] = "avatarthumbnail{$i}";
            $fileareas[] = "animationstates{$i}";
        }

        $tempdir = make_temp_directory('avataraddon_pro_export');
        $avatardir = $tempdir . '/' . clean_filename($avatar->name) . '_' . $avatarid;
        mkdir($avatardir);

        $xmlfile = $avatardir . '/avatar_data.xml';
        $xml->asXML($xmlfile);

        foreach ($fileareas as $filearea) {
            $areadir = $avatardir . '/' . $filearea;
            mkdir($areadir);
            $areafiles = $fs->get_area_files($context->id, 'mod_avatar', $filearea, $avatarid, 'sortorder', false);
            foreach ($areafiles as $file) {
                $file->copy_content_to($areadir . '/' . $file->get_filename());
            }
        }

        $packer = get_file_packer('application/zip');
        $zipfilename = clean_filename($avatar->name) . '_' . $avatarid . '.zip';
        $zipfile = $tempdir . '/' . $zipfilename;

        $packer->archive_to_pathname([basename($avatardir) => $avatardir], $zipfile);

        // Clean up the temporary directory.
        remove_dir($avatardir);

        return $zipfile;
    }

    /**
     * Import an avatar from a ZIP file.
     *
     * @param string $filepath The path to the uploaded ZIP file
     * @return bool True if import was successful, false otherwise
     */
    public static function import_avatar($filepath) {
        global $CFG, $DB, $USER;

        $packer = get_file_packer('application/zip');
        $tempdir = make_temp_directory('mod_avatar_import');

        if (!$packer->extract_to_pathname($filepath, $tempdir)) {
            return false;
        }

        // Find the avatar directory.
        $avatardirs = glob($tempdir . '/*', GLOB_ONLYDIR);
        if (empty($avatardirs)) {
            return false;
        }
        $avatardir = reset($avatardirs);

        $xmlfile = $avatardir . '/avatar_data.xml';
        if (!file_exists($xmlfile)) {
            echo "XML file not found in the ZIP archive.";exit;
            return false;
        }

        $handle = fopen($xmlfile, 'r');
        if ($handle) {
            $xmlcontent = '';
            while (!feof($handle)) {
                $xmlcontent .= fread($handle, 8192);
            }
            fclose($handle);
            $xmldata = simplexml_load_string($xmlcontent);

        }

        if (empty($xmldata)) {
            echo "XML data not found in the ZIP archive.";exit;
            return false;
        }

        $avatardata = [];
        foreach ($xmldata as $key => $element) {

            $avatardata[$key] = (string)$element[0];
        }

        // Prepare the data for insert.
        $time = time();
        $avatardata['timecreated'] = $time;
        $avatardata['timemodified'] = $time;
        $avatardata['usermodified'] = $USER->id;
        $avatardata['archived'] = 0;

        // Insert the avatar data into the database.
        $avatarid = $DB->insert_record('avatar_list', (object) $avatardata);

        // Insert the pro data.
        $avatardata['avatarid'] = $avatarid;
        if (!$DB->record_exists('avataraddon_pro_avatar', ['avatarid' => $avatarid])) {
            $DB->insert_record('avataraddon_pro_avatar', (object) $avatardata);
        }

        if ($avatarid) {
            // Handle file imports (preview image, additional media, variant images, etc.).
            $fs = get_file_storage();
            $context = \context_system::instance();

            $fileareas = ['previewimage', 'additionalmedia', 'description'];
            for ($i = 1; $i <= $avatardata['variants']; $i++) {
                $fileareas[] = "avatarimage{$i}";
                $fileareas[] = "avatarthumbnail{$i}";
                $fileareas[] = "animationstates{$i}";
            }

            foreach ($fileareas as $filearea) {
                $areadir = $avatardir . '/' . $filearea;
                if (is_dir($areadir)) {
                    $files = scandir($areadir);
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            $filepath = $areadir . '/' . $file;
                            $fs->create_file_from_pathname([
                                'contextid' => $context->id,
                                'component' => 'mod_avatar',
                                'filearea' => $filearea,
                                'itemid' => $avatarid,
                                'filepath' => '/',
                                'filename' => $file,
                            ], $filepath);
                        }
                    }
                }
            }

            // Clean up temporary directory.
            remove_dir($tempdir);

            return true;
        }

        // Clean up temporary directory.
        remove_dir($tempdir);

        return false;
    }
}
