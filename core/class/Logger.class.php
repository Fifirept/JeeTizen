<?php

/*
 * This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * ***************************** Includes ******************************
 */
if (defined('WEB')) {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
}

class Logger
{
    const ERROR = 'error';
    const INFO = 'info';
    const DEBUG = 'debug';
    const LOG_NAME = 'JeeTizen';

    static function log($log_type, $fn, $texte = '')
    {

        if (defined('WEB')) {
            log::add(self::LOG_NAME, $log_type, $fn . ' ' . $texte);
        } else
            echo self::LOG_NAME . ' ' . $log_type . ' ' . $fn . ' ' . $texte . PHP_EOL;
    }

    static function error($fn, $texte = '')
    {
        if (defined('WEB')) {
            log::add(self::LOG_NAME, Logger::ERROR, $fn . ' ' . $texte);
        } else
            echo self::LOG_NAME . ' ' . Logger::ERROR . ' ' . $fn . ' ' . $texte . PHP_EOL;
    }

    static function info($fn, $texte = '')
    {
        if (defined('WEB')) {
            log::add(self::LOG_NAME, Logger::INFO, $fn . ' ' . $texte);
        } else
            echo self::LOG_NAME . ' ' . Logger::INFO . ' ' . $fn . ' ' . $texte . PHP_EOL;
    }

    static function debug($fn, $texte = '')
    {
        if (defined('WEB')) {
            log::add(self::LOG_NAME, Logger::DEBUG, $fn . ' ' . $texte);
        } else
            echo self::LOG_NAME . ' ' . Logger::DEBUG . ' ' . $fn . ' ' . $texte . PHP_EOL;
    }
}

?>
