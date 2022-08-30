<?php

declare(strict_types=1);

namespace Porthorian\DBEntity\Tests\Suite;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Porthorian\EntityOrm\EntityException;
use Porthorian\DBEntity\Tests\TestEntity;
use Porthorian\DBEntity\Tests\TestFailedEntity;
use Porthorian\DBEntity\Tests\TestModel;
use Porthorian\PDOWrapper\DBWrapper;
use Porthorian\PDOWrapper\DBPool;
use Porthorian\PDOWrapper\Models\DatabaseModel;

class DBEntityTest extends TestCase
{
	public static function setUpBeforeClass() : void
	{
		$model = new DatabaseModel('test', getenv('DB_HOST'), 'root', 'test_password');
		$model->setPort((int)getenv('DB_PORT'));
		DBPool::addPool($model);
		DBWrapper::setDefaultDB('test');
	}

	public function setUp() : void
	{
		DBWrapper::factory('DROP TABLE IF EXISTS test_table');
		DBWrapper::factory('CREATE TABLE test_table(
			KEYID INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL
		)');
	}

	public function tearDown() : void
	{
		DBWrapper::factory('DROP TABLE test_table');
	}

	/**
	 * @group store
	 */
	public function testStore()
	{
		$model = new TestModel();
		$model->name = 'Hello world';

		$entity = $model->createEntity();
		$model = $entity->store();

		$this->assertEquals(1, $model->getPrimarykey());

		$model = new TestModel();
		$model->name = 'brave new world';
		$entity = $model->createEntity();
		$entity->setEntityCache(true);

		$model = $entity->store();

		$this->assertEquals(2, $model->getPrimarykey());
	}

	/**
	 * @group store
	 */
	public function testStoreOnFatalSqlQuery()
	{
		$model = new TestModel();
		$model->name = 'skipa a doo scooby doo.';

		$entity = (new TestFailedEntity())->withModel($model);

		$this->expectException(EntityException::class);
		$this->expectExceptionMessageMatches('/Unable to insert the model/');
		$entity->store();
	}

	/**
	 * @depends testStore
	 */
	public function testFind()
	{
		$this->testStore();

		$model = (new TestEntity())->find(1);
		$this->assertTrue($model->isInitialized());
		$this->assertEquals(1, $model->getPrimarykey());
		$this->assertNotEquals(random_int(30, 1000), $model->getPrimarykey());

		$entity = new TestEntity();
		$entity->setEntityCache(true);
		$uncached_model = $entity->find(1);

		$this->assertEquals($uncached_model, $entity->find(1));

		$this->expectException(EntityException::class);
		(new TestFailedEntity())->find(1);
	}

	/**
	 * @group update
	 * @depends testFind
	 */
	public function testUpdate()
	{
		$this->testStore();

		$model = (new TestEntity())->find(1);

		$last_name = $model->name;
		$model->name = 'test_name_change';
		$entity = $model->createEntity();
		$entity->setEntityCache(true);
		$this->assertNull($entity->update(['name']));

		$model = (new TestEntity())->find(1);
		$this->assertNotEquals($last_name, $model->name);
	}

	/**
	 * @group update
	 * @depends testUpdate
	 */
	public function testUpdateOnUninitializedModel()
	{
		$model = new TestModel();

		$entity = $model->createEntity();

		$this->expectException(EntityException::class);
		$this->expectExceptionMessageMatches('/is not initialized./');
		$entity->update(['name']);
	}

	/**
	 * @group update
	 * @depends testUpdate
	 */
	public function testUpdateOnUnknownColumnModel()
	{
		$model = new TestModel();
		$model->setInitializedFlag(true);
		$entity = $model->createEntity();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/unknown_column_name does not exist/');
		$entity->update(['name', 'unknown_column_name']);
	}

	/**
	 * @group update
	 * @depends testUpdate
	 */
	public function testUpdateOnFatalSqlQuery()
	{
		$model = new TestModel();
		$model->KEYID = 1;
		$model->name = 'skipa a doo scooby doo.';
		$model->setInitializedFlag(true);

		$entity = (new TestFailedEntity())->withModel($model);

		$this->expectException(EntityException::class);
		$this->expectExceptionMessageMatches('/Failed to update the model/');
		$entity->update(['name']);
	}

	/**
	 * @group delete
	 * @depends testFind
	 */
	public function testDelete()
	{
		$this->testStore();

		$model = (new TestEntity())->find(1);
		$this->assertTrue($model->isInitialized());

		$entity = $model->createEntity();
		$entity->setEntityCache(true);
		$entity->delete();

		$model = (new TestEntity())->find(1);
		$this->assertFalse($model->isInitialized());

		$entity = (new TestFailedEntity())->withModel($model);

		$this->expectException(EntityException::class);
		$this->expectExceptionMessageMatches('/as it is not initialized/');
		$entity->delete();
	}

	/**
	 * @group delete
	 */
	public function testDeleteOnFatalSqlQuery()
	{
		$model = new TestModel();
		$model->KEYID = 1;
		$model->name = 'failed elete';
		$model->setInitializedFlag(true);

		$entity = (new TestFailedEntity())->withModel($model);

		$this->expectException(EntityException::class);
		$this->expectExceptionMessageMatches('/Failed to delete the model/');
		$entity->delete();
	}
}
