<?php

/**
 * Class helper
 *
 * @package classes
 * @copyright 2017 Uemanet
 */
abstract class helper
{
    const FILE = "integracao.json";

    public static function debug()
    {
        return helper::parse_config_file('debug');
    }

    private static function parse_config_file($key = '')
    {
        $fileContent = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . helper::FILE);
        $json = json_decode($fileContent, true);

        if(array_key_exists($key, $json)){
            return $json[$key];
        }

        return null;
    }
}
