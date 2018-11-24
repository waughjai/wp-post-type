<?php

use PHPUnit\Framework\TestCase;
use WaughJ\WPPostType\WPPostType;

class WPPostTypeTest extends TestCase
{
	public function testObjectWorks()
	{
		$object = new WPPostType();
		$this->assertTrue( is_object( $object ) );
	}
}
