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

class enrol {
    /**
     * @param $courseid
     *
     * @return \stdClass|bool
     *
     * @throws \dml_exception
     */
    public static function get_manual_enrol_method_by_course($courseid) {
        global $DB, $CFG;

        try {
            return $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], '*', MUST_EXIST);
        } catch (\Exception $e) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * @param $userid
     * @param $courseid
     * @param $roleid
     *
     * @return false|void
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function enrol_user_in_moodle_course($userid, $courseid, $roleid) {
        global $CFG;

        $courseenrol = self::get_manual_enrol_method_by_course($courseid);

        if (!$courseenrol) {
            return false;
        }

        require_once($CFG->libdir . "/enrollib.php");
        if (!$enrolmanual = enrol_get_plugin('manual')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                throw new \coding_exception('Can not instantiate enrol_manual');
            }

            return false;
        }

        $enrolmanual->enrol_user($courseenrol, $userid, $roleid, time());
    }

    /**
     * @param $userid
     * @param $courseid
     *
     * @return false|void
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function unenrol_user_in_moodle_course($userid, $courseid) {
        global $CFG;

        $courseenrol = self::get_manual_enrol_method_by_course($courseid);

        if (!$courseenrol) {
            return false;
        }

        require_once($CFG->libdir . "/enrollib.php");
        if (!$enrolmanual = enrol_get_plugin('manual')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                throw new \coding_exception('Can not instantiate enrol_manual');
            }

            return false;
        }

        $enrolmanual->unenrol_user($courseenrol, $userid);
    }

    /**
     * @param $teacherid
     * @param $sectionid
     *
     * @return bool
     *
     * @throws \dml_exception
     */
    public static function verify_teacher_enroled_course_section($teacherid, $sectionid) {
        global $DB;

        $section = $DB->get_record('course_sections', ['id' => $sectionid], '*');

        $disciplines = $DB->get_records('int_discipline_section', ['pes_id' => $teacherid]);

        if (!empty($disciplines)) {
            foreach ($disciplines as $discipline) {
                $isenroled = $DB->get_record('course_sections', ['id' => $discipline->sectionid, 'course' => $section->course], 'id');

                if ($isenroled) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $teachermaping
     * @param $courseid
     * @return bool
     * @throws dml_exception
     */
    public static function verify_if_teacher_enroled_on_another_section_course($teachermaping, $courseid) {
        global $DB;

        foreach ($teachermaping as $maping) {

            // Verifica se o professor está vinculado em alguma section do mesmo curso.
            $section = $DB->get_record('course_sections', ['id' => $maping->sectionid]);
            if ($section->course == $courseid) {
                return true;
            }
        }

        return false;
    }
}
