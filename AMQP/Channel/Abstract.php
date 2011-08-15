<?php

abstract class AMQP_Channel_Abstract
{
    private static $CONTENT_METHODS = array(
        "60,60", // Basic.deliver
        "60,71", // Basic.get_ok
    );

    private static $CLOSE_METHODS = array(
        "10,60", // Connection.close
        "20,40", // Channel.close
    );

    public function __construct($connection, $channel_id)
    {
        $this->connection = $connection;
        $this->channel_id = $channel_id;
        $connection->channels[$channel_id] = $this;
        $this->frame_queue = array();  // Lower level queue for frames
        $this->method_queue = array(); // Higher level queue for methods
        $this->auto_decode = false;
    }


    function dispatch($method_sig, $args, $content)
    {
        if(!array_key_exists($method_sig, $this->METHOD_MAP))
            throw new Exception("Unknown AMQP method $method_sig");

        $amqp_method = $this->METHOD_MAP[$method_sig];
        if($content == NULL)
            return call_user_func(array($this,$amqp_method), $args);
        else
            return call_user_func(array($this,$amqp_method), $args, $content);
    }

    function next_frame()
    {
        AMQP_Misc::debug_msg("waiting for a new frame");
        if($this->frame_queue != NULL)
            return array_pop($this->frame_queue);
        return $this->connection->wait_channel($this->channel_id);
    }

    protected function send_method_frame($method_sig, $args="")
    {
        $this->connection->send_channel_method_frame($this->channel_id, $method_sig, $args);
    }

    function wait_content()
    {
        $frm = $this->next_frame();
        $frame_type = $frm[0];
        $payload = $frm[1];
        if($frame_type != 2)
            throw new Exception("Expecting Content header");

        $payload_reader = new AMQP_Reader(substr($payload,0,12));
        $class_id = $payload_reader->read_short();
        $weight = $payload_reader->read_short();

        $body_size = $payload_reader->read_longlong();
        $msg = new AMQP_Message();
        $msg->load_properties(substr($payload,12));

        $body_parts = array();
        $body_received = 0;
        while(bccomp($body_size,$body_received)==1)
        {
            $frm = $this->next_frame();
            $frame_type = $frm[0];
            $payload = $frm[1];
            if($frame_type != 3)
                throw new Exception("Expecting Content body, received frame type $frame_type");
            $body_parts[] = $payload;
            $body_received = bcadd($body_received, strlen($payload));
        }

        $msg->body = implode("",$body_parts);

        if($this->auto_decode and isset($msg->content_encoding))
        {
            try
            {
                $msg->body = $msg->body->decode($msg->content_encoding);
            } catch (Exception $e) {
                AMQP_Misc::debug_msg("Ignoring body decoding exception: " . $e->getMessage());
            }
        }

        return $msg;
    }

    /**
     * Wait for some expected AMQP methods and dispatch to them.
     * Unexpected methods are queued up for later calls to this Python
     * method.
     */
    public function wait($allowed_methods=NULL)
    {
        if($allowed_methods)
            AMQP_Misc::debug_msg("waiting for " . implode(", ", $allowed_methods));
        else
            AMQP_Misc::debug_msg("waiting for any method");

        //Process deferred methods
        foreach($this->method_queue as $qk=>$queued_method)
        {
            AMQP_Misc::debug_msg("checking queue method " . $qk);
            $method_sig = $queued_method[0];
            if($allowed_methods==NULL || in_array($method_sig, $allowed_methods))
            {
                unset($this->method_queue[$qk]);
                AMQP_Misc::debug_msg("Executing queued method: $method_sig: " .
                          AMQP_Misc::$METHOD_NAME_MAP[AMQP_Misc::methodSig($method_sig)]);

                return $this->dispatch($queued_method[0],
                                       $queued_method[1],
                                       $queued_method[2]);
            }
        }

        // No deferred methods?  wait for new ones
        while(true)
        {
            $frm = $this->next_frame();
            $frame_type = $frm[0];
            $payload = $frm[1];

            if($frame_type != 1)
                throw new Exception("Expecting AMQP method, received frame type: $frame_type");

            if(strlen($payload) < 4)
                throw new Exception("Method frame too short");

            $method_sig_array = unpack("n2", substr($payload,0,4));
            $method_sig = "" . $method_sig_array[1] . "," . $method_sig_array[2];
            $args = new AMQP_Reader(substr($payload,4));

            AMQP_Misc::debug_msg("> $method_sig: " . AMQP_Misc::$METHOD_NAME_MAP[AMQP_Misc::methodSig($method_sig)]);

            if(in_array($method_sig, self::$CONTENT_METHODS))
                $content = $this->wait_content();
            else
                $content = NULL;

            if($allowed_methods==NULL ||
               in_array($method_sig,$allowed_methods) ||
               in_array($method_sig, self::$CLOSE_METHODS))
            {
                return $this->dispatch($method_sig, $args, $content);
            }

            // Wasn't what we were looking for? save it for later
            AMQP_Misc::debug_msg("Queueing for later: $method_sig: " .
                                 AMQP_Misc::$METHOD_NAME_MAP[AMQP_Misc::methodSig($method_sig)]);
            array_push($this->method_queue,array($method_sig, $args, $content));
        }
    }
}
