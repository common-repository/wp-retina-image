<?php
/*
 * @package WP_Retina_Image
*/


include_once('WpRetinaImage_LifeCycle.php');

class WpRetinaImage_Plugin extends WpRetinaImage_LifeCycle {

	/**
	 * See: http://plugin.michael-simpson.com/?page_id=31
	 * @return array of option meta data.
	 */
	public function getOptionMetaData() {
		//  http://plugin.michael-simpson.com/?page_id=31
		return array(
			'srcset_1x2x' => array(__('Set the value of srcset to 1x, 2x (not recommended)', 'wp-retina-image'), 'false', 'true'),
		);
	}
	public function _l($message){
		if (is_array($message) || is_object($message)) {
			error_log(print_r($message, true));
		}
		else {
			error_log($message);
		}
	}

	protected function initOptions() {
		$options = $this->getOptionMetaData();
		if (!empty($options)) {
			foreach ($options as $key => $arr) {
				if (is_array($arr) && count($arr > 1)) {
					$this->addOption($key, $arr[1]);
				}
			}
		}
	}

	public function getPluginDisplayName() {
		return 'WP Retina Image';
	}

	protected function getMainPluginFileName() {
		return 'wp-retina-image.php';
	}

	/**
	 * See: http://plugin.michael-simpson.com/?page_id=101
	 * Called by install() to create any database tables if needed.
	 * Best Practice:
	 * (1) Prefix all table names with $wpdb->prefix
	 * (2) make table names lower case only
	 * @return void
	 */
	protected function installDatabaseTables() {
		//        global $wpdb;
		//        $tableName = $this->prefixTableName('mytable');
		//        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
		//            `id` INTEGER NOT NULL");
	}

	/**
	 * See: http://plugin.michael-simpson.com/?page_id=101
	 * Drop plugin-created tables on uninstall.
	 * @return void
	 */
	protected function unInstallDatabaseTables() {
		//        global $wpdb;
		//        $tableName = $this->prefixTableName('mytable');
		//        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
	}


	/**
	 * Perform actions when upgrading from version X to version Y
	 * See: http://plugin.michael-simpson.com/?page_id=35
	 * @return void
	 */
	public function upgrade() {
	}

	public function addActionsAndFilters() {

		// Add options administration page
		// http://plugin.michael-simpson.com/?page_id=47
		add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

		add_filter('image_size_names_choose', array(&$this, 'add_image_size_names') );
		add_filter('wp_handle_upload', array(&$this, 'add_image_size') );
		add_filter('image_resize_dimensions', array(&$this, 'resize_dimensions'), 10, 6 );
		add_filter('wp_prepare_attachment_for_js', array(&$this, 'rplace_attachment_for_js') );
		add_filter('get_image_tag', array(&$this, 'change_image_tag'), 10, 6);

		if('true' == $this->getOption('srcset_1x2x')){
			add_filter('wp_calculate_image_srcset', array(&$this, 'calculate_image_srcset'), 10, 5);
		}

	}
	// メディアライブラリに倍解像度画像サイズを登録する
	public function add_image_size_names( $sizes ) {
		// NOTO: この文言を入れたら2xに対応してない画像にも表示されちゃうからやめる
		// $sizes['full'] = $sizes['full'].__('(All 2x images)', 'wp-retina-image');
		return array_merge( $sizes, array(
			'1x'   => __('1x size', 'wp-retina-image'),
			// '2x'   => __('2x size(All 2x images)', 'wp-retina-image'),
		) );
	}
	// アップロードするファイル名によってサイズを指定する
	public function add_image_size($file){
		// 画像ファイル以外は飛ばす
		if(!preg_match("/^image\/png|jpe?g|gif$/", $file['type'])){
			return $file;
		}
		// 名前に「@2x」がない場合も無視
		if(preg_match('/@\dx/',$file['file']) == 0){
			return $file;
		}

		$size = getimagesize($file['file']);
		$width = $size[0];
		$height = $size[1];
		// 倍率取得
		preg_match("/@(\d)x./",$file['file'],$rearr);
		if ( count($rearr) <= 1 ){
			return $file;
		}
		$r = $rearr[1];
		add_image_size("{$r}x", $width-1, $height, false);
		add_image_size("1x", $width/$r, $height/$r, false);
		// 1px小さめにしないと登録されない...
		// resize_dimensionsの方で調整
		// (オリジナルとサイズが同じ場合サイズとして登録されない問題)
		return $file;
	}

