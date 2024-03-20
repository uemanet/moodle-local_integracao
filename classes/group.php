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

/**
 * Class local_wsintegracao_course
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group extends base {

    public static function create_group($group) {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::create_group_parameters(), array('group' => $group));

        // Transforma o array em objeto.
        $group = (object)$group;

        // Verifica se o grupo pode ser criado e recebe o id do course do group.
        $courseid = self::get_create_group_validation_rules($group);

        $returndata = null;

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Prepara o array para salvar os dados no moodle.
            $groupdata['courseid'] = $courseid;
            $groupdata['name'] = $group->name;
            $groupdata['description'] = $group->description;
            $groupdata['descriptionformat'] = 1;
            $groupdata['timecreated'] = time();
            $groupdata['timemodified'] = $groupdata['timecreated'];

            $resultid = $DB->insert_record('groups', $groupdata);

            // Caso o curso tenha sido criado adiciona na tabela de controle os dados do curso e da turma.
            if ($resultid) {
                $data['trm_id'] = $group->trm_id;
                $data['grp_id'] = $group->grp_id;
                $data['groupid'] = $resultid;

                $res = $DB->insert_record('int_grupo_group', $data);

                // Busca as configuracoes do curso.
                $courseoptions = $DB->get_record('course', array('id' => $courseid), '*');

                // Altera o formato de grupos do curso.
                $courseoptions->groupmode = 1;
                $courseoptions->groupmodeforce = 1;
                $DB->update_record('course', $courseoptions);

                // Invalidate the grouping cache for the course.
                cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($courseid));

                // Prepara o array de retorno.
                if ($res) {
                    $returndata['id'] = $resultid;
                    $returndata['status'] = 'success';
                    $returndata['message'] = 'Grupo criado com sucesso';
                } else {
                    $returndata['id'] = 0;
                    $returndata['status'] = 'error';
                    $returndata['message'] = 'Erro ao tentar criar o grupo';
                }
            }

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function create_group_parameters() {
        return new external_function_parameters(
            array(
                'group' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma do grupo no gestor'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                        'name' => new external_value(PARAM_TEXT, 'Nome do grupo'),
                        'description' => new external_value(PARAM_TEXT, 'Descrição do grupo')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function create_group_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do grupo criado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $group
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function update_group($group) {
        global $CFG, $DB;

        // Valida os parametros.
        self::validate_parameters(self::update_group_parameters(), array('group' => $group));

        // Transforma o array em objeto.
        $group = (object)$group;

        // Verifica se o grupo pode ser criado e recebe o id do course do group.
        $groupid = self::get_update_group_validation_rules($group);

        $groupobject = $DB->get_record('groups', array('id' => $groupid), '*');

        $groupobject->name = $group->grp_nome;

        try {

            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Inclui a biblioteca de grupos do moodle.
            require_once("{$CFG->dirroot}/group/lib.php");

            groups_update_group($groupobject);

            $transaction->allow_commit();

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = $groupid;
        $returndata['status'] = 'success';
        $returndata['message'] = "Grupo atualizado com sucesso";

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function update_group_parameters() {
        return new external_function_parameters(
            array(
                'group' => new external_single_structure(
                    array(
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                        'grp_nome' => new external_value(PARAM_TEXT, 'Nome do grupo')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function update_group_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do curso atualizado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $group
     * @return mixed
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function remove_group($group) {
        global $CFG, $DB;

        // Valida os parametros.
        self::validate_parameters(self::remove_group_parameters(), array('group' => $group));

        // Inclui a biblioteca de grupos do moodle.
        require_once("{$CFG->dirroot}/group/lib.php");

        // Transforma o array em objeto.
        $group = (object)$group;

        // Busca o id do curso apartir do trm_id da turma.
        $groupid = self::get_group_by_grp_id($group->grp_id);

        // Se nao existir curso mapeado para a turma dispara uma exceção.
        if (!$groupid) {
            throw new \Exception("Nenhum group mapeado com o grupo com grp_id: " . $group->grp_id);
        }

        try {

            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Deleta o curso usando a biblioteca do proprio moodle.
            groups_delete_group($groupid);

            // Deleta os registros da tabela de controle.
            $DB->delete_records('int_grupo_group', array('groupid' => $groupid));

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = $groupid;
        $returndata['status'] = 'success';
        $returndata['message'] = "Grupo excluído com sucesso";

        return $returndata;

    }

    /**
     * @return external_function_parameters
     */
    public static function remove_group_parameters() {
        return new external_function_parameters(
            array(
                'group' => new external_single_structure(
                    array(
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function remove_group_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do grupo removido'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $courseid
     * @param $name
     * @return mixed
     * @throws dml_exception
     */
    private static function get_group_by_name($courseid, $name) {
        global $DB;

        $group = $DB->get_record('groups', array('courseid' => $courseid, 'name' => $name), '*');

        return $group;
    }

    /**
     * @param $group
     * @return int
     * @throws Exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected static function get_create_group_validation_rules($group) {
        $groupid = self::get_group_by_grp_id($group->grp_id);

        // Dispara uma exceção caso ja exista um grupo com esse grp_id.
        if ($groupid) {
            throw new \Exception("Ja existe um grupo mapeado para o ambiente com grp_id: " . $groupid);
        }

        // Busca o id do curso apartir do trm_id da turma.
        $courseid = self::get_course_by_trm_id($group->trm_id);

        // Se nao existir curso mapeado para a turma dispara uma exceção.
        if (!$courseid) {
            throw new \Exception("Nenhum curso mapeado com a turma com trm_id: " . $group->trm_id);
        }

        $groupbyname = self::get_group_by_name($courseid, $group->name);

        // Dispara uma exceção caso ja exista um grupo com o mesmo nome no mesmo curso.
        if ($groupbyname) {
            throw new \Exception("Ja existe um grupo com o mesmo nome nessa turma trm_id: " . $group->trm_id);
        }

        return $courseid;
    }

    /**
     * @param $group
     * @return int
     * @throws Exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected static function get_update_group_validation_rules($group) {
        $groupid = self::get_group_by_grp_id($group->grp_id);

        // Dispara uma exceção caso não exista um grupo com esse grp_id.
        if (!$groupid) {
            throw new \Exception("Não existe nenhum grupo mapeado com o moodle com grp_id: " . $group->grp_id);
        }

        // Busca o id do curso apartir do trm_id da turma.
        $courseid = self::get_courseid_by_groupid($groupid);

        $groupbyname = self::get_group_by_name($courseid, $group->name);

        // Dispara uma exceção caso ja exista um grupo com o mesmo nome no mesmo curso.
        if ($groupbyname) {
            throw new \Exception("Ja existe um grupo com o mesmo nome nessa turma trm_id: " . $group->trm_id);
        }

        return $groupid;
    }
}
