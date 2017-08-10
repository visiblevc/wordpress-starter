<?php

/**
 * Author : NEUMANN-RYSTOW François <kalachnkv@free.fr>
 * Version : 0.12
 * Date : 7 Jan, 2009
 * Purpose : Convert SQL Query (only DQL(SELECT) and DML(INSERT, UPDATE, DELETE)) to TREE
 */
class dqml2tree {
	var $sql              = '';
	var $_sql             = '';
	var $_as              = Array();
	var $_base_rules      = Array();
	var $_inter_rules     = Array();
	var $_noval_rules     = Array();
	var $_sheet_rules     = Array();
	var $_table_rules     = Array();
	var $_list_base_rules = Array();
	var $_zonesQ          = Array();
	var $_zonesP          = Array();
	var $_tree            = Array();
	var $_base_tree       = Array();
	var $_inter_tree      = Array();
	var $_zonesP_tree     = Array();
	var $_lexs            = Array();

	function __construct( $query ) {
		$this->_base_rules['SQL'] = Array(
			'SELECT',
			'INSERT',
			'UPDATE',
			'DELETE'
		);

		$this->_base_rules['SELECT'] = Array(
			'FROM',
			'WHERE',
			'GROUP',
			'HAVING',
			'UNION',
			'INTERSECT',
			'MINUS',
			'EXCEPT',
			'ORDER',
			'LIMIT',
			'OFFSET'
		);

		$this->_base_rules['UPDATE'] = Array(
			'SET',
			'FROM',
			'WHERE'
		);

		$this->_base_rules['INSERT'] = Array(
			'INTO',
			'VALUES'
		);

		$this->_base_rules['DELETE'] = Array(
			'FROM',
			'WHERE'
		);

		$this->_base_rules['UNION']     = Array( 'SELECT' );
		$this->_base_rules['INTERSECT'] = Array( 'SELECT' );
		$this->_base_rules['MINUS']     = Array( 'SELECT' );
		$this->_base_rules['EXCEPT']    = Array( 'SELECT' );

		foreach ( $this->_base_rules as $key => $vals ) {
			$this->_list_base_rules[ $key ] = $key;
			foreach ( $vals as $val ) {
				$this->_list_base_rules[ $val ] = $val;
			}
		}

		$this->_inter_rules['SELECT']  = Array( '*SELECT' => ',' );
		$this->_inter_rules['UPDATE']  = Array( '*UPDATE' => ',' );
		$this->_inter_rules['INTO']    = Array( '*INSERT' => ' (' );
		$this->_inter_rules['*INSERT'] = Array( '*INTO' => ',' );
		$this->_inter_rules['FROM']    = Array( '*FROM' => ',' );
		$this->_inter_rules['WHERE']   = Array( '*OR' => ' OR ' );
		$this->_inter_rules['HAVING']  = Array( '*OR' => ' OR ' );
		$this->_inter_rules['GROUP']   = Array( '*GROUP' => ',' );
		$this->_inter_rules['ORDER']   = Array( '*ORDER' => ',' );
		$this->_inter_rules['LIMIT']   = Array( '*LIMIT' => ',' );
		$this->_inter_rules['VALUES']  = Array( '*VALUES' => ',' );
		$this->_inter_rules['SET']     = Array( '*SET' => ',' );
		$this->_inter_rules['*FROM']   = Array( '*JOIN' => ' JOIN ' );
		$this->_inter_rules['*JOIN']   = Array( '*ON' => ' ON ' );
		$this->_inter_rules['*ON']     = Array( '*OR' => ' OR ' );
		$this->_inter_rules['*OR']     = Array( '*AND' => ' AND ' );
		$this->_inter_rules['*SET']    = Array( '#SET' => '=' );
		$this->_inter_rules['*AND']    = Array(
			'!IN'     => ' IN ',
			'!IS'     => ' IS ',
			'!DIFF'   => '<>',
			'!EQLESS' => '<=',
			'!EQPLUS' => '>=',
			'!EQ'     => '=',
			'!LESS'   => '<',
			'!PLUS'   => '>',
			'!LIKE'   => ' LIKE ',
			'!ILIKE'  => ' ILIKE '
		);

		$this->_as                     = Array( '*AS' => ' AS ', '*AS' => ' ' );
		$this->_inter_rules['*SELECT'] = $this->_as;
		$this->_inter_rules['0|*JOIN'] = $this->_as;
		$this->_inter_rules['0|*ON']   = $this->_as;

		$this->_noval_rules = Array( '', ',', 'AS', 'BY', 'OR', 'AND', 'ON', 'JOIN' );
		$this->_sheet_rules = Array(
			'ORDER'  => Array( 'ASC', 'DESC' ),
			'*ORDER' => Array( 'ASC', 'DESC' ),
			'*JOIN'  => Array( 'INNER', 'OUTER', 'LEFT', 'RIGHT' ),
			'*ON'    => Array( 'INNER', 'OUTER', 'LEFT', 'RIGHT' )
		);
		foreach ( $this->_sheet_rules['*ON'] as $j ) {
			$this->_noval_rules[] = 'JOIN ' . $j;
		}
		$this->_table_rules = Array( 'FROM', 'JOIN', 'ON', 'INSERT', 'UPDATE', '*FROM', '*JOIN', '*ON', '*INSERT', '*UPDATE' );

		$this->sql                = $query;
		$this->_tree['SQL']['_a'] = 0;
		$this->_tree['SQL']['_z'] = strlen( $query );
	}

