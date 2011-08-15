#!/usr/bin/php
<?php
/**
 * Sends a message to a queue
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

$conn = new AMQP_Connection($HOST, $PORT, $USER, $PASS);
$ch = $conn->channel();
$ch->access_request($VHOST, false, false, true, true);

$msg_body = implode(' ', array_slice($argv, 1));
$msg = new AMQP_Message($msg_body, array('content_type' => 'text/plain'));
$ch->basic_publish($msg, $EXCHANGE);

$ch->close();
$conn->close();
?>
