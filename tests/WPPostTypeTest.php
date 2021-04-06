<?php

use PHPUnit\Framework\TestCase;
use WaughJ\WPPostType\WPPostType;

require_once( 'MockWordPress.php' );

class WPPostTypeTest extends TestCase
{
	public function testSanitizers()
	{
		$type = new WPPostType( ' Næws Items? ', ' Næws Items? ' );
		$this->assertEquals( 'naews-items', $type->getSlug() );
		$this->assertEquals( 'Næws Items?', $type->getName() );
		$this->assertEquals( 'Næws Items?', $type->getSingularName() );
	}
}
