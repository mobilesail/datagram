<?php

namespace React\Datagram;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\Promise;
use React\Datagram\SocketUnix;
use \Exception;
use React\Promise\CancellablePromiseInterface;

class FactoryUnix
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

        return new SocketUnix($loop, $socket);
    }

    public function createServer($address)
    {
        $loop = $this->loop;

        $socket = @stream_socket_server($address, $errno, $errstr);
        if (!$socket) {
            throw new Exception('Unable to create server socket: ' . $errstr, $errno);
        }

        return new SocketUnix($loop, $socket);
    }
}
