<?php

/**
 * Class local_wsintegracao_discipline
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('base.php');

class local_wsintegracao_discipline extends wsintegracao_base
{
    public static function create_discipline($discipline)
    {
        global $CFG, $DB;

        // Validação dos parametros
        self::validate_parameters(self::create_discipline_parameters(), array('discipline' => $discipline));

        // transforma o array em objeto
        $discipline['pes_id'] = $discipline['teacher']['pes_id'];
        $discipline = (object)$discipline;

        // Busca o id da section a partir do id da oferta da disciplina
        $sectionId = self::get_section_by_ofd_id($discipline->ofd_id);

        // Dispara uma exceção caso já tenha um mapeamento entre a oferta da disciplina e uma section
        if($sectionId) {
            throw new Exception('Já existe uma section mapeada para essa disciplina oferecida. ofd_id: '.$discipline->ofd_id);
        }

        // Busca o id do curso a partir do id da turma
        $courseId = self::get_course_by_trm_id($discipline->trm_id);

        // Dispara uma exceção caso essa turma não esteja mapeada para um curso
        if(!$courseId) {
            throw new Exception('Não existe curso mapeado para a turma onde essa disciplina foi oferecida. trm_id: '.$discipline->trm_id);
        }

        // Pega o numero da ultima section do curso
        $lastSection = self::get_last_section_course($courseId);

        // Ultima section do curso
        $lastSection = $lastSection->section;

        $returndata = null;

        try {

            // Inicia a transação, qualquer erro que aconteça o rollback será executado
            $transaction = $DB->start_delegated_transaction();

            // Insere nova section no curso
            $section['course'] = $courseId;
            $section['section'] = $lastSection + 1;
            $section['name'] = $discipline->name;
            $section['summaryformat'] = 1;
            $section['visible'] = 1;

            $section['id'] = $DB->insert_record('course_sections', $section);

            // Busca as configuracoes do formato do curso
            $courseFormatOptions = $DB->get_record('course_format_options', array('courseid'=>$courseId, 'name' => 'numsections'), '*');

            // Atualiza o total de sections do curso
            $courseFormatOptions->value = $lastSection + 1;

            $DB->update_record('course_format_options', $courseFormatOptions);

            //verifica se existe um usuário no moodle com esse Id no lado do harpia
            $userId = self::get_user_by_pes_id($discipline->pes_id);

            // Caso não exista usuario, cria-se um novo usuário
            if(!$userId) {
                $userId = self::create_teacher((object)$discipline->teacher);
            }

            // Atribui o professor ao curso
            $professor_role = get_config('local_integracao')->professor;
            self::enrol_user_in_moodle_course($userId, $courseId, $professor_role);

            // Adiciona as informações na tabela de controle entre as ofertas de disciplina e as sections
            $data['ofd_id'] = $discipline->ofd_id;
            $data['sectionid'] = $section['id'];
            $data['pes_id'] = $discipline->pes_id;

            $res = $DB->insert_record('int_discipline_section', $data);

            // Prepara o array de retorno.
            if ($res) {
                $returndata['id'] = $section['id'];
                $returndata['status'] = 'success';
                $returndata['message'] = 'Disciplina criada com sucesso';
            } else {
                $returndata['id'] = 0;
                $returndata['status'] = 'error';
                $returndata['message'] = 'Erro ao tentar criar disciplina';
            }

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        // Recria o cache do curso
        require_once($CFG->libdir . "/modinfolib.php");
        rebuild_course_cache($courseId, true);

        return $returndata;
    }

    public static function create_discipline_parameters()
    {
        return new external_function_parameters(
            array(
                'discipline' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma no gestor'),
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
                        ),
                        'name' => new external_value(PARAM_TEXT, 'Nome da disciplina ofertada')
                    )
                )
            )
        );
    }

    public static function create_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da disciplina criada'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    public static function enrol_student_discipline($enrol)
    {
        global $CFG, $DB;

        // Validação dos parametros
        self::validate_parameters(self::enrol_student_discipline_parameters(), array('enrol' => $enrol));

        $enrol = (object)$enrol;

        // Busca a seccao apartir do id da oferta da disciplina.
        $section = self::get_section_by_ofd_id($enrol->ofd_id);

        // Dispara uma excessao caso nao tenha um mapeamento entre a oferta da disciplina e uma section.
        if(!$section) {
            throw new Exception("Nao existe uma section mapeada para essa disciplina oferecida. ofd_id: " . $enrol->ofd_id);
        }

        // Busca o id do usuario apartir do alu_id do aluno.
        $userid = self::get_user_by_pes_id($enrol->pes_id);

        // Dispara uma excessao se esse aluno nao estiver mapeado para um usuario.
        if(!$userid) {
            throw new Exception("Nenhum usuario esta mapeado para o aluno com pes_id: " . $enrol->pes_id);
        }

        // Verifica se o aluno ja esta matriculado para a disciplina
        $userdiscipline = $DB->get_record('int_user_discipline', array('userid'=>$userid, 'sectionid'=>$section->sectionid), '*');

        if($userdiscipline) {
            throw new Exception("O aluno ja esta matriculado para essa disciplina. ofd_id: " . $enrol->ofd_id);
        }

        $returndata = null;

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            $data['mof_id'] = $enrol->mof_id;
            $data['userid'] = $userid;
            $data['sectionid'] = $section->sectionid;

            $res = $DB->insert_record('int_user_discipline', $data);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

            $returndata = array(
                'id' => $res,
                'status' => 'success',
                'message' => 'Aluno matriculado na disciplina'
            );

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;

    }

    public static function enrol_student_discipline_parameters()
    {
        return new external_function_parameters(
            array(
                'enrol' => new external_single_structure(
                  array(
                      'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia'),
                      'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina'),
                      'pes_id' => new external_value(PARAM_TEXT, 'Id do aluno')
                  )
                )
            )
        );
    }

    public static function enrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do aluno matriculado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    public static function remove_discipline($discipline)
    {
        global $CFG, $DB;

        // Validação dos parametros
        self::validate_parameters(self::remove_discipline_parameters(), array('discipline' => $discipline));

        // transforma o array em objeto
        $discipline = (object)$discipline;

        // Busca o id da section a partir do id da oferta da disciplina
        $sectionId = self::get_section_by_ofd_id($discipline->ofd_id);

        // Dispara uma exceção caso não tenha um mapeamento entre a oferta da disciplina e uma section
        if(!$sectionId) {
            throw new Exception('Não existe uma section mapeada para essa disciplina oferecida. ofd_id: '.$discipline->ofd_id);
        }

        try {
            // Inicia a transação, qualquer erro que aconteça o rollback será executado
            $transaction = $DB->start_delegated_transaction();

            //adiciona a biblioteca de cursos do moodle
            require_once("{$CFG->dirroot}/course/lib.php");

            // Busca a section no moodle
            $section = $DB->get_record('course_sections', array( 'id' => $sectionId->sectionid ));

            // Recebe o Id do curso do lado do Moodle
            $courseId = $section->course;

            //busca o curso da disciplina no moodle
            $course = $DB->get_record('course', array( 'id' => $courseId ));

            //pega a tabela auxiliar antes de deletar o registro
            $sectionMapeamento = $DB->get_record('int_discipline_section', array( 'ofd_id' => $discipline->ofd_id ));

            //deleta o registro da tabela de mapeamento
            $DB->delete_records('int_discipline_section', array( 'ofd_id' => $discipline->ofd_id ));

            //deleta a section do curso do moodle
            course_delete_section($course, $section);

            //verifica se o usuário que estava vinculado a disciplina está vinculado a alguma outra section no moodle depois de deletar o registro
            $professorMapeamento = $DB->get_records('int_discipline_section', array( 'pes_id' => $sectionMapeamento->pes_id ));

            //busca o usuário no moodle que tenha o pes_id enviado
            $userid = self::get_user_by_pes_id($sectionMapeamento->pes_id);

            if (!$professorMapeamento) {
                self::unenrol_user_in_moodle_course($userid, $courseId);
            }

            if ($professorMapeamento) {
                $vinculado = self::verify_if_teacher_enroled_on_another_section_course($professorMapeamento, $courseId);

                if (!$vinculado) {
                    self::unenrol_user_in_moodle_course($userid, $courseId);
                }
            }
            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch(Exception $e) {
            $transaction->rollback($e);
        }

        // Recria o cache do curso
        require_once($CFG->libdir . "/modinfolib.php");
        rebuild_course_cache($courseId, true);

        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Disciplina deletada com sucesso';

        return $returndata;
    }

    public static function remove_discipline_parameters()
    {
        return new external_function_parameters(
            array(
                'discipline' => new external_single_structure(
                    array(
                        'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no gestor')
                    )
                )
            )
        );
    }

    public static function remove_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da disciplina criada'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    private static function create_teacher($teacher)
    {
      global $DB;

      $userid = self::save_user($teacher);

      $data['pes_id'] = $teacher->pes_id;
      $data['userid'] = $userid;

      $DB->insert_record('int_pessoa_user', $data);

      return $userid;
    }

    private static function verify_if_teacher_enroled_on_another_section_course($professorMapeamento, $courseId)
    {
      global $DB;

      foreach ($professorMapeamento as $mapeamento) {

        //verifica se o professor está vinculado em alguma section do mesmo curso
        $section = $DB->get_record('course_sections', array( 'id' => $mapeamento->sectionid ));
        if ($section->course == $courseId){
          return true;
        }
      }
      return false;
    }
}
