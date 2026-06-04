<?php
/**
 * Class WSC_Barcode_Generator_Test
 *
 * @package WSC
 */

class WSC_Barcode_Generator_Test extends WP_UnitTestCase {

	/**
	 * Test QR Code Generation
	 */
	public function test_get_barcode_svg() {
		$generator = new WSC_Barcode_Generator();
		$content   = 'TEST-123';
		$svg       = $generator->get_barcode_svg( $content );

		// Assert it returns a string
		$this->assertIsString( $svg );

		// Assert it is an SVG
		$this->assertStringContainsString( '<svg', $svg );
		$this->assertStringContainsString( '</svg>', $svg );

		// Assert it contains the black fill
		$this->assertStringContainsString( 'fill="black"', $svg );

		// Assert it contains the white background
		$this->assertStringContainsString( 'fill="white"', $svg );
	}
	
	/**
	 * Test Empty Input
	 */
	public function test_empty_input() {
		$generator = new WSC_Barcode_Generator();
		$svg       = $generator->get_barcode_svg( '' );
		$this->assertEmpty( $svg );
	}
}
