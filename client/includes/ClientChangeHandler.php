<?php

namespace Wikibase;

/**
 * @todo: factor out specific types of handling and delegate tasks
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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 *
 * @fixme: ChangeHandler and ClientChangeHandler are named similarly but are quite unrelated.
 *         This is really a client side version of Change... perhaps rename to ClientSideChange?...
 */
class ClientChangeHandler {

	protected $change;

	/**
	 * @since 0.4
	 *
	 * @param Change $change
	 */
	public function __construct( Change $change ) {
		$this->change = $change;
	}

	/**
	 * @since 0.3
	 *
	 * @return bool
	 */
	public function changeNeedsRendering() {
		if ( $this->change instanceof ItemChange ) {
			if ( !$this->change->getSiteLinkDiff()->isEmpty() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @todo integrate this with the change handler stuff in a nicer way
	 *
	 * @since 0.3
	 *
	 * @return array|null
	 */
	public function siteLinkComment() {
		//FIXME: Allow for comments specific to the affected page.
		//       Different pages may be affected in different ways by the same change.
		//       Also, merged changes may affect the same page in multiple ways.
		$comment = null;
		if ( !$this->change->getSiteLinkDiff()->isEmpty() ) {
			$siteLinkDiff = $this->change->getSiteLinkDiff();

			$globalId = Settings::get( 'siteGlobalID' );

			// check that $sitecode is valid
			if ( !\Sites::singleton()->getSite( $globalId ) ) {
				trigger_error( "Site code $globalId does not exist in the sites table. "
					. "Has the sites table been populated?", E_USER_WARNING );

				return null; //XXX: can we do better?
			}

			$params = array();

			// change involved site link to client wiki
			if ( array_key_exists( $globalId, $siteLinkDiff ) ) {

				$diffOp = $siteLinkDiff[$globalId];

				if ( $this->change->getAction() === 'remove' ) {
					$params['message'] = 'wikibase-comment-remove';
				} else if ( $this->change->getAction() === 'restore' ) {
					$params['message'] = 'wikibase-comment-restore';
				} else if ( $diffOp instanceof \Diff\DiffOpAdd ) {
					$params['message'] = 'wikibase-comment-linked';
				} else if ( $diffOp instanceof \Diff\DiffOpRemove ) {
					$params['message'] = 'wikibase-comment-unlink';
				} else if ( $diffOp instanceof \Diff\DiffOpChange ) {
					$params['message'] = 'wikibase-comment-sitelink-change';
					$iwPrefix = Settings::get( 'siteLocalID' );
					$params['sitelink'] = array(
						'oldlink' => array(
							'lang' => $iwPrefix,
							'page' => $diffOp->getOldValue()
						),
						'newlink' => array(
							'lang' => $iwPrefix,
							'page' => $diffOp->getNewValue()
						)
					);
				}
			} else {
				$messagePrefix = 'wikibase-comment-sitelink-';
				/* Messages used:
					wikibase-comment-sitelink-add wikibase-comment-sitelink-change wikibase-comment-sitelink-remove
				*/
				$params['message'] = $messagePrefix . 'change';

				foreach( $siteLinkDiff as $siteKey => $diffOp ) {
					$site = \Sites::singleton()->getSite( $siteKey );
					if( !$site ) {
						trigger_error( "Could not get site with globalId $siteKey.", E_USER_WARNING );
						continue;
					}
					// assumes interwiki prefix is same as lang code
					// true for wikipedia but need todo more robustly
					$iwPrefix = $site->getLanguageCode();
					if ( $diffOp instanceof \Diff\DiffOpAdd ) {
						$params['message'] = $messagePrefix . 'add';
						$params['sitelink'] = array(
							'newlink' =>  array(
								'lang' => $iwPrefix,
								'page' => $diffOp->getNewValue()
							)
						);
					} else if ( $diffOp instanceof \Diff\DiffOpRemove ) {
						$params['message'] = $messagePrefix . 'remove';
						$params['sitelink'] = array(
							'oldlink' => array(
								'lang' => $iwPrefix,
								'page' => $diffOp->getOldValue()
							)
						);
					} else if ( $diffOp instanceof \Diff\DiffOpChange ) {
						$params['sitelink'] = array(
							'oldlink' => array(
								'lang' => $iwPrefix,
								'page' => $diffOp->getOldValue()
							),
							'newlink' => array(
								'lang' => $iwPrefix,
								'page' => $diffOp->getNewValue()
							)
						);
					}
					// @todo: because of edit conflict bug in repo
					// sometimes we get multiple stuff in diffOps
					break;
				}
			}

			return $params;
		}
	}
}