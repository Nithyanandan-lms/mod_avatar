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
 * Avatar list entity for report builder.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\category;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\filter;
use avataraddon_pro\reportbuilder\filters\category as customcategory;

use core_course_category;
use moodle_url;
use html_writer;

/**
 * Avatar list entity for report builder.
 */
class avatar_list extends base {

    /**
     * Database tables for entity.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['avatar_list', 'course_categories', 'user'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('avatar', 'mod_avatar');
    }

    /**
     * Initialize the entity.
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        list($filters, $conditions) = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        foreach ($conditions as $condition) {
            $this->add_condition($condition);
        }

        return $this;
    }

    /**
     * List of all available columns.
     *
     * @return array
     */
    protected function get_all_columns(): array {

        $this->set_table_alias('avatar_list', 'al');
        $this->set_table_alias('course_categories', 'cc');

        $tablealias = $this->get_table_alias('avatar_list');

        $columns = [];

        $columns[] = $this->get_name_column($tablealias);
        $columns[] = $this->get_status_column($tablealias);
        $columns[] = $this->get_idnumber_column($tablealias);
        $columns[] = $this->get_description_column($tablealias);
        $columns[] = $this->get_timecreated_column($tablealias);
        $columns[] = $this->get_createdby_column($tablealias);
        $columns[] = $this->get_createdbylinked_column($tablealias);
        $columns[] = $this->get_course_categories_column($tablealias);
        $columns[] = $this->get_cohorts_column($tablealias);
        $columns[] = $this->get_variants_column($tablealias);

        return $columns;
    }

