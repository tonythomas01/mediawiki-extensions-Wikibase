<?php

namespace Wikibase\Api;

use ApiBase, MWException;

use Wikibase\Autocomment;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\SnakObject;
use Wikibase\Claim;
use Wikibase\Claims;

/**
 * API module for setting the DataValue contained by the main snak of a claim.
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
 * @since 0.3
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SetClaimValue extends ApiWikibase implements IAutocomment{

	// TODO: example
	// TODO: rights
	// TODO: conflict detection
	// TODO: claim uniqueness

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$content = $this->getEntityContent();

		$params = $this->extractRequestParams();

		$claim = $this->updateClaim(
			$content->getEntity(),
			$params['claim'],
			$params['snaktype'],
			isset( $params['value'] ) ? \FormatJson::decode( $params['value'], true ) : null
		);

		$this->saveChanges( $content );

		$this->outputClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.3
	 *
	 * @return \Wikibase\EntityContent
	 */
	protected function getEntityContent() {
		$params = $this->extractRequestParams();

		$entityId = EntityId::newFromPrefixedId( Entity::getIdFromClaimGuid( $params['claim'] ) );
		$entityTitle = EntityContentFactory::singleton()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->dieUsage( 'No such entity', 'setclaimvalue-entity-not-found' );
		}

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;

		return $this->loadEntityContent( $entityTitle, $baseRevisionId );
	}

	/**
	 * Updates the claim with specified GUID to have a main snak with provided value.
	 * The claim is modified in the passed along entity and is returned as well.
	 *
	 * @since 0.3
	 *
	 * @param \Wikibase\Entity $entity
	 * @param string $guid
	 * @param string $snakType
	 * @param string|null $value
	 *
	 * @return \Wikibase\Claim
	 */
	protected function updateClaim( Entity $entity, $guid, $snakType, $value = null ) {
		$claims = new Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $guid ) ) {
			$this->dieUsage( 'No such claim', 'setclaimvalue-claim-not-found' );
		}

		$claim = $claims->getClaimWithGuid( $guid );

		$constructorArguments = array( $claim->getMainSnak()->getPropertyId() );

		if ( $value !== null ) {
			/**
			 * @var \Wikibase\PropertyContent $content
			 */
			$content = EntityContentFactory::singleton()->getFromId( $claim->getMainSnak()->getPropertyId() );

			if ( $content === null ) {
				$this->dieUsage(
					'The value cannot be interpreted since the property cannot be found, and thus the type of the value not be determined',
					'setclaimvalue-property-not-found'
				);
			}

			$constructorArguments[] = $content->getProperty()->newDataValue( $value );
		}

		$claim->setMainSnak( SnakObject::newFromType( $snakType, $constructorArguments ) );

		$entity->setClaims( $claims );

		return $claim;
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\EntityContent $content
	 */
	protected function saveChanges( EntityContent $content ) {
		$params = $this->extractRequestParams();

		$user = $this->getUser();
		$flags = 0;
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : false;
		$flags |= ( $user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;
		$flags |= EDIT_UPDATE;
		$editEntity = new \Wikibase\EditEntity( $content, $user, $baseRevisionId, $this->getContext() );

		$status = $editEntity->attemptSave(
			'', // TODO: automcomment
			$flags,
			isset( $params['token'] ) ? $params['token'] : ''
		);

		if ( !$status->isGood() ) {
			$this->dieUsage( 'Failed to save the change', 'setclaimvalue-save-failed' );
		}

		$statusValue = $status->getValue();

		if ( isset( $statusValue['revision'] ) ) {
			$this->getResult()->addValue(
				'pageinfo',
				'lastrevid',
				(int)$statusValue['revision']->getId()
			);
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\Claim $claim
	 */
	protected function outputClaim( Claim $claim ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $claim );

		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'claim',
			$serializer->getSerialized( $claim )
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'claim' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'value' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			),
			'snaktype' => array(
				ApiBase::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
				ApiBase::PARAM_REQUIRED => true,
			),
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'bot' => null,
		);
	}

	/**
	 * @see \ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'claim' => 'A GUID identifying the claim',
			'snaktype' => 'The type of the snak',
			'value' => 'The value to set the datavalue of the the main snak of the claim to',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),

		);
	}

	/**
	 * @see \ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for setting the value of a Wikibase claim.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			// TODO
			// 'ex' => 'desc'
		);
	}


	/**
	 * @see  \Wikibase\Api\IAutocomment::getTextForComment()
	 */
	public function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			$this->getModuleName(),
			array(
				/*plural */ (int)isset( $params['claim'] )
			)
		);
	}

	/**
	 * @see  \Wikibase\Api\IAutocomment::getTextForSummary()
	 */
	public function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'claim' )
		);
	}

}