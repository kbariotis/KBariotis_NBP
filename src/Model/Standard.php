<?php

/**
 * Class KBariotis_NBP_Model_Standard
 *
 * @author: Kostas Bariotis
 */
class KBariotis_NBP_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'nbp';

	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;

    protected $_formBlockType = 'nbp/form_nbp';
    protected $_canSaveCc     = false;

	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('nbp/payment/redirect', array('_secure' => true));
	}

}
?>