<?php

namespace React\Datagram;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\Promise;
use React\Datagram\Socket;
use \Exception;
use React\Promise\CancellablePromiseInterface;

class Factory
{
    protected $loop;
    protected $resolver;

    public function __construct(LoopInterface $loop, Resolver $resolver = null)
    {
        $this->loop = $loop;
        $this->resolver = $resolver;
    }

    public function createClient($address)
    {
        $loop = $this->loop;

        
        $socket = @stream_socket_client($address, $errno, $errstr);
        if (!$socket) {
            throw new Exception('Unable to create client socket: ' . $errstr, $errno);
        }

        return new Socket($loop, $socket);
    }

    public function createServer($address)
    {
        $loop = $this->loop;

        $socket = @stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);
        if (!$socket) {
            throw new Exception('Unable to create server socket: ' . $errstr, $errno);
        }

        return new Socket($loop, $socket);
    }
}
