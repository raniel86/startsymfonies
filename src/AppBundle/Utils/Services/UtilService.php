<?php
/**
 * Created by PhpStorm.
 * User: dsabre
 * Date: 28/09/17
 * Time: 10.01
 */

namespace AppBundle\Utils\Services;

use AppBundle\Entity\Symfony;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class UtilService{
	
	use ContainerAwareTrait;
	
	const URL_VERSION = 'https://raw.githubusercontent.com/raniel86/startsymfonies2/master/.version_number';
	const COOKIE_VERSION = 'remote_version';
	const URL_GITHUB = 'https://github.com/raniel86/startsymfonies2';
	
	/**
	 * UtilService constructor.
	 */
	public function __construct(ContainerInterface $container){
		$this->setContainer($container);
	}
	
	/**
	 * Return the local version number of the app
	 *
	 * @return string
	 */
	public function getLocalVersionNumber(){
		$version = file_get_contents($this->container->get('kernel')->getRootDir() . '/../.version_number');
		return trim($version);
	}
	
	/**
	 * Return the current version number of the app
	 *
	 * @param Response $response
	 *
	 * @return string
	 */
	public function getCurrentVersionNumber(Response $response){
		// get current version from cookie
		$curVersion = $this->container->get('request_stack')->getMasterRequest()->cookies->get(self::COOKIE_VERSION);
		
		if(!$curVersion){
			// get current version from remote github url
			$curVersion = file_get_contents(self::URL_VERSION . '?' . uniqid());
			$response->headers->setCookie(new Cookie(self::COOKIE_VERSION, $curVersion));
		}
		
		return trim($curVersion);
	}
	
	/**
	 * @return string
	 */
	public function getUrlGitHub(){
		return self::URL_GITHUB;
	}
	
	/**
	 * Return all hosts in hosts configuration
	 *
	 * @return array|bool|string
	 */
	public function getHosts(){
		$file = $this->container->getParameter('hosts_file');
		
		$hosts = file_get_contents($file);
		$hosts = explode(PHP_EOL, $hosts);
		$hosts = array_diff($hosts, ['']);
		
		foreach($hosts as $k => $v){
			$v = trim($v);
			
			if(preg_match('/^#/', $v)){
				unset($hosts[$k]);
				continue;
			}
			
			$hosts[$k] = $v;
		}
		
		return $hosts;
	}
	
}