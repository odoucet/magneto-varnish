<?php
require_once('Mage/Adminhtml/controllers/CacheController.php');

class Magneto_Varnish_CacheController extends Mage_Adminhtml_CacheController {

    /**
     * Overwrites Mage_Adminhtml_CacheController massRefreshAction
     */
    public function massRefreshAction(){
        // Handle varnish type
        $types = $this->getRequest()->getParam('types');

        if (Mage::app()->useCache('varnish') ) {
            if( (is_array($types) && in_array('varnish', $types)) || $types="varnish") {
				$varnishHelper = Mage::helper('varnish'); /* @var $varnishHelper Magneto_Varnish_Helper_Data */
                $errors = $varnishHelper->purgeAll();
                if (count($errors) > 0) {
					$this->_getSession()->addError(Mage::helper('adminhtml')->__("Error while purging Varnish cache:<br />" . implode('<br />', $errors)));
                } else {
                	$this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("Varnish cache purged: '/.*' "));
                }
            }
        }

        // Allow parrent handle core cache types
        parent::massRefreshAction();
    }

	/**
	 * Purge urls
	 *
	 * @return void
	 */
	public function purgeUrlsAction() {

		$urls = $this->getRequest()->getParam('purge_urls');
		$cleanedUrls = array();
		foreach (explode("\n", $urls) as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$cleanedUrls[] = $url;
			}
		}
		if (count($cleanedUrls) > 0) {
			$varnishHelper = Mage::helper('varnish'); /* @var $varnishHelper Magneto_Varnish_Helper_Data */
			$varnishHelper->purge($cleanedUrls);
			$this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("Purged Varnish Urls: ") . '<pre>' . implode("\n", $cleanedUrls).'</pre>');
		}
		$this->_redirect('*/*/index');
	}
}
