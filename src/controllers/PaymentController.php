<?php

/**
 * Class KBariotis_NBP_PaymentController
 *
 * @author: Kostas Bariotis
 */
class KBariotis_NBP_PaymentController extends Mage_Core_Controller_Front_Action
{

    /* The redirect action is triggered when someone places an order */
    public function redirectAction()
    {
        $model = new KBariotis_NBP_Model_NBP();

        $redirectUrl = $model->getRedirectUrl();

        if ($redirectUrl)
            $this->_redirectUrl($redirectUrl);
        else
            $this->_redirectUrl(Mage::getUrl('checkout/onepage/failure'));
    }

    public function successAction()
    {

        Mage::setIsDeveloperMode(true);

        try {
            if (Mage::getSingleton('checkout/session')
                    ->getLastRealOrderId()
            ) {

                $_request = $this->getRequest()
                                 ->getParam('dts_reference');
                $model    = new KBariotis_NBP_Model_NBP();

                if ($orderId = $model->queryRefTransaction($_request)) {
                    echo "KOSTARELO";

                    Mage_Core_Controller_Varien_Action::_redirect(
                                                      'checkout/onepage/success', array('_secure' => true)
                    );
                }
                else {
                    /* There must be a message too */
                    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure');
                }
            }
        } catch (Mage_Payment_Exception $e) {
            Mage::log("Something went wrong / " . $e->getMessage());
        }
    }
}