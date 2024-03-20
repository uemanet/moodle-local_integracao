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
 * @package integracao
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user extends base
{
    /**
     * @param $user
     * @return mixed
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function update_user($user)
    {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::update_user_parameters(), array('user' => $user));

        // Verifica se o o usuário enviado pelo harpia, existe no moodle.
        $user['id'] = self::get_user_by_pes_id($user['pes_id']);

        // Dispara uma excessao caso a pessoa com pes_id enviada pelo gestor não esteja mapeada com o moodle.
        if (!$user['id']) {
            throw new \Exception("Não existe um usuário mapeado com o moodle com pes_id:" . $user['pes_id']);
        }

        // Transforma o array em objeto.
        $user = (object)$user;

        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            // Inclui a biblioteca de usuários do moodle.
            require_once("{$CFG->dirroot}/user/lib.php");
            user_update_user($user, false);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = $user->id;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Usuário atualizado com sucesso';

        return $returndata;
    }

    /**
     * Update user params
     * @return external_function_parameters
     */
    public static function update_user_parameters()
    {
        return new external_function_parameters(
            array(
                'user' => new external_single_structure(
                    array(
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do usuário'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do usuário'),
                        'email' => new external_value(PARAM_TEXT, 'Email do usuário'),
                        'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do usuário'),
                        'city' => new external_value(PARAM_TEXT, 'Cidade do usuário')
                    )
                )
            )
        );
    }

    /**
     * Update user return structure
     * @return external_single_structure
     */
    public static function update_user_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $user
     * @return mixed
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_user($user)
    {
        global $CFG, $DB;

        self::validate_parameters(self::get_user_parameters(), array('user' => $user));

        $result = $DB->get_record('user', array('email' => $user['email']), '*');
        $mapped = $DB->get_record('int_pessoa_user', array('pes_id' => $user['pes_id'], 'userid' => $result->id), '*');

        $data['id'] = $result->id;
        $data['firstname'] = $result->firstname;
        $data['lastname'] = $result->lastname;
        $data['email'] = $result->email;
        $data['mapped'] = $mapped ? true : false;

        $returndata['id'] = $user->id;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Dados de usuário resgatado com sucesso';
        $returndata['data'] = $data;

        return $returndata;
    }

    /**
     * Update user params
     * @return external_function_parameters
     */
    public static function get_user_parameters()
    {
        return new external_function_parameters(
            array(
                'user' => new external_single_structure(
                    array(
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do usuário'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do usuário'),
                        'email' => new external_value(PARAM_TEXT, 'Email do usuário'),
                        'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do usuário'),
                        'city' => new external_value(PARAM_TEXT, 'Cidade do usuário')
                    )
                )
            )
        );
    }

    /**
     * Update user return structure
     * @return external_single_structure
     */
    public static function get_user_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao'),
                'data' => new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do usuário'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do usuário'),
                        'email' => new external_value(PARAM_TEXT, 'Email do usuário'),
                        'mapped' => new external_value(PARAM_BOOL, 'Email do usuário')

                    )
                )
            )
        );
    }


    /**
     * @param $user
     * @return mixed
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function map_user($user)
    {
        global $CFG, $DB;

        self::validate_parameters(self::map_user_parameters(), array('user' => $user));

        $user_moodle = $DB->get_record('user', array('email' => $user['email']), '*');
        $mapped = $DB->get_record('int_pessoa_user', array('pes_id' => $user['pes_id'], 'userid' => $user_moodle->id), '*');
        if ($mapped) {
            throw new \Exception("Usuário com " . $user['pes_id'] . " já está mapeado com o moodle.");
        }

        $data['pes_id'] = $user['pes_id'];
        $data['userid'] = $user_moodle->id;
        $res = $DB->insert_record('int_pessoa_user', $data);

        $returndata['id'] = $user->id;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Teste';

        return $returndata;
    }

    /**
     * Update user params
     * @return external_function_parameters
     */
    public static function map_user_parameters()
    {
        return new external_function_parameters(
            array(
                'user' => new external_single_structure(
                    array(
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do usuário'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do usuário'),
                        'email' => new external_value(PARAM_TEXT, 'Email do usuário'),
                        'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do usuário'),
                        'city' => new external_value(PARAM_TEXT, 'Cidade do usuário')
                    )
                )
            )
        );
    }

    /**
     * Update user return structure
     * @return external_single_structure
     */
    public static function map_user_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }
}
