<?php

declare(strict_types=1);

namespace Porthorian\DBEntity;

use InvalidArgumentException;
use Porthorian\EntityOrm\Entity;
use Porthorian\EntityOrm\EntityException;
use Porthorian\EntityOrm\Model\ModelInterface;
use Porthorian\PDOWrapper\Exception\DatabaseException;
use Porthorian\PDOWrapper\DBWrapper;
use Porthorian\PDOWrapper\Models\DBResult;
use Porthorian\PDOWrapper\Util\DatabaseLib;

abstract class DBEntity extends Entity
{
	/**
	* Houses the namespace path to the model for the entity
	*/
	abstract public function getModelPath() : string;

	/**
	* The table that we will be manipulating
	*/
	abstract public function getCollectionTable() : string;

	/**
	* Primary key for the database table
	*/
	abstract public function getCollectionPrimaryKey() : string;

	/**
	* The database in where the db_table is located.
	*/
	public function getCollectionName() : string
	{
		return DBWrapper::getDefaultDB();
	}

	/**
	 * Takes all current values inside the model and inserts them into the database
	 * @throws EntityException
	 * @return ModelInterface
	 */
	public function store() : ModelInterface
	{
		$this->initializeModelIfNotSet();

		$model = $this->getModel();

		try
		{
			$last_inserted_id = DBWrapper::insert($this->getCollectionTable(), $model->toArray(), $this->getCollectionName());
		}
		catch (DatabaseException $e)
		{
			throw new EntityException('Unable to insert the model '.get_class($model).' with values '.var_export($model->toArray(), true), $e);
		}

		$model->setPrimaryKey($last_inserted_id);
		$model->setInitializedFlag(true);
		$this->setModel($model);

		if ($this->useEntityCache())
		{
			self::setCacheItem($this->getCacheKey(), $model);
		}

		return $model;
	}

	/**
	 * Update the db entity based on the primary key of the model.
	 * @param $params - These are the fields that will be pulled from the model
	 * Ex ['registration_time', 'date_of_birth']
	 * @throws InvalidArgumentException - If the column does not exist inside the model.
	 * @throws EntityException
	 * @return void
	 */
	public function update(array $params = []) : void
	{
		if (empty($params))
		{
			throw new InvalidArgumentException('params can not be empty to update entity.');
		}

		$this->initializeModelIfNotSet();

		$model = $this->getModel();
		if (!$model->isInitialized())
		{
			throw new EntityException('Unable to update the model as it is not initialized. Model: '.get_class($model));
		}

		$update_values = $model->toArray();
		$update_params = [];
		foreach ($params as $column)
		{
			if (!array_key_exists($column, $update_values))
			{
				throw new InvalidArgumentException('That column: '.$column.' does not exist inside the model '.get_class($model));
			}
			$update_params[$column] = $update_values[$column];
		}

		$where = [$this->getCollectionPrimaryKey() => $model->getPrimaryKey()];
		try
		{
			DBWrapper::update($this->getCollectionTable(), $update_params, $where, $this->getCollectionName());
		}
		catch (DatabaseException $e)
		{
			throw new EntityException('Failed to update the model '.get_class($model).' with where clause: '.var_export($where, true), $e);
		}

		if ($this->useEntityCache())
		{
			self::setCacheItem($this->getCacheKey(), $model);
		}
	}

	/**
	 * Delete the entity based on the primary key from the model.
	 * @throws EntityException
	 * @return void
	 */
	public function delete() : void
	{
		$this->initializeModelIfNotSet();

		$model = $this->getModel();
		if (!$model->isInitialized())
		{
			throw new EntityException('Unable to delete the model as it is not initialized. Model: '.get_class($model));
		}

		$where = [$this->getCollectionPrimaryKey() => $model->getPrimaryKey()];
		try
		{
			DBWrapper::delete($this->getCollectionTable(), $where, $this->getCollectionName());
		}
		catch (DatabaseException $e)
		{
			throw new EntityException('Failed to delete the model '.get_class($model).' with where clause: '.var_export($where, true), $e);
		}

		if ($this->useEntityCache())
		{
			self::deleteCacheItem($this->getCacheKey());
		}

		$this->resetModel();
	}

	/**
	 * @param $pk_value
	 * @throws EntityException
	 * @return ModelInterface
	 */
	public function find(string|int $pk_value) : ModelInterface
	{
		$this->initializeModelIfNotSet();

		if ($this->useEntityCache() && self::hasCacheItem($this->getCacheKey($pk_value)))
		{
			return self::getCacheItem($this->getCacheKey($pk_value));
		}

		$model = $this->getModel();

		try
		{
			$results = DBWrapper::PResult('
				SELECT * FROM '.DatabaseLib::escapeSchemaName($this->getCollectionTable()).'
				WHERE '.DatabaseLib::escapeSchemaName($this->getCollectionPrimaryKey()).' = ?
			', [$pk_value], $this->getCollectionName());
		}
		catch (DatabaseException $e)
		{
			throw new EntityException('Failed to find a valid entity for model: '.get_class($model), $e);
		}

		$this->setModelProperties($results);

		if ($this->useEntityCache())
		{
			self::setCacheItem($this->getCacheKey(), $model);
		}

		return $model;
	}

	protected function setModelProperties(DBResult $result) : void
	{
		$this->resetModel();
		$model = $this->getModel();
		$model->setModelProperties($result->getRecord());

		$count = $result->count();
		if ($count === 1)
		{
			$model->setInitializedFlag(true);
		}
		else if ($count > 1)
		{
			throw new EntityException('There appears to be more than 1 record based off the model: '.get_class($model).' on entity: '.get_class($this));
		}

		$this->setModel($model);
	}

	private function initializeModelIfNotSet() : void
	{
		if (isset($this->model))
		{
			return;
		}

		$path = $this->getModelPath();
		$this->setModel(new $path);
	}
}
