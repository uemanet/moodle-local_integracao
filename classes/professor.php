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
 * Class local_wsintegracao_professor
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('base.php');

class local_wsintegracao_professor extends wsintegracao_base
{
    public static function change_teacher($discipline)
    {
        global $CFG, $DB;

        // Validação dos parametros
        self::validate_parameters(self::change_teacher_parameters(), array('discipline' => $discipline));

        // transforma o array em objeto
        $discipline['pes_id'] = $discipline['teacher']['pes_id'];
        $discipline = (object)$discipline;

        //busca o objeto da section
        $sectionOferta = self::get_section_by_ofd_id($discipline->ofd_id);

        if (!$sectionOferta) {
          throw new Exception('A oferta de disciplina com ofd_id: '.$discipline->ofd_id.'não está vinculada ao moodle');
        }
        //busca informações da section no moodle
        $section = $DB->get_record('course_sections', array('id'=>$sectionOferta->sectionid), '*');

        try{
            $transaction = $DB->start_delegated_transaction();

            //O trecho abaixo é para cadastrar um novo usuário, caso o professor em questão não esteja no moodle
            //verifica se existe um usuário no moodle com esse Id no lado do harpia
            $userId = self::get_user_by_pes_id($discipline->pes_id);
            // Caso não exista usuario, cria-se um novo usuário
            if(!$userId) {
              $userId = self::save_user((object)$discipline->teacher);
              $data['pes_id'] = $discipline->pes_id;
              $data['userid'] = $userId;
              $res = $DB->insert_record('int_pessoa_user', $data);
            }

            $antigoProfessor = $sectionOferta->pes_id;
            $novoProfessor = $discipline->pes_id;

            //verifica se o novo professor já está vinculado ao curso dessa disciplina
            $vinculado = self::verify_teacher_enroled_course_section($novoProfessor, $sectionOferta->sectionid);
            if(!$vinculado){
              // Atribui o professor ao curso
              $professor_role = get_config('local_integracao')->professor;
              self::enrol_user_in_moodle_course($userId, $section->course, $professor_role);
            }

            $sectionOferta->pes_id = $novoProfessor;
            $DB->update_record('int_discipline_section', $sectionOferta);
            //atualiza o novo registro para o professor novo

            //verifica se o antigo professor está vinculado a mais alguma outra disciplina do curso
            $vinculado = self::verify_teacher_enroled_course_section($antigoProfessor, $sectionOferta->sectionid);
            $sectionOferta = self::get_section_by_ofd_id($discipline->ofd_id);

            if(!$vinculado){

              // Atribui o professor ao curso
              $oldUserId = self::get_user_by_pes_id($antigoProfessor);
              self::unenrol_user_in_moodle_course($oldUserId, $section->course);

            }
            
            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();
        }catch(Exception $e) {
            $transaction->rollback($e);
        }

        $returndata['id'] = $result->ofd_id;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Disciplina atualizada com sucesso';
        return $returndata;

    }

    public static function change_teacher_parameters()
    {
        return new external_function_parameters(
            array(
                'discipline' => new external_single_structure(
                    array(
                        'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no gestor'),
                        'teacher' => new external_single_structure(
                            array(
                                'pes_id' => new external_value(PARAM_INT,'Id de pessoa vinculado ao professor no gestor'),
                                'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do professor'),
                                'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do professor'),
                                'email' => new external_value(PARAM_TEXT, 'Email do professor'),
                                'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do professor'),
                                'password' => new external_value(PARAM_TEXT, 'Senha do professor'),
                                'city' => new external_value(PARAM_TEXT, 'Cidade do tutor')
                            )
                        )
                    )
                )
            )
        );
    }

    public static function change_teacher_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da disciplina criada'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }
}