	function _reformatQuery() {
		$_sql       = $this->sql;
		$_sql       = strtoupper( $_sql );
		$_sql       = preg_replace( '/;[\s\n]*$/', '', $_sql );
		$_sql       = str_replace( "\'", "''", $_sql );
		$_sql       = str_replace( "\r", ' ', $_sql );
		$_sql       = str_replace( "\n", ' ', $_sql );
		$_sql       = str_replace( ' INNER JOIN ', ' JOIN INNER ', $_sql );
		$_sql       = str_replace( ' OUTER JOIN ', ' JOIN OUTER ', $_sql );
		$_sql       = str_replace( ' LEFT JOIN ', ' JOIN LEFT ', $_sql );
		$_sql       = str_replace( ' RIGHT JOIN ', ' JOIN RIGHT ', $_sql );
		$this->_sql = $_sql;
	}

	function _zonesQuote() {
		$char    = "'";
		$sub_sql = $this->_sql;
		$pos     = strpos( $sub_sql, $char );
		$index   = 0;
		$etat    = false;
		while ( $pos !== false ) {
			$index += $pos;
			$sub_sql                 = substr( $sub_sql, $pos + 1 );
			$sub_sql                 = ' ' . $sub_sql;
			$etat                    = ( $etat == false ? true : false );
			$this->_zonesQ[ $index ] = $etat;
			$pos                     = strpos( $sub_sql, $char );
		}

		$etat                                   = ( $etat == false ? true : false );
		$this->_zonesQ[ strlen( $this->_sql ) ] = $etat;
		$last_false                             = false;
		foreach ( $this->_zonesQ as $index => $etat ) {
			if ( $etat == false ) {
				$last_false = $index;
			} else if ( $last_false != false ) {
				if ( $last_false == $index - 1 ) {
					unset( $this->_zonesQ[ $index - 1 ] );
					unset( $this->_zonesQ[ $index ] );
				}
			}
		}

		$outzones   = Array();
		$inzone     = Array();
		$last_false = - 1;
		$last_true  = - 1;
		foreach ( $this->_zonesQ as $index => $etat ) {
			if ( $etat == true ) {
				$outzones[] = Array( '_a' => $last_false + 1, '_z' => $index );
				$last_true  = $index;
			}
			if ( $etat == false ) {
				$inzone[]   = Array( '_a' => $last_true + 1, '_z' => $index );
				$last_false = $index;
			}
		}

		$this->_zonesQ = Array( 'out' => $outzones, 'in' => $inzone );
	}

