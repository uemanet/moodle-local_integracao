<?php
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

/**
 * Integracao Web Service
 *
 * @package    contrib
 * @subpackage local_wsintegracao
 * @copyright  2017 Uemanet
 * @author     Uemanet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . "/externallib.php");

class wsintegracao_base extends external_api {

        protected static function get_course_by_trm_id($trm_id) {
            global $DB;

            try {
            $courseid = $DB->get_record('int_turma_course', array('trm_id'=>$trm_id), '*');

            } catch (\Exception $e) {
                  if(helper::debug()){
                      throw new moodle_exception('databaseaccesserror', 'local_wsintegracao', null, null, '');
                  }
              }

            if($courseid) {
                $courseid = $courseid->courseid;
            } else {
                $courseid = 0;
            }

            return $courseid;
       }

}
