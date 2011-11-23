<?php
/**
 * Varnish API
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Magneto_Varnish_Model_Api extends Mage_Api_Model_Resource_Abstract {

	/**
	 * Purge url
	 *
	 * @param array $urls
	 * @return array errors
	 */
	public function purge($urls) {
		$helper = Mage::helper('varnish'); /* @var $helper Magneto_Varnish_Helper_Data */
		$errors = $helper->purge($urls);
		return $errors;
	}

}
