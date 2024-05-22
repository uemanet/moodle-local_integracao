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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use dml_exception;
use dml_transaction_exception;
use invalid_parameter_exception;
use moodle_exception;

/**
 * Class local_wsintegracao_discipline
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol extends external_api {

    /**
     * @param $batch
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     * @return array
     */
    public static function batch_unenrol_student_discipline($batch) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            foreach ($batch as $enrol) {
                // Validação dos parametros.
                self::validate_parameters(self::unenrol_student_discipline_parameters(), ['enrol' => $enrol]);

                $enrol = (object)$enrol;

                // Verifica se o aluno ja esta matriculado para a disciplina.
                $params = ['mof_id' => $enrol->mof_id];
                $userdiscipline = $DB->get_record('int_user_discipline', $params, '*');

                if (!$userdiscipline) {
                    throw new \Exception("A matrícula em disciplina com mof_id: ".$enrol->mof_id ." não está mapeada com o ambiente virtual");
                }

                $DB->delete_records('int_user_discipline', ['mof_id' => $enrol->mof_id]);
            }

            // Persiste todas as matriculas em caso de sucesso.
            $transaction->allow_commit();

            return [
                'id' => $enrol->mof_id,
                'status' => 'success',
                'message' => 'Alunos desmatriculados em lote com sucesso'
            ];
        } catch (\Exception $exception) {
            $transaction->rollback($exception);
        }

        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function batch_unenrol_student_discipline_parameters() {
        return new external_function_parameters([
            'enrol' => new external_multiple_structure(
                new external_single_structure([
                    'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia')
                ])
            )
        ]);
    }

    /**
     * @return external_single_structure
     */
    public static function batch_unenrol_student_discipline_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Id da matrícula na disciplina'),
            'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
        ]);
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
        global $DB;

        // Validação dos parametros.
        self::validate_parameters(self::unenrol_student_discipline_parameters(), ['enrol' => $enrol]);

        $enrol = (object)$enrol;

        // Verifica se o aluno ja esta matriculado para a disciplina.
        $params = ['mof_id' => $enrol->mof_id];

        $userdiscipline = $DB->get_record('int_user_discipline', $params);

        if (!$userdiscipline) {
            throw new \Exception("A matrícula em disciplina com mof_id: ".$enrol->mof_id ." não está mapeada com o ambiente virtual");
        }

        // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
        $transaction = $DB->start_delegated_transaction();

        try {
            $DB->delete_records('int_user_discipline', ['mof_id' => $enrol->mof_id]);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

            return [
                'id' => $enrol->mof_id,
                'status' => 'success',
                'message' => 'Aluno desmatriculado da disciplina'
            ];

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return null;
    }

    /**
     * @return external_single_structure
     */
    public static function unenrol_student_discipline_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Id da matrícula na disciplina'),
            'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
        ]);
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_student_discipline_parameters() {
        return new external_function_parameters([
            'enrol' => new external_single_structure([
                'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia')
            ])
        ]);
    }
}
