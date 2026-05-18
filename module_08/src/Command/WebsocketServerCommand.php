<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

// Registers this class as the "websocket:server" Symfony console command
#[AsCommand(name: 'websocket:server')]
class WebsocketServerCommand extends Command
{
    // Declares an optional "action" argument so Symfony does not reject
    // the "start" argument that Workerman expects on the command line
    protected function configure(): void
    {
        $this->addArgument(
            'action',
            InputArgument::OPTIONAL,
            'Action to run (start / stop / restart)',
            'start'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Workerman reads $argv directly to determine the action.
        // We force $argv[1] to "start" so it always boots correctly
        // regardless of how the Symfony command was invoked.
        global $argv;
        $argv[1] = 'start';

        // Create a WebSocket server listening on all interfaces, port 8080
        $worker = new Worker('websocket://0.0.0.0:8080');

        // Triggered when a new client connects
        $worker->onConnect = function ($conn) {
            echo "New connection (id: {$conn->id})\n";
        };

        // Triggered when a message is received from any client.
        // Broadcasts the message to ALL connected clients so every
        // browser tab stays in sync (new post, deleted post, etc.)
        $worker->onMessage = function ($conn, $msg) use ($worker) {
            foreach ($worker->connections as $client) {
                $client->send($msg);
            }
        };

        // Triggered when a client disconnects
        $worker->onClose = function ($conn) {
            echo "Connection closed (id: {$conn->id})\n";
        };

        // Start the event loop — this is a blocking call
        Worker::runAll();

        return Command::SUCCESS;
    }
}