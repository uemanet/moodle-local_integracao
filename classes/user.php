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
 * Class local_wsintegracao_course
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("base.php");

class local_wsintegracao_user extends wsintegracao_base{

    public static function update_user($user) {
        global $CFG, $DB;

        // Validação dos paramêtros
        $params = self::validate_parameters(self::update_user_parameters(), array('user' => $user));


        //verifica se o o usuário enviado pelo harpia, existe no moodle
        $user['id'] = self::get_user_by_pes_id($user['pes_id']);

        // Dispara uma excessao caso a pessoa com pes_id enviada pelo gestor não esteja mapeada com o moodle
        if(!$user['id']) {
          throw new Exception("Não existe um usuário mapeado com o moodle com pes_id:" .$user['pes_id']);
        }

        // Transforma o array em objeto.
        $user = (object)$user;


        // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
        $transaction = $DB->start_delegated_transaction();

        // Inclui a biblioteca de usuários do moodle
        require_once("{$CFG->dirroot}/user/lib.php");
        user_update_user($user, false);

        // Persiste as operacoes em caso de sucesso.
        $transaction->allow_commit();


        // Prepara o array de retorno.
        $returndata['id'] = $result->id;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Usuário atualizado com sucesso';

        return $returndata;
    }
    public static function update_user_parameters() {
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
}
