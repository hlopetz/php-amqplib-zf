<?php

class AMQP_Misc
{
    public static $METHOD_NAME_MAP = array(
        "10,10" => "Connection.start",
        "10,11" => "Connection.start_ok",
        "10,20" => "Connection.secure",
        "10,21" => "Connection.secure_ok",
        "10,30" => "Connection.tune",
        "10,31" => "Connection.tune_ok",
        "10,40" => "Connection.open",
        "10,41" => "Connection.open_ok",
        "10,50" => "Connection.redirect",
        "10,60" => "Connection.close",
        "10,61" => "Connection.close_ok",
        "20,10" => "Channel.open",
        "20,11" => "Channel.open_ok",
        "20,20" => "Channel.flow",
        "20,21" => "Channel.flow_ok",
        "20,30" => "Channel.alert",
        "20,40" => "Channel.close",
        "20,41" => "Channel.close_ok",
        "30,10" => "Channel.access_request",
        "30,11" => "Channel.access_request_ok",
        "40,10" => "Channel.exchange_declare",
        "40,11" => "Channel.exchange_declare_ok",
        "40,20" => "Channel.exchange_delete",
        "40,21" => "Channel.exchange_delete_ok",
        "50,10" => "Channel.queue_declare",
        "50,11" => "Channel.queue_declare_ok",
        "50,20" => "Channel.queue_bind",
        "50,21" => "Channel.queue_bind_ok",
        "50,30" => "Channel.queue_purge",
        "50,31" => "Channel.queue_purge_ok",
        "50,40" => "Channel.queue_delete",
        "50,41" => "Channel.queue_delete_ok",
        "60,10" => "Channel.basic_qos",
        "60,11" => "Channel.basic_qos_ok",
        "60,20" => "Channel.basic_consume",
        "60,21" => "Channel.basic_consume_ok",
        "60,30" => "Channel.basic_cancel",
        "60,31" => "Channel.basic_cancel_ok",
        "60,40" => "Channel.basic_publish",
        "60,50" => "Channel.basic_return",
        "60,60" => "Channel.basic_deliver",
        "60,70" => "Channel.basic_get",
        "60,71" => "Channel.basic_get_ok",
        "60,72" => "Channel.basic_get_empty",
        "60,80" => "Channel.basic_ack",
        "60,90" => "Channel.basic_reject",
        "60,100" => "Channel.basic_recover",
        "90,10" => "Channel.tx_select",
        "90,11" => "Channel.tx_select_ok",
        "90,20" => "Channel.tx_commit",
        "90,21" => "Channel.tx_commit_ok",
        "90,30" => "Channel.tx_rollback",
        "90,31" => "Channel.tx_rollback_ok"
    );

    public static function debug_msg($s)
    {
        error_log($s);
    }

    public static function methodSig($a)
    {
        if(is_string($a))
            return $a;
        else
            return sprintf("%d,%d",$a[0] ,$a[1]);
    }

    /**
     * View any string as a hexdump.
     *
     * This is most commonly used to view binary data from streams
     * or sockets while debugging, but can be used to view any string
     * with non-viewable characters.
     *
     * @version     1.3.2
     * @author      Aidan Lister <aidan@php.net>
     * @author      Peter Waller <iridum@php.net>
     * @link        http://aidanlister.com/repos/v/function.hexdump.php
     * @param       string  $data        The string to be dumped
     * @param       bool    $htmloutput  Set to false for non-HTML output
     * @param       bool    $uppercase   Set to true for uppercase hex
     * @param       bool    $return      Set to true to return the dump
     */
    public static function hexdump ($data, $htmloutput = true, $uppercase = false, $return = false)
    {
        // Init
        $hexi   = '';
        $ascii  = '';
        $dump   = ($htmloutput === true) ? '<pre>' : '';
        $offset = 0;
        $len    = strlen($data);

        // Upper or lower case hexidecimal
        $x = ($uppercase === false) ? 'x' : 'X';

        // Iterate string
        for ($i = $j = 0; $i < $len; $i++)
        {
            // Convert to hexidecimal
            $hexi .= sprintf("%02$x ", ord($data[$i]));

            // Replace non-viewable bytes with '.'
            if (ord($data[$i]) >= 32) {
                $ascii .= ($htmloutput === true) ?
                                htmlentities($data[$i]) :
                                $data[$i];
            } else {
                $ascii .= '.';
            }

            // Add extra column spacing
            if ($j === 7) {
                $hexi  .= ' ';
                $ascii .= ' ';
            }

            // Add row
            if (++$j === 16 || $i === $len - 1) {
                // Join the hexi / ascii output
                $dump .= sprintf("%04$x  %-49s  %s", $offset, $hexi, $ascii);

                // Reset vars
                $hexi   = $ascii = '';
                $offset += 16;
                $j      = 0;

                // Add newline
                if ($i !== $len - 1) {
                    $dump .= "\n";
                }
            }
        }

        // Finish dump
        $dump .= $htmloutput === true ?
                    '</pre>' :
                    '';
        $dump .= "\n";

        // Output method
        if ($return === false) {
            echo $dump;
        } else {
            return $dump;
        }
    }
}
