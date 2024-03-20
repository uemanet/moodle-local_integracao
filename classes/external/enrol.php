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
class enrol extends base {

    /**
     * @param $batch
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     * @return array
     */
    public static function batch_unenrol_student_discipline($batch) {
        global $CFG, $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            foreach ($batch as $enrol) {
                // Validação dos parametros.
                self::validate_parameters(self::unenrol_student_discipline_parameters(), array('enrol' => $enrol));

                $enrol = (object)$enrol;

                // Verifica se o aluno ja esta matriculado para a disciplina.
                $params = array('mof_id' => $enrol->mof_id);
                $userdiscipline = $DB->get_record('int_user_discipline', $params, '*');

                if (!$userdiscipline) {
                    throw new \Exception("A matrícula em disciplina com mof_id: ".$enrol->mof_id ." não está mapeada com o ambiente virtual");
                }

                $returndata = null;
                $res = $DB->delete_records('int_user_discipline', ['mof_id' => $enrol->mof_id]);
            }

            // Persiste todas as matriculas em caso de sucesso.
            $transaction->allow_commit();

            $returndata = array(
                'id' => $enrol->mof_id,
                'status' => 'success',
                'message' => 'Alunos desmatriculados em lote com sucesso'
            );
        } catch (\Exception $exception) {
            $transaction->rollback($exception);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function batch_unenrol_student_discipline_parameters() {
        $innerstructure = new external_single_structure(
            array(
                'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia')
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
    public static function batch_unenrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da matrícula na disciplina'),
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
    public static function unenrol_student_discipline($enrol) {
        global $CFG, $DB;

        // Validação dos parametros.
        self::validate_parameters(self::unenrol_student_discipline_parameters(), array('enrol' => $enrol));

        $enrol = (object)$enrol;

        // Verifica se o aluno ja esta matriculado para a disciplina.
        $params = array('mof_id' => $enrol->mof_id);

        $userdiscipline = $DB->get_record('int_user_discipline', $params, '*');

        if (!$userdiscipline) {
            throw new \Exception("A matrícula em disciplina com mof_id: ".$enrol->mof_id ." não está mapeada com o ambiente virtual");
        }

        $returndata = null;

        // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
        $transaction = $DB->start_delegated_transaction();

        try {

            $res = $DB->delete_records('int_user_discipline',['mof_id' => $enrol->mof_id]);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

            $returndata = array(
                'id' => $enrol->mof_id,
                'status' => 'success',
                'message' => 'Aluno desmatriculado da disciplina'
            );

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_single_structure
     */
    public static function unenrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da matrícula na disciplina'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_student_discipline_parameters() {
        return new external_function_parameters(
            array(
                'enrol' => new external_single_structure(
                    array(
                        'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia')
                    )
                )
            )
        );
    }
}
