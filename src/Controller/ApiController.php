<?php

namespace App\Controller;

use App\Entity\CustomCommand;
use App\Entity\Symfony;
use App\Utils\Services\SymfoniesService;
use App\Utils\Services\UtilService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController extends Controller{
	
	/**
	 * @Route("/get-symfonies")
	 * @Route("/get-symfonies/{symfony}")
	 *
	 * @param Symfony|null $symfony
	 *
	 * @return JsonResponse
	 */
	public function getSymfonies(Symfony $symfony = null){
		//		$em = $this->getDoctrine()->getManager();
		//		foreach($this->getDoctrine()->getRepository(CustomCommand::class)->findAll() as $item){
		//			$em->remove($item);
		//		}
		//		$em->flush();
		//		die;
		
		$symfoniesService = $this->get(SymfoniesService::class);
		
		if($symfony === null){
			$symfonies = $this->getDoctrine()->getRepository(Symfony::class)->getAll();
		}
		else{
			$symfonies = [$symfony];
		}
		
		foreach($symfonies as $k => $symfony){
			$symfonies[$k] = $symfoniesService->toArray($symfony);
		}
		
		return new JsonResponse($symfonies);
	}
	
	/**
	 * @Route("/scan")
	 * @Route("/scan/{dirKey}")
	 *
	 * @param null|int        $dirKey
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function scan($dirKey = null, LoggerInterface $logger){
		try{
			$dir = null;
			if($dirKey !== null){
				$dir = $this->get(UtilService::class)->getConfig('directoriesToScan')[(int)$dirKey];
			}
			
			$this->get(SymfoniesService::class)->scan($dir);
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/prune")
	 * @Route("/prune/{dirKey}")
	 *
	 * @param null|int        $dirKey
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function prune($dirKey = null, LoggerInterface $logger){
		try{
			$dir = null;
			if($dirKey !== null){
				$dir = $this->get(UtilService::class)->getConfig('directoriesToScan')[(int)$dirKey];
			}
			
			$this->get(SymfoniesService::class)->prune($dir);
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/get-system-info")
	 *
	 * @return JsonResponse
	 */
	public function getSystemInfo(){
		$utilService = $this->get(UtilService::class);
		
		$config = $utilService->getConfig();
		
		$config['configured'] = !empty($config);
		$config['userRunning'] = $utilService->getUserRunning();
		$config['configPath'] = $utilService->getConfigPath();
		
		return new JsonResponse($config);
	}
	
	/**
	 * @Route("/get-php-executables")
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function getPhpExecutables(LoggerInterface $logger){
		try{
			return new JsonResponse($this->get(UtilService::class)->getPhpExecutables());
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/set-php-executables/{symfony}")
	 *
	 * @param Symfony         $symfony
	 * @param Request         $request
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function setPhpExecutables(Symfony $symfony, Request $request, LoggerInterface $logger){
		try{
			$data = json_decode($request->getContent(), true);
			
			$symfony->setPhpExecutable($data['phpExecutable']);
			
			//			$em = $this->getDoctrine()->getManager();
			//			$em->persist($symfony);
			//			$em->flush();
			
			$this->get(SymfoniesService::class)->recheckSymfony($symfony);
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/set-directories-to-scan")
	 *
	 * @param Request         $request
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function setDirectoriesToScan(Request $request, LoggerInterface $logger){
		try{
			$data = json_decode($request->getContent(), true);
			
			$configPath = $this->get(UtilService::class)->getConfigPath();
			
			$content = file_get_contents($configPath);
			$content = json_decode($content, true);
			
			$content['directoriesToScan'] = $data['directoriesToScan'];
			$content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			
			$fileSystem = new Filesystem();
			$fileSystem->dumpFile($configPath, $content);
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/recheck/{symfony}")
	 *
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function recheck(Symfony $symfony, LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->recheckSymfony($symfony);
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/switch-starred/{symfony}")
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function switchStarred(Symfony $symfony, LoggerInterface $logger){
		try{
			$starred = !$symfony->getStarred();
			$symfony->setStarred($starred);
			
			$symfony->setPhpExecutable($this->get(SymfoniesService::class)->getPhpExecutable($symfony));
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($symfony);
			$em->flush();
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/start-all")
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function startAll(LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->startAll();
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/stop-all")
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function stopAll(LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->stopAll();
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/start/{symfony}", methods={"POST"})
	 * @param Symfony         $symfony
	 * @param Request         $request
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function start(Symfony $symfony, Request $request, LoggerInterface $logger){
		try{
			$ip = $symfony->getIp();
			$port = $symfony->getPort();
			
			$data = json_decode($request->getContent(), true);
			
			$ip = !empty($data['ip']) ? $data['ip'] : $ip;
			$port = !empty($data['port']) ? $data['port'] : $port;
			
			if(!empty($data['entry'])){
				$entry = $data['entry'];
				
				// generate a json for each entry inserted
				$entry = explode(PHP_EOL, $entry);
				foreach($entry as $k => $v){
					$entry[$k] = trim($v);
				}
				$entry = json_encode($entry);
			}
			else{
				$entry = $symfony->getEntryPoint();
			}
			
			if(!empty($data['nipIo'])){
				$nipIo = $data['nipIo'];
				
				// generate a json for each entry inserted
				$nipIo = explode(PHP_EOL, $nipIo);
				foreach($nipIo as $k => $v){
					$nipIo[$k] = trim($v);
				}
				$nipIo = json_encode($nipIo);
			}
			else{
				$nipIo = $symfony->getNipIo();
			}
			
			$this->get(SymfoniesService::class)->startAndSave($symfony, $ip, $port, $entry, $nipIo);
			
			$symfonyArray = $this->get(SymfoniesService::class)->toArray($symfony);
			
			return new JsonResponse($symfonyArray);
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/edit/{symfony}", methods={"POST"})
	 * @param Symfony         $symfony
	 * @param Request         $request
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function edit(Symfony $symfony, Request $request, LoggerInterface $logger){
		try{
			$data = json_decode($request->getContent(), true);
			
			$entry = trim($data['entry']);
			$nipIo = trim($data['nipIo']);
			
			// generate a json for each entry inserted
			if($entry){
				$entry = explode(PHP_EOL, $entry);
				foreach($entry as $k => $v){
					$entry[$k] = trim($v);
				}
				$entry = json_encode($entry);
			}
			else{
				$entry = null;
			}
			
			// generate a json for each nip.io domain inserted
			if($nipIo){
				$nipIo = explode(PHP_EOL, $nipIo);
				foreach($nipIo as $k => $v){
					$nipIo[$k] = trim($v);
				}
				$nipIo = json_encode($nipIo);
			}
			else{
				$nipIo = null;
			}
			
			$symfony->setEntryPoint($entry);
			$symfony->setNipIo($nipIo);
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($symfony);
			$em->flush();
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/restart/{symfony}")
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function restart(Symfony $symfony, LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->restart($symfony);
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/stop/{symfony}")
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function stop(Symfony $symfony, LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->stop($symfony);
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/cache-assets-reset/{symfony}")
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function cacheAssetsReset(Symfony $symfony, LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->cacheAssetsReset($symfony);
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/delete-info/{symfony}")
	 *
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function deleteInfo(Symfony $symfony, LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->deleteSymfonyInfo($symfony);
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/remove/{symfony}")
	 *
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function remove(Symfony $symfony, LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->delete($symfony);
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/delete-all")
	 *
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function deleteAll(LoggerInterface $logger){
		try{
			$this->get(SymfoniesService::class)->prune();
			$this->get(SymfoniesService::class)->deleteAll();
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/composer/{activity}/{symfony}")
	 *
	 * @param string          $activity
	 * @param Symfony         $symfony
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function composerActivity($activity, Symfony $symfony, LoggerInterface $logger){
		try{
			if($activity !== 'show'){
				$this->get(SymfoniesService::class)->composer($symfony, $activity);
				
				return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
			}
			else{
				$response = $this->get(SymfoniesService::class)->composerShow($symfony);
				
				return new JsonResponse($response);
			}
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/git-pull-symfony/{symfony}/{gitBranch}")
	 *
	 * @param Symfony         $symfony
	 * @param string          $gitBranch
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function gitPullSymfony(Symfony $symfony, $gitBranch, LoggerInterface $logger){
		try{
			$symfoniesService = $this->get(SymfoniesService::class);
			
			$symfony = $symfoniesService->gitPullSymfony($symfony, $gitBranch);
			
			return new JsonResponse($symfoniesService->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/new-custom-command")
	 * @Route("/edit-custom-command/{customCommand}")
	 *
	 * @param CustomCommand|null $customCommand
	 * @param Request            $request
	 * @param LoggerInterface    $logger
	 *
	 * @return JsonResponse
	 */
	public function saveCustomCommand(CustomCommand $customCommand = null, Request $request, LoggerInterface $logger){
		try{
			$data = json_decode($request->getContent(), true);
			
			$label = trim($data['label']);
			$weightOnPreStart = trim($data['weightOnPreStart']);
			$weightOnPostStop = trim($data['weightOnPostStop']);
			$weightOnGitPull = trim($data['weightOnGitPull']);
			$weightOnComposerInstall = trim($data['weightOnComposerInstall']);
			$weightOnCacheAssetsReset = trim($data['weightOnCacheAssetsReset']);
			$command = trim($data['command']);
			$symfonyId = (int)$data['symfonyId'];
			$onPreStart = (int)$data['onPreStart'] === 1;
			$onPostStop = (int)$data['onPostStop'] === 1;
			$onGitPull = (int)$data['onGitPull'] === 1;
			$onComposerInstall = (int)$data['onComposerInstall'] === 1;
			$onCacheAssetsReset = (int)$data['onCacheAssetsReset'] === 1;
			
			if(!$customCommand){
				$symfony = $this->getDoctrine()->getRepository(Symfony::class)->find($symfonyId);
				$customCommand = new CustomCommand();
			}
			else{
				$symfony = $customCommand->getSymfony();
			}
			
			$customCommand->setLabel($label);
			$customCommand->setWeightOnPreStart($weightOnPreStart);
			$customCommand->setWeightOnPostStop($weightOnPostStop);
			$customCommand->setWeightOnGitPull($weightOnGitPull);
			$customCommand->setWeightOnComposerInstall($weightOnComposerInstall);
			$customCommand->setWeightOnCacheAssetsReset($weightOnCacheAssetsReset);
			$customCommand->setCommand($command);
			$customCommand->setOnPreStart($onPreStart);
			$customCommand->setOnPostStop($onPostStop);
			$customCommand->setOnGitPull($onGitPull);
			$customCommand->setOnComposerInstall($onComposerInstall);
			$customCommand->setOnCacheAssetsReset($onCacheAssetsReset);
			$customCommand->setSymfony($symfony);
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($customCommand);
			$em->flush();
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/delete-custom-command/{customCommand}")
	 *
	 * @param CustomCommand   $customCommand
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function deleteCustomCommand(CustomCommand $customCommand, LoggerInterface $logger){
		try{
			$symfony = $customCommand->getSymfony();
			
			$em = $this->getDoctrine()->getManager();
			$em->remove($customCommand);
			$em->flush();
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/change-weight-custom-command/{customCommand}")
	 *
	 * @param CustomCommand   $customCommand
	 * @param Request         $request
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function changeWeightCustomCommand(CustomCommand $customCommand, Request $request, LoggerInterface $logger){
		try{
			$symfony = $customCommand->getSymfony();
			
			$data = json_decode($request->getContent(), true);
			
			$weightProperty = trim($data['weightProperty']);
			$newWeight = (int)trim($data['newWeight']);
			
			$customCommand->{$weightProperty} = $newWeight;
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($customCommand);
			$em->flush();
			
			return new JsonResponse($this->get(SymfoniesService::class)->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/run-custom-command/{customCommand}")
	 *
	 * @param CustomCommand   $customCommand
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function runCustomCommand(CustomCommand $customCommand, LoggerInterface $logger){
		try{
			$this->get(UtilService::class)->processRun(true, true, $customCommand->getCommand(), $customCommand->getSymfony()->getPath());
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/add-symfony-from-path")
	 *
	 * @param Request         $request
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function addSymfonyFromPath(Request $request, LoggerInterface $logger){
		try{
			$symfoniesService = $this->get(SymfoniesService::class);
			
			$data = json_decode($request->getContent(), true);
			$path = trim($data['path']);
			$phpExecutable = trim($data['phpExecutable']);
			
			$symfony = $symfoniesService->newSymfonyFromPath($path, $phpExecutable);
			
			return new JsonResponse($symfoniesService->toArray($symfony));
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
	/**
	 * @Route("/set-config-value", methods={"POST"})
	 * @Route("/set-config-value/bool", methods={"POST"}, name="set_config_value_bool")
	 *
	 * @param Request         $request
	 * @param LoggerInterface $logger
	 *
	 * @return JsonResponse
	 */
	public function setConfigValueAction(Request $request, LoggerInterface $logger){
		try{
			$data = json_decode($request->getContent(), true);
			$field = trim($data['field']);
			$value = trim($data['value']);
			
			if($request->get('_route') === 'set_config_value_bool'){
				$value = $value == 1;
			}
			
			$configuration = $this->get(UtilService::class)->getConfig();
			
			$configuration[$field] = $value;
			
			$newConfig = json_encode($configuration, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			$configPath = $this->get(UtilService::class)->getConfigPath();
			
			$fileSystem = new Filesystem();
			$fileSystem->dumpFile($configPath, $newConfig);
			
			return new JsonResponse();
		}
		catch(\Exception $exc){
			$logger->error($exc);
			
			return new JsonResponse($exc->getMessage(), 500);
		}
	}
	
}
