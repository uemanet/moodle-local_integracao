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
use invalid_parameter_exception;
use moodle_exception;

/**
 * Class local_wsintegracao_grade
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade extends external_api {

    /**
     * @param $grades
     * @return array
     * @throws Exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_grades_batch($grades) {
        // Validate parameters.
        self::validate_parameters(self::get_grades_batch_parameters(), ['grades' => $grades]);

        $pesid = $grades['pes_id'];
        $userid = \local_integracao\entity\user::get_user_by_pes_id($pesid);

        if (!$userid) {
            throw new Exception('Não existe um aluno cadastrado com o pes_id: '.$pesid);
        }

        $itens = json_decode($grades['itens'], true);

        if (empty($itens)) {
            throw new Exception('Parâmetro itens está vazio.');
        }

        $itensnotas = [];
        foreach ($itens as $item) {
            $nota = \local_integracao\entity\grade::get_grade_by_itemid($item['id'], $userid);

            if ($nota) {
                $itensnotas[] = [
                    'id' => $item['id'],
                    'tipo' => $item['tipo'],
                    'nota' => $nota
                ];
            }
        }

        $return = [
            'pes_id' => $pesid,
            'grades' => json_encode($itensnotas),
            'status' => 'success',
            'message' => 'Notas mapeadas com sucesso.'
        ];

        return $return;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_grades_batch_parameters() {
        return new external_function_parameters([
            'grades' => new external_single_structure([
                'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no acadêmico'),
                'itens' => new external_value(PARAM_TEXT, "Array com os id's dos itens de nota")
            ])
        ]);
    }

    /**
     * @return external_single_structure
     */
    public static function get_grades_batch_returns() {
        return new external_single_structure([
            'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no acadêmico'),
            'grades' => new external_value(PARAM_TEXT, 'Array com as notas para cada item de nota'),
            'status' => new external_value(PARAM_TEXT, 'Status da operação'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem da operação')
        ]);
    }
}