	function _zonesParenthesis() {
		$chaine     = $this->sql;
		$open_char  = '(';
		$close_char = ')';
		foreach ( Array( $open_char => '_a', $close_char => '_z' ) as $char => $etat ) {
			$sub_sql = $chaine;
			$pos     = strpos( $sub_sql, $char );
			$index   = 0;
			while ( $pos !== false ) {
				$index += $pos;
				$sub_sql = substr( $sub_sql, $pos + 1 );
				$sub_sql = ' ' . $sub_sql;
				if ( ! $this->_inZone( $index, $this->_zonesQ['in'] ) ) {
					$this->_zonesP[ $index ] = $etat;
				}
				$pos = strpos( $sub_sql, $char );
			}
		}

		ksort( $this->_zonesP );
		$pile = Array();
		foreach ( $this->_zonesP as $index => $etat ) {
			if ( $etat == '_a' ) {
				array_push( $pile, $index );
				$monkey = &$this->_zonesP_tree;
				foreach ( $pile as $key => $value ) {
					$monkey = &$monkey[ $value ];
				}
				$monkey[ $etat ] = $index;
			}
			if ( $etat == '_z' ) {
				$monkey = &$this->_zonesP_tree;
				foreach ( $pile as $key => $value ) {
					$monkey = &$monkey[ $value ];
				}
				$monkey[ $etat ] = $index;
				$open            = array_pop( $pile );
			}
		}

		$this->_zonesP_tree['_a'] = 0;
		$this->_zonesP_tree['_z'] = strlen( $this->_sql );
		$this->_outZone( $this->_zonesP_tree );
	}

	function _outZone( &$inzones ) {
		$outzones[] = Array( '_a' => $inzones['_a'], '_z' => $inzones['_z'] );
		foreach ( $inzones as $i => $izone ) {
			if ( $i != '_a' && $i != '_z' ) {

				foreach ( $outzones as $o => $ozone ) {

					if ( $izone['_a'] < $ozone['_z'] && $izone['_z'] > $ozone['_a'] ) {

						if ( $izone['_a'] > $ozone['_a'] && $izone['_z'] < $ozone['_z'] ) {
							$outzones[] = Array(
								'_a' => $ozone['_a'],
								'_z' => $izone['_a'] - 1
							);
							$outzones[] = Array(
								'_a' => $izone['_z'] + 1,
								'_z' => $ozone['_z']
							);
							unset( $outzones[ $o ] );
						} elseif ( $izone['_z'] > $ozone['_z'] ) {
							$outzones[] = Array(
								'_a' => $ozone['_a'],
								'_z' => $izone['_z'] - 1
							);
							unset( $outzones[ $o ] );
						} elseif ( $izone['_a'] < $ozone['_a'] ) {
							$outzones[] = Array(
								'_a' => $izone['_z'] + 1,
								'_z' => $ozone['_z']
							);
							unset( $outzones[ $o ] );
						}
					}
				}

				$this->_outZone( $inzones[ $i ] );
			}
		}
		$inzones['out'] = $outzones;
	}

	function _inZone( $index, $zones ) {
		$in = false;
		foreach ( $zones as $zone ) {
			if ( $index >= $zone['_a'] && $index <= $zone['_z'] ) {
				$in = true;
			}
		}

		return $in;
	}

