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

require_once($CFG->libdir . "/externallib.php");

/**
 * Class wsintregacao_base
 *
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wsintegracao_base extends external_api {

    /**
     * @param $trmid
     * @return int
     * @throws moodle_exception
     */
    protected static function get_course_by_trm_id($trmid) {
        global $DB;

        try {
            $courseid = 0;

            $result = $DB->get_record('int_turma_course', array('trm_id' => $trmid), '*');

            if ($result) {
                $courseid = $result->courseid;
            }

            return $courseid;

        } catch (\Exception $e) {
            if (helper::debug()) {
                throw new moodle_exception('databaseaccesserror', 'local_wsintegracao', null, null, '');
            }
        }
    }

    /**
     * @param $pesid
     * @return object
     * @throws moodle_exception
     */
    protected static function get_user_by_pes_id($pesid) {
        global $DB;

        try {
            $userid = null;

            $result = $DB->get_record('int_pessoa_user', array('pes_id' => $pesid), '*');

            if ($result) {
                $userid = $result->userid;
            }

            return $userid;
        } catch (\Exception $e) {
            if (helper::debug()) {
                throw new moodle_exception('databaseaccesserror', 'local_integracao', null, null, '');
            }
        }
    }

    /**
     * @param $grpid
     * @return int
     * @throws moodle_exception
     */
    protected static function get_group_by_grp_id($grpid) {
        global $DB;

        try {
            $groupid = 0;

            $result = $DB->get_record('int_grupo_group', array('grp_id' => $grpid), '*');

            if ($result) {
                $groupid = $result->groupid;
            }

            return $groupid;

        } catch (\Exception $e) {
            if (helper::debug()) {
                throw new moodle_exception('databaseaccesserror', 'local_wsintegracao', null, null, '');
            }
        }
    }

    /**
     * @param $courseid
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_course_enrol($courseid) {
        global $DB;

        $enrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'), '*', MUST_EXIST);
        return $enrol;
    }

    /**
     * @param $userid
     * @param $courseid
     * @param $roleid
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function enrol_user_in_moodle_course($userid, $courseid, $roleid) {
        global $CFG;

        $courseenrol = self::get_course_enrol($courseid);

        require_once($CFG->libdir . "/enrollib.php");
        if (!$enrolmanual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }

        $enrolmanual->enrol_user($courseenrol, $userid, $roleid, time());
    }

    /**
     * @param $userid
     * @param $courseid
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function unenrol_user_in_moodle_course($userid, $courseid) {
        global $CFG;
        require_once($CFG->libdir . "/enrollib.php");

        $courseenrol = self::get_course_enrol($courseid);

        if (!$enrolmanual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }
        $enrolmanual->unenrol_user($courseenrol, $userid);

    }

    /**
     * @param $groupid
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_courseid_by_groupid($groupid) {
        global $DB;

        $group = $DB->get_record('groups', array('id' => $groupid), '*');

        return $group->courseid;
    }

    /**
     * @param $ofdid
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_section_by_ofd_id($ofdid) {
        global $DB;

        $section = $DB->get_record('int_discipline_section', array('ofd_id' => $ofdid), '*');

        return $section;
    }

    /**
     * @param $courseid
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_last_section_course($courseid) {
        global $DB;

        $sql = 'SELECT section FROM {course_sections} WHERE course = :courseid ORDER BY section DESC LIMIT 1';

        $params['courseid'] = $courseid;

        return current($DB->get_records_sql($sql, $params));
    }

    /**
     * @param $userid
     * @param $senha
     * @throws dml_exception
     */
    protected static function send_instructions_email($userid, $senha) {
        global $CFG, $DB;

        // Inclui a biblioteca do moodle para poder enviar o email.
        require_once("{$CFG->dirroot}/lib/moodlelib.php");

        $user = $DB->get_record('user', array('id' => $userid));

        $subject = "Instruções de acesso";

        $messagehtml = '<p style="line-height:21px;font-size:20px;margin-top:20px;margin-bottom:0px">Prezado usuário,<br><br>

                        Para nós é um enorme prazer tê-lo(a) em um dos nossos cursos de Educação a Distância.<br>

                        Você já possui cadastro no AVA, no entanto, deverá seguir as instruções abaixo para acessá-lo:</p><br>

                        <p style="line-height:28px;font-size:20px;margin-top:20px;margin-bottom:0px;text-align:center">
                          <strong style="line-height:inherit">Instruções para acesso ao AVA</strong>
                        </p>
                        <br><br>
                        <blockquote style="line-height:inherit;margin:20px 0px 0px;padding-left:14px;border-left:4px solid rgb(189,189,189)">
                          <p style="line-height:21px;font-size:14px;margin-top:20px;margin-bottom:20px">
                          - Acesse o endereço: ' . $CFG->wwwroot . '<br>
                          - na caixa de texto "Usuário", digite: ' . $user->username . '<br>
                          - na caixa de texto "Senha", digite: <b>changeme</b> <br>
                          - clique no botão "Acessar"<br>
                          - uma nova página com três caixas de texto será exibida.<br>
                          - na caixa de texto "Senha Atual", digite: <b>changeme</b><br>
                          - na caixa de texto "Nova senha", digite uma nova senha para ser utilizada nos seus próximos acessos<br>
                          - na caixa de texto "Nova senha (novamente)", digite novamente a sua nova senha de acesso<br>
                          - clique no botão "Salvar mudanças"<br>
                          - uma nova página com o texto "A senha foi alterada" será exibida.<br>
                          - clique em "Continuar"
                          </p>
                        </blockquote>
                        <br><br>

                        Seja Bem Vindo(a)!<br><br>

                        <b>Obs: Esse é apenas um e-mail informativo. Não responda este e-mail.</b><br>';

        email_to_user($user, '', $subject, '', $messagehtml, '', '', false);
    }

    /**
     * @param $user
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected static function save_user($user) {
        global $CFG;

        // Inclui a biblioteca de usuários do moodle.
        require_once("{$CFG->dirroot}/user/lib.php");

        // Cria o usuario usando a biblioteca do proprio moodle.
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $userid = user_create_user($user);

        self::send_instructions_email($userid, $user->password);

        return $userid;
    }

    /**
     * @param $teacherid
     * @param $sectionid
     * @return bool
     * @throws dml_exception
     */
    protected static function verify_teacher_enroled_course_section($teacherid, $sectionid) {
        global $CFG, $DB;

        // Inclui a biblioteca de usuários do moodle.
        require_once("{$CFG->dirroot}/user/lib.php");
        $section = $DB->get_record('course_sections', array('id' => $sectionid), '*');

        $result = $DB->get_records('int_discipline_section', array('pes_id' => $teacherid));

        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $isenroled = $DB->get_record('course_sections', array('id' => $value->sectionid, 'course' => $section->course), '*');
                if ($isenroled) {
                    return true;
                }
            }
        }

        return false;
    }
}
