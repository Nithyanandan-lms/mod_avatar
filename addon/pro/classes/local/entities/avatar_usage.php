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
 * Avatar usage entity for report builder.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;
use mod_avatar\avatar;
use avataraddon_pro\reportbuilder\filters\cohort as cohort_filter;
use avataraddon_pro\reportbuilder\filters\currentuser;
use avataraddon_pro\reportbuilder\filters\myusers;
use avataraddon_pro\reportbuilder\filters\mycohort;

/**
 * Avatar usage entiry.
 */
class avatar_usage extends base {

    /**
     * Get the default tables for the entity.
     *
     * @return array
     */
    protected function get_default_tables(): array {
        return ['avatar_list', 'avatar_user', 'course_modules', 'course', 'cohort_members', 'cohort', 'user'];
    }

    /**
     * Get the default title for the entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('usage', 'mod_avatar');
    }

    /**
     * Get the default entity name.
     *
     * @return string
     */
    public function initialise(): base {
        $columns = $this->add_all_columns();

        [$filters, $conditions] = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        foreach ($conditions as $condition) {
            $this->add_condition($condition);
        }

        return $this;
    }

    /**
     * Define the columns for the entity.
     *
     * @return void
     */
    protected function add_all_columns(): void {

        $this->set_table_alias('avatar_list', 'al');

        $al = $this->get_table_alias('avatar_list');
        $au = $this->get_table_alias('avatar_user');
        $cm = $this->get_table_alias('course_modules');
        $c = $this->get_table_alias('course');

        // Number of courses column.
        $numcoursescolumn = new column(
            'numcourses',
            new lang_string('numcourses', 'mod_avatar'),
            'avatar_usage'
        );
        $numcoursescolumn->add_field("{$al}.id", "numcourses");
        $numcoursescolumn->add_field("{$al}.id", "id");
        $numcoursescolumn->add_callback(static function ($id, $row) {
            $avatar = new avatar($id, $row);
            $usage = $avatar->info->get_avatar_usage();
            return $usage['courses'];
        });
        $this->add_column($numcoursescolumn);

        // Number of users column.
        $numuserscolumn = new column(
            'numusers',
            new lang_string('numusers', 'mod_avatar'),
            'avatar_usage'
        );
        $numuserscolumn->add_field("nu.count", 'numusers');
        $numuserscolumn->add_join("LEFT JOIN (
            SELECT count(*) as count, avatarid FROM {avatar_user} GROUP BY avatarid
        ) nu ON nu.avatarid = {$al}.id");
        $this->add_column($numuserscolumn);

        // Number of activities column.
        $numactivitiescolumn = new column(
            'numactivities',
            new lang_string('numactivities', 'mod_avatar'),
            'avatar_usage'
        );
        $numactivitiescolumn->add_field("{$al}.id", "numactivities");
        $numactivitiescolumn->add_field("{$al}.id", "id");
        $numactivitiescolumn->add_callback(static function ($id, $row) {
            $avatar = new avatar($id, $row);
            $usage = $avatar->info->get_avatar_usage();
            return $usage['activities'];
        });

        $this->add_column($numactivitiescolumn);

        // First collected time column.
        $firstcollectedtimecolumn = new column(
            'firstcollectedtime',
            new lang_string('firstcollectedtime', 'mod_avatar'),
            'avatar_usage'
        );
        $firstcollectedtimecolumn->add_field("(SELECT MIN({$au}.timecollected)
            FROM {avatar_user} {$au}
            WHERE {$au}.avatarid = {$al}.id)", 'firstcollectedtime');
        $firstcollectedtimecolumn->add_callback(static function ($timecollected) {
            return $timecollected ? userdate($timecollected) : '';
        });
        $this->add_column($firstcollectedtimecolumn);

        // First collected user column.
        $firstcollectedusercolumn = new column(
            'firstcollecteduser',
            new lang_string('firstcollecteduser', 'mod_avatar'),
            'avatar_usage'
        );

        $firstcollectedusercolumn->add_field("b.userid", 'firstcollecteduser');
        $firstcollectedusercolumn->add_joins(["LEFT JOIN (
            SELECT aus.* FROM (
                SELECT avatarid, MIN(timecollected) AS first_time
                FROM mdl_avatar_user
                GROUP BY avatarid
            ) a
            JOIN mdl_avatar_user aus ON aus.avatarid = a.avatarid
            WHERE aus.timecollected = a.first_time
        ) b ON {$al}.id = b.avatarid"]);

        $firstcollectedusercolumn->add_callback(static function ($firstcollecteduser) {
            return $firstcollecteduser ? fullname(\core_user::get_user($firstcollecteduser)) : '';
        });
        $this->add_column($firstcollectedusercolumn);

        // Last collected time column.
        $lastcollectedtimecolumn = new column(
            'lastcollectedtime',
            new lang_string('lastcollectedtime', 'mod_avatar'),
            'avatar_usage'
        );
        $lastcollectedtimecolumn->add_field("(SELECT MAX({$au}.timecollected)
            FROM {avatar_user} {$au}
            WHERE {$au}.avatarid = {$al}.id)", 'lastcollectedtime');
        $lastcollectedtimecolumn->add_callback(static function ($timecollected) {
            return $timecollected ? userdate($timecollected) : '';
        });
        $this->add_column($lastcollectedtimecolumn);

        // Last collected user column.
        $lastcollectedusercolumn = new column(
            'lastcollecteduser',
            new lang_string('lastcollecteduser', 'mod_avatar'),
            'avatar_usage'
        );
        $lastcollectedusercolumn->add_field("lau.userid", 'firstcollecteduser');
        $lastcollectedusercolumn->add_joins(["LEFT JOIN (
            SELECT aus.* FROM (
                SELECT avatarid, MAX(timecollected) AS lasttime
                FROM mdl_avatar_user
                GROUP BY avatarid
            ) a
            JOIN mdl_avatar_user aus ON aus.avatarid = a.avatarid
            WHERE aus.timecollected = a.lasttime
        ) lau ON {$al}.id = lau.avatarid"]);
        $lastcollectedusercolumn->add_callback(static function ($lastcollecteduser) {
            return $lastcollecteduser ? fullname(\core_user::get_user($lastcollecteduser)) : '';
        });
        $this->add_column($lastcollectedusercolumn);

        // Most collected in course column.
        $mostcollectedincoursecolumn = new column(
            'mostcollectedincourse',
            new lang_string('mostcollectedincourse', 'mod_avatar'),
            'avatar_usage'
        );
        $mostcollectedincoursecolumn->add_field("mcc.coursename", 'mostcollectedincourse');
        $mostcollectedincoursecolumn->add_joins(["LEFT JOIN (
            SELECT au.avatarid, c.id AS courseid, c.fullname AS coursename, COUNT(*) AS collection_count,
                   ROW_NUMBER() OVER (PARTITION BY au.avatarid ORDER BY COUNT(*) DESC) AS rn
            FROM {avatar_user} au
            JOIN {course_modules} cm ON cm.instance = au.avatarid
            JOIN {course} c ON c.id = cm.course
            GROUP BY au.avatarid, c.id, c.fullname
        ) mcc ON mcc.avatarid = {$al}.id AND mcc.rn = 1"]);
        $this->add_column($mostcollectedincoursecolumn);

        // Most collected in cohort column.
        $mostcollectedincohortcolumn = new column(
            'mostcollectedincohort',
            new lang_string('mostcollectedincohort', 'mod_avatar'),
            'avatar_usage'
        );
        $mostcollectedincohortcolumn->add_field("mcch.cohortname", 'mostcollectedincohort');
        $mostcollectedincohortcolumn->add_joins(["LEFT JOIN (
            SELECT au.avatarid, ch.id AS cohortid, ch.name AS cohortname, COUNT(*) AS collection_count,
                   ROW_NUMBER() OVER (PARTITION BY au.avatarid ORDER BY COUNT(*) DESC) AS rn
            FROM {avatar_user} au
            JOIN {cohort_members} cm ON cm.userid = au.userid
            JOIN {cohort} ch ON ch.id = cm.cohortid
            GROUP BY au.avatarid, ch.id, ch.name
        ) mcch ON mcch.avatarid = {$al}.id AND mcch.rn = 1"]);
        $this->add_column($mostcollectedincohortcolumn);

    }

    /**
     * Get all filters.
     *
     * @return array
     */
    protected function get_all_filters(): array {
        $filters = [];

        $al = $this->get_table_alias('avatar_list');
        $au = $this->get_table_alias('avatar_user');

        // Cohort filter.
        $filters[] = (new filter(
            cohort_filter::class,
            'cohortselect',
            new lang_string('selectcohort', 'core_cohort'),
            $this->get_entity_name(),
            "{$al}.cohorts",
        ))
            ->add_joins($this->get_joins());

        // Current user same cohorts.
        $mycohort = (new filter(
            mycohort::class,
            'mycohort',
            new lang_string('mycohorts', 'mod_avatar'),
            $this->get_entity_name(),
            "{$al}.cohorts"
        ))
        ->add_joins($this->get_joins());
        $conditions[] = $mycohort;

        // Current user avatar filter.
        $currentuseravatar = (new filter(
            currentuser::class,
            'currentuseravatar',
            new lang_string('currentuseravatar', 'mod_avatar'),
            $this->get_entity_name(),
            "{$au}.userid"
        ))
        ->add_joins(["LEFT JOIN {avatar_user} {$au} ON {$au}.avatarid = {$al}.id"]);

        $conditions[] = $currentuseravatar;

        // My users avatar filter.
        $myusers = (new filter(
            myusers::class,
            'myusers',
            new lang_string('myusers', 'mod_avatar'),
            $this->get_entity_name(),
            "{$au}.userid"
        ))
        ->add_joins(["LEFT JOIN {avatar_user} {$au} ON {$au}.avatarid = {$al}.id"]);

        $conditions[] = $myusers;

        return [$filters, $conditions];
    }
}