	function _makeBaseTree( &$_tree, $inzones ) {
		$sqlU                          = str_replace( '_', 'U', substr( $this->_sql, $inzones['_a'], $inzones['_z'] - $inzones['_a'] ) );
		$this->_lexs[ $inzones['_a'] ] = str_word_count( $sqlU, 2 );
		$deep                          = 0;
		$branch[ $deep ]               = 'SQL';

		foreach ( $this->_lexs[ $inzones['_a'] ] as $index => $candidate_rule ) {
			$index += $inzones['_a'];

			if ( in_array( $candidate_rule, $this->_list_base_rules ) && $this->_inZone( $index, $inzones['out'] ) && $this->_inZone( $index, $this->_zonesQ['out'] ) ) {

				while ( ! isset( $this->_base_rules[ $branch[ $deep ] ] ) ) {
					unset( $branch[ $deep ] );
					$deep --;
				}

				while ( ! in_array( $candidate_rule, $this->_base_rules[ $branch[ $deep ] ] ) ) {
					unset( $branch[ $deep ] );
					$deep --;
					if ( $deep < 0 ) {
						exit;
					}
				}

				if ( in_array( $candidate_rule, $this->_base_rules[ $branch[ $deep ] ] ) ) {
					$deep ++;
					$branch[ $deep ] = $candidate_rule;
					$monkey          = &$_tree;
					foreach ( $branch as $key => $value ) {
						$monkey = &$monkey[ $value ];
					}
					$monkey['_a'] = $index + strlen( $candidate_rule );
					if ( isset( $oldmonkey ) ) {
						$oldmonkey['_z']   = $index - 1;
						$oldmonkey['_SQL'] = substr( $this->sql, $oldmonkey['_a'], $oldmonkey['_z'] - $oldmonkey['_a'] );
					}
					$oldmonkey = &$monkey;
				}
			}
		}

		$oldmonkey['_z']   = $inzones['_z'];
		$oldmonkey['_SQL'] = substr( $this->sql, $oldmonkey['_a'], $inzones['_z'] - $oldmonkey['_a'] );
	}

	function _makeInterBranches( $inter_rule_key, $inter_rule_val, $begin, $end, $outzones ) {
		$poses       = Array();
		$length_rule = strlen( $inter_rule_val );
		$lr          = 0;
		$min_lr      = 0;
		if ( substr( $this->_sql, $begin, 1 ) == '(' ) {
			$min_lr = 1;
		}

		if ( $inter_rule_val != '' ) {
			$pos = strpos( $this->_sql, $inter_rule_val, $begin );
			while ( $pos !== false ) {
				if ( $this->_inZone( $pos, $outzones ) && $this->_inZone( $pos, $this->_zonesQ['out'] ) && $pos < $end ) {
					$poses[] = $pos;
				}
				$sbegin = $pos + 1;
				$pos    = strpos( $this->_sql, $inter_rule_val, $sbegin );
			}
		}

		$i        = 0;
		$last_pos = $begin;
		foreach ( $poses as $pos ) {
			if ( ! in_array( trim( substr( $this->_sql, $last_pos + $lr, $pos - $last_pos - $lr ) ), $this->_noval_rules ) ) {
				$lr                                           = ( $i > 0 ? $length_rule : $min_lr );
				$inter_branches[ $i . '|' . $inter_rule_key ] = Array(
					'_a'   => $last_pos,
					'_z'   => $pos,
					'_SQL' => str_repeat( ' ', $lr ) . substr( $this->sql, $last_pos + $lr, $pos - $last_pos - $lr )
				);
				$last_pos                                     = $pos;
				$i ++;
			}
		}

		if ( ! in_array( trim( substr( $this->_sql, $last_pos + $lr, $end - $last_pos - $lr ) ), $this->_noval_rules ) ) {
			$lr                                           = ( $i > 0 ? $length_rule : $min_lr );
			$inter_branches[ $i . '|' . $inter_rule_key ] = Array(
				'_a'   => $last_pos,
				'_z'   => $end,
				'_SQL' => str_repeat( ' ', $lr ) . substr( $this->sql, $last_pos + $lr, $end - $last_pos - $lr )
			);
		}

		return $inter_branches;
	}

