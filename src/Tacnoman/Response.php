<?php

namespace Tacnoman;

class Response
{

    /**
     * Exception.
     *
     * @param $code
     * @param $message
     */
    public function error($code, $message)
    {
        header('X-Error-Message: '.$message, true, $code);
        die($message);
    }
}
