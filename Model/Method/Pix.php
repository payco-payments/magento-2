<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use Payco\Payments\Model\Config\Config;
use Payco\Payments\Helper\Logger as LoggerHelper;
use \Payco\Payments\Model\Request;

class Pix extends AbstractMethod
{
    const CODE = 'payco_payments_pix';

    protected $_code = self::CODE;
    protected $_infoBlockType = \Payco\Payments\Block\Info\Pix::class;
    protected $_isInitializeNeeded = true;

    private Config $config;
    private Request $apiService;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        LoggerHelper $loggerHelper,
        Config $config,
        Request $apiService,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data, $directory);
        $this->loggerHelper = $loggerHelper;
        $this->config = $config;
        $this->apiService = $apiService;
        $this->storeManager = $storeManager;
    }

    public function initialize($paymentAction, $stateObject)
    {

        try {

            /* @var $order \Magento\Sales\Model\Order */
            $order = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $response = $this->createCharge($this->buildPayload($order, $payment));
            $pixId = $response['id'];
            $transactionId = $response['transaction_id'];
            $expirationDate = $response['expiration_date'];
            $qrcode = $response['qr_code'];
            $qrcodeBase64 = $response['qr_code_base64'];
            $qrcodeImageUrl = $response['qr_code_url'];

            $additionalInfo = [
                'payco' => [
                    'pix_id' => $pixId,
                    'transaction_id' => $transactionId,
                    'expiration_date' => $expirationDate,
                    'qr_code' => $qrcode,
                    'qr_code_base64' => $qrcodeBase64,
                    'qr_code_url' => $qrcodeImageUrl
                ]
            ];

            $payment->setAdditionalInformation($additionalInfo);

        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__($exception->getMessage()));
        }
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $info = $this->getInfoInstance();

        $documentCpf = $data['additional_data']['document_cpf'] ?? '';
        $info->setAdditionalInformation(self::CODE . '_cpf', $documentCpf);

        return $this;
    }

    public function createCharge($data)
    {
        return $this->apiService->request('/payments/pix', $data);
    }

    public function buildPayload($order, $payment)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $expirationDate = $this->generateExpirationDate();
        $amount = $order->getGrandTotal();
        $description = '#' . $order->getIncrementId();
        $callbackUrl = 'rest/V1/payco/webhook/pix';
        $customer = $this->getCustomer($order, $payment);

        return [
            "expiration_date" => $expirationDate,
            "amount" => $amount,
            "description" => $description,
            "callback_url" => $this->getBaseUrl($callbackUrl),
            "customer" => $customer,
            "metadata" => [
                "magento_order_id" => $order->getIncrementId()
            ]
        ];
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function generateExpirationDate(): string
    {
        $pixExpirationMinutes = $this->config->getPixExpiration();

        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $date->add(new \DateInterval('PT' . $pixExpirationMinutes . 'M'));
        return $date->format('Y-m-d\TH:i:s.v\Z');
    }

    public function getCpf($order, $payment)
    {
        if ($payment->getAdditionalInformation(self::CODE . '_cpf')) {
            return $payment->getAdditionalInformation(self::CODE . '_cpf');
        }

        if($order->getBillingAddress()->getVatId()){
            return preg_replace('/[^0-9]/', '', $order->getBillingAddress()->getVatId());
        }
    }

    public function getCustomer($order, $payment)
    {
        /* @var $order \Magento\Sales\Model\Order */

        $billing = $order->getBillingAddress();
        $firstname = $billing->getFirstname();
        $lastname = $billing->getLastname();

        $cpf = $this->getCpf($order,$payment);

        return [
            "name" => $firstname . ' ' . $lastname,
            "document" => [
                "number" => $cpf,
                "type" => "CPF"
            ]
        ];
    }

    private function getBaseUrl(string $url)
    {
        return $this->storeManager->getStore()->getBaseUrl() . $url;
    }
}
