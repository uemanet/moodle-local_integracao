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
 * Integracao Web Service - helper manager
 *
 * @package    contrib
 * @subpackage local_integracao
 * @copyright  2017 UemaNet
 * @authors    UemaNet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
class local_integracao_helper {
    public static $lookupeventswithoutredirecttime = array(
        '\mod_assign\event\submission_status_viewed',
        '\mod_assign\event\submission_form_viewed',
        '\mod_resource\event\course_module_viewed',
        '\mod_forum\event\course_module_viewed',
    );
    public static $lookupevents = array(
        '\mod_page\event\course_module_viewed',
        '\mod_folder\event\course_module_viewed',
        '\mod_quiz\event\course_module_viewed',
        '\mod_wiki\event\page_viewed',
        '\mod_forum\event\discussion_viewed',
        '\mod_quiz\event\attempt_viewed',
        '\core\event\course_viewed'
    );
    /**
     * Observe the events, and dispatch them if necessary.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function observer(\core\event\base $event)
    {
        if(self::isMonitoredEvent($event)) {
            self::verifyEvent($event);
        }
    }

    /**
     *
     * @param \core\event\base $event The event.
     * @return boolean
     */
    protected static function isMonitoredEvent(\core\event\base $event)
    {
        if(in_array($event->eventname, array_merge(self::$lookupevents, self::$lookupeventswithoutredirecttime))) {
            return true;
        }
        if($event instanceof \core\event\course_module_viewed) {
            return true;
        }
        return false;
    }
    /**
     * Observe the events, and dispatch them if necessary.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    protected static function verifyEvent(\core\event\base $event) {
        if(is_siteadmin()) {
            return;
        }
        // So executa as acoes de bloqueio caso seja aluno
        $context = context_course::instance($event->courseid);
        $userRoles = get_user_roles($context, $event->userid);
        $isStudent = false;

        // pega as configuraçoes do plugin
        $config = get_config('local_integracao');

        $aluno = $config->aluno;
        $alunoconcluido = $config->aluno_concluido;
        $alunoreprovado = $config->aluno_reprovado;
        $alunotrancado = $config->aluno_trancado;

        if(!empty($userRoles)) {
            foreach ($userRoles as $r => $role) {
                if($role->roleid == $aluno || $role->roleid == $aluno_concluido || $role->roleid == $aluno_reprovado || $role->roleid == $aluno_trancado ) {
                    $isStudent = true;
                    break;
                }
            }
        }
        if ($isStudent) {
            $manager = local_integracao_manager::get($event->courseid);
            $manager->capture_event($event);
        }
    }
}
