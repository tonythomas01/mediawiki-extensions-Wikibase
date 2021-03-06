<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ChangeOp\ChangeOps
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpsTest extends \PHPUnit_Framework_TestCase {

	public function testEmptyChangeOps() {
		$changeOps = new ChangeOps();
		$this->assertEmpty( $changeOps->getChangeOps() );
	}

	/**
	 * @return ChangeOp[]
	 */
	public function changeOpProvider() {
		$ops = array();
		$ops[] = array ( new ChangeOpLabel( 'en', 'myNewLabel' ) );
		$ops[] = array ( new ChangeOpDescription( 'de', 'myNewDescription' ) );
		$ops[] = array ( new ChangeOpLabel( 'en', null ) );

		return $ops;
	}

	/**
	 * @dataProvider changeOpProvider
	 *
	 * @param ChangeOp $changeOp
	 */
	public function testAdd( $changeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$this->assertEquals( array( $changeOp ), $changeOps->getChangeOps() );
	}

	public function changeOpArrayProvider() {
		$ops = array();
		$ops[] = array (
					array(
						new ChangeOpLabel( 'en', 'enLabel' ),
						new ChangeOpLabel( 'de', 'deLabel' ),
						new ChangeOpDescription( 'en', 'enDescr' ),
					)
				);

		return $ops;
	}

	/**
	 * @dataProvider changeOpArrayProvider
	 *
	 * @param $changeOpArray
	 */
	public function testAddArray( $changeOpArray ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOpArray );
		$this->assertEquals( $changeOpArray, $changeOps->getChangeOps() );
	}

	public function invalidChangeOpProvider() {
		$ops = array();
		$ops[] = array ( 1234 );
		$ops[] = array ( array( new ChangeOpLabel( 'en', 'test' ), 123 ) );

		return $ops;
	}

	/**
	 * @dataProvider invalidChangeOpProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param $invalidChangeOp
	 */
	public function testInvalidAdd( $invalidChangeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $invalidChangeOp );
	}

	public function changeOpsProvider() {
		$args = array();

		$language = 'en';
		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( $language, 'newLabel' ) );
		$changeOps->add( new ChangeOpDescription( $language, 'newDescription' ) );
		$args[] = array( $changeOps, $language, 'newLabel', 'newDescription' );

		return $args;
	}

	/**
	 * @dataProvider changeOpsProvider
	 *
	 * @param ChangeOps $changeOps
	 * @param string $language
	 * @param string $expectedLabel
	 * @param string $expectedDescription
	 */
	public function testApply( $changeOps, $language, $expectedLabel, $expectedDescription ) {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();

		$changeOps->apply( $entity );
		$this->assertEquals( $expectedLabel, $entity->getLabel( $language ) );
		$this->assertEquals( $expectedDescription, $entity->getDescription( $language ) );
	}

	/**
	 * @expectedException  \Wikibase\ChangeOp\ChangeOpException
	 */
	public function testInvalidApply() {
		$item = ItemContent::newEmpty();

		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( 'en', 'newLabel' ) );
		$changeOps->add( new ChangeOpAliases( 'en', array( 'test' ), 'invalidAction' ) );

		$changeOps->apply( $item->getEntity() );
	}

}
