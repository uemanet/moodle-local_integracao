<?php
// This file is part of wsintegracao plugin for Moodle.
//
// wsintegracao is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// wsintegracao is distributed in the hope that it will be useful,
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
        self::validate_parameters(self::enrol_student_parameters(), array('student' => $student));

        // Transforma o array em objeto.
        $student = (object)$student;

        $returndata = null;

        // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
        $transaction = $DB->start_delegated_transaction();

        try{
            //verifica se o aluno pode ser matriculado no curso
            $data = self::get_enrol_student_course_validation_rules($student);

            //matricula o aluno em um curso no moodle
            $student_role = get_config('local_integracao')->aluno;
            self::enrol_user_in_moodle_course($data['userid'], $data['courseid'], $student_role);

            if ($data['groupid']) {
                //adiciona a bibliteca de grupos do moodle
                require_once("{$CFG->dirroot}/group/lib.php");

                //vincula um usuário a um grupo
                $res = groups_add_member($data['groupid'],$data['userid']);
            }

            //prepara os dados que serão inseridos na tabela de controle
            $aluCourse['mat_id'] = $student->mat_id;
            $aluCourse['userid'] = $data['userid'];
            $aluCourse['pes_id'] = $student->pes_id;
            $aluCourse['trm_id'] = $student->trm_id;
            $aluCourse['courseid'] = $data['courseid'];
            $aluCourse['grp_id'] = $student->grp_id;
            $aluCourse['groupid'] = $data['groupid'];

            //insere os dados na tabela de controle
            $result = $DB->insert_record('int_student_course', $aluCourse);

            // Prepara o array de retorno.

            if ($result) {
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
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    public static function enrol_student_parameters() {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matricula do aluno no harpia'),
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma do aluno no harpia'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor', VALUE_DEFAULT, null),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no gestor'),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do student'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do student'),
                        'email' => new external_value(PARAM_TEXT, 'Email do student'),
                        'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do student'),
                        'password' => new external_value(PARAM_TEXT, 'Senha do student'),
                        'city' => new external_value(PARAM_TEXT, 'Cidade do student')
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

    public static function change_role_student_course($student)
    {
        global $CFG, $DB;

        // validação dos parâmetros
        self::validate_parameters(self::change_role_student_course_parameters(), array('student', $student));

        // Transforma o array em objeto
        $student = (object)$student;

        // verifica se o aluno existe no moodle
        $userid = self::get_user_by_pes_id($student->pes_id);

        if (!$userid) {
            throw new Exception("Não existe um aluno cadastrado no moodle com pes_id:" .$student->pes_id);
        }

        // verifica se existe um curso mapeado no moodle com a turma do aluno
        $courseid = self::get_course_by_trm_id($student->trm_id);

        if(!$courseid) {
            throw new Exception("Não existe uma turma mapeada no moodle com trm_id:" .$student->trm_id);
        }

        $instance = self::get_course_enrol($courseid);

        // verifica se o aluno está matriculado no curso
        $isMatriculado = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid));

        if (!$isMatriculado) {
            throw new coding_exception('Usuario não matriculado na turma.');
        }

        $message = '';

        // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
        $transaction = $DB->start_delegated_transaction();

        try {
            // pega o centexto do curso
            $context = context_course::instance($courseid);

            // pega as configuraçoes do plugin
            $config = get_config('local_integracao');

            // pega o id da role de acordo com o novo status
            $roleid = null;
            if ($student->new_status == 'concluido') {
                $roleid = $config->aluno_concluido;
            } else if ($student->new_status == 'reprovado') {
                $roleid = $config->aluno_reprovado;
            } else if ($student->new_status == 'trancado') {
                $roleid = $config->aluno_trancado;
            }

            // pega a instancia de mdl_role_assignments de acordo com o usuario e o contexto do curso
            $role_assignment = $DB->get_record('role_assignments', array('userid' => $userid, 'contextid' => $context->id));

            // atualiza a role do aluno
            $role_assignment->roleid = $roleid;
            $DB->update_record('role_assignments', $role_assignment);

            context_course::instance($instance->courseid)->mark_dirty(); // reset enrol caches

            // Trigger event.
            $event = \core\event\user_enrolment_updated::create(
                array(
                    'objectid' => $isMatriculado->id,
                    'courseid' => $instance->courseid,
                    'context' => context_course::instance($instance->courseid),
                    'relateduserid' => $isMatriculado->userid,
                    'other' => array('enrol' => 'manual')
                )
            );

            $event->trigger();
            
            $transaction->allow_commit();
            
            $message = 'Status da Matricula alterado com sucesso';

        } catch(Exception $e) {
            $transaction->rollback($e);
        }

        return array(
            'id' => $role_assignment->id,
            'status' => 'success',
            'message' => $message
        );

    }

    public static function change_role_student_course_parameters()
    {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma do aluno no gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no gestor'),
                        'new_status' => new external_value(PARAM_TEXT, 'Novo status da matricula do aluno no gestor')
                    )
                )
            )
        );
    }

    public static function change_role_student_course_returns()
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

        //verifica se a matricula passada pelo harpia já está mapeada com o moodle
        $matricula = $DB->get_record('int_student_course', array('mat_id' => $student->mat_id), '*');
        if ($matricula) {
            throw new Exception("A matricula de mat_id: " .$student->mat_id. " já está mapeada no moodle com o course de id:".$courseid);
        }

        //verifica se existe um curso mapeado no moodle com a turma enviada pelo harpia
        $courseid = self::get_course_by_trm_id($student->trm_id);
        if(!$courseid) {
          throw new Exception("Não existe uma turma mapeada no moodle com trm_id:" .$student->trm_id);
        }

        //verifica se o campo de grupo existe, se existir, pegar o seu id no lado do moodle
        $result['groupid'] = null;
        if ($student->grp_id){

          $groupid = self::get_group_by_grp_id($student->grp_id);

          // Dispara uma excessao caso o grupo com grp_id informado não exista
          if(!$groupid) {
            throw new Exception("Não existe um grupo mapeado no moodle com grp_id:" .$student->grp_id);
          }
          //coloca o valor de groupid em um array de retorno
          $result['groupid'] = $groupid;
        }

        //verifica se o o usuário enviado pelo harpia, existe no moodle
        $userid = self::get_user_by_pes_id($student->pes_id);
        //se ele não existir, criar o usuário e adicioná-lo na tabela de controle
        if(!$userid){

            $userid = self::save_user($student);

            $data['pes_id'] = $student->pes_id;
            $data['userid'] = $userid;

            $res = $DB->insert_record('int_pessoa_user', $data);
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
