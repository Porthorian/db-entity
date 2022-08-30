<?php

declare(strict_types=1);

namespace Porthorian\DBEntity\Tests;

use Porthorian\DBEntity\DBEntity;

class TestEntity extends DBEntity
{
	public function getModelPath() : string
	{
		return '\Porthorian\DBEntity\Tests\TestModel';
	}

	public function getCollectionTable() : string
	{
		return 'test_table';
	}

	public function getCollectionPrimaryKey() : string
	{
		return 'KEYID';
	}
}
