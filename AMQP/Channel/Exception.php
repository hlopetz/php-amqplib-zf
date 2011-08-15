<?php

class AMQP_Channel_Exception extends AMQP_Exception
{
    public function __construct($reply_code, $reply_text, $method_sig)
    {
        parent::__construct($reply_code, $reply_text, $method_sig);
    }
}
