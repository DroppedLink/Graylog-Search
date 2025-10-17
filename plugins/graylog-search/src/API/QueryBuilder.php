<?php
/**
 * Graylog Query Builder Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\API;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Builder Class
 */
class QueryBuilder {

	/**
	 * Build Graylog search query.
	 *
	 * @param string $search_query Search query string.
	 * @param string $search_mode Search mode (simple, advanced, query_builder).
	 * @param string $filter_out Terms to filter out.
	 * @return string Built query string.
	 */
	public static function build_query( $search_query, $search_mode, $filter_out = '' ) {
		$query_parts = array();

		// Handle search query based on mode.
		if ( ! empty( $search_query ) ) {
			if ( 'advanced' === $search_mode || 'query_builder' === $search_mode ) {
				// Advanced mode: user provides full Lucene syntax.
				$query_parts[] = trim( $search_query );
			} else {
				// Simple mode: search across multiple common fields.
				$term_queries = self::build_simple_query( $search_query );
				if ( ! empty( $term_queries ) ) {
					if ( count( $term_queries ) > 1 ) {
						$query_parts[] = '(' . implode( ' OR ', $term_queries ) . ')';
					} else {
						$query_parts[] = $term_queries[0];
					}
				}
			}
		}

		// Add filter out terms (NOT).
		if ( ! empty( $filter_out ) ) {
			$filters = self::parse_multivalue_input( $filter_out );
			foreach ( $filters as $filter ) {
				if ( ! empty( $filter ) ) {
					$query_parts[] = 'NOT ' . $filter;
				}
			}
		}

		// If no query parts, search for everything.
		if ( empty( $query_parts ) ) {
			return '*';
		}

		// AND together all parts.
		return implode( ' AND ', $query_parts );
	}

	/**
	 * Build simple mode query.
	 *
	 * @param string $search_query Search query.
	 * @return array Array of query parts.
	 */
	private static function build_simple_query( $search_query ) {
		// DEBUG: Log the raw search query.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[QueryBuilder] Raw search_query: "' . $search_query . '"' );
			error_log( '[QueryBuilder] Search query length: ' . strlen( $search_query ) );
			error_log( '[QueryBuilder] Search query hex: ' . bin2hex( $search_query ) );
		}
		
		$terms        = self::parse_multivalue_input( $search_query, false );
		
		// DEBUG: Log parsed terms.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[QueryBuilder] Parsed terms count: ' . count( $terms ) );
			error_log( '[QueryBuilder] Parsed terms: ' . print_r( $terms, true ) );
		}
		
		$term_queries = array();

		foreach ( $terms as $term ) {
			$term = trim( $term );
			if ( empty( $term ) ) {
				continue;
			}

			// Check if term contains spaces (phrase search).
			$has_spaces = preg_match( '/\s/', $term );

			// Escape special Lucene characters except * and ?.
			$term = preg_replace( '/([+\-&|!(){}\[\]^"~:\\\])/', '\\\\$1', $term );

			// Build simplified query without field specifications.
			// Graylog will search all fields automatically.
			if ( $has_spaces ) {
				// Phrase search - use quotes, no wildcards.
				$term_queries[] = '"' . str_replace( '*', '', $term ) . '"';
			} else {
				// Single word - add trailing wildcard for partial matching.
				if ( false === strpos( $term, '*' ) && false === strpos( $term, '?' ) ) {
					$term = $term . '*';
				}
				$term_queries[] = $term;
			}
		}

		return $term_queries;
	}

	/**
	 * Parse multi-value input (newlines, commas, or optionally spaces).
	 *
	 * @param string $input Input string.
	 * @param bool   $split_on_spaces Whether to split on spaces.
	 * @return array Array of values.
	 */
	private static function parse_multivalue_input( $input, $split_on_spaces = true ) {
		$values = array();

		// First split by newlines.
		$lines = preg_split( '/\r\n|\r|\n/', $input );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Check if line contains commas.
			if ( false !== strpos( $line, ',' ) ) {
				// Split by comma.
				$parts = explode( ',', $line );
				foreach ( $parts as $part ) {
					$part = trim( $part );
					if ( ! empty( $part ) ) {
						$values[] = $part;
					}
				}
			} else {
				// Check if line contains spaces.
				if ( $split_on_spaces && false !== strpos( $line, ' ' ) ) {
					// Split by space.
					$parts = explode( ' ', $line );
					foreach ( $parts as $part ) {
						$part = trim( $part );
						if ( ! empty( $part ) ) {
							$values[] = $part;
						}
					}
				} else {
					// Single value or phrase.
					$values[] = $line;
				}
			}
		}

		return $values;
	}
}
