<?php
// This file is part of wstutor plugin for Moodle.
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
// along with wstutor.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class local_integracao_external
 * @package     classes
 * @copyright   2016 Uemanet
 * @author
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_integracao_external extends external_api{

      public static function create_course($course) {
          global $CFG, $DB;

          return $course;
      }
      public static function create_course_parameters() {
          return new external_function_parameters(
              array(
                  'course' => new external_single_structure(
                      array(
                          'trm_id' => new external_value(PARAM_INT, 'Id da turma no gestor'),
                          'category' => new external_value(PARAM_INT, 'Categoria do curso'),
                          'shortname' => new external_value(PARAM_TEXT, 'Nome curto do curso'),
                          'fullname' => new external_value(PARAM_TEXT, 'Nome completo do curso'),
                          'summaryformat' => new external_value(PARAM_INT, 'Formato do sumario'),
                          'format' => new external_value(PARAM_TEXT, 'Formato do curso'),
                          'numsections' => new external_value(PARAM_INT, 'Quantidade de sections')
                      )
                  )
              )
          );
      }
      public static function create_course_returns() {
          return new external_single_structure(
              array(
                  'id' => new external_value(PARAM_INT, 'Id do curso criado'),
                  'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                  'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
              )
          );
      }

}
