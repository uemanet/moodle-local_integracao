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

use core\context\course as context_course;

/**
 * Integracao Web Service - helper manager
 *
 * @package local_integracao
 * @copyright 2017 UemaNet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    public static $lookupeventswithoutredirecttime = [
        '\mod_assign\event\submission_status_viewed',
        '\mod_assign\event\submission_form_viewed',
        '\mod_resource\event\course_module_viewed',
        '\mod_forum\event\course_module_viewed',
    ];

    public static $lookupevents = [
        '\mod_page\event\course_module_viewed',
        '\mod_folder\event\course_module_viewed',
        '\mod_quiz\event\course_module_viewed',
        '\mod_wiki\event\page_viewed',
        '\mod_forum\event\discussion_viewed',
        '\mod_quiz\event\attempt_viewed',
        '\core\event\course_viewed'
    ];

    /**
     * Observe the events, and dispatch them if necessary.
     *
     * @param \core\event\base $event The event.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function observer(\core\event\base $event) {
        if (self::is_monitored_event($event)) {
            self::verify_event($event);
        }
    }

    /**
     *
     * @param \core\event\base $event The event.
     * @return boolean
     */
    protected static function is_monitored_event(\core\event\base $event) {
        if (in_array($event->eventname, array_merge(self::$lookupevents, self::$lookupeventswithoutredirecttime))) {
            return true;
        }

        if ($event instanceof \core\event\course_module_viewed) {
            return true;
        }

        return false;
    }

    /**
     * Observe the events, and dispatch them if necessary.
     *
     * @param \core\event\base $event The event.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected static function verify_event(\core\event\base $event) {

        if (is_siteadmin()) {
            return;
        }

        // So executa as acoes de bloqueio caso seja aluno.
        $context = context_course::instance($event->courseid);
        $userroles = get_user_roles($context, $event->userid);
        $isstudent = false;

        // Pega as configuraçoes do plugin.
        $config = get_config('local_integracao');

        $aluno = $config->aluno;
        $alunoconcluido = $config->aluno_concluido;
        $alunoreprovado = $config->aluno_reprovado;
        $alunotrancado = $config->aluno_trancado;

        if (!empty($userroles)) {
            foreach ($userroles as $r => $role) {

                if ($role->roleid == $aluno || $role->roleid == $alunoconcluido ||
                    $role->roleid == $alunoreprovado || $role->roleid == $alunotrancado) {

                    $isstudent = true;
                    break;
                }
            }
        }

        if ($isstudent) {
            $manager = manager::get($event->courseid);
            $manager->capture_event($event);
        }
    }
}
