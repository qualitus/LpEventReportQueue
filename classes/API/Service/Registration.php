<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace QU\LERQ\API\Service;

use QU\LERQ\API\DataCaptureRoutinesInterface;
use QU\LERQ\Model\ProviderModel;
use QU\LERQ\Model\RoutinesModel;

/**
 * Class Registration
 * @package QU\LERQ\API\Service
 * @author Ralph Dittrich <dittrich@qualitus.de>
 */
class Registration
{
	const DB_PROVIDER_REG = 'lerq_provider_register';

	/**
	 * @return array
	 */
	public function load()
	{
		return $this->_load();
	}

	/**
	 * @param string $name
	 * @return bool|ProviderModel
	 */
	public function loadByName(string $name)
	{
		$providers = $this->_load();
		/** @var ProviderModel $provider */
		foreach ($providers as $provider) {
			if ($provider->getName() === $name) {
				return $provider;
			}
		}
		return false;
	}

	/**
	 * @param string $namespace
	 * @return bool|ProviderModel
	 */
	public function loadByNamespace(string $namespace)
	{
		$providers = $this->_load();
		/** @var ProviderModel $provider */
		foreach ($providers as $provider) {
			if ($provider->getNamespace() === $namespace) {
				return $provider;
			}
		}
		return false;
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 * @param string $path
	 * @param bool $hasOverrides
	 * @return bool
	 */
	public function create(string $name, string $namespace, string $path, bool $hasOverrides = false)
	{
		if (!$this->loadByNamespace($namespace)) {
			$provider = new ProviderModel();
			$provider->setName($name)
				->setNamespace($namespace)
				->setPath($path)
				->setHasOverrides($hasOverrides);

			$routines = new RoutinesModel();
			if ($hasOverrides) {
				try {
					$routines_path = $path . '/CaptureRoutines/Routines.php'; // @Todo get a better way to find the file!
					require_once $routines_path;
					$class = $namespace . '\Routines';
					$overrideClass = new $class();
					if ($overrideClass instanceof DataCaptureRoutinesInterface) {
						$overrides = $overrideClass->getOverrides();
						// @Todo change setters below to prevent errors if provider does not support newest overrides (isset() ? :)
						$routines->setCollectUserData($overrides['collectUserData'])
							->setCollectUDFData($overrides['collectUDFData'])
							->setCollectMemberData($overrides['collectMemberData'])
							->setCollectLpPeriod($overrides['collectLpPeriod'])
							->setCollectObjectData($overrides['collectObjectData']);
					}
				} catch (\Exception $e) {
					global $DIC;

					$DIC->logger()->root()->error($e->getMessage());
					return false;
				}
			}

			$provider->setActiveOverrides($routines);

			return $this->_save($provider);
		}
		return true;
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 * @param string $path
	 * @param bool|null $hasOverrides
	 * @return bool
	 */
	public function update(string $name, string $namespace, string $path, bool $hasOverrides = null)
	{
		if (($provider = $this->loadByNamespace($namespace)) !== false) {

			if ($provider->getName() === $name) {

				$provider->setPath($path);
				$provider->setHasOverrides((isset($hasOverrides) ? $hasOverrides : $provider->getHasOverrides()));

				return $this->_save($provider, true);
			}
		}
		return false;
	}

	/**
	 * @param string $name
	 * @param string $namespace
	 * @return bool
	 */
	public function remove(string $name, string $namespace)
	{
		if (($provider = $this->loadByNamespace($namespace)) !== false) {

			if ($provider->getName() === $name) {
				return $this->_delete($provider);
			}
		}
		return false;
	}


	/**
	 * @return ProviderModel[]
	 */
	private function _load(): array
	{
		global $DIC;

		$query = 'SELECT * FROM `' . self::DB_PROVIDER_REG . '` ';
		$providers = [];

		$res = $DIC->database()->query($query);
		while ($row = $DIC->database()->fetchAssoc($res)) {
			$provider = new ProviderModel();
			$provider->setName($row['name'])
				->setNamespace($row['namespace'])
				->setPath($row['path'])
				->setHasOverrides(($row['has_overrides'] == true));

			$overrides = json_decode($row['active_overrides'], true);
			$routines = new RoutinesModel();
			$routines->setCollectUserData($overrides['collectUserData'])
				->setCollectUDFData($overrides['collectUDFData'])
				->setCollectMemberData($overrides['collectMemberData'])
				->setCollectLpPeriod($overrides['collectLpPeriod']);

			$provider->setActiveOverrides($routines);
			$providers[$provider->getName()] = $provider;
			$provider = null;
		}

		return $providers;
	}

	/**
	 * @param ProviderModel $provider
	 * @return bool
	 */
	private function _save(ProviderModel $provider, bool $update = false): bool
	{
		global $DIC;

		if ($update) {
			$res = $DIC->database()->update(self::DB_PROVIDER_REG,
				[
					'path' => array('text', $provider->getPath()),
					'has_overrides' => array('integer', $provider->getHasOverrides()),
				],
				[
					'name' => array('text', $provider->getName()),
					'namespace' => array('text', $provider->getNamespace()),
				]);
		} else {
			$res = $DIC->database()->insert(self::DB_PROVIDER_REG,
				array(
					'id' => array('integer', $DIC->database()->nextId(self::DB_PROVIDER_REG)),
					'name' => array('text', $provider->getName()),
					'namespace' => array('text', $provider->getNamespace()),
					'path' => array('text', $provider->getPath()),
					'has_overrides' => array('integer', $provider->getHasOverrides()),
					'active_overrides' => array('text', $provider->getActiveOverrides()),
					'created_at' => array('timestamp', date('Y-m-d H:i:s')),
				)
			);
		}

		return ($res === false);
	}

	/**
	 * @param ProviderModel $provider
	 * @return bool
	 */
	private function _delete(ProviderModel $provider): bool
	{
		global $DIC;

		$query = 'DELETE FROM ' . self::DB_PROVIDER_REG .
			' WHERE namespace = ' . $DIC->database()->quote($provider->getNamespace(), 'text') . ';';

		$res = $DIC->database()->manipulate($query);

		return ($res === false);
	}
}