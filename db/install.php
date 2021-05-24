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

/**
 *
 * Report problem functions and service definitions.
 *
 * @package   local_integracao_v2
 * @copyright 2020 Pedro Fellipe Melo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_integracao_install()
{
    global $DB, $CFG;
    require_once($CFG->libdir . '/db/upgradelib.php');

    $dbman = $DB->get_manager();

    if (!$dbman->table_exists('int_pessoa_user')) {
        // Create new 'message_contact_requests' table.
        $table = new xmldb_table('int_pessoa_user');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('pes_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'userid');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id'], null, null);

        $dbman->create_table($table);

    }
    return true;
}
