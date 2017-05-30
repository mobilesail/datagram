<?php

namespace React\Datagram;

use React\EventLoop\LoopInterface;
use Evenement\EventEmitter;
use Exception;

class SocketUnixClient extends EventEmitter implements SocketInterface
{
    protected $loop;
    protected $socket;

    protected $buffer;

    public $bufferSize = 65536;

    public function __construct(LoopInterface $loop, $socket, Buffer $buffer = null)
    {
        $this->loop = $loop;
        $this->socket = $socket;

        if ($buffer === null) {
            $buffer = new Buffer($loop, $socket);
        }
        $this->buffer = $buffer;

        $that = $this;
        $this->buffer->on('error', function ($error) use ($that) {
            $that->emit('error', array($error, $that));
        });
        $this->buffer->on('close', array($this, 'close'));

        $this->resume();
    }
    
    public function getSocketStream(){
        return $this->socket;
    }
    
    public function getLocalAddress()
    {
        return $this->sanitizeAddress(@stream_socket_get_name($this->socket, false));
    }

    public function getRemoteAddress()
    {
        return $this->sanitizeAddress(@stream_socket_get_name($this->socket, true));
    }

    public function send($data, $remoteAddress = null)
    {
        $this->buffer->send($data, $remoteAddress);
    }

    public function pause()
    {
        $this->loop->removeReadStream($this->socket);
        $this->loop->removeEnterIdle($this->socket);
        $this->loop->removeSignalInterrupted($this->socket);
        $this->loop->removeOnWake($this->socket);
    }

    public function resume()
    {
        if ($this->socket !== false) {
            $this->loop->addReadStream($this->socket, array($this, 'onReceive'));
            $this->loop->addEnterIdle($this->socket, array($this, 'onEnterIdle'));
            $this->loop->addSignalInterrupted($this->socket, array($this, 'onSignalInterrupted'));
            $this->loop->addOnWake($this->socket, array($this, 'onWake'));
        }
    }

    public function onReceive()
    {
        try {
            $data = $this->handleReceive($peer);
        }
        catch (Exception $e) {
            // emit error message and local socket
            $this->emit('error', array($e, $this));
            return;
        }

        $this->emit('data', array($data, $this));
    }
    
    public function onEnterIdle()
    {
        $this->emit('EnterIdle', array($this));
    }
    
    public function onSignalInterrupted()
    {
        $this->emit('SignalInterrupted', array($this));
    }
    
    public function onWake()
    {
        $this->emit('onWake', array($this));
    }

    public function close()
    {
        if ($this->socket === false) {
            return;
        }

        $this->emit('close', array($this));
        $this->pause();

        $this->handleClose();
        $this->socket = false;
        $this->buffer->close();

        $this->removeAllListeners();
    }

    public function end()
    {
        $this->buffer->end();
    }

    private function sanitizeAddress($address)
    {
        if ($address === false) {
            return null;
        }

        // this is an IPv6 address which includes colons but no square brackets
        $pos = strrpos($address, ':');
        if ($pos !== false && strpos($address, ':') < $pos && substr($address, 0, 1) !== '[') {
            $port = substr($address, $pos + 1);
            $address = '[' . substr($address, 0, $pos) . ']:' . $port;
        }
        return $address;
    }

    protected function handleReceive(&$peerAddress)
    {
        $data = stream_socket_recvfrom($this->socket, $this->bufferSize);
        
        
        if ($data === false || (string)$data === '') {
            //    empty data => connection was closed
            $this->close();
            return;
        }

        $peerAddress = $this->sanitizeAddress($peerAddress);

        return $data;
    }

    protected function handleClose()
    {
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
        fclose($this->socket);
    }
}
