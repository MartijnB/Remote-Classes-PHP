<?php

define('RCP_VERSION', '0.1');

define('RCP_PROTOCOL_WELCOME', 'Hello RCP 0.1');

define('RCP_CMD_WELCOME', 0x01);
define('RCP_CMD_SPAWN_OBJECT', 0x02);
define('RCP_CMD_CALL_METHOD', 0x03);
define('RCP_CMD_GET_PROPERTY', 0x04);
define('RCP_CMD_SET_PROPERTY', 0x05);
define('RCP_CMD_ISSET_PROPERTY', 0x06);
define('RCP_CMD_UNSET_PROPERTY', 0x07);

define('RCP_STATUS_OK', 0x01);
define('RCP_STATUS_ERROR', 0x02);
define('RCP_STATUS_EXCEPTION', 0x03);

require_once dirname(__FILE__) . '/RCP/RemoteClass.php';
require_once dirname(__FILE__) . '/RCP/RemoteClassClient.php';
require_once dirname(__FILE__) . '/RCP/AbstractRemoteClassServer.php';
require_once dirname(__FILE__) . '/RCP/RemoteClassPipeServer.php';
require_once dirname(__FILE__) . '/RCP/RemoteClassTcpServer.php';
require_once dirname(__FILE__) . '/RCP/Pipe.php';
require_once dirname(__FILE__) . '/RCP/TcpClient.php';
require_once dirname(__FILE__) . '/RCP/TcpServer.php';

interface RCP_IRemoteCallable {}