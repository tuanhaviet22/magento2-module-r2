<?php

declare(strict_types=1);

namespace TH\R2\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TH\R2\Driver\R2Factory;
use TH\R2\Model\Config;
use Throwable;

/**
 * Tests Cloudflare R2 connectivity.
 */
class TestConnectionCommand extends Command
{
    private const NAME = 'th:r2:test-connection';

    /**
     * @var \TH\R2\Driver\R2Factory
     */
    private $r2Factory;

    /**
     * @var \TH\R2\Model\Config
     */
    private $config;

    /**
     * @param \TH\R2\Driver\R2Factory $r2Factory
     * @param \TH\R2\Model\Config $config
     */
    public function __construct(
        R2Factory $r2Factory,
        Config $config
    ) {
        $this->r2Factory = $r2Factory;
        $this->config = $config;

        parent::__construct(self::NAME);
    }

    /**
     * Configure command metadata.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Test Cloudflare R2 connection using TH_R2 configuration.');
    }

    /**
     * Run connection test.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->config->isEnabled()) {
            $output->writeln('<error>TH_R2 configuration is disabled.</error>');

            return Cli::RETURN_FAILURE;
        }

        try {
            $driver = $this->r2Factory->createConfigured(
                $this->config->getClientConfig(),
                $this->config->getPrefix()
            );
            $driver->test();
        } catch (Throwable $exception) {
            $output->writeln('<error>Cloudflare R2 connection failed.</error>');
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Cli::RETURN_FAILURE;
        }

        $output->writeln('<info>Cloudflare R2 connection succeeded.</info>');

        return Cli::RETURN_SUCCESS;
    }
}
