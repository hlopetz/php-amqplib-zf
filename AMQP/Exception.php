<?php

class AMQP_Exception extends Exception
{
    public function __construct($reply_code, $reply_text, $method_sig)
    {
        parent::__construct(NULL,0);

        $this->amqp_reply_code = $reply_code;
        $this->amqp_reply_text = $reply_text;
        $this->amqp_method_sig = $method_sig;

        $ms = AMQP_Misc::methodSig($method_sig);
        if(array_key_exists($ms, AMQP_Misc::$METHOD_NAME_MAP))
            $mn = AMQP_Misc::$METHOD_NAME_MAP[$ms];
        else
            $mn = "";
        $this->args = array(
            $reply_code,
            $reply_text,
            $method_sig,
            $mn
        );
    }
}
