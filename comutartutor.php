<?php

require ('Curl.php');

$param = [];

$param['cmt_id'] = '353853';
$param['url'] = "http://localhost/moodle";
$param['token'] = "05535e3207f4d84cf787edf2fdd47035";
$param['action'] = "INSERT";
$param['functioname'] = "local_integracao_enrol_tutor";

$data['tutor']['grp_id']        = 48;
$data['tutor']['pes_id']      = 1;
$data['tutor']['firstname']     =  "cara";
$data['tutor']['lastname']      = "da meia";
$data['tutor']['email'] = "ocara@dameia.com";
$data['tutor']['username']        = "ocara";
$data['tutor']['password']   = "changeme";
$data['tutor']['city']   = "são luis";

$param['data'] = $data;

$serverurl = $param['url'].'/webservice/rest/server.php?wstoken='.$param['token'].'&wsfunction='.$param['functioname'].'&moodlewsrestformat=json';

$curl = new Curl;

if($param['action'] != 'SELECT'){
    $method = 'post';
}else{
    $method = 'get';
}

$comutarReturn = json_decode($curl->$method($serverurl,$param['data']));

var_dump($comutarReturn);
