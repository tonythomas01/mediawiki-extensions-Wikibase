<?php

namespace Wikibase;

use IContextSource;
use MWException;
use Diff\Diff;
use Diff\DiffOp;
use Diff\DiffOpChange;
use Diff\DiffOpAdd;
use Diff\DiffOpRemove;
use SiteStore;

/**
 * Class for generating views of EntityDiff objects.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class EntityDiffVisualizer {

	/**
	 * @since 0.4
	 *
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @since 0.4
	 *
	 * @var ClaimDiffer|null
	 */
	private $claimDiffer;

	/**
	 * @since 0.4
	 *
	 * @var ClaimDifferenceVisualizer|null
	 */
	private $claimDiffVisualizer;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param IContextSource $contextSource
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffView
	 * @param SiteStore $siteStore
	 */
	public function __construct( IContextSource $contextSource,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		SiteStore $siteStore
	) {
		$this->context = $contextSource;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
		$this->siteStore = $siteStore;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityDiff.
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $diff
	 *
	 * @return string
	 */
	public function visualizeDiff( EntityDiff $diff ) {
		$html = '';

		$termDiffVisualizer = new DiffView(
			array(),
			new Diff(
				array (
					$this->context->getLanguage()->getMessage( 'wikibase-diffview-label' ) => $diff->getLabelsDiff(),
					$this->context->getLanguage()->getMessage( 'wikibase-diffview-alias' ) => $diff->getAliasesDiff(),
					$this->context->getLanguage()->getMessage( 'wikibase-diffview-description' ) => $diff->getDescriptionsDiff(),
				),
				true
			),
			$this->siteStore,
			$this->context
		);

		$html .= $termDiffVisualizer->getHtml();

		foreach ( $diff->getClaimsDiff() as $claimDiffOp ) {
			$html .= $this->getClaimDiffHtml( $claimDiffOp );
		}

		// FIXME: this does not belong here as it is specific to items
		if ( $diff instanceof ItemDiff ) {
			$termDiffVisualizer = new DiffView(
				array(),
				new Diff(
					array (
						$this->context->getLanguage()->getMessage( 'wikibase-diffview-link' ) => $diff->getSiteLinkDiff(),
					),
					true
				),
				$this->siteStore,
				$this->context
			);

			$html .= $termDiffVisualizer->getHtml();
		}

		return $html;
	}

	/**
	 * Returns the HTML for a single claim DiffOp.
	 *
	 * @since 0.4
	 *
	 * @param DiffOp $claimDiffOp
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function getClaimDiffHtml( DiffOp $claimDiffOp ) {
		if ( $claimDiffOp instanceof DiffOpChange ) {
			$claimDifference = $this->claimDiffer->diffClaims(
				$claimDiffOp->getOldValue(),
				$claimDiffOp->getNewValue()
			);
			return $this->claimDiffVisualizer->visualizeClaimChange(
				$claimDifference,
				$claimDiffOp->getNewValue()
			);
		}

		if ( $claimDiffOp instanceof DiffOpAdd ) {
			return $this->claimDiffVisualizer->visualizeNewClaim( $claimDiffOp->getNewValue() );
		} elseif ( $claimDiffOp instanceof DiffOpRemove ) {
			return $this->claimDiffVisualizer->visualizeRemovedClaim( $claimDiffOp->getOldValue() );
		} else {
			throw new MWException( 'Encountered an unexpected diff operation type for a claim' );
		}
	}

}
