<?php
// This file is part of wsintegracao plugin for Moodle.
//
// wstutor is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// wstutor is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with wsintegracao.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class local_wsintegracao_student
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");

class local_wsintegracao_student extends wsintegracao_base{

      public static function enrol_student($student) {
          global $CFG, $DB;

          // Validação dos paramêtros
          $params = self::validate_parameters(self::enrol_student_parameters(), array('student' => $student));

          // Transforma o array em objeto.
          $student = (object)$student;

          //verifica se o aluno pode ser matriculado no curso
          $data = self::get_enrol_student_course_validation_rules($tutor);

          $courseid = self::get_courseid_by_groupid($data['groupid']);

          // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
          $transaction = $DB->start_delegated_transaction();

          //vincula o tutor a um curso no moodle
          self::enrol_user_in_moodle_course($data['userid'], $courseid, self::TUTOR_ROLEID);

          //adiciona a bibliteca de grupos do moodle
          require_once("{$CFG->dirroot}/group/lib.php");
          //vincula um usuário a um grupo
          $res = groups_add_member($data['groupid'],$data['userid']);

          if ($res){
            $tutGroup['pes_id'] = $tutor->pes_id;
            $tutGroup['userid'] = $data['userid'];
            $tutGroup['grp_id'] = $tutor->grp_id;
            $tutGroup['groupid'] = $data['groupid'];
            $tutGroup['courseid'] = $courseid;

            $result = $DB->insert_record('int_tutor_group', $tutGroup);

          }

          // Prepara o array de retorno.
          $returndata = null;
          if($result) {
              $returndata['id'] = $result->id;
              $returndata['status'] = 'success';
              $returndata['message'] = 'Tutor vinculado com sucesso';
          } else {
              $returndata['id'] = 0;
              $returndata['status'] = 'error';
              $returndata['message'] = 'Erro ao tentar vincular o tutor';
          }

          // Persiste as operacoes em caso de sucesso.
          $transaction->allow_commit();

          return $returndata;
      }
      public static function enrol_students_parameters() {
          return new external_function_parameters(
              array(
                  'tutor' => new external_single_structure(
                      array(
                          'mat_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                          'trm_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                          'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                          'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no gestor'),
                          'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do tutor'),
                          'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do tutor'),
                          'email' => new external_value(PARAM_TEXT, 'Email do tutor'),
                          'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do tutor'),
                          'password' => new external_value(PARAM_TEXT, 'Senha do tutor'),
                          'city' => new external_value(PARAM_TEXT, 'Cidade do tutor')
                      )
                  )
              )
          );
      }

      public static function enrol_student_returns()
      {
          return new external_single_structure(
              array(
                  'id' => new external_value(PARAM_INT, 'Id'),
                  'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                  'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
              )
          );
      }

      public static function get_enrol_tutor_group_validation_rules($tutor){
        global $CFG, $DB;
          //verifica se o o usuário enviado pelo harpia, existe no moodle
          $userid = self::get_user_by_pes_id($tutor->pes_id);

          //se ele não existir, criar o usuário e adicioná-lo na tabela de controle
          if(!$userid){

            $userid = self::save_user($tutor);

            $data['pes_id'] = $tutor->pes_id;
            $data['userid'] = $userid;

            $res = $DB->insert_record('int_pessoa_user', $data);
          }

          //verifica se o grupo existe
          $groupid = self::get_group_by_grp_id($tutor->grp_id);

          // Dispara uma excessao caso o grupo com grp_id informado não exista
          if(!$groupid) {
            throw new Exception("Não existe um grupo mapeado no moodle com grp_id:" .$tutor->grp_id);
          }

          //verifica se o tutor pode ser vinculado ao grupo
          $tutGroup = $DB->get_record('int_tutor_group', array('pes_id' => $tutor->pes_id, 'groupid' => $groupid), '*');
          if ($tutGroup) {
            throw new Exception("O tutor de pes_id " .$tutor->pes_id. " já está vinculado ao grupo de groupid ".$groupid);
          }

          //prepara o array de retorno
          $result['userid'] = $userid;
          $result['groupid'] = $groupid;

          return $result;

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
