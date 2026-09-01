<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\Console;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Revolt\EventLoop;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Trollbus\MessageBus\Async\Consumer;
use Trollbus\MessageBus\Async\TransportConsumer;

#[AsCommand('trollbus:consumer:run', 'Run consumer for queues')]
final class ConsumerRunCommand extends Command
{
    public function __construct(
        private readonly TransportConsumer $transportConsumer,
        private readonly ContainerInterface $queueToConsumer,
        ?string $name = null,
        ?callable $code = null,
    ) {
        parent::__construct($name, $code);
    }

    protected function configure(): void
    {
        $this->addArgument('queues', InputArgument::REQUIRED | InputArgument::IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var non-empty-list<non-empty-string> $queues */
        $queues = $input->getArgument('queues');

        /** @var list<\Closure> $consumers */
        $cancellations = [];

        foreach ($queues as $queue) {
            try {
                $consumer = $this->queueToConsumer->get($queue);
                \assert($consumer instanceof Consumer);
                $cancellations[] = $this->transportConsumer->runConsume($consumer);
            } catch (NotFoundExceptionInterface $e) {
                throw new \RuntimeException(
                    message: \sprintf('Queue "%s" not found.', $queue),
                    code: $e->getCode(),
                    previous: $e,
                );
            }
        }

        $cancel = static function () use ($cancellations): void {
            foreach ($cancellations as $c) {
                $c();
            }
        };

        EventLoop::onSignal(SIGINT, $cancel);
        EventLoop::onSignal(SIGTERM, $cancel);
        EventLoop::run();

        return Command::SUCCESS;
    }
}
