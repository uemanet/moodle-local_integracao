<?php
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
 * Integracao Web Service
 *
 * @package    contrib
 * @subpackage local_wsintegracao
 * @copyright  2017 Uemanet
 * @author     Uemanet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class wsintegracao_base extends external_api
{
    protected static function get_course_by_trm_id($trm_id)
    {
        global $DB;
        try {
            $courseid = 0;

            $result = $DB->get_record('int_turma_course', array('trm_id'=>$trm_id), '*');

            if($result) {
                $courseid = $result->courseid;
            }

            return $courseid;

        } catch (\Exception $e) {
            if(helper::debug()){
                throw new moodle_exception('databaseaccesserror', 'local_wsintegracao', null, null, '');
            }
        }
    }

    protected static function get_user_by_pes_id($pes_id)
    {
        global $DB;
        try {
            $userid = null;

            $result = $DB->get_record('int_pessoa_user', array('pes_id'=>$pes_id), '*');

            if($result) {
                $userid = $result->userid;
            }

            return $userid;

        } catch (\Exception $e) {
            if(helper::debug()){
                throw new moodle_exception('databaseaccesserror', 'local_integracao', null, null, '');
            }
        }
    }

    protected static function get_group_by_grp_id($grp_id)
    {
        global $DB;
        try {
            $groupid = 0;

            $result = $DB->get_record('int_grupo_group', array('grp_id'=>$grp_id), '*');

            if($result) {
                $groupid = $result->groupid;
            }

            return $groupid;

        } catch (\Exception $e) {
            if(helper::debug()){
                throw new moodle_exception('databaseaccesserror', 'local_wsintegracao', null, null, '');
            }
        }
    }

    protected static function save_user($user)
    {
        global $CFG, $DB;

        // Inclui a biblioteca de aluno do moodle
        require_once("{$CFG->dirroot}/user/lib.php");

        // Cria o usuario usando a biblioteca do proprio moodle.
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $userid = user_create_user($user);

        return $userid;
    }

    protected static function get_course_enrol($courseid) {
        global $DB;
        $enrol = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'), '*', MUST_EXIST);
        return $enrol;
    }

    protected static function enrol_user_in_moodle_course($userid, $courseid, $roleid) {
        global $CFG;
        $courseenrol = self::get_course_enrol($courseid);
        require_once($CFG->libdir . "/enrollib.php");
        if (!$enrol_manual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }
        $enrol_manual->enrol_user($courseenrol, $userid, $roleid, time());
    }

    protected static function get_courseid_by_groupid($groupid){
        global $DB;
        $group = $DB->get_record('groups', array('id' => $groupid), '*');
        return $group->courseid;
    }

    protected static function get_section_by_ofd_id($ofd_id){
        global $DB;
        $section = $DB->get_record('int_discipline_section', array('ofd_id'=>$ofd_id), '*');
        return $section;
    }

    protected static function get_last_section_course($courseId)
    {
        global $DB;
        $sql = 'SELECT section FROM {course_sections} WHERE course = :courseid ORDER BY section DESC LIMIT 1';
        $params['courseid'] = $courseId;
        return current($DB->get_records_sql($sql, $params));
    }
}