    /**
     * List of all available filters.
     *
     * @return array
     */
    protected function get_all_filters(): array {
        $filters = [];
        $conditions = [];

        $al = $this->get_table_alias('avatar_list');
        $cc = $this->get_table_alias('course_categories');
        $ua = $this->get_table_alias('user');

        // Course category filter.
        $categoryfilter = (new filter(
            customcategory::class,
            'category',
            new lang_string('categoryselect', 'core_reportbuilder'),
            $this->get_entity_name(),
            "{$al}.coursecategories"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                'requiredcapabilities' => 'moodle/category:viewcourselist',
            ]);

        $filters[] = $categoryfilter;
        $conditions[] = $categoryfilter;

        // Status filter.
        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('status', 'mod_avatar'),
            $this->get_entity_name(),
            "{$al}.status"
        ))->set_options([
            0 => new lang_string('inactive'),
            1 => new lang_string('active'),
        ]);

        $conditions[] = (new filter(
            select::class,
            'status',
            new lang_string('status', 'mod_avatar'),
            $this->get_entity_name(),
            "{$al}.archived"
        ))->set_options([
            1 => new lang_string('archived', 'mod_avatar'),
            0 => new lang_string('active'),
        ]);

        // Name filter.
        $name = (new filter(
            text::class,
            'name',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$al}.name"
        ));
        $filters[] = $name;
        $conditions[] = $name;

        // ID number filter.
        $idnumber = (new filter(
            text::class,
            'idnumber',
            new lang_string('idnumber'),
            $this->get_entity_name(),
            "{$al}.idnumber"
        ));

        $filters[] = $idnumber;
        $conditions[] = $idnumber;

        // Time created filter.
        $timecreated = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated'),
            $this->get_entity_name(),
            "{$al}.timecreated"
        ));

        $filters[] = $timecreated;
        $conditions[] = $timecreated;

        // Created by filter.
        $canviewfullnames = has_capability('moodle/site:viewfullnames', \context_system::instance());
        [$fullnamesql, $fullnameparams] = \core_user\fields::get_sql_fullname($ua, $canviewfullnames);
        $createdby = (new filter(
            text::class,
            'usermodified',
            new lang_string('createdby', 'mod_avatar'),
            $this->get_entity_name(),
            $fullnamesql,
            $fullnameparams
        ))
        ->add_joins([
            "LEFT JOIN {user} {$ua} ON {$ua}.id = {$al}.usermodified"]);

        $filters[] = $createdby;
        $conditions[] = $createdby;

        return [$filters, $conditions];
    }

    /**
     * Name of the avatar column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_name_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.name")
            ->set_is_sortable(true);
    }

    /**
     * Status of the avatar column
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_status_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'status',
            new lang_string('status', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.status")
            ->set_is_sortable(true)
            ->add_callback(static function($value): string {
                return $value ? get_string('status_active', 'mod_avatar') : get_string('status_inactive', 'mod_avatar');
            });
    }

    /**
     * Idnumber of the avatar.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_idnumber_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'idnumber',
            new lang_string('idnumber'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.idnumber")
            ->set_is_sortable(true);
    }

    /**
     * Description of the avatar column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_description_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'description',
            new lang_string('description'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.description")
            ->add_field("{$tablealias}.descriptionformat")
            ->add_callback(static function($description, $value): string {
                return format_text($description, $value->descriptionformat);
            });
    }

    /**
     * Avatar created time column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_timecreated_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'timecreated',
            new lang_string('timecreated'),
            $this->get_entity_name()
        ))
        ->add_field("{$tablealias}.timecreated")
        ->set_is_sortable(true)
        ->add_callback(fn($v, $row) => format::userdate((int) $v, $row));
    }

    /**
     * Avatar created by user column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_createdby_column(string $tablealias): \core_reportbuilder\local\report\column {
        global $DB;

        $fullname = $DB->sql_fullname('uc.firstname', 'uc.lastname');
        return (new \core_reportbuilder\local\report\column(
            'usermodified',
            new lang_string('createdby', 'mod_avatar'),
            $this->get_entity_name()
        ))
        ->add_field("{$fullname}", 'fullname')
        ->add_joins(["LEFT JOIN {user} uc ON uc.id = {$tablealias}.usermodified"])
        ->set_is_sortable(true);
    }

    /**
     * Avatar created by user with link column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_createdbylinked_column(string $tablealias): \core_reportbuilder\local\report\column {
        global $DB;

        $fullname = $DB->sql_fullname('uc.firstname', 'uc.lastname');
        return (new \core_reportbuilder\local\report\column(
            'usermodifiedlinked',
            new lang_string('createdbylinked', 'mod_avatar'),
            $this->get_entity_name()
        ))
        ->add_field("{$tablealias}.usermodified")
        ->add_field("{$fullname}", 'fullname')
        ->add_joins(["LEFT JOIN {user} uc ON uc.id = {$tablealias}.usermodified"])
        ->set_is_sortable(true)
        ->add_callback(fn($v, $row) => html_writer::link(new moodle_url('/user/profile.php', ['id' => $v]), $row->fullname));
    }

    /**
     * Course categories column
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_course_categories_column(string $tablealias): \core_reportbuilder\local\report\column {

        return (new \core_reportbuilder\local\report\column(
            'coursecategories',
            new lang_string('coursecategories', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.coursecategories")
            ->set_is_sortable(false)
            ->add_callback(function($value) {
                $categories = json_decode($value, true);
                if (empty($categories)) {
                    return get_string('none');
                }
                $categorynames = array_map(function($categoryid) {
                    $category = \core_course_category::get($categoryid, IGNORE_MISSING);
                    return $category ? $category->get_formatted_name() : '';
                }, $categories);
                return implode(', ', array_filter($categorynames));
            });
    }

    /**
     * Cohorts column
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_cohorts_column(string $tablealias): \core_reportbuilder\local\report\column {
        global $DB;

        return (new \core_reportbuilder\local\report\column(
            'cohorts',
            new lang_string('cohorts', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.cohorts")
            ->set_is_sortable(false)
            ->add_callback(function($value) use ($DB) {
                $cohorts = json_decode($value, true);
                if (empty($cohorts)) {
                    return get_string('none');
                }
                $cohortnames = array_map(function($cohortid) use ($DB) {
                    $cohort = $DB->get_record('cohort', ['id' => $cohortid]);
                    return $cohort ? $cohort->name : '';
                }, $cohorts);
                return implode(', ', array_filter($cohortnames));
            });
    }

    /**
     * Variants column
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_variants_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'variants',
            new lang_string('variants', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.variants")
            ->set_is_sortable(true);
    }
}
