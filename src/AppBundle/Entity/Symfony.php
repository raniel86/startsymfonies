<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Symfony
 *
 * @ORM\Table(name="symfony")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SymfonyRepository")
 */
class Symfony{
	
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="path", type="text", unique=true)
	 */
	private $path;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="version", type="string", length=255)
	 */
	private $version;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="ip", type="string", length=255, nullable=true)
	 */
	private $ip;
	
	/**
	 * @var int
	 *
	 * @ORM\Column(name="port", type="integer", nullable=true)
	 */
	private $port;
	
	/**
	 * @var bool
	 *
	 * @ORM\Column(name="starred", type="boolean")
	 */
	private $starred;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="entry_point", type="string", length=255, nullable=true)
	 */
	private $entryPoint;
	
	/**
	 * Symfony constructor.
	 */
	public function __construct(){
		$this->setStarred(false);
	}
	
	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}
	
	/**
	 * Set path
	 *
	 * @param string $path
	 *
	 * @return Symfony
	 */
	public function setPath($path){
		$this->path = rtrim($path, '/');
		
		return $this;
	}
	
	/**
	 * Get path
	 *
	 * @return string
	 */
	public function getPath(){
		return rtrim($this->path, '/');
	}
	
	/**
	 * Set ip
	 *
	 * @param string $ip
	 *
	 * @return Symfony
	 */
	public function setIp($ip){
		$this->ip = $ip;
		
		return $this;
	}
	
	/**
	 * Get ip
	 *
	 * @return string
	 */
	public function getIp(){
		return $this->ip;
	}
	
	/**
	 * Set port
	 *
	 * @param integer $port
	 *
	 * @return Symfony
	 */
	public function setPort($port){
		$this->port = $port;
		
		return $this;
	}
	
	/**
	 * Get port
	 *
	 * @return int
	 */
	public function getPort(){
		return $this->port;
	}
	
	/**
	 * Set starred
	 *
	 * @param boolean $starred
	 *
	 * @return Symfony
	 */
	public function setStarred($starred){
		$this->starred = $starred;
		
		return $this;
	}
	
	/**
	 * Get starred
	 *
	 * @return boolean
	 */
	public function getStarred(){
		return $this->starred;
	}
	
	/**
	 * Set entryPoint
	 *
	 * @param string $entryPoint
	 *
	 * @return Symfony
	 */
	public function setEntryPoint($entryPoint){
		$this->entryPoint = $entryPoint ? $entryPoint : null;
		
		return $this;
	}
	
	/**
	 * Get entryPoint
	 *
	 * @return string
	 */
	public function getEntryPoint(){
		return $this->entryPoint;
	}
	
	/**
	 * Set version
	 *
	 * @param string $version
	 *
	 * @return Symfony
	 */
	public function setVersion($version){
		$this->version = $version;
		
		return $this;
	}
	
	/**
	 * Get version
	 *
	 * @return string
	 */
	public function getVersion($returnMain = false){
		return !$returnMain ? $this->version : (int)$this->version[0];
	}
}