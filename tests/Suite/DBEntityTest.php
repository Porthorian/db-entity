<?php

declare(strict_types=1);

namespace Porthorian\DBEntity\Tests\Suite;

use PHPUnit\Framework\TestCase;
use Porthorian\EntityOrm\EntityException;
use Porthorian\EntityOrm\EntityInterface;
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

	public function testStore()
	{
		$model = new TestModel();
		$model->name = 'Hello world';

		$entity = $model->createEntity();
		$this->assertInstanceOf(EntityInterface::class, $entity);
		$model = $entity->store();

		$this->assertEquals(1, $model->getPrimarykey());

		$model = new TestModel();
		$model->name = 'brave new world';
		$entity = $model->createEntity();
		$entity->setEntityCache(true);

		$model = $entity->store();

		$this->assertEquals(2, $model->getPrimarykey());
	}

	public function testStoreException()
	{
		$model = new TestModel();
		$model->name = 'skipa a doo scooby doo.';

		$entity = (new TestFailedEntity())->withModel($model);

		$this->expectException(EntityException::class);
		$entity->store();
	}
}
