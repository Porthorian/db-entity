<?php

declare(strict_types=1);

namespace Porthorian\DBEntity\Tests;

use Porthorian\EntityOrm\Model\Model;

class TestModel extends Model
{
	public int $KEYID = 0;
	public string $name = 'hello_world';

	public function setPrimaryKey(string|int $pk_value) : void
	{
		$this->KEYID = $pk_value;
	}

	public function getPrimaryKey() : string|int
	{
		return $this->KEYID;
	}

	public function getEntityPath() : string
	{
		return '\Porthorian\DBEntity\Tests\TestEntity';
	}

	public function toArray() : array
	{
		return [
			'KEYID' => $this->KEYID,
			'name' => $this->name
		];
	}

	public function toPublicArray() : array
	{
		return [
			'KEYID' => $this->KEYID,
			'name' => $this->name
		];
	}
}
