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

namespace local_integracao\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use dml_exception;
use dml_transaction_exception;
use invalid_parameter_exception;

/**
 * Class local_wsintegracao_professor
 * @copyright 2017 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class professor extends external_api {

    /**
     * @param $discipline
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function change_teacher($discipline) {
        global $DB;

        // Validação dos parametros.
        self::validate_parameters(self::change_teacher_parameters(), ['discipline' => $discipline]);

        // Transforma o array em objeto.
        $discipline['pes_id'] = $discipline['teacher']['pes_id'];
        $discipline = (object)$discipline;

        // Busca o objeto da section.
        $sectionoferta = \local_integracao\entity\section::get_section_by_ofd_id($discipline->ofd_id);

        if (!$sectionoferta) {
            throw new Exception('A oferta de disciplina com ofd_id: ' . $discipline->ofd_id . 'não está vinculada ao moodle');
        }

        // Busca informações da section no moodle.
        $section = $DB->get_record('course_sections', ['id' => $sectionoferta->sectionid]);

        try {
            $transaction = $DB->start_delegated_transaction();

            // O trecho abaixo é para cadastrar um novo usuário, caso o professor em questão não esteja no moodle
            // Verifica se existe um usuário no moodle com esse Id no lado do harpia.

            $userid = \local_integracao\entity\user::get_user_by_pes_id($discipline->pes_id);

            // Caso não exista usuario, cria-se um novo usuário.
            if (!$userid) {
                $userid = \local_integracao\entity\user::save((object)$discipline->teacher);
                $data['pes_id'] = $discipline->pes_id;
                $data['userid'] = $userid;
                $res = $DB->insert_record('int_pessoa_user', $data);
            }

            $oldteacher = $sectionoferta->pes_id;
            $newteacher = $discipline->pes_id;

            // Verifica se o novo professor já está vinculado ao curso dessa disciplina.
            $vinculado = \local_integracao\entity\enrol::verify_teacher_enroled_course_section($newteacher, $sectionoferta->sectionid);

            if (!$vinculado) {
                // Atribui o professor ao curso.
                $teacherrole = get_config('local_integracao')->professor;
                \local_integracao\entity\enrol::enrol_user_in_moodle_course($userid, $section->course, $teacherrole);
            }

            $sectionoferta->pes_id = $newteacher;
            $DB->update_record('int_discipline_section', $sectionoferta);
            // Atualiza o novo registro para o professor novo.

            // Verifica se o antigo professor está vinculado a mais alguma outra disciplina do curso.
            $vinculado = \local_integracao\entity\enrol::verify_teacher_enroled_course_section($oldteacher, $sectionoferta->sectionid);

            if (!$vinculado) {
                // Atribui o professor ao curso.
                $olduserid = \local_integracao\entity\user::get_user_by_pes_id($oldteacher);

                \local_integracao\entity\enrol::unenrol_user_in_moodle_course($olduserid, $section->course);
            }

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        $returndata['id'] = $discipline->ofd_id;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Disciplina atualizada com sucesso';

        return $returndata;

    }

    /**
     * @return external_function_parameters
     */
    public static function change_teacher_parameters() {
        return new external_function_parameters([
            'discipline' => new external_single_structure([
                'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no gestor'),
                'teacher' => new external_single_structure([
                    'pes_id' => new external_value(PARAM_INT, 'Id de pessoa vinculado ao professor no gestor'),
                    'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do professor'),
                    'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do professor'),
                    'email' => new external_value(PARAM_TEXT, 'Email do professor'),
                    'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do professor'),
                    'password' => new external_value(PARAM_TEXT, 'Senha do professor'),
                    'city' => new external_value(PARAM_TEXT, 'Cidade do tutor')
                ])
            ])
        ]);
    }

    /**
     * @return external_single_structure
     */
    public static function change_teacher_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Id da disciplina criada'),
            'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
        ]);
    }
}
