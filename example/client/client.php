<?php

/**************************/
// Config
/**************************/

$sRemoteServerHost = '127.0.0.1';
$iRemoteServerPort = 1667;
$sEncryptionKey = 'secretKey';

/**************************/

require_once dirname(__FILE__) . '/../../lib/RCP.php';


$mTest = array(
    1,
    'a
    ',
    1.8328932,
    true,
    array(
        1,
        'a',
        1.8328932,
        true,
        new stdClass()
    ),
    new stdClass()
);

//var_dump(serialize($mTest));

//connect to the remote server
$oRemotePHPServer = new RCP_RemoteClassClient($sRemoteServerHost, $iRemoteServerPort, $sEncryptionKey);

//create a remote object
//use getNewObject to force a new object, getObject will return the same remote object every call
$oRemoteMathClass = $oRemotePHPServer->getObject('RemoteMathClass'); 
$oRemoteReturnClass = $oRemotePHPServer->getObject('RemoteReturnClass');

var_dump($oRemoteReturnClass->returnValue($mTest));

var_dump($oRemoteReturnClass->getRemoteClass()->getWelcome());

$oRemoteReturnClass->customValue = 'Something';
var_dump($oRemoteReturnClass->customValue);

$oRemoteReturnClass->getRemoteClass()->customValue = 'SubSomething';
var_dump($oRemoteReturnClass->getRemoteClass()->customValue);

if (!isset($oRemoteReturnClass->getRemoteClass()->customValue)) {
    throw new Exception('Isset should be true!');
}

unset($oRemoteReturnClass->getRemoteClass()->customValue);

if (isset($oRemoteReturnClass->getRemoteClass()->customValue)) {
    throw new Exception('Isset should be false!');
}

var_dump($oRemoteMathClass->add(3, 6));
