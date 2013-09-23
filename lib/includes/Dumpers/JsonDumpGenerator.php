<?php

namespace Wikibase\Dumpers;

use MWException;
use Traversable;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityLookup;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\StorageException;

/**
 * JsonDumpGenerator generates an JSON dump of a given set of entities.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class JsonDumpGenerator {

	/**
	 * @var int flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 */
	public $jsonFlags = 0;

	/**
	 * @var resource File handle for output
	 */
	protected $out;

	/**
	 * @var Serializer
	 */
	protected $entitySerializer;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var int
	 */
	protected $shardingFactor = 1;

	/*
	 * @var int
	 */
	protected $shard = 0;

	/*
	 * @var string|null
	 */
	protected $entityType = null;

	/**
	 * @param resource $out
	 * @param \Wikibase\EntityLookup $lookup
	 * @param Serializer $entitySerializer
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $out, EntityLookup $lookup, Serializer $entitySerializer ) {
		if ( !is_resource( $out ) ) {
			throw new \InvalidArgumentException( '$out must be a file handle!' );
		}

		$this->out = $out;
		$this->entitySerializer = $entitySerializer;
		$this->entityLookup = $lookup;
	}

	/**
	 * Set the sharding factor and desired shard.
	 * For instance, to generate four dumps in parallel, use setShardingFilter( 4, 0 )
	 * for the first dump, setShardingFilter( 4, 1 ) for the second dump,
	 * etc.
	 *
	 * @param int $shardingFactor
	 * @param int $shard
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setShardingFilter( $shardingFactor, $shard ) {
		if ( !is_int( $shardingFactor ) || $shardingFactor < 1 ) {
			throw new \InvalidArgumentException( '$shardingFactor must be an integer > 0' );
		}

		if ( !is_int( $shard ) || $shard < 0 ) {
			throw new \InvalidArgumentException( '$shard must be an integer >= 0' );
		}

		if ( $shard >= $shardingFactor ) {
			throw new \InvalidArgumentException( '$shard must be smaller than $shardingFactor' );
		}

		$this->shardingFactor = $shardingFactor;
		$this->shard = $shard;
	}

	/**
	 * Set the entity type to be included in the output.
	 *
	 * @param string|null $type The desired type (use null for any type).
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setEntityTypeFilter( $type ) {
		$this->entityType = $type;
	}

	/**
	 * Generates a JSON dump, writing to the file handle provided to the constructor.
	 *
	 * @param Traversable $idStream an Iterator that returns EntityId instances
	 */
	public function generateDump( Traversable $idStream ) {

		$json = "[\n"; //TODO: make optional
		$this->writeToDump( $json );

		$i = 0;

		/* @var EntityId $id */
		foreach ( $idStream as $id ) {
			if ( !$this->idMatchesFilters( $id ) ) {
				continue;
			}

			try {
				if ( $i++ > 0 ) {
					$this->writeToDump( ",\n" );
				}

				$entity = $this->entityLookup->getEntity( $id );
				$data = $this->entitySerializer->getSerialized( $entity );
				$json = $this->encode( $data );
				$this->writeToDump( $json );
			} catch ( StorageException $ex ) {
				$this->handleStorageException( $ex );
			}

			//TODO: use callback for reporting progress
		}

		$json = "\n]\n"; //TODO: make optional
		$this->writeToDump( $json );
	}

	private function idMatchesFilters( EntityId $id ) {
		return $this->idMatchesShard( $id )
			&& $this->idMatchesType( $id );
	}

	private function idMatchesShard( EntityId $id ) {
		$hash = sha1( $id->getSerialization() );
		$n = (int)hexdec( substr( $hash, 0, 8 ) ); // 4 bytes of the hash
		$n = $n % $this->shardingFactor; // modulo number of shards

		return $n == $this->shard;
	}

	private function idMatchesType( EntityId $id ) {
		return $this->entityType === null
			|| ( $id->getEntityType() === $this->entityType );
	}

	/**
	 * @param $ex
	 * @throws StorageException
	 */
	private function handleStorageException( $ex ) {
		//TODO: optionally, log & ignore.
		throw $ex;
	}

	/**
	 * Encodes the given data as JSON
	 *
	 * @param $data
	 *
	 * @return string
	 * @throws \MWException
	 */
	public function encode( $data ) {
		$json = json_encode( $data, $this->jsonFlags );

		if ( $json === false ) {
			// TODO: optionally catch & skip this
			throw new MWException( 'Failed to encode data structure.' );
		}

		return $json;
	}

	/**
	 * Writers the given string to the output provided to the constructor.
	 *
	 * @param $json
	 */
	private function writeToDump( $json ) {
		//TODO: use output stream object
		fwrite( $this->out, $json );
	}

	/**
	 * Flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 *
	 * @param int $jsonFlags
	 */
	public function setJsonFlags( $jsonFlags ) {
		$this->jsonFlags = $jsonFlags;
	}

	/**
	 * Flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 *
	 * @return int
	 */
	public function getJsonFlags() {
		return $this->jsonFlags;
	}
}