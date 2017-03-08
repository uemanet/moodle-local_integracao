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

class local_wsintegracao_tutor extends wsintegracao_base{

    public static function enrol_tutor($tutor) {
        global $CFG, $DB;

        // Validação dos paramêtros
        $params = self::validate_parameters(self::enrol_tutor_parameters(), array('tutor' => $tutor));

        // Transforma o array em objeto.
        $tutor = (object)$tutor;

        try{

          // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
          $transaction = $DB->start_delegated_transaction();

          //verifica se o tutor pode ser vinculado ao grupo
          $data = self::get_enrol_tutor_group_validation_rules($tutor);

          //recebe o valor de courseid, tendo groupid como parâmetro
          $courseid = self::get_courseid_by_groupid($data['groupid']);

          //vincula o tutor a um curso no moodle
          self::enrol_user_in_moodle_course($data['userid'], $courseid, self::TUTOR_ROLEID);

          //adiciona a bibliteca de grupos do moodle
          require_once("{$CFG->dirroot}/group/lib.php");

          //vincula um usuário a um grupo
          $res = groups_add_member($data['groupid'],$data['userid']);

          if ($res){
            $tutGroup['pes_id'] = $tutor->pes_id;
            $tutGroup['userid'] = $data['userid'];
            $tutGroup['grp_id'] = $tutor->grp_id;
            $tutGroup['groupid'] = $data['groupid'];
            $tutGroup['courseid'] = $courseid;

            $result = $DB->insert_record('int_tutor_group', $tutGroup);

            // Persiste as operacoes em caso de sucesso.
          }

          $transaction->allow_commit();
        }catch(Exception $e) {
          $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata = null;
        if($result) {
            $returndata['id'] = $result->id;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Tutor vinculado com sucesso';
        } else {
            $returndata['id'] = 0;
            $returndata['status'] = 'error';
            $returndata['message'] = 'Erro ao tentar vincular o tutor';
        }

        return $returndata;
    }
    public static function enrol_tutor_parameters() {
        return new external_function_parameters(
            array(
                'tutor' => new external_single_structure(
                    array(
                        'ttg_tipo_tutoria' => new external_value(PARAM_TEXT, 'Tipo de tutoria do tutor'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no gestor'),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do tutor'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do tutor'),
                        'email' => new external_value(PARAM_TEXT, 'Email do tutor'),
                        'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do tutor'),
                        'password' => new external_value(PARAM_TEXT, 'Senha do tutor'),
                        'city' => new external_value(PARAM_TEXT, 'Cidade do tutor')
                    )
                )
            )
        );
    }

    public static function enrol_tutor_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    public static function get_enrol_tutor_group_validation_rules($tutor){
        global $CFG, $DB;

        //verifica se o grupo existe
        $groupid = self::get_group_by_grp_id($tutor->grp_id);
        // Dispara uma excessao caso o grupo com grp_id informado não exista
        if(!$groupid) {
          throw new Exception("Não existe um grupo mapeado no moodle com grp_id:" .$tutor->grp_id);
        }
        
        //verifica se o o usuário enviado pelo harpia, existe no moodle
        $userid = self::get_user_by_pes_id($tutor->pes_id);

        //se ele não existir, criar o usuário e adicioná-lo na tabela de controle
        if(!$userid){

            $userid = self::save_user($tutor);

            $data['pes_id'] = $tutor->pes_id;
            $data['userid'] = $userid;

            $res = $DB->insert_record('int_pessoa_user', $data);
        }

        //verifica se o tutor pode ser vinculado ao grupo
        $tutGroup = $DB->get_record('int_tutor_group', array('pes_id' => $tutor->pes_id, 'groupid' => $groupid), '*');
        if ($tutGroup) {
            throw new Exception("O tutor de pes_id " .$tutor->pes_id. " já está vinculado ao grupo de groupid ".$groupid);
        }

        //prepara o array de retorno
        $result['userid'] = $userid;
        $result['groupid'] = $groupid;

        return $result;

    }
}
