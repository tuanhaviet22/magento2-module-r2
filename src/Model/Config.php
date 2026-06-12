<?php

declare(strict_types=1);

namespace TH\R2\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads Cloudflare R2 configuration values.
 */
class Config
{
    public const DRIVER_CODE = 'cloudflare-r2';

    private const XML_PATH_ENABLED = 'th_r2/general/enabled';
    private const XML_PATH_DRIVER_CODE = 'th_r2/general/driver_code';
    private const XML_PATH_ACCOUNT_ID = 'th_r2/connection/account_id';
    private const XML_PATH_ENDPOINT = 'th_r2/connection/endpoint';
    private const XML_PATH_BUCKET = 'th_r2/connection/bucket';
    private const XML_PATH_REGION = 'th_r2/connection/region';
    private const XML_PATH_ACCESS_KEY_ID = 'th_r2/connection/access_key_id';
    private const XML_PATH_SECRET_ACCESS_KEY = 'th_r2/connection/secret_access_key';
    private const XML_PATH_PREFIX = 'th_r2/connection/prefix';
    private const XML_PATH_PUBLIC_BASE_URL = 'th_r2/connection/public_base_url';
    private const XML_PATH_PATH_STYLE = 'th_r2/connection/path_style';
    private const XML_PATH_TIMEOUT = 'th_r2/connection/timeout';
    private const XML_PATH_RETRIES = 'th_r2/connection/retries';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * Check whether module configuration is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * Get remote storage driver code.
     *
     * @return string
     */
    public function getDriverCode(): string
    {
        return $this->getValue(self::XML_PATH_DRIVER_CODE) ?: self::DRIVER_CODE;
    }

    /**
     * Get configured object key prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return trim($this->getValue(self::XML_PATH_PREFIX), '/');
    }

    /**
     * Build S3-compatible configuration from admin values.
     *
     * @return array<string, mixed>
     */
    public function getClientConfig(): array
    {
        $config = [
            'account_id' => $this->getValue(self::XML_PATH_ACCOUNT_ID),
            'endpoint' => $this->getEndpoint(),
            'bucket' => $this->getValue(self::XML_PATH_BUCKET),
            'region' => $this->getValue(self::XML_PATH_REGION) ?: 'auto',
            'credentials' => [
                'key' => $this->getEncryptedValue(self::XML_PATH_ACCESS_KEY_ID),
                'secret' => $this->getEncryptedValue(self::XML_PATH_SECRET_ACCESS_KEY),
            ],
            'path_style' => $this->isPathStyleEnabled() ? '1' : '0',
            'path-style' => $this->isPathStyleEnabled() ? '1' : '0',
            'public_base_url' => $this->getPublicBaseUrl(),
        ];

        $timeout = $this->getPositiveFloat(self::XML_PATH_TIMEOUT);
        if ($timeout > 0) {
            $config['timeout'] = $timeout;
        }

        $retries = $this->getNonNegativeInteger(self::XML_PATH_RETRIES);
        if ($retries >= 0) {
            $config['retries'] = $retries;
        }

        return $config;
    }

    /**
     * Get Cloudflare R2 endpoint.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        $endpoint = rtrim($this->getValue(self::XML_PATH_ENDPOINT), '/');

        if ($endpoint) {
            return $endpoint;
        }

        $accountId = $this->getValue(self::XML_PATH_ACCOUNT_ID);

        if (!$accountId) {
            return '';
        }

        return sprintf('https://%s.r2.cloudflarestorage.com', $accountId);
    }

    /**
     * Get public media base URL.
     *
     * @return string
     */
    public function getPublicBaseUrl(): string
    {
        return rtrim($this->getValue(self::XML_PATH_PUBLIC_BASE_URL), '/');
    }

    /**
     * Check whether path-style endpoint mode is enabled.
     *
     * @return bool
     */
    public function isPathStyleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PATH_STYLE);
    }

    /**
     * Get raw config value.
     *
     * @param string $path
     * @return string
     */
    private function getValue(string $path): string
    {
        return trim((string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Get decrypted config value.
     *
     * @param string $path
     * @return string
     */
    private function getEncryptedValue(string $path): string
    {
        $value = $this->getValue($path);

        if (!$value) {
            return '';
        }

        return (string)$this->encryptor->decrypt($value);
    }

    /**
     * Get a non-negative integer config value.
     *
     * @param string $path
     * @return int
     */
    private function getNonNegativeInteger(string $path): int
    {
        $value = $this->getValue($path);

        if ($value === '' || !is_numeric($value)) {
            return -1;
        }

        return max(0, (int)$value);
    }

    /**
     * Get a positive float config value.
     *
     * @param string $path
     * @return float
     */
    private function getPositiveFloat(string $path): float
    {
        $value = $this->getValue($path);

        if ($value === '' || !is_numeric($value)) {
            return 0.0;
        }

        return max(0.0, (float)$value);
    }
}
