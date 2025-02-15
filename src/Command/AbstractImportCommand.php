<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\PipelineInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputOption;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Factory\PipelineFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;

abstract class AbstractImportCommand extends Command
{
    use LockableTrait;

    /** @var string The default command description */
    protected static $defaultDescription = '';

    protected PipelineInterface $pipeline;

    public function __construct(
        protected LoggerInterface $logger,
        protected PayloadFactoryInterface $payloadFactory,
        private PipelineFactoryInterface $pipelineFactory,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription(static::$defaultDescription)
            ->addOption('continue')
            ->addOption('parallel', 'p', InputOption::VALUE_NONE, 'Allow parallel task processing')
            ->addOption('disable-batch', 'd', InputOption::VALUE_NONE, 'Disable batch processing')
            ->addOption('batch-size', 's', InputOption::VALUE_OPTIONAL, 'Batch Size', 100)
            ->addOption('max-concurrency', 'c', InputOption::VALUE_OPTIONAL, 'Max process concurrency', 5)
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Add filter')
        ;
    }

    protected function preExecute(): void
    {
        if (!$this->lock()) {
            throw new CommandLockedException(Messages::commandAlreadyRunning());
        }

        $this->logger->notice(static::$defaultName ?? '');

        $this->pipeline = $this->pipelineFactory->create();
    }

    protected function postExecute(): void
    {
        $this->logger->notice(Messages::endOfCommand(static::$defaultName ?? ''));
        $this->release();
    }
}
