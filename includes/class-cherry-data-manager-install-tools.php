<?php
/**
 * Add cherry theme install sample content service methods
 *
 * @package   cherry_data_manager
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * main importer class
 *
 * @since  1.0.1
 */
class Cherry_Data_Manager_Install_Tools {

	/**
	 * Holds XML file path
	 * @var string
	 */
	public $xml_file = null;

	public $html_meta = null;

	/**
	 * Constructor for the class
	 */
	function __construct() {
		$this->xml_file = get_transient( 'cherry_xml_file_path' );
	}

	/**
	 * Set XML file path
	 *
	 * @since 1.0.1
	 * @param string $file file path
	 */
	public function add_xml_file( $file ) {
		set_transient( 'cherry_xml_file_path', $file, DAY_IN_SECONDS );
		return true;
	}

	/**
	 * parse XML file into data array
	 *
	 * @param  string $file path to XML file
	 * @return array        parsed data
	 * @since  1.0.1
	 */
	public function _parse_xml( $file ) {

		$file_content = file_get_contents($file);
		$file_content = iconv('utf-8', 'utf-8//IGNORE', $file_content);
		$file_content = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $file_content);

		if ( !$file_content ) {
			return false;
		}

		$dom = new DOMDocument('1.0');
		$dom->loadXML( $file_content );

		$xml                   = simplexml_import_dom( $dom );
		$old_upload_url        = $xml->xpath('/rss/channel/wp:base_site_url');
		$old_upload_url        = $old_upload_url[0];
		$upload_dir            = wp_upload_dir();
		$upload_url            = $upload_dir['url'] . '/';
		$upload_dir            = $upload_dir['url'];
		$cut_upload_dir        = substr($upload_dir, strpos($upload_dir, 'wp-content/uploads'), strlen($upload_dir)-1);
		$cut_date_upload_dir   = '<![CDATA[' . substr($upload_dir, strpos($upload_dir, 'wp-content/uploads') + 19, strlen( $upload_dir ) - 1 );
		$cut_date_upload_dir_2 = "\"" . substr($upload_dir, strpos($upload_dir, 'wp-content/uploads') + 19, strlen( $upload_dir ) - 1 );

		$pattern            = '/[\"\']http:.{2}(?!livedemo).[^\'\"]*wp-content.[^\'\"]*\/(.[^\/\'\"]*\.(?:jp[e]?g|png))[\"\']/i';
		$patternCDATA       = '/<!\[CDATA\[\d{4}\/\d{2}/i';
		$pattern_meta_value = '/("|\')\d{4}\/\d{2}/i';

		$file_content = str_replace( $old_upload_url, site_url(), $file_content );
		$file_content = preg_replace( $patternCDATA, $cut_date_upload_dir, $file_content );
		$file_content = preg_replace( $pattern_meta_value, $cut_date_upload_dir_2, $file_content );
		$file_content = preg_replace( $pattern, '"' . $upload_url .'$1"', $file_content );

		$parser       = new Cherry_WXR_Parser();
		$parser_array = $parser->parse( $file_content, $file );

		return $parser_array;

	}

	/**
	 * Get parsed XML data
	 *
	 * @since  1.0.1
	 * @param  string $key
	 * @return mixed
	 */
	public function get_xml_data( $key ) {

		if ( ! $this->xml_file ) {
			return false;
		}

		$data = $this->_parse_xml( $this->xml_file );

		if ( isset( $data[$key] ) ) {
			return $data[$key];
		}

	}

	/**
	 * Add unserialized meta to prevent dropping HTML markup
	 *
	 * @param int    $post_id post ID
	 * @param string $key     meta key
	 * @param string $value   meta value
	 */
	public function fix_html_meta( $post_id, $key, $value ) {

		// esc HTML meta
		$value = preg_replace_callback(
			'/(\"fetures-text\";s:)(\d+)(:\")(.*)(\";s:5:\"price\")/s',
			array( $this, 'replace_html_meta' ),
			$value
		);

		$value = maybe_unserialize( $value );
		add_post_meta( $post_id, $key, $value );

		if ( null != $this->html_meta ) {
			$meta = get_post_meta( $post_id, $key, true );
			$meta['fetures-text'] = $this->html_meta;
			update_post_meta( $post_id, $key, $meta );
		}

	}

	/**
	 * Replace callback for HTML meta search
	 *
	 * @since  1.0.4
	 * @param  array $matches
	 * @return string
	 */
	public function replace_html_meta( $matches ) {

		if ( empty( $matches[4] ) ) {
			$this->html_meta = null;
			return $matches[0];
		}

		$this->html_meta = $matches[4];

		return $matches[1] . '0' . $matches[3] . $matches[5];
	}

}