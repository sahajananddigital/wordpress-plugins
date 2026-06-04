<?php
/**
 * Barcode Generator Class (QR Code Edition)
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/class-wsc-qr-code-lib.php';

class WSC_Barcode_Generator {

	/**
	 * Get QR Code SVG
	 *
	 * @param string $data Data to encode.
	 * @param int    $height Target height (ignored for QR as it is square).
	 * @return string SVG content.
	 */
	public function get_barcode_svg( $data, $height = 50 ) {
		// Use WSC_QRCode library to generate matrix
		// Options: 's' => 'qrl' (Quality Low), 'md' => 1 (Module Density)
		try {
			$qr = new WSC_QRCode( $data, array( 's' => 'qrm' ) );
			$matrix = $qr->get_matrix();
		} catch ( Exception $e ) {
			return '';
		}

		if ( empty( $matrix ) ) {
			return '';
		}

		$size = count( $matrix );
		
		// SVG Construction
		// viewBox is 0 0 size size
		// Style width/height auto to maintain aspect ratio, max-width to keep it sane
		$svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" preserveAspectRatio="xMinYMin meet" style="width: auto; height: ' . $height . 'px; max-width: 100%;">';
		
		// Background (White) - important for scanning if printed on non-white paper or transparency
		$svg .= '<rect width="100%" height="100%" fill="white"/>';
		
		// Build Path for Black Modules
		$path = '';
		foreach ( $matrix as $y => $row ) {
			foreach ( $row as $x => $color ) {
				// If color is truthy, it is the foreground (black)
				if ( $color ) {
					// Draw 1x1 rectangle at x,y
					$path .= 'M' . $x . ' ' . $y . 'h1v1h-1z ';
				}
			}
		}

		$svg .= '<path d="' . $path . '" fill="black" />';
		$svg .= '</svg>';
		
		return $svg;
	}
}
