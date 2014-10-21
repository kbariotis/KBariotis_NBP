<?php

/**
 * Class KBariotis_NBP_Model_NBP
 *
 * @author: Kostas Bariotis
 */
class KBariotis_NBP_Model_NBP extends Mage_Core_Model_Abstract
{

    private $proxyPayEndPoint = null;
    private $merchantID = null;
    private $merchantSecret = null;
    private $newOrderStatus = null;
    private $pageSetId = null;


    protected function _construct()
    {
        $this->merchantID       = Mage::getStoreConfig('payment/nbp/merchant_id');
        $this->proxyPayEndPoint = Mage::getStoreConfig('payment/nbp/proxy_pay_endpoint');
        $this->merchantSecret   = Mage::getStoreConfig('payment/nbp/merchant_confirmation_pwd');
        $this->pageSetId        = Mage::getStoreConfig('payment/nbp/page_set_id');
        $this->newOrderStatus   = Mage::getStoreConfig('payment/nbp/order_status');
    }

    public function getRedirectUrl()
    {

        $order   = new Mage_Sales_Model_Order();
        $orderId = Mage::getSingleton('checkout/session')
                       ->getLastRealOrderId();
        $order->loadByIncrementId($orderId);
        $orderTotal = $order->getBaseGrandTotal();
        $successUrl = Mage::getUrl('nbp/payment/success/');

        $request = $this->createXMLRequestPreTransaction($orderId, $orderTotal, $successUrl);

        if ($response = $this->makeRequest($request))
            return $response->HpsTxn->hps_url . '?HPS_SessionID=' . $response->HpsTxn->session_id;

        return false;
    }

    private function createXMLRequestPreTransaction($orderId, $orderTotal, $successUrl)
    {
        $request = new SimpleXMLElement("<Request></Request>");
        $request->addAttribute("version", "2");

        $auth = $request->addChild("Authentication");
        $auth->addChild("password", $this->merchantSecret);
        $auth->addChild("client", $this->merchantID);

        $transaction = $request->addChild("Transaction");
        $txnDetails  = $transaction->addChild("TxnDetails");
        $txnDetails
            ->addChild("merchantreference", $orderId);
        $txnDetails
            ->addChild("amount", $orderTotal)
            ->addAttribute("currency", "EUR");
        $txnDetails
            ->addChild("capturemethod", "ecomm");

        $hpsTxn = $transaction->addChild("HpsTxn");
        $hpsTxn
            ->addChild("method", "setup_full");
        $hpsTxn
            ->addChild("page_set_id", $this->pageSetId);
        $hpsTxn
            ->addChild("return_url", $successUrl);

        $cardTxn = $transaction->addChild('CardTxn');
        $cardTxn
            ->addChild("method", "auth");

        return $request;
    }

    public function queryRefTransaction($ref)
    {

        $request = $this->createXMLRequestPostTransaction($ref);

        if ($response = $this->makeRequest($request))
            return $response->merchantreference;

        return false;

    }

    private function createXMLRequestPostTransaction($ref)
    {

        $request = new SimpleXMLElement("<Request></Request>");
        $request->addAttribute("version", "2");

        $auth = $request->addChild("Authentication");
        $auth->addChild("password", $this->merchantSecret);
        $auth->addChild("client", $this->merchantID);

        $transaction = $request->addChild("Transaction");
        $historicTxn = $transaction->addChild("HistoricTxn");
        $historicTxn
            ->addChild("method", "query");
        $historicTxn
            ->addChild("reference", $ref);

        return $request;
    }

    private function makeRequest($request)
    {
        $client = new Varien_Http_Client($this->proxyPayEndPoint);
        $client->setMethod(Varien_Http_Client::POST);
        $client->setRawData($request->asXML());

        $response = $client->request();
        if (!$response->isSuccessful())
            throw new Mage_Payment_Exception('Could not communicate to payment server');

        $responseBody = $response->getBody();

        $response = simplexml_load_string($responseBody);

        $status = intval($response->status);

        if ($status != 1 && $status != 7)
            Mage::log('Error from the Bank : ' . $responseBody);

        if ($status == 7)
            Mage::log('Bank refused the payment : ' . $responseBody);

        if ($status == 1)
            return $response;

        return false;
    }

    public function getNewOrderStatus()
    {
        return $this->newOrderStatus;
    }
}