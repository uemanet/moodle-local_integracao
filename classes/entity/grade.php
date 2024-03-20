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

class grade {
    /**
     * @param $itemid
     * @param $userid
     * @return float|int|mixed|string
     * @throws dml_exception
     */
    public static function get_grade_by_itemid($itemid, $userid) {
        global $DB;

        $finalgrade = 0;

        $sql = "SELECT gg.*, gi.scaleid
                FROM {grade_grades} gg
                INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE userid = :userid
                AND itemid = :itemid";

        $grade = $DB->get_record_sql($sql, ['userid' => $userid, 'itemid' => $itemid]);

        // Retorna 0 caso não seja encontrados registros.
        if (!$grade) {
            return 0;
        }

        if ($grade->scaleid) {
            return self::get_grade_by_scale($grade->scaleid, $grade->finalgrade);
        }

        // Formata a nota final.
        if ($grade->finalgrade) {
            $finalgrade = number_format($grade->finalgrade, 2);
        }

        if ($grade->rawgrademax > 10 && $grade->finalgrade > 1) {
            $finalgrade = ($grade->finalgrade - 1) / $grade->rawgrademax;
            $finalgrade = number_format($finalgrade, 2);
        }

        return $finalgrade;
    }

    /**
     * @param $scaleid
     * @param $grade
     * @return mixed
     * @throws dml_exception
     */
    public static function get_grade_by_scale($scaleid, $grade) {
        global $DB;

        $scale = $DB->get_record('scale',['id' => $scaleid]);
        $scale = $scale->scale;
        $scalearr = explode(', ', $scale);

        return $scalearr[(int) $grade - 1];
    }
}
