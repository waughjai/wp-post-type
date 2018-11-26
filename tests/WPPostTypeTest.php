<?php

use PHPUnit\Framework\TestCase;
use WaughJ\WPPostType\WPPostType;

require_once( 'MockWordPress.php' );

class WPPostTypeTest extends TestCase
{
	public function testObjectWorks()
	{
		$type = new WPPostType( 'news', 'News' );
		$this->assertTrue( is_object( $type ) );
	}
}
