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

namespace local_integracao\entity;

class section {
    /**
     * @param $ofdid
     * @return mixed
     *
     * @throws \dml_exception
     */
    public static function get_section_by_ofd_id($ofdid) {
        global $DB;

        return $DB->get_record('int_discipline_section', ['ofd_id' => $ofdid]);
    }

    /**
     * @param $courseid
     *
     * @return int
     *
     * @throws \dml_exception
     */
    public static function get_last_section_course($courseid): mixed
    {
        global $DB;

        $sql = 'SELECT section FROM {course_sections} WHERE course = :courseid ORDER BY section DESC LIMIT 1';

        $section = $DB->get_record_sql($sql, ['courseid' => $courseid]);

        return $section->section;
    }
}
