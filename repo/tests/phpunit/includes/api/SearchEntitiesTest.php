<?php

namespace Wikibase\Test\Api;

/**
 * @covers Wikibase\Api\SearchEntities
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SearchEntitiesTest extends WikibaseApiTestCase {

	private static $hasSetup;

	public function setUp() {
		parent::setUp();

		if( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( array( 'Berlin', 'London', 'Oslo', 'Episkopi', 'Leipzig', 'Guangzhou' ) );
		}
		self::$hasSetup = true;
	}

	public function provideData() {
		$testCases = array();

		//Search via full Labels
		$testCases[] = array( array( 'search' => 'berlin', 'language' => 'en' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => 'LoNdOn', 'language' => 'en' ), array( 'handle' => 'London' ) );
		$testCases[] = array( array( 'search' => 'OSLO', 'language' => 'en' ), array( 'handle' => 'Oslo' ) );
		$testCases[] = array( array( 'search' => '广州市', 'language' => 'zh-cn' ), array( 'handle' => 'Guangzhou' ) );

		//Search via partial Labels
		$testCases[] = array( array( 'search' => 'BER', 'language' => 'nn' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => 'Episkop', 'language' => 'de' ), array( 'handle' => 'Episkopi' ) );
		$testCases[] = array( array( 'search' => 'L', 'language' => 'de' ), array( 'handle' => 'Leipzig' ) );

		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSearchEntities( $params, $expected ) {
		$params['action'] = 'wbsearchentities';

		list( $result,, ) = $this->doApiRequest( $params );

		$this->assertResultLooksGood( $result );
		$this->assertApiResultHasExpected( $result['search'], $params, $expected );
	}

	private function assertResultLooksGood( $result ) {
		$this->assertResultSuccess( $result );
		$this->assertArrayHasKey( 'searchinfo', $result );
		$this->assertArrayHasKey( 'search', $result['searchinfo'] );
		$this->assertArrayHasKey( 'search', $result );

		foreach( $result['search'] as $key => $searchresult ) {
			$this->assertInternalType( 'integer', $key );
			$this->assertArrayHasKey( 'id', $searchresult );
			$this->assertArrayHasKey( 'url', $searchresult );
		}

	}

	private function assertApiResultHasExpected( $searchResults, $params, $expected ) {
		$foundResult = 0;

		$expectedId = EntityTestHelper::getId( $expected['handle'] );
		$expectedContent = EntityTestHelper::getEntityData( $expected['handle'] );

		foreach( $searchResults as $searchResult ) {
			$assertFound = $this->assertSearchResultHasExpected( $searchResult, $params, $expectedId, $expectedContent );
			$foundResult = $foundResult + $assertFound;
		}
		$this->assertEquals( 1, $foundResult, 'Could not find expected search result in array of results' );
	}

	private function assertSearchResultHasExpected( $searchResult, $params, $expectedId, $expectedContent  ){
		if( $expectedId === $searchResult['id'] ) {
			$this->assertEquals( $expectedId, $searchResult['id'] );
			$this->assertStringEndsWith( $expectedId, $searchResult['url'] );
			if( array_key_exists( 'descriptions', $expectedContent ) ) {
				$this->assertSearchResultHasExpectedDescription( $searchResult, $params, $expectedContent );
			}
			if( array_key_exists( 'labels', $expectedContent ) ) {
				$this->assertSearchResultHasExpectedLabel( $searchResult, $params, $expectedContent );
			}
			return 1;
		}
		return 0;
	}

	private function assertSearchResultHasExpectedDescription( $searchResult, $params, $expectedContent ) {
		foreach( $expectedContent['descriptions'] as $description ) {
		if( $description['language'] == $params['language'] ) {
			$this->assertEquals( $description['value'], $searchResult['description'] );
		}
	}
	}

	private function assertSearchResultHasExpectedLabel( $searchResult, $params, $expectedContent ) {
		foreach( $expectedContent['labels'] as $description ) {
			if( $description['language'] == $params['language'] ) {
				$this->assertEquals( $description['value'], $searchResult['label'] );
			}
		}
	}
}