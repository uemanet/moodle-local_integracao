<?php

/**
 * ping
 *
 * @package monitor
 * @copyright 2017 Uemanet
 * @author    Uemanet
 */
class local_wsintegracao_ping extends external_api
{
    public static function ping_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function ping()
    {
        return array('response' => true);
    }

    public static function ping_returns()
    {
        return new external_function_parameters(array(
            'response' => new external_value(PARAM_BOOL, 'Default response')
        ));
    }
}
