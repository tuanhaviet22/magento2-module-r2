<?php

declare(strict_types=1);

namespace TH\R2\Driver;

use Magento\AwsS3\Driver\AwsS3Factory;
use Magento\Framework\Exception\LocalizedException;
use Magento\RemoteStorage\Driver\DriverException;
use Magento\RemoteStorage\Driver\DriverFactoryInterface;
use Magento\RemoteStorage\Driver\RemoteDriverInterface;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use TH\R2\Model\Config as R2Config;

/**
 * Creates a Cloudflare R2 remote storage driver.
 */
class R2Factory implements DriverFactoryInterface
{
    /**
     * @var \Magento\AwsS3\Driver\AwsS3Factory
     */
    private $awsS3Factory;

    /**
     * @var \Magento\RemoteStorage\Model\Config
     */
    private $remoteStorageConfig;

    /**
     * @var \TH\R2\Model\Config
     */
    private $r2Config;

    /**
     * @param \Magento\AwsS3\Driver\AwsS3Factory $awsS3Factory
     * @param \Magento\RemoteStorage\Model\Config $remoteStorageConfig
     * @param \TH\R2\Model\Config $r2Config
     */
    public function __construct(
        AwsS3Factory $awsS3Factory,
        RemoteStorageConfig $remoteStorageConfig,
        R2Config $r2Config
    ) {
        $this->awsS3Factory = $awsS3Factory;
        $this->remoteStorageConfig = $remoteStorageConfig;
        $this->r2Config = $r2Config;
    }

    /**
     * Create configured R2 remote driver.
     *
     * @return \Magento\RemoteStorage\Driver\RemoteDriverInterface
     * @throws \Magento\RemoteStorage\Driver\DriverException
     */
    public function create(): RemoteDriverInterface
    {
        try {
            $config = $this->remoteStorageConfig->getConfig();
            $prefix = $this->remoteStorageConfig->getPrefix();
        } catch (LocalizedException $exception) {
            throw new DriverException(__($exception->getMessage()), $exception);
        }

        if (!$config) {
            $config = $this->r2Config->getClientConfig();
            $prefix = $this->r2Config->getPrefix();
        }

        return $this->createConfigured($config, $prefix);
    }

    /**
     * Create R2 remote driver from config.
     *
     * @param array<string, mixed> $config
     * @param string $prefix
     * @param string $cacheAdapter
     * @param array<string, mixed> $cacheConfig
     * @return \Magento\RemoteStorage\Driver\RemoteDriverInterface
     * @throws \Magento\RemoteStorage\Driver\DriverException
     */
    public function createConfigured(
        array $config,
        string $prefix,
        string $cacheAdapter = '',
        array $cacheConfig = []
    ): RemoteDriverInterface {
        $config = $this->normalizeConfig($config);

        return $this->awsS3Factory->createConfigured($config, trim($prefix, '/'), $cacheAdapter, $cacheConfig);
    }

    /**
     * Normalize R2 config into the shape expected by Magento_AwsS3.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     * @throws \Magento\RemoteStorage\Driver\DriverException
     */
    private function normalizeConfig(array $config): array
    {
        $accountId = trim((string)($config['account_id'] ?? ''));
        $endpoint = rtrim((string)($config['endpoint'] ?? ''), '/');

        if (!$endpoint && $accountId) {
            $endpoint = sprintf('https://%s.r2.cloudflarestorage.com', $accountId);
        }

        $config['endpoint'] = $endpoint;
        $config['region'] = (string)($config['region'] ?? 'auto') ?: 'auto';
        $config['bucket'] = (string)($config['bucket'] ?? '');

        if (isset($config['path-style']) && !isset($config['path_style'])) {
            $config['path_style'] = $config['path-style'];
        }

        if (isset($config['access_key']) || isset($config['secret_key'])) {
            $config['credentials'] = [
                'key' => (string)($config['access_key'] ?? ''),
                'secret' => (string)($config['secret_key'] ?? ''),
            ];
        }

        if (!isset($config['credentials']) || !is_array($config['credentials'])) {
            $config['credentials'] = [
                'key' => '',
                'secret' => '',
            ];
        }

        if (!$config['endpoint']) {
            throw new DriverException(__('Cloudflare R2 endpoint is required.'));
        }

        if (!$config['bucket']) {
            throw new DriverException(__('Cloudflare R2 bucket is required.'));
        }

        return $config;
    }
}