	function _makeInterTree( &$_tree, $outzones ) {
		foreach ( $_tree as $branch_rule => $sub_tree ) {
			if ( $branch_rule != '_a' && $branch_rule != '_z' && $branch_rule != '_SQL' ) {

				$found_rule = false;
				$pur_rule   = $branch_rule;
				if ( array_key_exists( $pur_rule, $this->_inter_rules ) ) {
					$found_rule = true;
				} else {
					$rules = explode( '|', $branch_rule, 2 );
					if ( is_numeric( substr( $rules[0], 0, 1 ) ) ) {
						$pur_rule = $rules[1];
					}
					if ( array_key_exists( $pur_rule, $this->_inter_rules ) ) {
						$found_rule = true;
					}
				}

				if ( $found_rule ) {
					foreach ( $this->_inter_rules[ $pur_rule ] as $inter_rule_key => $inter_rule_val ) {

						if ( is_numeric( $sub_tree['_a'] ) && is_numeric( $sub_tree['_z'] ) ) {

							$new_branches = $this->_makeInterBranches( $inter_rule_key, $inter_rule_val, $sub_tree['_a'], $sub_tree['_z'], $outzones );

							if ( count( $new_branches ) > 1 || substr( $inter_rule_key, 0, 1 ) != '!' ) {
								if ( is_array( $new_branches ) ) {
									$_tree[ $branch_rule ] += $new_branches;
								}
								if ( substr( $inter_rule_key, 0, 1 ) == '!' ) {
									break;
								}
							}
						}
					}
				}

				$this->_makeInterTree( $_tree[ $branch_rule ], $outzones );
			}
		}
	}

	function _makeChildsTree( &$_tree, $parents, &$inzones ) {
		foreach ( $_tree as $branch => $sub_tree ) {
			if ( $branch == '_SQL' && count( $_tree ) < 4 ) {

				$child = substr( $this->_sql, $_tree['_a'], $_tree['_z'] - $_tree['_a'] );
				if ( strlen( $child ) > 0 ) {
					$pos = strpos( $child, '(', 1 );
					if ( $pos !== false ) {

						$pos += $_tree['_a'];
						if ( isset( $inzones[ $pos ] ) ) {
							$new_tree = Array();
							$this->_makeBaseTree( $new_tree, $inzones[ $pos ] );

							if ( empty( $new_tree ) ) {
								unset( $new_tree );
								// remontée récurcive jusqu'à la dernière base rule
								preg_match( '/([A-Z]+)\(/', substr( $this->_sql, $_tree['_a'], $pos - $_tree['_a'] + 1 ), $agr );
								if ( isset( $agr[1] ) ) {
									$p                              = $agr[1];
									$this->_inter_rules[ $p ]       = Array( '*' . $p => ',' );
									$this->_inter_rules[ '*' . $p ] = $this->_as;
								} else {
									$n = count( $parents );
									while ( $n > 0 ) {
										if ( in_array( $parents[ $n ], $this->_list_base_rules ) ) {
											$p = $parents[ $n ];
											$n = 0;
										}
										$n --;
									}
								}
								$new_tree[ $p ]['_a']   = $inzones[ $pos ]['_a'];
								$new_tree[ $p ]['_z']   = $inzones[ $pos ]['_z'];
								$new_tree[ $p ]['_SQL'] = ' ' . substr( $this->sql, $inzones[ $pos ]['_a'] + 1, $inzones[ $pos ]['_z'] - ( $inzones[ $pos ]['_a'] + 1 ) );
								$parents                = Array( 'SQL' );
							} else {
								$parents[] = $branch;
							}

							$this->_makeInterTree( $new_tree, $inzones[ $pos ]['out'] );
							$this->_makeChildsTree( $new_tree, $parents, $inzones[ $pos ] );
							$_tree += $new_tree;
						}
					}
				}
			} elseif ( $branch != '_SQL' && $branch != '_a' && $branch != '_z' ) {
				$parents[] = $branch;
				$this->_makeChildsTree( $_tree[ $branch ], $parents, $inzones );
			}
		}
	}

