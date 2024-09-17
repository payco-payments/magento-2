<?php

declare(strict_types=1);

namespace Payco\Payments\Controller\Checkout;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Payco\Payments\Model\Request;

class Status implements HttpGetActionInterface
{

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var Request
     */
    protected Request $apiService;

    /**
     * Index constructor.
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        Request $apiService
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->apiService = $apiService;
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $json = $this->jsonFactory->create();
        $transactionId = $this->request->getParam('transactionId');

        $requestApi = $this->apiService->request('/payments/pix/' . $transactionId, [], 'GET');
        $statusPayment = $requestApi['status'];
        $expirationDate = $requestApi['expiration_date'];

        $date = new \DateTime($expirationDate);
        $now = new \DateTime();

        if($date < $now) {
            $statusPayment = 'EXPIRED';
        }

        $data = [];
        switch ("$statusPayment") {
            case 'WAITING_PAYMENT':
                $data['status'] = [
                    "value" => 'pending',
                    "label" => __('Awaiting payment')
                ];
                break;
            case 'PAID':
                $data['status'] = [
                    "value" => 'paid',
                    "label" => __('Payment received')
                ];
                break;
            case 'EXPIRED':
                $data['status'] = [
                    "value" => "expired",
                    "label" => __('Expired payment')
                ];
                break;
            case 'FAILED':
                $data['status'] = [
                    "value" => "failed",
                    "label" => __('Payment failure')
                ];
                break;
            default:
                $data['status'] = [
                    "value" => 'pending',
                    "label" => __('Awaiting payment')
                ];
                break;
        }

        $json->setData($data);
        return $json;
    }
}
