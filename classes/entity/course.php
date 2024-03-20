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

class course {
    /**
     * @param $trmid
     *
     * @return int|bool
     *
     * @throws \dml_exception
     */
    public static function get_course_by_trm_id($trmid) {
        global $DB, $CFG;

        try {
            $result = $DB->get_record('int_turma_course', ['trm_id' => $trmid], 'id, courseid');

            if ($result) {
                return $result->courseid;
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
     * @param $groupid
     * @return mixed
     * @throws dml_exception
     */
    public static function get_courseid_by_groupid($groupid) {
        global $DB;

        $group = $DB->get_record('groups', ['id' => $groupid], 'id, courseid', MUST_EXIST);

        return $group->courseid;
    }
}