	function _reduceTree( &$_tree ) {
		while ( list( $branch, $sub_tree ) = each( $_tree ) ) {
			if ( $branch != '_SQL' && $branch != '_a' && $branch != '_z' ) {
				$this->_reduceTree( $_tree[ $branch ] );
				if ( array_key_exists( '_a', $_tree ) && $_tree['_a'] == $sub_tree['_a'] && $_tree['_z'] == $sub_tree['_z'] && count( $_tree ) < 5 ) {
					$_tree = $sub_tree;
				}
			}
		}
	}

	function _makeAdvanceTree( &$_tree, $parent, $grand_parent ) {
		foreach ( (Array) $_tree as $branch => $sub_tree ) {
			if ( $branch == '_SQL' ) {

				if ( count( $_tree ) < 4 ) {

					$sub_tree = str_replace( "\r", ' ', $sub_tree );
					$sub_tree = str_replace( "\n", ' ', $sub_tree );

					if ( substr( $parent, - 2 ) == 'AS' ) {
						$_parent = $grand_parent;
					} else {
						$_parent = $parent;
					}

					$rules = explode( '|', $_parent, 2 );
					if ( is_numeric( substr( $rules[0], 0, 1 ) ) ) {
						$num_rule = $rules[0];
						$pur_rule = $rules[1];
					} else {
						$num_rule = 0;
						$pur_rule = $rules[0];
					}

					if ( $num_rule == 0 || substr( $pur_rule, 0, 1 ) != '!' ) {
						$subs     = explode( ' ', $sub_tree );
						$sub_tree = '';
						if ( ! isset( $this->_sheet_rules[ $pur_rule ] ) ) {
							$this->_sheet_rules[ $pur_rule ] = Array();
						}
						foreach ( $subs as $sub ) {
							$subU = strtoupper( $sub );
							if ( ! in_array( $subU, $this->_noval_rules ) ) {
								if ( in_array( $subU, $this->_sheet_rules[ $pur_rule ] ) ) {
									$_tree[ $sub ] = $sub;
								} else {
									$sub_tree .= $sub . ' ';
								}
							}
						}
					}
					$lsub_tree = trim( $sub_tree );

					if ( in_array( $pur_rule, $this->_table_rules ) ) {
						$_tree['TABLE'] = $lsub_tree;
					} elseif ( is_numeric( $lsub_tree ) || substr( $lsub_tree, 0, 1 ) == "'" ) {
						$_tree['VAL'] = $lsub_tree;
					} else {
						$lsubs = explode( '.', $lsub_tree );
						if ( count( $lsubs ) > 1 ) {
							$_tree['TABLE'] = $lsubs[0];
							$_tree['FIELD'] = $lsubs[1];
						} else {
							$_tree['FIELD'] = str_replace( ',', '', $lsub_tree );
						}
					}
				}

				unset( $_tree['_SQL'] );
				unset( $_tree['_a'] );
				unset( $_tree['_z'] );
			} elseif ( $branch != '_SQL' && $branch != '_a' && $branch != '_z' ) {
				$this->_makeAdvanceTree( $_tree[ $branch ], $branch, $parent );
			}
		}
	}

	function make() {
		$this->_reformatQuery();
		$this->_zonesQuote();
		$this->_zonesParenthesis();
		$this->_makeBaseTree( $this->_tree, $this->_zonesP_tree );
		$this->_makeInterTree( $this->_tree['SQL'], $this->_zonesP_tree['out'] );
		$this->_makeChildsTree( $this->_tree['SQL'], Array( 'SQL' ), $this->_zonesP_tree );
		$this->_reduceTree( $this->_tree );
		$this->_makeAdvanceTree( $this->_tree, 'SQL', '' );

		return $this->_tree;
	}
}