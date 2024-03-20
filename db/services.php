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

defined('MOODLE_INTERNAL') || die();

/*
 * Web Service local plugin functions and services definition
 *
 * @package integracao
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$functions = [
    'local_integracao_create_course' => [
        'classname' => 'local_integracao\external\course',
        'methodname' => 'create_course',
        'classpath' => 'local/integracao/classes/external/course.php',
        'description' => 'Creates a new course',
        'type' => 'write'
    ],
    'local_integracao_update_course' => [
        'classname' => 'local_integracao\external\course',
        'methodname' => 'update_course',
        'classpath' => 'local/integracao/classes/external/course.php',
        'description' => 'Update a course',
        'type' => 'write'
    ],
    'local_integracao_delete_course' => [
        'classname' => 'local_integracao\external\course',
        'methodname' => 'remove_course',
        'classpath' => 'local/integracao/classes/external/course.php',
        'description' => 'Delete a course',
        'type' => 'write'
    ],
    'local_integracao_create_group' => [
        'classname' => 'local_integracao\external\group',
        'methodname' => 'create_group',
        'classpath' => 'local/integracao/classes/external/group.php',
        'description' => 'Create a group',
        'type' => 'write'
    ],
    'local_integracao_update_group' => [
        'classname' => 'local_integracao\external\group',
        'methodname' => 'update_group',
        'classpath' => 'local/integracao/classes/external/group.php',
        'description' => 'Update a group',
        'type' => 'write'
    ],
    'local_integracao_delete_group' => [
        'classname' => 'local_integracao\external\group',
        'methodname' => 'remove_group',
        'classpath' => 'local/integracao/classes/external/group.php',
        'description' => 'Delete a group',
        'type' => 'write'
    ],
    'local_integracao_enrol_tutor' => [
        'classname' => 'local_integracao\external\tutor',
        'methodname' => 'enrol_tutor',
        'classpath' => 'local/integracao/classes/external/tutor.php',
        'description' => 'Enrol a tutor',
        'type' => 'write'
    ],
    'local_integracao_unenrol_tutor_group' => [
        'classname' => 'local_integracao\external\tutor',
        'methodname' => 'unenrol_tutor_group',
        'classpath' => 'local/integracao/classes/external/tutor.php',
        'description' => 'Unenrol a tutor from a group',
        'type' => 'write'
    ],
    'local_integracao_enrol_student' => [
        'classname' => 'local_integracao\external\student',
        'methodname' => 'enrol_student',
        'classpath' => 'local/integracao/classes/external/student.php',
        'description' => 'Enrol a student',
        'type' => 'write'
    ],
    'local_integracao_unenrol_student' => [
        'classname' => 'local_integracao\external\student',
        'methodname' => 'unenrol_student',
        'classpath' => 'local/integracao/classes/external/student.php',
        'description' => 'Unenrol a student',
        'type' => 'write'
    ],
    'local_integracao_change_role_student_course' => [
        'classname' => 'local_integracao\external\student',
        'methodname' => 'change_role_student_course',
        'classpath' => 'local/integracao/classes/external/student.php',
        'description' => 'Change role for student in a course',
        'type' => 'write'
    ],
    'local_integracao_change_student_group' => [
        'classname' => 'local_integracao\external\student',
        'methodname' => 'change_student_group',
        'classpath' => 'local/integracao/classes/external/student.php',
        'description' => 'Change a student from a group',
        'type' => 'write'
    ],
    'local_integracao_unenrol_student_group' => [
        'classname' => 'local_integracao\external\student',
        'methodname' => 'unenrol_student_group',
        'classpath' => 'local/integracao/classes/external/student.php',
        'description' => 'Unenrol a student from a group',
        'type' => 'write'
    ],
    'local_integracao_create_discipline' => [
        'classname' => 'local_integracao\external\discipline',
        'methodname' => 'create_discipline',
        'classpath' => 'local/integracao/classes/external/discipline.php',
        'description' => 'Create a discipline',
        'type' => 'write'
    ],
    'local_integracao_enrol_student_discipline' => [
        'classname' => 'local_integracao\external\discipline',
        'methodname' => 'enrol_student_discipline',
        'classpath' => 'local/integracao/classes/external/discipline.php',
        'description' => 'Enrol a student in a discipline',
        'type' => 'write'
    ],
    'local_integracao_unenrol_student_discipline' => [
        'classname' => 'local_integracao\external\enrol',
        'methodname' => 'unenrol_student_discipline',
        'classpath' => 'local/integracao/classes/external/enrol.php',
        'description' => 'Unenrol a student in a discipline',
        'type' => 'write'
    ],
    'local_integracao_batch_enrol_student_discipline' => [
        'classname' => 'local_integracao\external\discipline',
        'methodname' => 'batch_enrol_student_discipline',
        'classpath' => 'local/integracao/classes/external/discipline.php',
        'description' => 'Enrol multiple students in a discipline',
        'type' => 'write'
    ],
    'local_integracao_batch_unenrol_student_discipline' => [
        'classname' => 'local_integracao\external\enrol',
        'methodname' => 'batch_unenrol_student_discipline',
        'classpath' => 'local/integracao/classes/external/enrol.php',
        'description' => 'Unenrol multiple students in a discipline',
        'type' => 'write'
    ],
    'local_integracao_delete_discipline' => [
        'classname' => 'local_integracao\external\discipline',
        'methodname' => 'remove_discipline',
        'classpath' => 'local/integracao/classes/external/discipline.php',
        'description' => 'Delete a discipline',
        'type' => 'write'
    ],
    'local_integracao_get_grades_batch' => [
        'classname' => 'local_integracao\external\grade',
        'methodname' => 'get_grades_batch',
        'classpath' => 'local/integracao/classes/external/grade.php',
        'description' => 'Return final grade of a list of students',
        'type' => 'read'
    ],
    'local_integracao_change_teacher' => [
        'classname' => 'local_integracao\external\professor',
        'methodname' => 'change_teacher',
        'classpath' => 'local/integracao/classes/external/professor.php',
        'description' => 'Changes the teacher of a discipline',
        'type' => 'write'
    ],
    'local_integracao_update_user' => [
        'classname' => 'local_integracao\external\user',
        'methodname' => 'update_user',
        'classpath' => 'local/integracao/classes/external/user.php',
        'description' => 'Update a user',
        'type' => 'write'
    ],
    'local_integracao_get_user' => [
        'classname' => 'local_integracao\external\user',
        'methodname' => 'get_user',
        'classpath' => 'local/integracao/classes/external/user.php',
        'description' => 'Get a user',
        'type' => 'read'
    ],
    'local_integracao_map_user' => [
        'classname' => 'local_integracao\external\user',
        'methodname' => 'map_user',
        'classpath' => 'local/integracao/classes/external/user.php',
        'description' => 'Map a user',
        'type' => 'write'
    ],
    'local_integracao_ping' => [
        'classname' => 'local_integracao\external\ping',
        'methodname' => 'ping',
        'classpath' => 'local/integracao/classes/external/ping.php',
        'description' => 'Ping function',
        'type' => 'read'
    ],
];

$services = [
    'Integracao' => [
        'functions' => [
            'local_integracao_create_course',
            'local_integracao_update_course',
            'local_integracao_delete_course',
            'local_integracao_create_group',
            'local_integracao_update_group',
            'local_integracao_delete_group',
            'local_integracao_enrol_tutor',
            'local_integracao_unenrol_tutor_group',
            'local_integracao_enrol_student',
            'local_integracao_unenrol_student',
            'local_integracao_change_role_student_course',
            'local_integracao_change_student_group',
            'local_integracao_unenrol_student_group',
            'local_integracao_create_discipline',
            'local_integracao_enrol_student_discipline',
            'local_integracao_unenrol_student_discipline',
            'local_integracao_batch_enrol_student_discipline',
            'local_integracao_batch_unenrol_student_discipline',
            'local_integracao_delete_discipline',
            'local_integracao_get_grades_batch',
            'local_integracao_change_teacher',
            'local_integracao_update_user',
            'local_integracao_map_user',
            'local_integracao_get_user',
            'local_integracao_ping'
        ],
        'restrictedusers' => 0,
        'enabled' => 1
    ]
];
