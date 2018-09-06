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

defined('MOODLE_INTERNAL') || die();

require_once("base.php");

/**
 * Class local_wsintegracao_student
 * @copyright 2017 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsintegracao_student extends wsintegracao_base {

    /**
     * @param $student
     * @return null
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function enrol_student($student) {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::enrol_student_parameters(), array('student' => $student));

        $student = (object)$student;

        $returndata = null;

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Verifica se o aluno pode ser matriculado no curso.
            $data = self::get_enrol_student_course_validation_rules($student);

            // Matricula o aluno em um curso no moodle.
            $studentrole = get_config('local_integracao')->aluno;
            self::enrol_user_in_moodle_course($data['userid'], $data['courseid'], $studentrole);

            if ($data['groupid']) {

                // Adiciona a bibliteca de grupos do moodle.
                require_once("{$CFG->dirroot}/group/lib.php");

                // Vincula um usuário a um grupo.
                groups_add_member($data['groupid'], $data['userid']);
            }

            // Prepara os dados que serão inseridos na tabela de controle.
            $studentcourse['mat_id'] = $student->mat_id;
            $studentcourse['userid'] = $data['userid'];
            $studentcourse['pes_id'] = $student->pes_id;
            $studentcourse['trm_id'] = $student->trm_id;
            $studentcourse['courseid'] = $data['courseid'];
            $studentcourse['grp_id'] = $student->grp_id;
            $studentcourse['groupid'] = $data['groupid'];

            // Insere os dados na tabela de controle.
            $result = $DB->insert_record('int_student_course', $studentcourse);

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

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
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

    /**
     * @return external_single_structure
     */
    public static function enrol_student_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $student
     * @return null
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function unenrol_student($student) {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::unenrol_student_parameters(), array('student' => $student));

        $student = (object)$student;

        $returndata = null;

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Verifica se o aluno ja esta matriculado para a disciplina.
            $params = array('mat_id' => $student->mat_id);
            $usercourse = $DB->get_record('	mdl_int_student_course', $userparams, '*');

            if (!$usercourse) {
                throw new \Exception("A matrícula com mat_id: ".$enrol->mat_id ." não está mapeada com o ambiente virtual");
            }

            self::unenrol_user_in_moodle_course($usercourse->userid, $usercourse->courseid);

            //Remove o registro da tabela de mapeamento
            $res = $DB->delete_records('int_student_course', ['mat_id' => $student->mat_id]);

            // Prepara o array de retorno.
            $returndata['id'] = $student->mat_id;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Aluno desmatriculado com sucesso';
            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_student_parameters() {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matricula do aluno no harpia')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function unenrol_student_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $student
     * @return array
     * @throws \Exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function change_role_student_course($student) {
        global $DB;

        // Validação dos parâmetros.
        self::validate_parameters(self::change_role_student_course_parameters(), array('student' => $student));

        $student = (object)$student;

        // Verifica se o aluno existe no moodle.
        $userid = self::get_user_by_pes_id($student->pes_id);

        if (!$userid) {
            throw new \Exception("Não existe um aluno cadastrado no moodle com pes_id:" . $student->pes_id);
        }

        // Verifica se existe um curso mapeado no moodle com a turma do aluno.
        $courseid = self::get_course_by_trm_id($student->trm_id);

        if (!$courseid) {
            throw new \Exception("Não existe uma turma mapeada no moodle com trm_id:" . $student->trm_id);
        }

        $instance = self::get_course_enrol($courseid);

        // Verifica se o aluno está matriculado no curso.
        $isenrolled = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid));

        if (!$isenrolled) {
            $message = 'Usuario não matriculado na turma. trm_id: ' . $student->trm_id . ', pes_id: ' . $student->pes_id;
            throw new coding_exception($message);
        }

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Pega o centexto do curso.
            $context = context_course::instance($courseid);

            // Pega as configuraçoes do plugin.
            $config = get_config('local_integracao');

            // Pega o id da role de acordo com o novo status.
            $roleid = null;
            if ($student->new_status == 'concluido') {
                $roleid = $config->aluno_concluido;
            } else if ($student->new_status == 'reprovado') {
                $roleid = $config->aluno_reprovado;
            } else if ($student->new_status == 'cursando') {
                $roleid = $config->aluno;
            } else if ($student->new_status == 'evadido') {
                $roleid = $config->aluno_evadido;
            } else if ($student->new_status == 'desistente') {
                $roleid = $config->aluno_desistente;
            } else if ($student->new_status == 'trancado') {
                $roleid = $config->aluno_trancado;
            }

            // Pega a instancia de mdl_role_assignments de acordo com o usuario e o contexto do curso.
            $roleassignment = $DB->get_record('role_assignments', array('userid' => $userid, 'contextid' => $context->id));

            if ($roleassignment) {
                // Atualiza a role do aluno.
                $roleassignment->roleid = $roleid;
                $DB->update_record('role_assignments', $roleassignment);

                $transaction->allow_commit();

                return array(
                    'id' => $roleassignment->id,
                    'status' => 'success',
                    'message' => 'Status da Matricula alterado com sucesso'
                );
            }

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return array(
            'id' => $userid,
            'status' => 'error',
            'message' => 'Usuario não possui papel atribuido no curso'
        );

    }

    /**
     * @return external_function_parameters
     */
    public static function change_role_student_course_parameters() {
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

    /**
     * @return external_single_structure
     */
    public static function change_role_student_course_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $student
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_enrol_student_course_validation_rules($student) {
        global $DB;

        // Verifica se existe um curso mapeado no moodle com a turma enviada pelo harpia.
        $courseid = self::get_course_by_trm_id($student->trm_id);

        if (!$courseid) {
            throw new \Exception("Não existe uma turma mapeada no moodle com trm_id:" . $student->trm_id);
        }

        // Verifica se a matricula passada pelo harpia já está mapeada com o moodle.
        $matricula = $DB->get_record('int_student_course', array('mat_id' => $student->mat_id), '*');

        if ($matricula) {
            $message = "A matricula de mat_id: " . $student->mat_id;
            $message .= " já está mapeada no moodle com o course de id:" . $courseid;
            throw new \Exception($message);
        }

        // Verifica se o campo de grupo existe, se existir, pegar o seu id no lado do moodle.
        $result['groupid'] = null;

        if ($student->grp_id) {

            $groupid = self::get_group_by_grp_id($student->grp_id);

            // Dispara uma excessao caso o grupo com grp_id informado não exista.
            if (!$groupid) {
                throw new \Exception("Não existe um grupo mapeado no moodle com grp_id:" . $student->grp_id);
            }

            // Coloca o valor de groupid em um array de retorno.
            $result['groupid'] = $groupid;
        }

        // Verifica se o o usuário enviado pelo harpia, existe no moodle.
        $userid = self::get_user_by_pes_id($student->pes_id);

        // Se ele não existir, criar o usuário e adicioná-lo na tabela de controle.
        if (!$userid) {

            $userid = self::save_user($student);

            $data['pes_id'] = $student->pes_id;
            $data['userid'] = $userid;

            $res = $DB->insert_record('int_pessoa_user', $data);
        }

        // Verifica se o aluno ja está matriculado no curso.
        $studentcourse = $DB->get_record('int_student_course', array('pes_id' => $student->pes_id, 'courseid' => $courseid), '*');

        if ($studentcourse) {
            $message = "O aluno de pes_id " . $student->pes_id . " já está vinculado ao curso de courseid " . $courseid;
            throw new \Exception($message);
        }

        // Prepara o array de retorno.
        $result['userid'] = $userid;
        $result['courseid'] = $courseid;

        return $result;
    }

    /**
     * @param $student
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function change_student_group($student) {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::change_student_group_parameters(), array('student' => $student));

        // Transforma o array em objeto.
        $student = (object)$student;

        // Verifica se o usuário enviado pelo harpia, existe no moodle.
        $userid = self::get_user_by_pes_id($student->pes_id);

        // Dispara uma excessao caso a pessoa com pes_id enviada pelo gestor não esteja mapeada com o moodle.
        if (!$userid) {
            throw new \Exception("Não existe um usuário mapeado com o moodle com pes_id:" . $student->pes_id);
        }

        $oldgroupid = null;
        if ($student->old_grp_id) {
            // Verifica se o grupo enviado pelo harpia, existe no moodle.
            $oldgroupid = self::get_group_by_grp_id($student->old_grp_id);

            // Dispara uma excessao caso o grupo com grp_id enviado pelo gestor não esteja mapeado com o moodle.
            if (!$oldgroupid) {
                throw new \Exception("Não existe um grupo mapeado com o moodle com grp_id:" . $student->old_grp_id);
            }
        }

        // Verifica se o aluno está realmente vinculado a esse grupo enviado pelo harpia.
        $queryparams = array('mat_id' => $student->mat_id, 'groupid' => $oldgroupid, 'pes_id' => $student->pes_id);
        $studentgroup = $DB->get_record('int_student_course', $queryparams, '*');

        // Dispara uma excessao caso a pessoa com pes_id enviada pelo gestor não esteja mapeada com o moodle.
        if (!$studentgroup) {
            $message = "O usuário com pes_id:" . $student->pes_id;
            $message .= " não está vinculado em nenhum grupo no moodle com grp_id:" . $student->grp_id;
            throw new \Exception($message);
        }

        // Verifica se o grupo enviado pelo harpia, existe no moodle.
        $newgroupid = self::get_group_by_grp_id($student->new_grp_id);

        // Dispara uma excessao caso o grupo com grp_id enviado pelo gestor não esteja mapeado com o moodle.
        if (!$newgroupid) {
            $message = "O grupo ao qual o usuário está sendo inserido não está mapeado com o moodle. grp_id:";
            $message .= $student->new_grp_id;
            throw new \Exception($message);
        }

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Adiciona a bibliteca de grupos do moodle.
            require_once("{$CFG->dirroot}/group/lib.php");

            if ($oldgroupid) {
                // Chama a função para remover o membro do antigo grupo.
                groups_remove_member($oldgroupid, $userid);
            }

            // Chama a função para adicionar o membro no novo grupo.
            groups_add_member($newgroupid, $userid);

            // Atualiza a tabela de controle para o novo grupo do usuário.
            $studentgroup->grp_id = $student->new_grp_id;
            $studentgroup->groupid = $newgroupid;
            $DB->update_record('int_student_course', $studentgroup);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = $userid;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Aluno trocado de grupo com sucesso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function change_student_group_parameters() {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matrícula da pessoa do gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'old_grp_id' => new external_value(PARAM_INT, 'Id do antigo grupo no gestor', VALUE_DEFAULT, null),
                        'new_grp_id' => new external_value(PARAM_INT, 'Id do novo grupo no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function change_student_group_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $student
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function unenrol_student_group($student) {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::unenrol_student_group_parameters(), array('student' => $student));

        // Transforma o array em objeto.
        $student = (object)$student;

        // Verifica se o usuário enviado pelo harpia, existe no moodle.
        $userid = self::get_user_by_pes_id($student->pes_id);

        // Dispara uma excessao caso a pessoa com pes_id enviada pelo gestor não esteja mapeada com o moodle.
        if (!$userid) {
            throw new \Exception("Não existe um usuário mapeado com o moodle com pes_id:" . $student->pes_id);
        }

        // Verifica se o grupo enviado pelo harpia, existe no moodle.
        $groupid = self::get_group_by_grp_id($student->grp_id);

        // Dispara uma excessao caso o grupo com grp_id enviado pelo gestor não esteja mapeado com o moodle.
        if (!$groupid) {
            throw new \Exception("Não existe um grupo mapeado com o moodle com grp_id:" . $student->grp_id);
        }

        // Verifica se o aluno está realmente vinculado a esse grupo.
        $queryparams = array('mat_id' => $student->mat_id, 'groupid' => $groupid, 'pes_id' => $student->pes_id);
        $studentgroup = $DB->get_record('int_student_course', $queryparams, '*');

        // Dispara uma excessao caso a pessoa com pes_id enviada pelo gestor não esteja mapeada com o moodle.
        if (!$studentgroup) {
            $message = "O usuário com pes_id: " . $student->pes_id;
            $message .= " não está vinculado em nenhum grupo no moodle com grp_id:" . $student->grp_id;
            throw new \Exception($message);
        }

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Adiciona a bibliteca de grupos do moodle.
            require_once("{$CFG->dirroot}/group/lib.php");

            // Chama a função para remover o membro do grupo.
            groups_remove_member($groupid, $userid);

            // Atualiza a tabela de controle para que o usuário não esteja mais vinculado a um grupo.
            $studentgroup->grp_id = null;
            $studentgroup->groupid = null;
            $DB->update_record('int_student_course', $studentgroup);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = $userid;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Aluno desvinculado do grupo com sucesso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_student_group_parameters() {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matrícula da pessoa do gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function unenrol_student_group_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }
}
