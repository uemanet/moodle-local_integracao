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
 * Integracao settings file
 *
 * @package integracao
 * @author Felipe Pimenta
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

// Ensure the configurations for this site are set.
if ($hassiteconfig) {

    global $CFG;

    // Create the new settings page
    // - in a local plugin this is not defined as standard, so normal $settings->methods will throw an error as
    // $settings will be NULL.
    $settings = new admin_settingpage('local_integracao', 'Configurações');

    // Create.
    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page.

    require_once($CFG->libdir . '/accesslib.php');

    $roles = get_all_roles();

    $papeis = array();

    foreach ($roles as $role) {
        $papeis[$role->id] = $role->shortname;
    }

    $settings->add(
        new admin_setting_configselect('local_integracao/aluno', 'Aluno',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/aluno_concluido', 'Aluno Concluído',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/aluno_reprovado', 'Aluno Reprovado',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/aluno_trancado', 'Aluno Trancado',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/aluno_evadido', 'Aluno Evadido',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/aluno_desistente', 'Aluno Desistente',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/professor', 'Professor',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/tutor_presencial', 'Tutor Presencial',
            '', '1', $papeis)
    );

    $settings->add(
        new admin_setting_configselect('local_integracao/tutor_distancia', 'Tutor à Distância',
            '', '1', $papeis)
    );
}
