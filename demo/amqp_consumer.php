#!/usr/bin/php
<?php
/**
 * Repeatedly receive messages from queue until it receives a message with
 * 'quit' as the body.
 *
 * @author Sean Murphy<sean@iamseanmurphy.com>
 */

set_include_path('..' . PATH_SEPARATOR . get_include_path());
include 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_AutoLoader::getInstance();
$loader->setFallbackAutoloader(true);
$loader->suppressNotFoundWarnings(true);

$HOST = 'localhost';
$PORT = 5672;
$USER = 'guest';
$PASS = 'guest';
$VHOST = '/';
$EXCHANGE = 'router';
$QUEUE = 'msgs';
$CONSUMER_TAG = 'consumer';

AMQP_Misc::enableDebug();
$conn = new AMQP_Connection($HOST, $PORT, $USER, $PASS);
$ch = $conn->channel();
$ch->access_request($VHOST, false, false, true, true);

$ch->queue_declare($QUEUE);
$ch->exchange_declare($EXCHANGE, 'direct', false, false, false);
$ch->queue_bind($QUEUE, $EXCHANGE);

function process_message($msg) {
    global $ch, $CONSUMER_TAG;
    
    echo "\n--------\n";
    echo $msg->body;
    echo "\n--------\n";
    
    $ch->basic_ack($msg->delivery_info['delivery_tag']);
    
    // Cancel callback
    if ($msg->body === 'quit') {
        $ch->basic_cancel($CONSUMER_TAG);
    }
}

$ch->basic_consume($QUEUE, $CONSUMER_TAG, false, false, false, false, 'process_message');

// Loop as long as the channel has callbacks registered
while(count($ch->callbacks)) {
    $ch->wait();
}

$ch->close();
$conn->close();
?>
