<?php
/**
 * CSV helpers.
 *
 * Adapted from the axellcore plugin's Axellcore_Fields::csv_line() and
 * escape_cell().
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * RFC 4180 lines with spreadsheet formula protection.
 */
final class Csv {

	/**
	 * UTF-8 byte order mark, so Excel opens the file as UTF-8.
	 */
	const BOM = "\xEF\xBB\xBF";

	/**
	 * Build one CSV line.
	 *
	 * A cell is quoted when it contains the delimiter, a double quote or a line
	 * break, or when it has leading or trailing whitespace. Quotes inside a
	 * quoted cell are doubled.
	 *
	 * @param string[] $cells     Cell values.
	 * @param string   $delimiter Single-character delimiter.
	 * @return string Line ending in "\n".
	 */
	public static function line( array $cells, $delimiter = ',' ) {
		$out = array();

		foreach ( $cells as $cell ) {
			$cell = self::escape_cell( (string) $cell );

			$needs_quotes = '' !== $cell && (
				false !== strpos( $cell, $delimiter )
				|| false !== strpos( $cell, '"' )
				|| false !== strpos( $cell, "\n" )
				|| false !== strpos( $cell, "\r" )
				|| trim( $cell ) !== $cell
			);

			$out[] = $needs_quotes ? '"' . str_replace( '"', '""', $cell ) . '"' : $cell;
		}

		return implode( $delimiter, $out ) . "\n";
	}

	/**
	 * Neutralise spreadsheet formulas by prefixing a single quote.
	 *
	 * @param string $value Cell value.
	 * @return string
	 */
	public static function escape_cell( $value ) {
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
