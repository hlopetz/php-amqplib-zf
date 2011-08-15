<?php

  //AMQP PHP library test

include 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_AutoLoader::getInstance();
$loader->setFallbackAutoloader(true);
$loader->suppressNotFoundWarnings(true);

$EXCHANGE = 'test';
$BROKER_HOST   = 'localhost';
$BROKER_PORT   = 5672;
$QUEUE    = 'echo';
$USER     ='guest';
$PASSWORD ='guest';

$msg_body = NULL;

try
{
    echo "Creating connection\n";
    $conn = new AMQP_Connection($BROKER_HOST, $BROKER_PORT,
                               $USER,
                               $PASSWORD);
    
    echo "Getting channel\n";
    $ch = $conn->channel();
    echo "Requesting access\n";
    $ch->access_request('/data', false, false, true, true);
    
    echo "Declaring exchange\n";
    $ch->exchange_declare($EXCHANGE, 'direct', false, false, false);
    echo "Creating message\n";
    $msg = new AMQP_Message($msg_body, array('content_type' => 'text/plain'));
    
    echo "Publishing message\n";
    $ch->basic_publish($msg, $EXCHANGE, $QUEUE);
    
    echo "Closing channel\n";
    $ch->close();
    echo "Closing connection\n";
    $conn->close();
    echo "Done.\n";
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage();
    echo "\nTrace:\n" . $e->getTraceAsString();
}
?>
