<?php
declare(strict_types=1);

namespace Payco\Payments\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Payco\Payments\Model\Config\Config;
use Payco\Payments\Helper\Logger as LoggerHelper;
use \Payco\Payments\Model\Request;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class CreditCard extends AbstractMethod
{
    const CODE = 'payco_payments_credit_card';

    protected $_code = self::CODE;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_supportedCurrencyCodes = ['BRL'];
    protected $_infoBlockType = \Payco\Payments\Block\Info\Cc::class;
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    protected $_isInitializeNeeded = true;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Request
     */
    protected $apiService;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        Context                                                 $context,
        Registry                                                $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory       $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory            $customAttributeFactory,
        \Magento\Payment\Helper\Data                            $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig,
        \Magento\Payment\Model\Method\Logger                    $logger,
        LoggerHelper                                            $loggerHelper,
        Config                                                  $config,
        InvoiceService                                          $invoiceService,
        InvoiceSender                                           $invoiceSender,
        Transaction                                             $transaction,
        \Magento\Framework\DB\TransactionFactory                $transactionFactory,
        Request                                                 $apiService,
        StoreManagerInterface                                   $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [],
        DirectoryHelper                                         $directory = null
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data, $directory);
        $this->loggerHelper = $loggerHelper;
        $this->config = $config;
        $this->apiService = $apiService;
        $this->storeManager = $storeManager;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->transactionFactory = $transactionFactory;
    }

    public function initialize($paymentAction, $stateObject)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $this->getInfoInstance()->getOrder();
        $payment = $order->getPayment();

        try {
            $payload = $this->buildPayload($order, $payment);
            $response = $this->createCharge($payload);

            $payment->setSkipOrderProcessing(true);

            $additionalInfo = [
                'payco' => [
                    'payment_id' => $response['id'],
                    'nsu' => $response['transaction_id'],
                    'installments' => $payment->getAdditionalInformation(self::CODE . '_installments'),
                    'cc_exp_year' => $payment->getAdditionalInformation(self::CODE . '_cc_exp_year'),
                    'cc_exp_month' => $payment->getAdditionalInformation(self::CODE . '_cc_exp_month'),
                    'cc_type' => $payment->getAdditionalInformation(self::CODE . '_cc_type'),
                    'cc_encrypt_card' => $payment->getAdditionalInformation(self::CODE . '_cc_number_encrypted'),
                    'cc_holder_name' => $payment->getAdditionalInformation(self::CODE . '_cc_holder_name')
                ]
            ];

            $payment->setAdditionalInformation($additionalInfo);

            if ($response['status'] === 'SETTLED') {
                $newStatus = $this->config->getOrderStatusPaymentApproved();
                $order->setState($this->config->_getAssignedState($newStatus));
                $order->addStatusToHistory($newStatus, __('Payment Approved'), true);
                $this->createInvoice($order, $payment, $response['amount']);
            }

            if ($response['status'] === 'PENDING') {
                $newStatus = $this->config->getOrderStatusPaymentPending();
                $order->setState($this->config->_getAssignedState($newStatus));
                $order->addStatusToHistory($newStatus, __('Payment Pending'), true);
                $order->addStatusToHistory($newStatus, __('Payco - Please capture the payment on your payco dashboard'), false);
            }




        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__($exception->getMessage()));
        }

        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }
        return $this;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $info = $this->getInfoInstance();
        $documentCpf = $data['additional_data']['documentCpf'] ?? '';
        $info->setAdditionalInformation(self::CODE . '_cpf', $documentCpf);
        $info->setAdditionalInformation(self::CODE . '_cc_token', $data['additional_data']['cc_number_token'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_cc_antifroud_token', $data['additional_data']['code_antifraud'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_device_info', $data['additional_data']['device_info'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_installments', $data['additional_data']['installments'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_cc_type', $data['additional_data']['cc_type'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_cc_exp_year', $data['additional_data']['cc_exp_year'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_cc_exp_month', $data['additional_data']['cc_exp_month'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_cc_number_encrypted', $data['additional_data']['cc_number_encrypted'] ?? '');
        $info->setAdditionalInformation(self::CODE . '_cc_holder_name', $data['additional_data']['cc_holder_name'] ?? '');

        return $this;
    }

    public function createCharge($payload)
    {
        return $this->apiService->request('/payments/card', $payload);
    }

    public function buildPayload(Order $order, InfoInterface $payment)
    {
        $amount = $order->getGrandTotal();
        $description = '#' . $order->getIncrementId();
        $callbackUrl = 'rest/V1/payco/webhook/cc';
        $customer = $this->getCustomer($order, $payment);
        $deviceInfo = $this->getDeviceInfo($payment);
        $items = $this->getItems($order);
        $installments = $payment->getAdditionalInformation(self::CODE . '_installments');
        $cardToken = $payment->getAdditionalInformation(self::CODE . '_cc_token');
        $antiFraudToken = $payment->getAdditionalInformation(self::CODE . '_cc_antifroud_token');

        return [
            "amount" => $amount,
            "description" => $description,
            "installments" => $installments,
            "capture_type" => $this->config->getPaymentType(),
            "callback_url" => $this->getBaseUrl($callbackUrl),
            "card_vault_token" => $cardToken,
            "code_antifraud" => $antiFraudToken,
            "customer" => $customer,
            "device_info" => $deviceInfo,
            "items" => $items,
            "metadata" => [
                "magento_order_id" => $order->getIncrementId()
            ]
        ];
    }

    public function getDeviceInfo($payment)
    {
        $deviceInfo = $payment->getAdditionalInformation(self::CODE . '_device_info');
        return json_decode($deviceInfo, true);
    }

    public function getItems(Order $order)
    {
        $orderitems = $order->getItems();
        $items = [];

        foreach ($orderitems as $item) {
            $items[] = [
                "id" => $item->getSku(),
                "amount" => $item->getBaseRowTotalInclTax(),
                "description" => $item->getName()
            ];
        }

        return $items;
    }

    public function getCpf($order, $payment)
    {
        if ($payment->getAdditionalInformation(self::CODE . '_cpf')) {
            return $payment->getAdditionalInformation(self::CODE . '_cpf');
        }

        if ($order->getBillingAddress()->getVatId()) {
            return preg_replace('/[^0-9]/', '', $order->getBillingAddress()->getVatId());
        }
    }

    public function getCustomer($order, $payment)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $billing = $order->getBillingAddress();
        $cpf = $this->getCpf($order, $payment);
        $customerName = $billing->getFirstname() . ' ' . $billing->getLastname();
        if ($payment->getAdditionalInformation(self::CODE . '_cc_holder_name')) {
            $customerName = $payment->getAdditionalInformation(self::CODE . '_cc_holder_name');
        }

        $address = $this->formatAddressStreet($billing->getStreet());
        
        return [
            "document_type" => "CPF",
            "document_number" => $cpf,
            "name" => $customerName,
            "email" => $billing->getEmail(),
            "phone_number" => $billing->getTelephone(),
            "mobile_phone_number" => "11987683332",
            "address" => $address[0],
            "complement" => $address[1],
            "city" => $billing->getCity(),
            "state" => $billing->getRegionCode(),
            "zip_code" => $billing->getPostcode(),
            "ip_address" => $order->getRemoteIp(),
            "country" => "BR"
        ];
    }

    private function getBaseUrl(string $url)
    {
        return $this->storeManager->getStore()->getBaseUrl() . $url;
    }

    private function formatAddressStreet($street) {    
        $address = [
            isset($street[0]) ? $street[0] : '',
            isset($street[1]) ? $street[1] : ''
        ];
    
        if (count($street) > 2) {
            $address = [
                isset($street[0]) ? $street[0] . (isset($street[1]) ? ' ' . $street[1] : '') : '',
                isset($street[2]) ? $street[2] : ''
            ];
    
            if (isset($street[3])) {
                $address[0] .= ' ' . $street[3];
            }
        }
    
        return $address;
    }
    

    /**
     * @param $order
     * @param $payment
     * @param $amountPaid
     * @return void
     */
    private function createInvoice($order, $payment, $amountPaid){
        $invoice = $order->prepareInvoice()->register();
        $invoice->setOrder($order);

        $invoice->setBaseGrandTotal($order->getGrandTotal());
        $invoice->setGrandTotal($order->getGrandTotal());
        $invoice->setSubtotal($order->getGrandTotal());
        $invoice->setBaseSubtotal($order->getGrandTotal());

        $invoice->addComment(__('Captured by Payco Payments'));

        $order->addRelatedObject($invoice);
        $payment->setCreatedInvoice($invoice);
        $payment->setShouldCloseParentTransaction(true);
    }

    public function validate()
    {
        return $this;
    }
}
