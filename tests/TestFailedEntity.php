<?php

declare(strict_types=1);

namespace Porthorian\DBEntity\Tests;

class TestFailedEntity extends TestEntity
{
	public function getModelPath() : string
	{
		return '\Porthorian\DBEntity\Tests\TestModel';
	}

	public function getCollectionTable() : string
	{
		return 'unknown_table';
	}

	public function getCollectionPrimaryKey() : string
	{
		return 'KEYID';
	}
}
