<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Payco\Payments\Api\RequestInterface;
use Payco\Payments\Model\Config\Config;
use Payco\Payments\Helper\Logger;

class Request implements RequestInterface
{
    const API_PROD_URL = 'https://api.payments.payco.com.br';
    const API_AUTH_URL = 'https://sso.payments.payco.com.br/realms/payco-payments/protocol/openid-connect/token';
    const API_VERSION = 'v1/';
    const API_PUBLIC_ENDPOINT = '/public-api/api/' . self::API_VERSION;
    const API_OPEN_ENDPOINT = '/open/api/' . self::API_VERSION;

    /**
     * @var Config
     */
    protected Config $config;
    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $_encryptor;
    /**
     * @var Json
     */
    protected $_json;
    /**
     * @var Logger
     */
    protected $loggerHelper;
    /**
     * @var ClientFactory
     */
    protected $clientFactory;
    /**
     * @var Curl
     */
    protected $_curl;

    public function __construct(
        Config $config,
        EncryptorInterface $encryptor,
        Json $json,
        Logger $loggerHelper,
        ClientFactory $clientFactory,
        Curl $curl
    )
    {
        $this->config = $config;
        $this->_encryptor = $encryptor;
        $this->_json = $json;
        $this->loggerHelper = $loggerHelper;
        $this->clientFactory = $clientFactory;
        $this->_curl = $curl;
    }

    /**
     * @param string $endpoint
     * @param string | array $payload
     * @param string $method
     * @param bool $isOpenRequest
     * @return DataObject
     * @throws LocalizedException
     */
    public function request(string $endpoint, $payload, string $method = 'POST', bool $isOpenRequest = false)
    {
        if (is_array($payload)) {
            $payload = json_encode($payload);
        }

        $this->loggerHelper->debug(__('PAYLOAD: %1', $payload));

        $client = $this->clientFactory->create();

        try {
            $accessToken = $this->newAuthToken()->getData('access_token');

            $apiUrl = self::API_PROD_URL . ($isOpenRequest ? self::API_OPEN_ENDPOINT : self::API_PUBLIC_ENDPOINT);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . $endpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
            }
            if ($method !== 'POST') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $this->loggerHelper->debug(__('RESPONSE: %1', $response));
            $responseBody = $this->_json->unserialize($response);


            if (isset($responseBody['code'])) {
                if (isset($responseBody['message'])) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($responseBody['message']));
                }
                $this->loggerHelper->error(__('API ERROR: %1', $response));
                throw new \Magento\Framework\Exception\LocalizedException(__('An error occurred in your payment, Please contact the store.'));
            }

            return new \Magento\Framework\DataObject($responseBody);
        } catch (\Exception $e) {
            $this->loggerHelper->error(__('API ERROR: %1', $e->getMessage()));
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @return DataObject
     * @throws LocalizedException
     */
    private function newAuthToken()
    {
        $credentials = $this->config->getCredentials();
        $client_id = $credentials['client_id'];
        $client_secret = $this->_encryptor->decrypt($credentials['client_secret']);

        $fields = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'client_credentials'
        ];

        try {
            $ch = curl_init();
            $postData = http_build_query($fields);
            curl_setopt($ch, CURLOPT_URL, self::API_AUTH_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $body = json_decode($response, true);

            if (!isset($body)) {
                $this->loggerHelper->debug($response);
                throw new \Magento\Framework\Exception\LocalizedException(__('An error occurred in your payment, Please contact the store.'));
            }

            if (isset($body['error'])) {
                $this->loggerHelper->debug($response);
                throw new \Magento\Framework\Exception\LocalizedException(__('An error occurred in your payment, Please contact the store.'));
            }

            return new \Magento\Framework\DataObject($body);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    private function handleResponseCode($responseBody)
    {
        if (isset($responseBody['code'])) {
            $this->loggerHelper->error(__('API ERROR: %1', $responseBody['message']));
            throw new \Magento\Framework\Exception\LocalizedException(__($responseBody['message']));
        }
    }
}
