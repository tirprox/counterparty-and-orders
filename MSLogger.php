<?php
/**
 * Created by PhpStorm.
 * User: dreamwhite
 * Date: 21.02.2018
 * Time: 13:48
 */

class MSLogger
{

    static public function log(string $message) : void {
        file_put_contents('log.txt',  date('Y-m-d H:i:s') . ": " . $message.PHP_EOL , FILE_APPEND | LOCK_EX);
    }
}