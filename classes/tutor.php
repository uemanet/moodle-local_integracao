<?php
// This file is part of wsintegracao plugin for Moodle.
//
// wstutor is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// wstutor is distributed in the hope that it will be useful,
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
          $params = self::validate_parameters(self::enrol_tutor_parameters(), array('group' => $group));

          return $returndata;
      }
      public static function enrol_tutor_parameters() {
          return new external_function_parameters(
              array(
                  'tutor' => new external_single_structure(
                      array(
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
      public static function enrol_tutor_returns() {
          return new external_single_structure(
              array(
                  'id' => new external_value(PARAM_INT, 'Id'),
                  'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                  'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
              )
          );
      }
}
