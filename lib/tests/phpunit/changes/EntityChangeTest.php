<?php

namespace Wikibase\Test;
use Wikibase\EntityChange;
use Wikibase\Entity;

/**
 * Tests for the Wikibase\EntityChange class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.3
*
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityChangeTest extends DiffChangeTest {

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		// don't include entity data, it's skipped during serialization!
		// $this->allowedInfoKeys[] = 'entity';

		$this->allowedChangeKeys = array( // see TestChanges::getChanges()
			'property-creation',
			'property-deletion',
			'property-set-label',
			'item-creation',
			'item-deletion',
			'set-dewiki-sitelink',
			'set-enwiki-sitelink',
			'change-dewiki-sitelink',
			'change-enwiki-sitelink',
			'remove-dewiki-sitelink',
			'set-de-label',
			'set-en-label',
			'set-en-aliases',
			'add-claim',
			'remove-claim',
			'item-deletion-linked',
			'remove-enwiki-sitelink',
		);
	}

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.4
	 * @return string
	 */
	protected function getRowClass() {
		return 'Wikibase\EntityChange';
	}

	public function entityProvider() {
		return array_map(
			function( Entity $entity ) {
				return array( $entity );
			},
			TestChanges::getEntities()
		);
	}

	/**
	 * @dataProvider entityProvider
	 *
	 * @param \Wikibase\Entity $entity
	 */
	public function testNewFromUpdate( Entity $entity ) {
		/* @var EntityChange $entityChange */
		$class = $this->getRowClass();
		$entityChange = $class::newFromUpdate( EntityChange::UPDATE, null, $entity );
		$this->assertInstanceOf( $class, $entityChange );

		$this->assertEquals( $entity->isEmpty(), $entityChange->getDiff()->isEmpty(),
			"The diff must be empty if and only if the entity was empty" );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\EntityChange $entityChange
	 */
	public function testGetType( EntityChange $entityChange ) {
		$this->assertInternalType( 'string', $entityChange->getType() );
	}

	/**
	 * @dataProvider entityProvider
	 *
	 * @param \Wikibase\Entity $entity
	 */
	public function testSetAndGetEntity( Entity $entity ) {
		$class = $this->getRowClass();
        $entityChange = $class::newFromUpdate( EntityChange::UPDATE, null, $entity );
		$entityChange->setEntity( $entity );
		$this->assertEquals( $entity, $entityChange->getEntity() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @since 0.3
	 */
	public function testMetadata( EntityChange $entityChange ) {
		$entityChange->setMetadata( array(
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		) );
		$this->assertEquals(
			array(
				'rev_id' => 23,
				'user_text' => '171.80.182.208',
				'comment' => $entityChange->getComment(),
			),
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 * @since 0.3
	 */
	public function testGetEmptyMetadata( EntityChange $entityChange ) {
		$entityChange->setField( 'info', array() );
		$this->assertEquals(
			false,
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 * @since 0.4
	 */
	public function testToString( EntityChange $entityChange ) {
		$s = "$entityChange"; // magically calls __toString()

		$id = $entityChange->getEntityId()->getPrefixedId();
		$type = $entityChange->getType();

		$this->assertTrue( strpos( $s, $id ) !== false, "contains entity ID" );
		$this->assertTrue( strpos( $s, $type ) !== false, "contains type" );
	}
}
