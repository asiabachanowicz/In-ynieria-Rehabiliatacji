<?php
namespace SiteGround_Optimizer\Parser;

use SiteGround_Optimizer\Minifier\Minifier;
use SiteGround_Optimizer\Options\Options;
use SiteGround_Optimizer\Combinator\Css_Combinator;
use SiteGround_Optimizer\Combinator\Js_Combinator;
use SiteGround_Optimizer\Combinator\Fonts_Combinator;
use SiteGround_Optimizer\DNS_Prefetch\DNS_Prefetch;

/**
 * Parser functions and main initialization class.
 */
class Parser {

	/**
	 * The constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		if ( ! defined( 'WP_CLI' ) ) {
			// Add the hooks that we will use to combine the css.
			add_action( 'init', array( $this, 'start_bufffer' ) );
			add_action( 'shutdown', array( $this, 'end_buffer' ) );
		}
	}

	/**
	 * Run the parser.
	 *
	 * @since  5.5.2
	 *
	 * @param  string $html The page html.
	 *
	 * @return string $html The modified html.
	 */
	public function run( $html ) {
		// Do not run optimizations for amp.
		if ( $this->is_amp_enabled( $html ) ) {
			return $html;
		}

		if ( Options::is_enabled( 'siteground_optimizer_optimize_html' ) ) {
			$html = Minifier::get_instance()->run( $html );
		}

		if ( Options::is_enabled( 'siteground_optimizer_combine_css' ) ) {
			$html = Css_Combinator::get_instance()->run( $html );
		}

		if ( Options::is_enabled( 'siteground_optimizer_combine_javascript' ) ) {
			$html = Js_Combinator::get_instance()->run( $html );
		}

		if ( Options::is_enabled( 'siteground_optimizer_combine_google_fonts' ) ) {
			$html = Fonts_Combinator::get_instance()->run( $html );
		}
		
		if ( Options::is_enabled( 'siteground_optimizer_dns_prefetch' ) ) {
			$html = DNS_Prefetch::get_instance()->run( $html );
		}
		return $html;
	}

	/**
	 * AMP Atribute check. Runs a check if AMP option is enabled
	 *
	 * @since 5.5.8
	 *
	 * @param string $html The page html.
	 *
	 * @return bool $run_amp_check Wheter the page is loaded via AMP.
	 */
	public function is_amp_enabled( $html ) {
		// Get the first 200 chars of the file to make the preg_match check faster.
		$is_amp = substr( $html, 0, 200 );
		// Cheks if the document is containing the amp tag.
		$run_amp_check = preg_match( '/<html[^>]+(amp|???)[^>]*>/', $is_amp );

		return $run_amp_check;
	}

	/**
	 * Start buffer.
	 *
	 * @since  5.5.0
	 */
	public function start_bufffer() {
		if ( \is_user_logged_in() ) {
			return;
		}

		ob_start( array( $this, 'run' ) );
	}

	/**
	 * End the buffer.
	 *
	 * @since  5.5.0
	 */
	public function end_buffer() {
		if ( ob_get_length() ) {
			ob_end_flush();
		}
	}
}
