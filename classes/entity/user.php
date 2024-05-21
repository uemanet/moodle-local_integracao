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

namespace local_integracao\entity;

class user {
    /**
     * @param $pesid
     *
     * @return int|bool
     *
     * @throws \dml_exception
     */
    public static function get_user_by_pes_id($pesid) {
        global $DB, $CFG;

        try {
            $result = $DB->get_record('int_pessoa_user', ['pes_id' => $pesid], 'id, userid');

            if ($result) {
                return $result->userid;
            }

            return false;
        } catch (\Exception $e) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * @param $user
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function save($user) {
        global $DB, $CFG;

        // Inclui a biblioteca de usuários do moodle.
        require_once("{$CFG->dirroot}/user/lib.php");
        $result = $DB->get_record('user', ['email' => $user->email]);

        if($result){
            throw new \Exception("Este email já está cadastrado no moodle. Id:" . $result->id);
        }

        // Cria o usuario usando a biblioteca do proprio moodle.
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $userid = user_create_user($user);

        self::send_instructions_email($userid, $user->password);

        return $userid;
    }

    /**
     * @param $teacher
     *
     * @return int
     *
     * @throws \dml_exception
     */
    public static function save_teacher($teacher) {
        global $DB;

        $userid = self::save($teacher);

        $data['pes_id'] = $teacher->pes_id;
        $data['userid'] = $userid;

        $DB->insert_record('int_pessoa_user', $data);

        return $userid;
    }

    /**
     * @param $userid
     * @param $senha
     * @throws dml_exception
     */
    protected static function send_instructions_email($userid, $password) {
        global $CFG, $DB;

        // Inclui a biblioteca do moodle para poder enviar o email.
        require_once("{$CFG->dirroot}/lib/moodlelib.php");

        $user = $DB->get_record('user', ['id' => $userid]);

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
                          - na caixa de texto "Senha Atual", digite: <b>' . $password . '</b><br>
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

        email_to_user($user, 'Integração', $subject, '', $messagehtml, '', '', false);
    }
}
