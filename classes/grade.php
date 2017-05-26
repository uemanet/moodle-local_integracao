<?php

/**
* Class local_wsintegracao_grade
* @copyright   2017 Uemanet
* @author      Uemanet
* @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once('base.php');

class local_wsintegracao_grade extends wsintegracao_base
{

    public static function get_grades_batch($grades)
    {
        global $DB;

        // validate parameters
        self::validate_parameters(self::get_grades_batch_parameters(), array('grades' => $grades));

        $retorno = [];

        $pes_id = $grades['pes_id'];
        $userid = self::get_user_by_pes_id($pes_id);

        if (!$userid) {
            throw new Exception('Não existe um aluno cadastrado com o pes_id: '.$pes_id);
        }

        $itens = json_decode($grades['itens'], true);

        if (empty($itens)) {
            throw new Exception('Paramêtro itens está vazio.');
        }

        $itens_notas = [];
        foreach ($itens as $item) {
            $nota = self::get_grade_by_itemid($item, $userid);

            if (!$nota) {
                throw new Exception('Item de nota com id: '.$item.' não existe.');
            }

            $itens_notas[$item] = $nota;
        }

        $retorno = [
            'pes_id' => $pes_id,
            'grades' => json_encode($itens_notas),
            'status' => 'success',
            'message' => 'Notas mapeadas com sucesso.'
        ];

        return $retorno;
    }

    public static function get_grades_batch_parameters()
    {
        return new external_function_parameters(
            array(
                'grades' => new external_single_structure(
                    array(
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no acadêmico'),
                        'itens' => new external_value(PARAM_TEXT, "Array com os id's dos itens de nota")
                    )
                )
            )
        );
    }

    public static function get_grades_batch_returns()
    {
            return new external_single_structure(
                array(
                    'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no acadêmico'),
                    'grades' => new external_value(PARAM_TEXT, 'Array com as notas para cada item de nota'),
                    'status' => new external_value(PARAM_TEXT, 'Status da operação'),
                    'message' => new external_value(PARAM_TEXT, 'Mensagem da operação')
                )
            );
    }

    public static function get_grade_by_itemid($itemid, $userid)
    {
        global $DB;

        $finalgrade = 0;

        $sql = "SELECT gg.*, gi.scaleid 
                FROM {grade_grades} gg 
                INNER JOIN {grade_items} gi ON gi.id = gg.itemid 
                WHERE userid = :userid
                AND itemid = :itemid";

        $grade = $DB->get_record_sql($sql, array('userid' => $userid, 'itemid' => $itemid));

        // Retorna 0 caso não seja encontrados registros
        if (!$grade) {
            return 0;
        }

        if ($grade->scaleid) {
            return self::get_grade_by_scale($grade->scaleid, $grade->finalgrade);
        }

        // Formata a nota final
        if ($grade->finalgrade) {
            $finalgrade = number_format($grade->finalgrade, 2);
        }

        if ($grade->rawgrademax > 10 && $grade->finalgrade > 1) {
            $finalgrade = ($grade->finalgrade - 1) / $grade->rawgrademax;
            $finalgrade = number_format($finalgrade, 2);
        }

        return $finalgrade;
    }

    protected static function get_grade_by_scale($scaleid, $grade)
    {
        global $DB;

        $scale = $DB->get_record('scale', array('id' => $scaleid), '*');

        $scale = $scale->scale;

        $scale_arr = explode(', ', $scale);

        $grade = (int)$grade - 1;

        return $scale_arr[$grade];
    }
}