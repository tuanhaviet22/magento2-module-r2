<?php

declare(strict_types=1);

namespace TH\R2\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\RemoteStorage\Model\Config;
use Magento\RemoteStorage\Model\Synchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Synchronizes local Magento media to the configured remote storage.
 */
class MediaSyncCommand extends Command
{
    private const NAME = 'th:r2:media:sync';

    /**
     * @var \Magento\RemoteStorage\Model\Synchronizer
     */
    private $synchronizer;

    /**
     * @var \Magento\RemoteStorage\Model\Config
     */
    private $config;

    /**
     * @param \Magento\RemoteStorage\Model\Synchronizer $synchronizer
     * @param \Magento\RemoteStorage\Model\Config $config
     */
    public function __construct(
        Synchronizer $synchronizer,
        Config $config
    ) {
        $this->synchronizer = $synchronizer;
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
        $this->setDescription('Synchronize local Magento media files to Cloudflare R2 remote storage.');
    }

    /**
     * Run media synchronization.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if (!$this->config->isEnabled()) {
                $output->writeln('<error>Remote storage is not enabled.</error>');

                return Cli::RETURN_FAILURE;
            }

            $output->writeln('<info>Uploading media files to Cloudflare R2 remote storage.</info>');

            $count = 0;
            foreach ($this->synchronizer->execute() as $file) {
                $count++;
                $output->writeln('- ' . $file);
            }
        } catch (Throwable $exception) {
            $output->writeln('<error>Cloudflare R2 media sync failed.</error>');
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Cli::RETURN_FAILURE;
        }

        $output->writeln(sprintf('<info>End of upload. Synchronized entries: %d.</info>', $count));

        return Cli::RETURN_SUCCESS;
    }
}
