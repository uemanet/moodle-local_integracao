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
 * Web Service local plugin functions and services definition
 * @package     db
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'local_integracao_create_course' => array(
        'classname' => 'local_wsintegracao_course',
        'methodname' => 'create_course',
        'classpath' => 'local/integracao/classes/course.php',
        'description' => 'Creates a new course',
        'type' => 'write'
    ),
    'local_integracao_update_course' => array(
        'classname' => 'local_wsintegracao_course',
        'methodname' => 'update_course',
        'classpath' => 'local/integracao/classes/course.php',
        'description' => 'Update a course',
        'type' => 'write'
    ),
    'local_integracao_delete_course' => array(
        'classname' => 'local_wsintegracao_course',
        'methodname' => 'remove_course',
        'classpath' => 'local/integracao/classes/course.php',
        'description' => 'Delete a course',
        'type' => 'write'
    ),
    'local_integracao_create_group' => array(
        'classname' => 'local_wsintegracao_group',
        'methodname' => 'create_group',
        'classpath' => 'local/integracao/classes/group.php',
        'description' => 'Create a group',
        'type' => 'write'
    ),
    'local_integracao_update_group' => array(
        'classname' => 'local_wsintegracao_group',
        'methodname' => 'update_group',
        'classpath' => 'local/integracao/classes/group.php',
        'description' => 'Update a group',
        'type' => 'write'
    ),
    'local_integracao_delete_group' => array(
        'classname' => 'local_wsintegracao_group',
        'methodname' => 'remove_group',
        'classpath' => 'local/integracao/classes/group.php',
        'description' => 'Delete a group',
        'type' => 'write'
    ),
    'local_integracao_enrol_tutor' => array(
        'classname' => 'local_wsintegracao_tutor',
        'methodname' => 'enrol_tutor',
        'classpath' => 'local/integracao/classes/tutor.php',
        'description' => 'Enrol a tutor',
        'type' => 'write'
    ),
    'local_integracao_unenrol_tutor_group' => array(
        'classname' => 'local_wsintegracao_tutor',
        'methodname' => 'unenrol_tutor_group',
        'classpath' => 'local/integracao/classes/tutor.php',
        'description' => 'Unenrol a tutor from a group',
        'type' => 'write'
    ),
    'local_integracao_enrol_student' => array(
        'classname' => 'local_wsintegracao_student',
        'methodname' => 'enrol_student',
        'classpath' => 'local/integracao/classes/student.php',
        'description' => 'Enrol a student',
        'type' => 'write'
    ),
    'local_integracao_change_role_student_course' => array(
        'classname' => 'local_wsintegracao_student',
        'methodname' => 'change_role_student_course',
        'classpath' => 'local/integracao/classes/student.php',
        'description' => 'Change role for student in a course',
        'type' => 'write'
    ),
    'local_integracao_change_student_group' => array(
        'classname' => 'local_wsintegracao_student',
        'methodname' => 'change_student_group',
        'classpath' => 'local/integracao/classes/student.php',
        'description' => 'Change a student from a group',
        'type' => 'write'
    ),
    'local_integracao_unenrol_student_group' => array(
        'classname' => 'local_wsintegracao_student',
        'methodname' => 'unenrol_student_group',
        'classpath' => 'local/integracao/classes/student.php',
        'description' => 'Unenrol a student from a group',
        'type' => 'write'
    ),
    'local_integracao_create_discipline' => array(
        'classname' => 'local_wsintegracao_discipline',
        'methodname' => 'create_discipline',
        'classpath' => 'local/integracao/classes/discipline.php',
        'description' => 'Create a discipline',
        'type' => 'write'
    ),
    'local_integracao_enrol_student_discipline' => array(
        'classname' => 'local_wsintegracao_discipline',
        'methodname' => 'enrol_student_discipline',
        'classpath' => 'local/integracao/classes/discipline.php',
        'description' => 'Enrol a student in a discipline',
        'type' => 'write'
    ),
    'local_integracao_delete_discipline' => array(
        'classname' => 'local_wsintegracao_discipline',
        'methodname' => 'remove_discipline',
        'classpath' => 'local/integracao/classes/discipline.php',
        'description' => 'Delete a discipline',
        'type' => 'write'
    ),
    'local_integracao_update_user' => array(
        'classname' => 'local_wsintegracao_user',
        'methodname' => 'update_user',
        'classpath' => 'local/integracao/classes/user.php',
        'description' => 'Update a user',
        'type' => 'write'
    ),

    'integracao_ping' => array(
        'classname' => 'local_wsintegracao_ping',
        'methodname' => 'ping',
        'classpath' => 'local/integracao/classes/ping.php',
        'description' => 'Ping function',
        'type' => 'read'
    ),
);

$services = array(
    'Integracao' => array(
        'functions' => array(
            'local_integracao_create_course',
            'local_integracao_update_course',
            'local_integracao_delete_course',
            'local_integracao_create_group',
            'local_integracao_update_group',
            'local_integracao_delete_group',
            'local_integracao_enrol_tutor',
            'local_integracao_unenrol_tutor_group',
            'local_integracao_enrol_student',
            'local_integracao_change_role_student_course',
            'local_integracao_change_student_group',
            'local_integracao_unenrol_student_group',
            'local_integracao_create_discipline',
            'local_integracao_enrol_student_discipline',
            'local_integracao_delete_discipline',
            'local_integracao_update_user',
            'integracao_ping'
        ),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
