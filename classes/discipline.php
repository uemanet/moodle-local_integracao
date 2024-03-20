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

namespace local_integracao;

use Exception;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;

/**
 * Class local_wsintegracao_discipline
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discipline extends base {

    /**
     * @param $discipline
     * @return null
     * @throws \Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function create_discipline($discipline) {
        global $CFG, $DB;

        // Validação dos parametros.
        self::validate_parameters(self::create_discipline_parameters(), array('discipline' => $discipline));

        // Transforma o array em objeto.
        $discipline['pes_id'] = $discipline['teacher']['pes_id'];
        $discipline = (object)$discipline;

        // Busca o id da section a partir do id da oferta da disciplina.
        $sectionid = self::get_section_by_ofd_id($discipline->ofd_id);

        // Dispara uma exceção caso já tenha um mapeamento entre a oferta da disciplina e uma section.
        if ($sectionid) {
            throw new \Exception('Já existe uma section mapeada para essa disciplina oferecida. ofd_id: ' . $discipline->ofd_id);
        }

        // Busca o id do curso a partir do id da turma.
        $courseid = self::get_course_by_trm_id($discipline->trm_id);

        // Dispara uma exceção caso essa turma não esteja mapeada para um curso.
        if (!$courseid) {
            $message = 'Não existe curso mapeado para a turma onde essa disciplina foi oferecida. trm_id: ' . $discipline->trm_id;
            throw new \Exception($message);
        }

        // Pega o numero da ultima section do curso.
        $lastsection = self::get_last_section_course($courseid);

        // Ultima section do curso.
        $lastsection = $lastsection->section;

        $returndata = null;

        try {

            // Inicia a transação, qualquer erro que aconteça o rollback será executado.
            $transaction = $DB->start_delegated_transaction();

            // Insere nova section no curso.
            $section['course'] = $courseid;
            $section['section'] = $lastsection + 1;
            $section['name'] = $discipline->name;
            $section['summaryformat'] = 1;
            $section['visible'] = 1;

            $section['id'] = $DB->insert_record('course_sections', $section);

            // Verifica se existe um usuário no moodle com esse Id no lado do harpia.
            $userid = self::get_user_by_pes_id($discipline->pes_id);

            // Caso não exista usuario, cria-se um novo usuário.
            if (!$userid) {
                $userid = self::create_teacher((object)$discipline->teacher);
            }

            // Atribui o professor ao curso.
            $teacherrole = get_config('local_integracao')->professor;
            self::enrol_user_in_moodle_course($userid, $courseid, $teacherrole);

            // Adiciona as informações na tabela de controle entre as ofertas de disciplina e as sections.
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

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Recria o cache do curso.
        require_once($CFG->libdir . "/modinfolib.php");
        rebuild_course_cache($courseid, true);

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function create_discipline_parameters() {
        return new external_function_parameters(
            array(
                'discipline' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma no gestor'),
                        'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no gestor'),
                        'teacher' => new external_single_structure(
                            array(
                                'pes_id' => new external_value(PARAM_INT, 'Id de pessoa vinculado ao professor no gestor'),
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

    /**
     * @return external_single_structure
     */
    public static function create_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da disciplina criada'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $enrol
     * @return array|null
     * @throws \Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function enrol_student_discipline($enrol) {
        global $CFG, $DB;

        // Validação dos parametros.
        self::validate_parameters(self::enrol_student_discipline_parameters(), array('enrol' => $enrol));

        $enrol = (object)$enrol;

        // Busca a seccao apartir do id da oferta da disciplina.
        $section = self::get_section_by_ofd_id($enrol->ofd_id);

        // Dispara uma excessao caso nao tenha um mapeamento entre a oferta da disciplina e uma section.
        if (!$section) {
            throw new \Exception("Nao existe uma section mapeada para essa disciplina oferecida. ofd_id: " . $enrol->ofd_id);
        }

        // Busca o id do usuario apartir do alu_id do aluno.
        $userid = self::get_user_by_pes_id($enrol->pes_id);

        // Dispara uma excessao se esse aluno nao estiver mapeado para um usuario.
        if (!$userid) {
            throw new \Exception("Nenhum usuario esta mapeado para o aluno com pes_id: " . $enrol->pes_id);
        }

        // Verifica se o aluno ja esta matriculado para a disciplina.
        $userparams = array('userid' => $userid, 'sectionid' => $section->sectionid);
        $userdiscipline = $DB->get_record('int_user_discipline', $userparams, '*');

        if ($userdiscipline) {
            throw new \Exception("O aluno ja esta matriculado para essa disciplina. ofd_id: " . $enrol->ofd_id);
        }

        $returndata = null;

        // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
        $transaction = $DB->start_delegated_transaction();

        try {

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

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function enrol_student_discipline_parameters() {
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

    /**
     * @return external_single_structure
     */
    public static function enrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do aluno matriculado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $batch
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     * @return array
     */
    public static function batch_enrol_student_discipline($batch) {
        global $CFG, $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            foreach ($batch as $enrol) {
                // Validação dos parametros.
                self::validate_parameters(self::enrol_student_discipline_parameters(), array('enrol' => $enrol));

                $enrol = (object)$enrol;

                // Busca a seccao apartir do id da oferta da disciplina.
                $section = self::get_section_by_ofd_id($enrol->ofd_id);

                // Dispara uma excessao caso nao tenha um mapeamento entre a oferta da disciplina e uma section.
                if (!$section) {
                    throw new \Exception("Nao existe uma section mapeada para a disciplina oferecida. ofd_id: " . $enrol->ofd_id);
                }

                // Busca o id do usuario apartir do alu_id do aluno.
                $userid = self::get_user_by_pes_id($enrol->pes_id);

                // Dispara uma excessao se esse aluno nao estiver mapeado para um usuario.
                if (!$userid) {
                    throw new \Exception("Nenhum usuario esta mapeado para o aluno com pes_id: " . $enrol->pes_id);
                }

                // Verifica se o aluno ja esta matriculado para a disciplina.
                $userparams = array('userid' => $userid, 'sectionid' => $section->sectionid);
                $userdiscipline = $DB->get_record('int_user_discipline', $userparams, '*');

                if ($userdiscipline) {
                    throw new \Exception("O aluno ja esta matriculado para essa disciplina. ofd_id: " . $enrol->ofd_id);
                }

                $returndata = null;

                // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
                $data['mof_id'] = $enrol->mof_id;
                $data['userid'] = $userid;
                $data['sectionid'] = $section->sectionid;

                $res = $DB->insert_record('int_user_discipline', $data);
            }

            // Persiste todas as matriculas em caso de sucesso.
            $transaction->allow_commit();

            $returndata = array(
                'id' => $res,
                'status' => 'success',
                'message' => 'Matrícula em lote concluída com sucesso'
            );
        } catch (\Exception $exception) {
            $transaction->rollback($exception);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function batch_enrol_student_discipline_parameters() {
        $innerstructure = new external_single_structure(
            array(
                'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia'),
                'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina'),
                'pes_id' => new external_value(PARAM_TEXT, 'Id do aluno')
            )
        );

        return new external_function_parameters(
            array(
                'enrol' => new external_multiple_structure($innerstructure)
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function batch_enrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do aluno matriculado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $discipline
     * @return mixed
     * @throws \Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function remove_discipline($discipline) {
        global $CFG, $DB;

        // Validação dos parametros.
        self::validate_parameters(self::remove_discipline_parameters(), array('discipline' => $discipline));

        $discipline = (object)$discipline;

        // Busca o id da section a partir do id da oferta da disciplina.
        $sectionid = self::get_section_by_ofd_id($discipline->ofd_id);

        // Dispara uma exceção caso não tenha um mapeamento entre a oferta da disciplina e uma section.
        if (!$sectionid) {
            throw new \Exception('Não existe uma section mapeada para essa disciplina oferecida. ofd_id: ' . $discipline->ofd_id);
        }

        try {
            // Inicia a transação, qualquer erro que aconteça o rollback será executado.
            $transaction = $DB->start_delegated_transaction();

            // Adiciona a biblioteca de cursos do moodle.
            require_once("{$CFG->dirroot}/course/lib.php");

            // Busca a section no moodle.
            $section = $DB->get_record('course_sections', array('id' => $sectionid->sectionid));

            // Recebe o Id do curso do lado do Moodle.
            $courseid = $section->course;

            // Busca o curso da disciplina no moodle.
            $course = $DB->get_record('course', array('id' => $courseid));

            // Pega a tabela auxiliar antes de deletar o registro.
            $sectionmaping = $DB->get_record('int_discipline_section', array('ofd_id' => $discipline->ofd_id));

            // Deleta o registro da tabela de mapeamento.
            $DB->delete_records('int_discipline_section', array('ofd_id' => $discipline->ofd_id));

            // Deleta a section do curso do moodle.
            course_delete_section($course, $section);

            // Verifica se o usuário que estava vinculado a disciplina está vinculado
            // a alguma outra section no moodle depois de deletar o registro.
            $teachermaping = $DB->get_records('int_discipline_section', array('pes_id' => $sectionmaping->pes_id));

            // Busca o usuário no moodle que tenha o pes_id enviado.
            $userid = self::get_user_by_pes_id($sectionmaping->pes_id);

            if (!$teachermaping) {
                self::unenrol_user_in_moodle_course($userid, $courseid);
            }

            if ($teachermaping) {
                $vinculado = self::verify_if_teacher_enroled_on_another_section_course($teachermaping, $courseid);

                if (!$vinculado) {
                    self::unenrol_user_in_moodle_course($userid, $courseid);
                }
            }

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Recria o cache do curso.
        require_once($CFG->libdir . "/modinfolib.php");
        rebuild_course_cache($courseid, true);

        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Disciplina deletada com sucesso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function remove_discipline_parameters() {
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

    /**
     * @return external_single_structure
     */
    public static function remove_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da disciplina criada'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $teacher
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    private static function create_teacher($teacher) {
        global $DB;

        $userid = self::save_user($teacher);

        $data['pes_id'] = $teacher->pes_id;
        $data['userid'] = $userid;

        $DB->insert_record('int_pessoa_user', $data);

        return $userid;
    }

    /**
     * @param $teachermaping
     * @param $courseid
     * @return bool
     * @throws dml_exception
     */
    private static function verify_if_teacher_enroled_on_another_section_course($teachermaping, $courseid) {
        global $DB;

        foreach ($teachermaping as $maping) {

            // Verifica se o professor está vinculado em alguma section do mesmo curso.
            $section = $DB->get_record('course_sections', array('id' => $maping->sectionid));
            if ($section->course == $courseid) {
                return true;
            }
        }
        return false;
    }
}
