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

class group {
    /**
     * @param $grpid
     * @return int|bool
     *
     * @throws \dml_exception
     */
    public static function get_group_by_grp_id($grpid) {
        global $DB, $CFG;

        try {
            $result = $DB->get_record('int_grupo_group', ['grp_id' => $grpid], 'id, groupid');

            if ($result) {
                return $result->groupid;
            }

            return false;
        } catch (\Exception $e) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * @param $courseid
     * @param $name
     * @return mixed
     * @throws dml_exception
     */
    public static function get_group_by_name($courseid, $name) {
        global $DB;

        return $DB->get_record('groups', ['courseid' => $courseid, 'name' => $name]);
    }
}
