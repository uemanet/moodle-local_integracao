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

use moodle_url;

/**
 * Integracao Web Service - manager
 *
 * @package local_integracao
 * @copyright 2017 UemaNet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var array Array of singletons. */
    protected static $instances;

    /** @var int Course ID. */
    protected $courseid = null;

    protected $course = null;

    /**
     * Constructor
     *
     * @param int $courseid The course ID.
     * @throws dml_exception
     * @return void
     */
    protected function __construct($courseid) {
        global $DB;
        $this->courseid = $courseid;
        $this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    }

    /**
     * Capture an event.
     *
     * @param \core\event\base $event The event.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function capture_event(\core\event\base $event) {
        global $DB, $CFG;

        $redirecttime = 0;
        if ($event->courseid !== $this->courseid) {
            throw new coding_exception('Event course ID does not match event course ID');
        }

        // The capture has not been enabled yet.
        if (!$this->is_enabled($event->userid)) {
            return;
        }

        if ($event instanceof \core\event\course_viewed && $event->contextlevel == 50
            && ($event->contextinstanceid == $event->courseid)) {
            return;
        }

        if ($event instanceof \core\event\course_viewed && isset($event->other['coursesectionnumber'])) {
            $sql = "SELECT * FROM {course_sections} cs
                    INNER JOIN {int_discipline_section} dc ON dc.sectionid = cs.id
                    INNER JOIN {int_user_discipline} ud ON ud.sectionid = cs.id AND ud.userid = :userid
                    WHERE
                      cs.course = :courseid
                      AND cs.section = :sectionnumber";
            $params['userid'] = $event->userid;
            $params['courseid'] = $event->contextinstanceid;
            $params['sectionnumber'] = $event->other['coursesectionnumber'];

            $section = $DB->get_record_sql($sql, $params);

            if ($section) {
                return;
            }
            redirect(new moodle_url('/course/view.php',
                array('id' => $this->courseid)), 'Você não esta matriculado nesta disciplina.', $redirecttime);
        }

        // Verifica se a atividade esta em uma section mapeada.
        $sql = "SELECT cs.id as sectionid
                FROM {course_modules} cm
                INNER JOIN {course_sections} cs ON cm.section = cs.id
                INNER JOIN {int_discipline_section} dc ON dc.sectionid = cs.id
                WHERE cm.id = :instanceid";
        $params['instanceid'] = $event->contextinstanceid;
        $section = $DB->get_record_sql($sql, $params);
        if (!$section) {
            return;
        }

        // Verifica se o aluno esta matriculado na disciplina.
        $countparams = array('sectionid' => $section->sectionid, 'userid' => $event->userid);
        $isenrolled = $DB->count_records('int_user_discipline', $countparams);

        if (!$isenrolled) {
            if (!in_array($event->eventname, local_integracao_helper::$lookupeventswithoutredirecttime)) {
                $redirecttime = 5;
            }
            redirect(new moodle_url('/course/view.php',
                array('id' => $this->courseid)), 'Você não esta matriculado na disciplina desta atividade.', $redirecttime);
        }
    }

    /**
     * Get an instance of the manager.
     *
     * @param int $courseid The course ID.
     * @param bool $forcereload Force the reload of the singleton, to invalidate local cache.
     * @return local_integracao_manager The instance of the manager.
     * @throws dml_exception
     */
    public static function get($courseid, $forcereload = false) {
        if ($forcereload || !isset(self::$instances[$courseid])) {
            self::$instances[$courseid] = new local_integracao_manager($courseid);
        }

        return self::$instances[$courseid];
    }

    /**
     * Return the current course ID.
     *
     * @return int The course ID.
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Is the block enabled on the course?
     *
     * @return boolean True if enabled.
     * @throws dml_exception
     */
    public function is_enabled($userid) {
        global $DB;

        $isintegrado = $DB->count_records('int_turma_course', array('courseid' => $this->get_courseid()));
        if ($isintegrado) {
            return true;
        }

        return false;
    }
}
