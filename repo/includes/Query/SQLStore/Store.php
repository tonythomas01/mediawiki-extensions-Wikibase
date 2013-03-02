<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Query\QueryStore;
use Wikibase\Repo\Database\TableDefinition;

/**
 * Simple query store for relational SQL databases.
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
 * @since wd.qe
 *
 * @file
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Store implements QueryStore {

	private $tablePrefix;

	private $dvHandlers;

	public function __construct( $tablePrefix, array $dataValueHandlers ) {
		$this->tablePrefix = $tablePrefix;
		$this->dvHandlers = $dataValueHandlers;
	}

	/**
	 * @since wd.qe
	 *
	 * @return TableDefinition
	 */
	public function getTables() {
		return array();
	}

	/**
	 * @see QueryStore::getName
	 *
	 * @since wd.qe
	 *
	 * @return string
	 */
	public function getName() {
		return 'Wikibase SQL store';
	}

	// TODO

}