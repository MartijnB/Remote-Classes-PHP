<?php

class RCP_TcpClient {
    private $_ip;
	private $_port;
	
	private $_isConnected = false;
	
	private $_connection;
	
	public function __construct($sIP, $iPort) {
		$this->_ip = $sIP;
		$this->_port = $iPort;
	}
	
	public function __destruct() {
		if ($this->_isConnected) {
			$this->disconnect();
		}
	}
    
    public function __wakeup() {
        if ($this->_isConnected) {
            $this->connect();            
        }    
    }        
	
	public function connect($iTimeout = 30) {
		$this->_connection = @stream_socket_client('tcp://'.$this->_ip . ':' . $this->_port, $errorCode, $errorString, $iTimeout);
		
		if ($this->_connection === false) {
			throw new Exception('Connect failed!');
		}
		
		$this->_isConnected = true;
	}
	
	public function disconnect() {
        if ($this->_isConnected) {
			stream_socket_shutdown($this->_connection, STREAM_SHUT_RDWR);
            fclose($this->_connection);
			
			$this->_isConnected = false;
		}
	}
    
    public function setTimeout($iTimeoutSeconds) {
        if ($this->_isConnected) {
            stream_set_timeout($this->_connection, $iTimeoutSeconds);
        }
    }
    
    public function flush() {
        if ($this->_isConnected) {
            return fflush($this->_connection);
        }
    }
    
    public function isClosed() {
        return feof($this->_connection);
    }
    
    public function isReady() {
        $aReadStreams = array($this->_connection);
        $aWriteStreams = array();
        
        if (stream_select($aReadStreams, $aWriteStreams, $aWriteStreams, 1) !== false) {
            return true;
        }
        else {
            return false;
        }
    }
	
	public function read($length = 8192) {
        if ($this->isClosed()) {
            throw new Exception('Socket closed!');
        }
       
        $mData = fread($this->_connection, $length);
        
        if ($mData === false) {
            throw new Exception('Read failure!');
        }
       
        return $mData;
    }
    
    public function readLine($length = -1) {
    	if ($length > -1) {
    		return rtrim(fgets($this->_connection, $length));
    	}
    	else {
    		return rtrim(fgets($this->_connection));
    	}
    }
    
    public function write($data) {
    	return fwrite($this->_connection, $data);
    } 
    
    public function writeLine($data) {
    	return fwrite($this->_connection, $data . PHP_EOL);
    }
}