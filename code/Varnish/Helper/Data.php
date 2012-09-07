<?php

class Magneto_Varnish_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Check if varnish is enabled in Cache management.
     * 
     * @return boolean  True if varnish is enable din Cache management. 
     */
    public function useVarnishCache(){
        return Mage::app()->useCache('varnish');
    }

    /**
     * Return varnish servers from configuration
     * 
     * @return array 
     */
    public function getVarnishServers()
    {
        $serverConfig = Mage::getStoreConfig('varnish/options/servers');
        $varnishServers = array();
        
        foreach (explode(',', $serverConfig) as $value ) {
            $varnishServers[] = trim($value);
        }

        return $varnishServers;
    }

    /**
     * Purges all cache on all Varnish servers.
     * 
     * @return array errors if any
     */
    public function purgeAll()
    {
        return $this->purge(array('/.*'));
    }

    /**
     * Purge an array of urls on all varnish servers.
     * 
     * @param array $urls
     * @return array with all errors 
     */
    public function purge(array $urls)
    {
        $varnishServers = $this->getVarnishServers();
        $errors = array();

        // Init curl handler
        $curlHandlers = array(); // keep references for clean up
        $mh = curl_multi_init();
        
        foreach ((array)$varnishServers as $varnishServer) {
            foreach ($urls as $url) {
                $varnishUrl = "http://" . $varnishServer . $url;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $varnishUrl);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                curl_multi_add_handle($mh, $ch);
                $curlHandlers[] = $ch;
            }
        }

        do {
            $n = curl_multi_exec($mh, $active);
        } while ($active);
        
        // Error handling and clean up
        foreach ($curlHandlers as $ch) {
            $info = curl_getinfo($ch);
            
            if (curl_errno($ch)) {
                $errors[] = "Cannot purge url {$info['url']} due to error" . curl_error($ch);
            } else if ($info['http_code'] != 200 && $info['http_code'] != 404) {
                $errors[] = "Cannot purge url {$info['url']}, http code: {$info['http_code']}. curl error: " . curl_error($ch);
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

		$this->logAdminAction(
		    empty($errors),
			implode(', ', $urls),
			null,
			$errors
	    );
        
        return $errors;
    }

	/**
	 * Log admin action
	 *
	 * @param bool $success
	 * @param null $generalInfo
	 * @param null $additionalInfo
	 * @return mixed
	 */
	protected function logAdminAction($success=true, $generalInfo=null, $additionalInfo=null, $errors=array()) {
		$eventCode = 'varnish_purge'; // this needs to match the code in logging.xml

		if (!Mage::getSingleton('enterprise_logging/config')->isActive($eventCode, true)) {
			return;
		}

		$username = null;
		$userId   = null;
		if (Mage::getSingleton('admin/session')->isLoggedIn()) {
			$userId = Mage::getSingleton('admin/session')->getUser()->getId();
			$username = Mage::getSingleton('admin/session')->getUser()->getUsername();
		}

		$request = Mage::app()->getRequest();
		return Mage::getSingleton('enterprise_logging/event')->setData(array(
			'ip'         => Mage::helper('core/http')->getRemoteAddr(),
			'x_forwarded_ip'=> Mage::app()->getRequest()->getServer('HTTP_X_FORWARDED_FOR'),
			'user'       => $username,
			'user_id'    => $userId,
			'is_success' => $success,
			'fullaction' => "{$request->getRouteName()}_{$request->getControllerName()}_{$request->getActionName()}",
			'event_code' => $eventCode,
			'action'     => 'purge',
			'info'       => $generalInfo,
			'additional_info' => $additionalInfo,
			'error_message' => implode("\n", $errors),
		))->save();
	}

}
