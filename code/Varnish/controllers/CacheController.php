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
                $errors = Mage::helper('varnish')->purgeAll();
                if (count($errors) > 0) {
					$this->_getSession()->addError(Mage::helper('adminhtml')->__("Error while purging Varnish cache:<br />" . implode('<br />', $errors)));
                } else {
                	$this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("Varnish cache type purged." . var_export($errors, 1)));
                }
            }
        }

        // Allow parrent handle core cache types
        parent::massRefreshAction();
    }
}
