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
  const TUTOR_ROLEID = 4;
  const STUDENT_ROLEID = 5;

        protected static function get_course_by_trm_id($trm_id)
        {
            global $DB;

            try {
            $courseid = $DB->get_record('int_turma_course', array('trm_id'=>$trm_id), '*');

            } catch (\Exception $e) {
                  if(helper::debug()){
                      throw new moodle_exception('databaseaccesserror', 'local_wsintegracao', null, null, '');
                  }
              }

            if($courseid) {
                $courseid = $courseid->courseid;
            } else {
                $courseid = 0;
            }

            return $courseid;
       }

       protected static function get_user_by_pes_id($pes_id)
       {
           global $DB;

           try {
           $userid = $DB->get_record('int_pessoa_user', array('pes_id'=>$pes_id), '*');

           } catch (\Exception $e) {
                 if(helper::debug()){
                     throw new moodle_exception('databaseaccesserror', 'local_integracao', null, null, '');
                 }
             }

           if($userid) {
               $userid = $userid->userid;
           } else {
               $userid = null;
           }

           return $userid;
      }


        protected static function get_group_by_grp_id($grp_id)
        {
            global $DB;

            try {
            $groupid = $DB->get_record('int_grupo_group', array('grp_id'=>$grp_id), '*');

            } catch (\Exception $e) {
                  if(helper::debug()){
                      throw new moodle_exception('databaseaccesserror', 'local_wsintegracao', null, null, '');
                  }
              }

            if($groupid) {
                $groupid = $groupid->groupid;
            } else {
                $groupid = 0;
            }

            return $groupid;
       }

      protected static function save_user($user)
      {
        global $CFG, $DB;

        // Inlcui a biblioteca de aluno do moodle
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

}
