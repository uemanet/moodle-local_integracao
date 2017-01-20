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
 * @copyright   2016 Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    'local_integracao_create_course' => array(
        'classname' => 'local_integracao_external',
        'methodname' => 'create_course',
        'classpath' => 'local/moodle-local_integracao/classes/external',
        'description' => 'Creates a new course',
        'type' => 'write'
    )

);

$services = array(
    'Integracao' => array(
        'functions' => array('local_integracao_create_course'),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
