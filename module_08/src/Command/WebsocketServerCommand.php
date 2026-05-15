<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

#[AsCommand(name: 'websocket:server')]
class WebsocketServerCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::OPTIONAL, 'Action (start/stop/restart)', 'start');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Workerman lit argv directement, on s'assure que 'start' est bien là
        global $argv;
        $argv[1] = 'start';

        $worker = new Worker('websocket://0.0.0.0:8080');

        $worker->onConnect = function ($conn) {
            echo "Nouvelle connexion ({$conn->id})\n";
        };

        $worker->onMessage = function ($conn, $msg) use ($worker) {
            foreach ($worker->connections as $client) {
                $client->send($msg);
            }
        };

        $worker->onClose = function ($conn) {
            echo "Connexion fermée ({$conn->id})\n";
        };

        Worker::runAll();
        return Command::SUCCESS;
    }
}