	// add_filter(image_resize_dimensions)
	public function resize_dimensions( $payload, $orig_w, $orig_h, $dest_w, $dest_h, $crop ){
		// オリジナルとサイズが同じ場合サイズとして登録されない問題解決
		if($dest_w === $orig_w - 1 && $orig_h === $dest_h && !$crop){
			return array( 0, 0, 0, 0, $orig_w, $orig_h, $orig_w, $orig_h );
		}
	}
	// 編集画面で画像挿入時に1xの画像を挿入する
	function change_image_tag($html, $id, $alt, $title, $align, $size) {

		if (in_array($size, array('full','1x'))) {
			// クラス抽出
			if(preg_match('/class=["\'](?<value>.*)?["\']/', $html, $class)) {
				// サイズ抽出
				if(preg_match('/size-(?<value>.*)?[ $]/', $class['value'], $psize)) {
					$size_str = $psize['value'];
					// self::_l(print_r($html,true));
					if($size_str == "1x"){
						// 1xサイズは半分のサイズにする
						$metadata = wp_get_attachment_metadata($id);
						if(array_key_exists('1x', $metadata['sizes'])){

							//1xのサイズに変える
							if(preg_match('/^(?<before>.*width=["\'])(?<value>[\d]+)(?<after>["\'].*)$/', $html, $m)) {
								$val = $metadata['sizes']['1x']['width'];
								$html = $m['before'].$val.$m['after'];
							}
							if(preg_match('/^(?<before>.*height=["\'])(?<value>[\d]+)(?<after>["\'].*)$/', $html, $m)) {
								$val = $metadata['sizes']['1x']['height'];
								$html = $m['before'].$val.$m['after'];
							}
						}
					}
				}
			}
		}
		return $html;
	}
	// メディア挿入画面のフルサイズのサイズ表示を変える
	function rplace_attachment_for_js($response) {
		if (array_key_exists('sizes',$response) && array_key_exists('full', $response['sizes']) ){
			if (array_key_exists('2x', $response['sizes'])) {
				$width  = round($response['sizes']['2x']['width']/2);
				$height = round($response['sizes']['2x']['height']/2);
				$response['sizes']['full']['width'] = $width;
				$response['sizes']['full']['height'] = $height;

				$response['sizes']['2x']['width']  = $response['width'];
				$response['sizes']['2x']['height'] = $response['height'];
			}
		}
		return $response;

	}

	// imgを表示する際にsrcsetが自動で作成されるのを改修
	function calculate_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id ){

		// Retina対応の場合
		if( array_key_exists('1x', $image_meta['sizes'] ) ){
			$options = $this->getOptionMetaData();
			if(strpos($image_src, $image_meta['sizes']['1x']['file']) !== false ){
				// 1x指定の場合
				$image_url = wp_get_upload_dir()['url'];
				$sources = array();
				$sources[ 0 ] = array(
						'url'        => $image_url. "/".$image_meta['sizes']['1x']['file'],
						'descriptor' => 'x',
						'value'      => 1,
				);
				if( array_key_exists('2x', $image_meta['sizes'] ) ){
					$sources[ 1 ] = array(
							'url'        => $image_url. "/".$image_meta['sizes']['2x']['file'],
							'descriptor' => 'x',
							'value'      => 2,
					);
				}
			}
		}

		return $sources;
	}



}
