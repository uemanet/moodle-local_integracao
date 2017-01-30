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

          // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
          $transaction = $DB->start_delegated_transaction();

          //matricula o aluno em um curso no moodle
          self::enrol_user_in_moodle_course($data['userid'], $data['courseid'], self::STUDENT_ROLEID);

          if($data['groupid']){
              //adiciona a bibliteca de grupos do moodle
              require_once("{$CFG->dirroot}/group/lib.php");
              //vincula um usuário a um grupo
              $res = groups_add_member($data['groupid'],$data['userid']);
          }

          if ($res){
            $aluCourse['mat_id'] = $student->mat_id;
            $aluCourse['userid'] = $data['userid'];
            $aluCourse['pes_id'] = $student->pes_id;
            $aluCourse['trm_id'] = $student->trm_id;
            $aluCourse['courseid'] = $courseid;
            $aluCourse['grp_id'] = $student->grp_id;
            $aluCourse['groupid'] = $data['groupid'];

            $result = $DB->insert_record('int_student_course', $aluCourse);

          }

          // Prepara o array de retorno.
          $returndata = null;
          if($result) {
              $returndata['id'] = $result->id;
              $returndata['status'] = 'success';
              $returndata['message'] = 'Aluno matriculado com sucesso';
          } else {
              $returndata['id'] = 0;
              $returndata['status'] = 'error';
              $returndata['message'] = 'Erro ao tentar matricular o aluno';
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
                          'mat_id' => new external_value(PARAM_INT, 'Id da matricula do aluno no harpia'),
                          'trm_id' => new external_value(PARAM_INT, 'Id da turma do aluno no harpia'),
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

      public static function get_enrol_student_course_validation_rules($student){
        global $CFG, $DB;
          //verifica se o o usuário enviado pelo harpia, existe no moodle
          $userid = self::get_user_by_pes_id($student->pes_id);

          //se ele não existir, criar o usuário e adicioná-lo na tabela de controle
          if(!$userid){

            $userid = self::save_user($tutor);

            $data['pes_id'] = $tutor->pes_id;
            $data['userid'] = $userid;

            $res = $DB->insert_record('int_pessoa_user', $data);
          }

          $courseid = self::get_course_by_trm_id($trm_id);

          if(!$courseid) {
            throw new Exception("Não existe uma turma mapeada no moodle com trm_id:" .$student->trm_id);
          }

          //verifica se o campo de grupo existe, se existir, pegar o seu id no lado do moodle
          $result['groupid'] = null;
          if ($student->grp_id){

              $groupid = self::get_group_by_grp_id($tutor->grp_id);

              // Dispara uma excessao caso o grupo com grp_id informado não exista
              if(!$groupid) {
                throw new Exception("Não existe um grupo mapeado no moodle com grp_id:" .$tutor->grp_id);
              }
              //coloca o valor de groupid em um array de retorno
              $result['groupid'] = $groupid;
          }

          //verifica se o aluno ja está matriculado no curso
          $aluCourse = $DB->get_record('int_student_course', array('pes_id' => $student->pes_id, 'courseid' => $courseid), '*');

          if ($aluCourse) {
            throw new Exception("O aluno de pes_id " .$student->pes_id. " já está vinculado ao curso de courseid ".$courseid);
          }

          //prepara o array de retorno
          $result['userid'] = $userid;
          $result['courseid'] = $courseid;

          return $result;

      }

}
