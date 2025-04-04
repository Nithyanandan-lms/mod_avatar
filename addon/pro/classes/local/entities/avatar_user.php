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
 * Avatar user entity for report builder.
 *
 * @package    avataraddon_pro
 * @copyright  2025 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace avataraddon_pro\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\filter;

/**
 * Avatar user entity for report builder.
 */
class avatar_user extends base {

    /**
     * Database tables that this entity uses,
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['avatar_user'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('user_avatars', 'mod_avatar');
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

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        return $this;
    }

    /**
     * List of all available columns.
     *
     * @return array
     */
    protected function get_all_columns(): array {
        $tablealias = $this->get_table_alias('avatar_user');

        $columns = [];

        // Variant column.
        $columns[] = $this->get_variant_column($tablealias);

        // Time collected column.
        $columns[] = $this->get_timecollected_column($tablealias);

        // Time modified column.
        $columns[] = $this->get_primary_column($tablealias);

        // Interval since the time collected.
        $columns[] = $this->get_timesincecollected_column($tablealias);

        return $columns;
    }

    /**
     * List of all available filters
     *
     * @return array
     */
    protected function get_all_filters(): array {
        $filters = [];

        // Variant filter.
        $filters[] = (new filter(
            number::class,
            'variant',
            new lang_string('variant', 'mod_avatar'),
            $this->get_entity_name(),
            'variant'
        ));

        return $filters;
    }

    /**
     * Column of the variant collected.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_variant_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'variant',
            new lang_string('variant', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.variant")
            ->set_is_sortable(true);
    }

    /**
     * Time of the avatar collected column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_timecollected_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'timecollected',
            new lang_string('timecollected', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.timecollected")
            ->set_is_sortable(true)
            ->add_callback(fn($v, $row) => format::userdate((int) $v, $row));
    }

    /**
     * Primary status of the avatar column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_primary_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'isprimary',
            new lang_string('type', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.isprimary")
            ->set_is_sortable(true)
            ->add_callback(fn($v, $row) => $v == 1
                ? new lang_string('primary', 'mod_avatar') : new lang_string('secondary', 'mod_avatar'));
    }

    /**
     * Avatar modified time column.
     *
     * @param string $tablealias
     * @return \core_reportbuilder\local\report\column
     */
    protected function get_timesincecollected_column(string $tablealias): \core_reportbuilder\local\report\column {
        return (new \core_reportbuilder\local\report\column(
            'timesincecollected',
            new lang_string('timesincecollected', 'mod_avatar'),
            $this->get_entity_name()
        ))
            ->add_field("{$tablealias}.timecollected")
            ->set_is_sortable(true)
            ->add_callback(function($timecollected, $row) {
                $since = time() - $timecollected;
                $days = floor($since / (60 * 60 * 24));
                return $days . ' ' . new lang_string('days');
            });
    }
}
