<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Api\Webhook;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Payco\Payments\Api\Webhook\PixInterface;
use \Magento\Framework\Webapi\Rest\Response;
use Payco\Payments\Helper\Logger;

class Pix implements PixInterface
{

    /**
     * @var Response
     */
    protected $response;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Payco\Payments\Model\Config\Config
     */
    protected $config;
    /**
     * @var StatusFactory
     */
    protected $statusFactory;
    /**
     * @var Logger
     */
    protected $loggerHelper;

    public function __construct(
        \Payco\Payments\Model\Config\Config $config,
        Response $response,
        Request $request,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StatusFactory $statusFactory,
        Logger $loggerHelper
    )
    {
        $this->response = $response;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->config = $config;
        $this->statusFactory = $statusFactory;
        $this->loggerHelper = $loggerHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $request = $this->request;
        $body = $request->getBodyParams();

        if (!isset($body['payload'])) {
            $this->loggerHelper->debug('PAYCO WEBHOOK: payload not found, please contact Payco support!');
            return $this->setResponse([
                "message" => __("Invalid data, please contact support!")
            ], 400);
        }

        if (!isset($body['payload']['metadata']['magento_order_id'])) {
            $this->loggerHelper->debug('PAYCO WEBHOOK: metadata not found, please contact Payco support!');
            return $this->setResponse([
                "message" => __("Invalid data, please contact support!")
            ], 400);
        }

        $payload = new DataObject($body['payload']);

        $this->loggerHelper->debug(__("PAYCO WEBHOOK: here"));

        $integrationStatus = $payload->getStatus();
        $orderIncrement = $payload->getMetadata()['magento_order_id'];

        $order = $this->getOrder($orderIncrement);

        if (!$order) {
            $this->loggerHelper->debug(__("PAYCO WEBHOOK: Order #%1 don't exist, please contact Payco support!", $orderIncrement));

            return $this->setResponse([
                "message" => __("Order don't exist, please contact support!")
            ], 400);
        }
        $response = [];
        switch ($integrationStatus) {
            case 'WAITING_PAYMENT':
                $statusToUpdate = $this->config->getOrderStatusPaymentPending();
                $message = __('Awaiting Payment');
                $this->loggerHelper->debug(__("PAYCO WEBHOOK: %1", $message));
                if ($this->checkStatusAlreadyUpdated($statusToUpdate, $order)) {
                    $response = [
                        "message" => __('Order status already update.'),
                        "orderStatus" => $statusToUpdate
                    ];
                }
                if (!$this->checkStatusAlreadyUpdated($statusToUpdate, $order)) {
                    $order->setState($this->_getAssignedState($statusToUpdate));
                    $order->addStatusToHistory($statusToUpdate, $message, true);
                    $this->orderRepository->save($order);
                    $response = [
                        "message" => __('Order is awaiting payment.'),
                        "orderStatus" => $statusToUpdate
                    ];
                }
                break;
            case 'PAID':
                $statusToUpdate = $this->config->getOrderStatusPaymentApproved();
                $message = __('Payment paid');
                $this->loggerHelper->debug(__("PAYCO WEBHOOK: %1", $message));
                if ($this->checkStatusAlreadyUpdated($statusToUpdate, $order)) {
                    $response = [
                        "message" => __('Order status already update.'),
                        "orderStatus" => $statusToUpdate
                    ];
                }
                if (!$this->checkStatusAlreadyUpdated($statusToUpdate, $order)) {
                    $order->setState($this->_getAssignedState($statusToUpdate));
                    $order->addStatusToHistory($statusToUpdate, $message, true);
                    $this->orderRepository->save($order);
                    $response = [
                        "message" => __('Order was paid.'),
                        "orderStatus" => $statusToUpdate
                    ];
                }
                break;
            case 'EXPIRED':
            case 'FAILED':
                $statusToUpdate = $this->config->getOrderStatusPaymentCanceled();
                $message = '';
                $this->loggerHelper->debug(__("PAYCO WEBHOOK: %1", $message));
                if (!$order->canCancel()) {
                    $response = [
                        "message" => __('Order can not be cancel or is already cancel.')
                    ];
                }

                if ($integrationStatus === 'EXPIRED') {
                    $message = __('Payment Expired.');
                }
                if ($integrationStatus === 'FAILED') {
                    $message = __('Payment Failed.');
                }

                if ($order->canCancel()) {
                    $order->cancel();
                    $order->setState($this->_getAssignedState($statusToUpdate));
                    $order->addStatusToHistory($statusToUpdate, $message, true);
                    $this->orderRepository->save($order);
                    $response = [
                        "message" => __('Order was cancel.'),
                        "orderStatus" => $statusToUpdate
                    ];
                }
                break;
        }

        return $this->setResponse($response);
    }

    /**
     * @param array $body
     * @param int $statusCode
     * @return int|null
     */
    private function setResponse(array $body, int $statusCode = 200): ?int
    {
        $this->response->setHeader('Content-Type', 'application/json', true);

        return $this->response->setBody(json_encode($body))
            ->setHttpResponseCode($statusCode)
            ->sendResponse();
    }

    /**
     * @param string $incrementId
     * @return false|mixed
     */
    private function getOrder(string $incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId)->create();
        $items = $this->orderRepository->getList($searchCriteria)->getItems();

        return reset($items);
    }

    /**
     * @param $statusToUpdate
     * @param $order
     * @return bool
     */
    private function checkStatusAlreadyUpdated($statusToUpdate, $order)
    {
        $orderUpdated = false;
        $commentsObject = $order->getStatusHistoryCollection(true);
        foreach ($commentsObject as $commentObj) {
            if ($commentObj->getStatus() === $statusToUpdate) {
                $orderUpdated = true;
            }
        }

        return $orderUpdated;
    }

    /**
     * @param $status
     * @return mixed
     */
    private function _getAssignedState($status)
    {
        $collection = $this->statusFactory->joinStates()->addFieldToFilter('main_table.status', $status);
        $collectionItems = $collection->getItems();
        return array_pop($collectionItems)->getState();
    }
}
