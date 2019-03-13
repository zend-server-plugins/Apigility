<?php
/*********************************
	Apigility Z-Ray Extension
	Version: 1.03
**********************************/
namespace Apigility;

use Zend\Mvc\MvcEvent;

class Apigility {
    
	private $isApigilityRoleSaved = false;
	private $zre = null;

    public function setZRE($zre) {
        $this->zre = $zre;
    }
	
	public function storeTriggerExit($context, &$storage) {
	    if (isset( $context["functionArgs"][0])) {
    		$mvcEvent = $context["functionArgs"][0];
			
			// to sure that it's ZF3
			if  (is_string($mvcEvent)) {
				// disable extension - in ZF2 the first parma is $name - string
				$this->zre->setEnabled(false);
				return;
			}
			
    		if (class_exists('ZF\MvcAuth\MvcAuthEvent') && is_a($mvcEvent, 'ZF\MvcAuth\MvcAuthEvent') && $mvcEvent->getIdentity()) {
    			//event: authentication, authentication.post authorization authorization.post in Apigility
    			if (! $this->isApigilityRoleSaved &&
    			      method_exists($mvcEvent, 'getIdentity') && 
    			      method_exists($mvcEvent->getIdentity(), 'getRoleId')) {
    			    $storage['identity_role'][] = array('roleId' => $mvcEvent->getIdentity()->getRoleId());
    			    $this->isApigilityRoleSaved = true;
    			}
				
			$authService = $mvcEvent->getAuthenticationService();
			$authServiceAdapter = is_object($authService) ? $authService->getAdapter() : null;
			$authServiceStorage = is_object($authService) ? $authService->getStorage() : null; 
			 
			$authServiceName = is_object($authService) ? get_class($authService) : 'N/A';
			$authServiceAdapterName = is_object($authServiceAdapter) ? get_class($authServiceAdapter) : 'N/A';
			$authServiceStorageName = is_object($authServiceStorage) ? get_class($authServiceStorage) : 'N/A'; 
			 
    			$storage['Mvc_Auth_Event'][] = array(	'eventName' => $context["functionArgs"][0],
        												'AuthenticationService' => $authServiceName . ': Adapter-' . $authServiceAdapterName. '  Storage-' . $authServiceStorageName,
        												'hasAuthenticationResult' => $mvcEvent->hasAuthenticationResult(),
        												'AuthorizationService' => $mvcEvent->getAuthorizationService(),
        												'Identity' =>  $mvcEvent->getIdentity(),
        												'isAuthorized' => $mvcEvent->isAuthorized());
    		}
	    }
	}
}

$apigilityStorage = new Apigility();

$apigilityExtension = new \ZRayExtension("Apigility");

$apigilityExtension->setMetadata(array(
	'logo' => __DIR__ . DIRECTORY_SEPARATOR . 'logo.png',
));
$apigilityExtension->setEnabledAfter('Zend\Mvc\Application::init');
$apigilityStorage->setZRE($apigilityExtension);
$apigilityExtension->traceFunction("Zend\EventManager\EventManager::triggerListeners",  function(){}, array($apigilityStorage, 'storeTriggerExit'));
