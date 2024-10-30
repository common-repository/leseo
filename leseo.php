<?php
/**
Plugin Name: LeSeo
Plugin URI: https://www.lezaiyun.com/leseo.html
Description: LeSeo，一个比较全面、免费的WordPress SEO插件。公众号：老蒋朋友圈
Version: 0.5.2
Author: 老蒋和他的伙伴们
Author URI: https://www.lezaiyun.com/
Requires PHP: 7.0
 */
namespace lezaiyun\Leseo;

if (!defined('ABSPATH')) die();
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if (!class_exists('LESEO')) {
	class LESEO {
		private $option_var_name       = '_lezaiyun_leseo_option';       // 插件参数保存名称
		private $options;
		/**
		 * @var string
		 */
		private $base_folder;
		/**
		 * 一个关键字少于多少不替换;
		 * @var int
		 */
		private $match_num_from = 3;
		/**
		 * 一个关键字最多替换
		 * @var int
		 */
		private $match_num_to   = 2;


		public function __construct() {
			$this->includes();

			$this->options = get_option( $this->option_var_name );

			$this->base_folder = plugin_basename(dirname(__FILE__));
			$this->menu_slug       = [
				'setting_page'     => $this->base_folder . '/setting',
				'manually_submit'  => $this->base_folder . '/manually_submit',
				'check_baidu_page' => $this->base_folder . '/check_baidu',
			];

			$this->site    = site_url();

			add_action( 'laobuluo_bs_event', array($this, 'bs_cron_event') );

			register_deactivation_hook( __FILE__, array($this, 'leseo_deactivate') );  # 禁用时触发钩子

			//基础优化设置 - 禁用5.0编辑器
			if ( isset($this->options['leseo-gutenberg']) && $this->options['leseo-gutenberg'] == 1 ) {
				add_filter( 'use_block_editor_for_post_type', '__return_false' );
				remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
			}

			//基础优化设置 - 禁止自动保存文章
			if ( isset($this->options['leseo-autosave']) && $this->options['leseo-autosave'] == 1 ) {
				add_action( 'wp_print_scripts', array($this, 'leseo_no_autosave') );
			}

			//禁止WP自动升级
			if ( isset($this->options['leseo-autoupgrade']) && $this->options['leseo-autoupgrade'] == 1 ) {
				add_filter( 'automatic_updater_disabled', '__return_true' );
			}

			//禁止RSS订阅功能
			if ( isset($this->options['leseo-rssfeed']) && $this->options['leseo-rssfeed'] == 1 ) {
				add_action( 'do_feed', array($this, 'leseo_disable_feed'), 1 );
				add_action( 'do_feed_rdf', array($this, 'leseo_disable_feed'), 1 );
				add_action( 'do_feed_rss', array($this, 'leseo_disable_feed'), 1 );
				add_action( 'do_feed_rss2', array($this, 'leseo_disable_feed'), 1 );
				add_action( 'do_feed_atom', array($this, 'leseo_disable_feed'), 1 );
			}

			//禁止内容字符转码
			if ( isset($this->options['leseo-wptexturize']) && $this->options['leseo-wptexturize'] == 1 ) {
				add_filter( 'run_wptexturize', '__return_false' );
			}

			//禁止JSON
			if ( isset($this->options['leseo-wpjson']) && $this->options['leseo-wpjson'] == 1 ) {
				remove_action( 'wp_head', 'rest_output_link_wp_head' );
				remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
				remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );
			}

			//禁止小工具样式
			if ( isset($this->options['leseo-widgets-block-editor']) && $this->options['leseo-widgets-block-editor'] == 1 ) {
				add_action( 'after_setup_theme', array($this, 'leseo_example_theme_support') );
			}

			//禁止XML-RPC
			if ( isset($this->options['leseo-xmlrpc']) && $this->options['leseo-xmlrpc'] == 1 ) {
				add_filter( 'xmlrpc_enabled', '__return_false' );
			}

			//禁止离线编辑端口
			if ( isset($this->options['leseo-wlwmanifest']) && $this->options['leseo-wlwmanifest'] == 1 ) {
				remove_action( 'wp_head', 'rsd_link' );
				remove_action( 'wp_head', 'wlwmanifest_link' );
			}

			//禁止EMOJI表情
			if ( isset($this->options['leseo-emoji']) && $this->options['leseo-emoji'] == 1 ) {
				add_action( 'init', array($this, 'leseo_disable_emojis') );
			}

			// 功能优化
			// 上传图片重命名
			if ( isset($this->options['leseo-renameimg']) && $this->options['leseo-renameimg'] == 1 ) {
				add_filter( 'wp_handle_upload_prefilter', array($this, 'leseo_rename_upload_img') );
			}

			//禁止裁剪大图2560
			if ( isset($this->options['leseo-cropimage']) && $this->options['leseo-cropimage'] == 1 ) {
				add_filter( 'big_image_size_threshold', '__return_false' );
			}

			//禁止垃圾评论
			if ( isset($this->options['leseo-spamcomments']) && $this->options['leseo-spamcomments'] == 1 ) {
				add_filter( 'preprocess_comment', array($this, 'leseo_refused_spam_comments') );
			}

			//禁止生成缩略图  From https://perishablepress.com/disable-wordpress-generated-images/
			if ( isset($this->options['leseo-autothumbnail']) && $this->options['leseo-autothumbnail'] == 1 ) {
				add_action('intermediate_image_sizes_advanced', array($this, 'leseo_shapeSpace_disable_image_sizes'));
				// disable scaled image size
				add_filter( 'big_image_size_threshold', '__return_false' );
				add_action( 'init', array($this, 'leseo_shapeSpace_disable_other_image_sizes') );
			}

			//压缩HTML  From https://zhangge.net/4731.html
			if ( isset($this->options['leseo-compress-html']) && $this->options['leseo-compress-html'] == 1 ) {
				add_action( 'after_setup_theme', array($this, 'leseo_compress_html') );
			}

			//精简头部无用代码(持续增加)
			if ( isset($this->options['leseo-remove-header']) && $this->options['leseo-remove-header'] == 1 ) {
				remove_action( 'wp_head', 'index_rel_link' );//去除本页唯一链接信息
				remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); //清除前后文信息
				remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );//清除前后文信息
				remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );//清除前后文信息
				remove_action( 'wp_head', 'feed_links', 2 );//移除文章和评论feed
				remove_action( 'wp_head', 'feed_links_extra', 3 ); //移除分类等feed
				remove_action( 'wp_head', 'rel_canonical' ); //rel=canonical
				remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 ); //rel=shortlink
				remove_action( 'wp_head', 'wp_resource_hints', 2 );//移除dns-prefetch
			}

			//移除CSS JS版本号
			if ( isset($this->options['leseo-remove-cssjsversion']) && $this->options['leseo-remove-cssjsversion'] == 1 ) {
				add_filter( 'style_loader_src', array($this, 'leseo_remove_cssjs_ver'), 999 );
				add_filter( 'script_loader_src', array($this, 'leseo_remove_cssjs_ver'), 999 );
			}

			//禁止前端搜索功能  From https://hostpapasupport.com/disable-search-feature-wordpress/
			if ( isset($this->options['leseo-disable-search']) && $this->options['leseo-disable-search'] == 1 ) {
				add_action( 'parse_query', array($this, 'leseo_wpb_filter_query') );
				add_filter( 'get_search_form', function($a) { return null; } );
				add_action( 'widgets_init', array($this, 'leseo_remove_search_widget') );
			}

			// 粘贴图片本地化（功能优化）
			if ( isset($this->options['leseo-disable-uploadimg']) && $this->options['leseo-disable-uploadimg'] == 1 ) {
				// Todo: 粘贴图片本地化（功能优化）！ 暂时仍然并行进行，待优化超时的可能性。
				// 发布/草稿/预览时触发 - 优先级设置为大值，尽可能晚去执行。
				add_action('save_post', array($this, 'leseo_save_images_in_post'), 99999, 2);
			}

			// 移除图片 srcset 和 Size 标签（功能优化）
			if ( isset($this->options['leseo-disable-srcsetsize']) && $this->options['leseo-disable-srcsetsize'] == 1 ) {
				// Todo: 验证
				add_filter( 'max_srcset_image_width', function () { return 1; } );
			}

			// 关闭图片高度限制（功能优化）
			if ( isset($this->options['leseo-disable-widthheight']) && $this->options['leseo-disable-widthheight'] == 1 ) {
				// Todo: 验证
				add_filter( 'post_thumbnail_html', array($this, 'leseo_remove_width_attribute'), 10 );
				add_filter( 'image_send_to_editor', array($this, 'leseo_remove_width_attribute'), 10 );
			}

			// 移除 wp-block-library-css 样式（功能优化）
			if ( isset($this->options['leseo-disable-wpblock-librarycss']) && $this->options['leseo-disable-wpblock-librarycss'] == 1 ) {
				// Todo: 验证
				add_action( 'wp_enqueue_scripts', function () { wp_dequeue_style( 'wp-block-library' ); }, 100 );
			}

			// 禁止复制（功能优化）
			if ( isset($this->options['leseo-disable-copyrights']) && $this->options['leseo-disable-copyrights'] == 1 ) {
				// Todo: 验证
				add_action( 'wp_enqueue_scripts',
					function () {
						if ( ! current_user_can('edit_posts') ) {
							wp_enqueue_script('disable_copy_script', plugins_url('static/js/disable-copy.js', __FILE__), [], '1.0.0', true);
						}
					},
					100
				);
			}


			// SEO优化 - 基础优化
			// 页面添加反斜杠
			if ( isset($this->options['leseo-backslash']) && $this->options['leseo-backslash'] == 1 ) {
				add_filter( 'user_trailingslashit', array($this, 'leseo_nice_trailingslashit'), 10, 2 );
			}

			//隐藏分类Category
			if ( isset($this->options['leseo-remove-category']) && $this->options['leseo-remove-category'] == 1 ) {
				add_action( 'load-themes.php', array($this, 'leseo_no_category_base_refresh_rules') );
				add_action( 'created_category', array($this, 'leseo_no_category_base_refresh_rules') );
				add_action( 'edited_category', array($this, 'leseo_no_category_base_refresh_rules') );
				add_action( 'delete_category', array($this, 'leseo_no_category_base_refresh_rules') );
				register_deactivation_hook( __FILE__, array($this, 'leseo_no_category_base_deactivate') );
				add_action( 'init', array($this, 'leseo_no_category_base_permastruct') );  // Remove category base
				// Add our custom category rewrite rules
				add_filter( 'category_rewrite_rules', array($this, 'leseo_no_category_base_rewrite_rules') );
				// Add 'category_redirect' query variable
				add_filter( 'query_vars', array($this, 'leseo_no_category_base_query_vars') );
				// Redirect if 'category_redirect' is set
				add_filter( 'request', array($this, 'leseo_no_category_base_request') );
			}

			//内容图片加上ALT标签
			if ( isset($this->options['leseo-autoimgalt']) && $this->options['leseo-autoimgalt'] == 1 ) {
				add_filter( 'the_content', array($this, 'leseo_image_alt_tag'), 99999 );
			}

			//自动TAG内链
			if ( isset($this->options['leseo-autotaglink']) && $this->options['leseo-autotaglink'] == 1 ) {
				add_filter( 'the_content', array($this, 'leseo_tag_link'), 1 );
			}

			// TDK SEO
			if ( isset($this->options['leseo-selfseotdk']) && $this->options['leseo-selfseotdk'] ) {
				add_action( 'wp_head', array($this, 'leseo_seo'), 1 );
				add_filter( 'pre_get_document_title', array($this, 'leseo_pre_get_document_title') );
				if (isset($this->options['leseo-linkmark']) && $this->options['leseo-linkmark']) {
					add_filter('document_title_separator', array($this, 'leseo_document_title_separator'));
				}
			}

			// 网站地图
			if ( isset($this->options['leseo-selfsitemap']) && $this->options['leseo-selfsitemap'] ) {
				//禁止WordPress默认地图
				add_filter( 'wp_sitemaps_enabled', '__return_false' );
			}


			// 搜索推送
			if ( isset($this->options['leseo-submit-switch']) && $this->options['leseo-submit-switch'] ) {
				$this->leseo_meta_box_info     = [
					'id'               => 'leseo_baidu_submitter_meta_box_id',
					'title'            => '百度推送',
					'context'          => 'side',
					'priority'         => 'default',
					'nonce'            => [
						'action'       => 'leseo_baidu_submitter_nonce_action',
						'name'         => 'leseo_baidu_submitter_nonce',
					],
				];
				$this->leseo_submit_meta_key = 'is_leseo_baidu_submit';
				# 添加快速收录于普通收录勾选meta_box
				add_action( 'add_meta_boxes', array($this, 'leseo_add_baidu_submitter_meta_box') );

				add_action( 'save_post', array($this, 'leseo_save_baidu_submitter_post_data') );
			}

			// 页头页尾CSS代码插入（附加功能）
			if ( ! empty($this->options['leseo-code-header']) ) {
				// Todo: 验证
				// 头部JS
				$leseo_customize_code_header = $this->options['leseo-code-header'];
				add_action('wp_head', function () use ($leseo_customize_code_header) {
					echo sanitize_text_field($leseo_customize_code_header);
				});
			}
			if ( ! empty($this->options['leseo-code-footer']) ) {
				// 底部JS
				$leseo_customize_code_footer = $this->options['leseo-code-footer'];
				add_action('wp_footer', function () use ($leseo_customize_code_footer) {
					echo sanitize_text_field($leseo_customize_code_footer);
				});
			}
			if ( ! empty($this->options['leseo-code-cssjs']) ) {
				// 自定义CSS
				$leseo_customize_code_cssjs = $this->options['leseo-code-cssjs'];
				add_action('wp_head', function () use ($leseo_customize_code_cssjs) {
					echo '<style>' . sanitize_text_field($leseo_customize_code_cssjs) . '</style>';
				});
			}


		}

		private function includes() {
			require plugin_dir_path( __FILE__ ) . 'leseo-admin-options.php';  //获取后台主题选项参数
			include plugin_dir_path( __FILE__ ) . 'inc/baidu-submit/api.php';
			include plugin_dir_path( __FILE__ ) . 'inc/cache/LeCache.php';
		}
		public function leseo_deactivate() {
			// 停用插件
			$this->options = get_option( $this->option_var_name );
			if ( $this->options['Delete'] ) { delete_option( $this->option_var_name ); }
		}

		public function leseo_no_autosave() {
			wp_deregister_script( 'autosave' );
		}

		public function leseo_disable_feed() {
			wp_die( __( '网站关闭RSS订阅功能' ) );
		}

		public function leseo_example_theme_support() { remove_theme_support( 'widgets-block-editor' ); }

		public function leseo_disable_emojis() {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			add_filter( 'tiny_mce_plugins', array($this, 'leseo_disable_emojis_tinymce') );
		}

		public function leseo_disable_emojis_tinymce( $plugins ) {
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			} else {
				return array();
			}
		}


		public function leseo_rename_upload_img( $file ) {
			$time         = date( "Y-m-d H:i:s" );
			$file['name'] = $time . "" . mt_rand( 100, 999 ) . "." . pathinfo( $file['name'], PATHINFO_EXTENSION );
			return $file;
		}

		public function leseo_refused_spam_comments( $comment_data ) {
			// 评论中需要有中文 防止全部英文评论
			$pattern  = '/[一-龥]/u';
			$jpattern = '/[ぁ-ん]+|[ァ-ヴ]+/u';
			if ( ! preg_match( $pattern, $comment_data['comment_content'] ) ) {
				err( __( '评论中需要有一个汉字！' ) );
			}
			if ( preg_match( $jpattern, $comment_data['comment_content'] ) ) {
				err( __( '不能有日文！' ) );
			}
			return ( $comment_data );
		}

		public function leseo_shapeSpace_disable_image_sizes( $sizes ) {
			unset( $sizes['thumbnail'] );    // disable thumbnail size
			unset( $sizes['medium'] );       // disable medium size
			unset( $sizes['large'] );        // disable large size
			unset( $sizes['medium_large'] ); // disable medium-large size
			unset( $sizes['1536x1536'] );    // disable 2x medium-large size
			unset( $sizes['2048x2048'] );    // disable 2x large size
			return $sizes;
		}

		public function leseo_shapeSpace_disable_other_image_sizes() {
			// disable other image sizes
			remove_image_size( 'post-thumbnail' ); // disable images added via set_post_thumbnail_size()
			remove_image_size( 'another-size' );   // disable any other added image sizes
		}

		public function leseo_compress_html() {
			if (!is_user_logged_in()) {
				ob_start(
					function ( $buffer ) {
						$initial = strlen( $buffer );
						$buffer  = explode( "<!--wp-compress-html-->", $buffer );
						$count   = count( $buffer );
						$buffer_out = '';
						for ( $i = 0; $i < $count; $i ++ ) {
							if ( stristr( $buffer[ $i ], '<!--wp-compress-html no compression-->') ) {
								$buffer[ $i ] = ( str_replace( "<!--wp-compress-html no compression-->", " ", $buffer[ $i ] ) );
							} else {
								$buffer[ $i ] = ( str_replace( "\t", " ", $buffer[ $i ] ) );
								$buffer[ $i ] = ( str_replace( "\n\n", "\n", $buffer[ $i ] ) );
								$buffer[ $i ] = ( str_replace( "\n", "", $buffer[ $i ] ) );
								$buffer[ $i ] = ( str_replace( "\r", "", $buffer[ $i ] ) );
								while ( stristr( $buffer[ $i ], '  ' ) ) {
									$buffer[ $i ] = ( str_replace( "  ", " ", $buffer[ $i ] ) );
								}
							}
							$buffer_out .= $buffer[ $i ];
						}
						$final      = strlen( $buffer_out );
						$savings    = ( $initial - $final ) / $initial * 100;
						$savings    = round( $savings, 2 );
						$buffer_out .= "\n<!--压缩前的大小: $initial bytes; 压缩后的大小: $final bytes; 节约：$savings% -->";

						return $buffer_out;
					}
				);
			}
		}

		public function leseo_remove_cssjs_ver( $src ) {
			if ( strpos( $src, 'ver=' ) ) { $src = remove_query_arg( 'ver', $src ); }
			return $src;
		}

		public function leseo_wpb_filter_query( $query, $error = true ) {
			if ( is_search() && ! is_user_logged_in() ) {
				$query->is_search       = false;
				$query->query_vars[ 's' ] = false;
				$query->query[ 's' ]      = false;
				if ( $error ) { $query->is_404 = true; }
			}
		}

		public function leseo_remove_search_widget() {
			unregister_widget( 'WP_Widget_Search' );
		}


		/**
		 * 下载图片 & 替换正文内容
		 *
		 * @param $match_images :  图片本地绝对路径
		 * @param $wp_upload_dir : 图片mimetype
		 * @param $post :          Post
		 *
		 * @return mixed :         Post
		 */
		public function _leseo_curl_get_contents( $match_images, $wp_upload_dir, $post) {
			set_time_limit(1800);  // 脚本执行限制时间30分钟。若不能满足需求，后续可设置为可配置。
			$ch = curl_init();                                             // 创建会话

			// 设置请求选项
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // 使其以字符串形式返回数据而不是直接输出
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 取消SSL证书验证
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);   // 启用跟随location重定向
			curl_setopt($ch, CURLOPT_MAXREDIRS,20);           // 指定最大重定向次数为20
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);     // 指定连接超时时间为30秒

			foreach ($match_images[1] as $src) {
				// 当非本站图片时
				if (isset($src) && strpos($src, $_SERVER['HTTP_HOST']) === false) {
					curl_setopt($ch, CURLOPT_URL, $src);            // 设置cURL的URL选项为图片$url
					if ( ! wp_check_filetype( basename($src) )['ext'] ) { // file_info['ext' =>'扩展名','type' =>'类型']
						// 无扩展名和webp格式的图片无法判断类型
						$file_name = dechex(mt_rand(10000, 99999)) .'-'. date('YmdHis').'.tmp';
					} else {
						// 重命名图片防重复
						$file_name = dechex(mt_rand(10000, 99999)) . '-' . basename($src);
					}
					$file_path = $wp_upload_dir['path'] . '/' . $file_name;
					$fp = fopen($file_path, 'w');
					curl_setopt($ch, CURLOPT_FILE, $fp);            // 将响应写入到文件中
					curl_exec($ch);                                       // 执行会话并获取响应
					fclose($fp);

					if ( file_exists($file_path) && filesize($file_path) > 0 ) {
						// 将扩展名为tmp的图片转换为jpeg文件并重命名
						if ( pathinfo($file_path, PATHINFO_EXTENSION) == 'tmp' ) {
							$file_path = $this->_leseo_image_convert( $file_path );
						}

						$filename = basename($file_path);
						$new_src = $wp_upload_dir['url'] . '/' . $filename;

						// 替换文章内容中的src
						$post->post_content = str_replace($src, $new_src, $post->post_content);

						// 构造附件post参数并插入媒体库(作为一个post插入到数据库)
						$file_type = wp_check_filetype($filename);
						$attachment = array(
							'post_type' => 'attachement',
							'guid' => $new_src,
							'post_mime_type' => $file_type['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
							'post_content' => '',
							'post_status' => 'inherit'
						);

						// 生成并更新图片的metadata信息
						$attach_id = wp_insert_attachment( $attachment, ltrim($wp_upload_dir['subdir'] . '/' . $filename, '/'), 0 );
						if ( !function_exists('wp_generate_attachment_metadata') ) require ABSPATH . 'wp-admin/includes/image.php';
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );  // 生成wp图片的各种规格
						wp_update_attachment_metadata( $attach_id, $attach_data );                 // 更新metadata
					}
				}
			}
			curl_close($ch);  // 关闭会话

			return $post;
		}

		/**
		 * 转换未知格式的图片为jpg格式
		 *
		 * @param $non_type_image : 未知格式的图片本地绝对路径
		 *
		 * @return String 转换后的图片本地绝对路径
		 */
		public function _leseo_image_convert( $non_type_image): string {
			// 加载图片
			$image = imagecreatefromstring(file_get_contents($non_type_image));

			// 获得图片的宽度和高度
			$width = imagesx($image);
			$height = imagesy($image);

			// 创建新的JPG图片
			$jpeg = imagecreatetruecolor($width, $height);

			// 将未知类型的图片转换为JPG格式
			imagecopy($jpeg, $image, 0, 0, 0, 0, $width, $height);

			$new_image_path = str_replace('.tmp', '.jpeg', $non_type_image);
			if (imagejpeg($jpeg, $new_image_path, 100)) {
				try {
					unlink($non_type_image);
				} catch (Exception $e) {
					$error_msg = sprintf('删除本地文件失败 %s: %s', $image,
						$e->getMessage());
					error_log($error_msg);
				}
			} else {
				$new_image_path = $non_type_image;
			}
			// 释放内存
			imagedestroy($image);
			imagedestroy($jpeg);

			return $new_image_path;
		}



		/**
		 * 钩子函数：将post_content中本站服务器域名外的img上传至服务器并替换url
		 * 正文图片本地化（功能优化）
		 *
		 * @param $post_id : post id
		 * @param $post    : WP_Post对象
		 */
		public function leseo_save_images_in_post( $post_id, $post) {
			if($post->post_status == 'publish') {  // 触发发布时执行
				// 匹配<img>、src，存入$matches数组,
				$preg = '/<img.*[\s]src=[\"|\'](.*)[\"|\'].*>/iU';
				$num = preg_match_all($preg, $post->post_content, $matches);

				if ($num) {
					$post = $this->_leseo_curl_get_contents($matches, wp_upload_dir(), $post);
					global $wpdb;
					$wpdb->update( $wpdb->posts, array('post_content' => $post->post_content), array('ID' => $post->ID));
				}
			}
		}


		public function leseo_remove_width_attribute( $html ) {
			return preg_replace( '/(width|height)="\d*"\s/', "", $html );
		}


		public function leseo_nice_trailingslashit( $string, $type_of_url ) {
			if ( $type_of_url != 'single' ) { $string = trailingslashit( $string ); }
			return $string;
		}

		public function leseo_no_category_base_refresh_rules() {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}

		public function leseo_no_category_base_deactivate() {
			remove_filter( 'category_rewrite_rules', array($this, 'leseo_no_category_base_rewrite_rules') );
			$this->leseo_no_category_base_refresh_rules();
		}

		public function leseo_no_category_base_permastruct() {
			global $wp_rewrite, $wp_version;
			if ( version_compare( $wp_version, '3.4', '<' ) ) {
				// For pre-3.4 support
				$wp_rewrite->extra_permastructs['category'][0] = '%category%';
			} else {
				$wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
			}
		}

		public function leseo_no_category_base_rewrite_rules( $category_rewrite ) {
			$category_rewrite = array();
			$categories       = get_categories( array( 'hide_empty' => false ) );
			foreach ( $categories as $category ) {
				$category_nicename = $category->slug;
				if ( $category->parent == $category->cat_ID )// recursive recursion
				{
					$category->parent = 0;
				} elseif ( $category->parent != 0 ) {
					$category_nicename = get_category_parents( $category->parent, false, '/', true ) . $category_nicename;
				}
				$category_rewrite[ '(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
				$category_rewrite[ '(' . $category_nicename . ')/page/?([0-9]{1,})/?$' ]                  = 'index.php?category_name=$matches[1]&paged=$matches[2]';
				$category_rewrite[ '(' . $category_nicename . ')/?$' ]                                    = 'index.php?category_name=$matches[1]';
			}
			// Redirect support from Old Category Base
//			global $wp_rewrite;
			$old_category_base                                 = get_option( 'category_base' ) ? get_option( 'category_base' ) : 'category';
			$old_category_base                                 = trim( $old_category_base, '/' );
			$category_rewrite[ $old_category_base . '/(.*)$' ] = 'index.php?category_redirect=$matches[1]';

			return $category_rewrite;
		}

		public function leseo_no_category_base_query_vars( $public_query_vars ) {
			$public_query_vars[] = 'category_redirect';
			return $public_query_vars;
		}

		public function leseo_no_category_base_request( $query_vars ) {
			if ( isset( $query_vars['category_redirect'] ) ) {
				$catlink = trailingslashit( get_option( 'home' ) ) . user_trailingslashit( $query_vars['category_redirect'], 'category' );
				status_header( 301 );
				header( "Location: $catlink" );
				exit();
			}
			return $query_vars;
		}

	/**
	* 批量给 WordPress 没有Alt 和Title加上标签
	*/
		public function leseo_image_alt_tag($content) {
        global $post;
        $post_title = $post->post_title;
        $pattern = '/<img(.*?)\/>/i';
        preg_match_all($pattern, $content, $matches);
        foreach ($matches[0] as $index => $img_tag) {
            if (strpos($img_tag, ' alt=') === false || preg_match('/ alt=["\']\s*["\']/', $img_tag)) {
                $replacement = preg_replace('/<img/', '<img alt="' . $post_title . ' - 第' . ($index + 1) . '张" title="' . $post_title . ' - 第' . ($index + 1) . '张"', $img_tag);
                $content = str_replace($img_tag, $replacement, $content);
            }
        }
        return $content;
    }
    

		public function leseo_tag_link( $content ) {
			//改变标签关键字
			$post_tags = get_the_tags();
			if ( $post_tags ) {
				usort( $post_tags,
					function ( $a, $b ) {
						// 按长度排序
						if ( $a->name == $b->name ) { return 0; }
						return ( strlen( $a->name ) > strlen( $b->name ) ) ? - 1 : 1;
					});
				foreach ( $post_tags as $tag ) {
					$link    = get_tag_link( $tag->term_id );
					$keyword = $tag->name;
					//连接代码
					$clean_keyword = stripslashes( $keyword );
					$url           = "<a href=\"$link\" title=\"" . str_replace( '%s', addcslashes( $clean_keyword, '$' ), __( 'View all posts in %s' ) ) . "\"";
					$url           .= ' target="_blank" class="tag_link"';
					$url           .= ">" . addcslashes( $clean_keyword, '$' ) . "</a>";
					$limit         = rand( $this->match_num_from, $this->match_num_to );
					//不连接的代码
					$ex_word = ''; $case='';  // 保留口子，可以拓展
					$content      = preg_replace( '|(<a[^>]+>)(.*)(' . $ex_word . ')(.*)(</a[^>]*>)|U' . $case, '$1$2%&&&&&%$4$5', $content );
					$content       = preg_replace( '|(<img)(.*?)(' . $ex_word . ')(.*?)(>)|U' . $case, '$1$2%&&&&&%$4$5', $content );
					$clean_keyword = preg_quote( $clean_keyword, '\'' );
					$regEx         = '\'(?!((<.*?)|(<a.*?)))(' . $clean_keyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
					$content       = preg_replace( $regEx, $url, $content, $limit );
					$content      = str_replace( '%&&&&&%', stripslashes( $ex_word ), $content );
				}
			}
			return $content;
		}

		public function leseo_add_baidu_submitter_meta_box() {
			# 添加meta box模块
			$types = isset($this->options['leseo-resource-type']) && $this->options['leseo-resource-type']
				? $this->options['leseo-resource-type'] : array();
			foreach ( $types as $type ) {
				add_meta_box(
					$this->leseo_meta_box_info['id'],                       // Meta Box在前台页面源码中的id
					$this->leseo_meta_box_info['title'],                    // 显示的标题
					array($this, 'leseo_render_baidu_submitter_meta_box'),  // 回调方法，用于输出Meta Box的HTML代码
					$type,                                            // 在哪个post type页面添加
					$this->leseo_meta_box_info['context'],                  // 在哪显示该Meta Box
					$this->leseo_meta_box_info['priority']                  // 优先级
				);
			}
		}

		public function leseo_render_baidu_submitter_meta_box( $post ) {
			# 显示meta box的html代码
//			if ( !isset($this->options['leseo-resource-type']) && !$this->options['leseo-resource-type'] ) return;
			if ( !isset($this->options['leseo-resource-type']) ) return;

			// 添加 nonce 项用于save post时的安全检查
			wp_nonce_field( $this->leseo_meta_box_info['nonce']['action'], $this->leseo_meta_box_info['nonce']['name'] );

			$is_daily_html = '';
			$is_normal_html = '';

			// 检测是否存在已推送标签，若已推送，则更改展示。
			$meta_value = get_post_meta( $post->ID, $this->leseo_submit_meta_key, true );  # (optional) 如果设置为 true，返回单个值。

			$cache = new inc\cache\LeCache('remain');
			$_remain = $cache->get('remain');
			$_remain_daily = $cache->get('remain_daily');
			$remain = ($_remain and $_remain[1] > current_time('timestamp')) ? $_remain[0] : False;
			$remain_daily = ($_remain_daily and $_remain_daily[1] > current_time('timestamp')) ? $_remain_daily[0] : False;

			if ('normal' == $meta_value) {
				$html = '已提交普通收录';
			} elseif ('daily' == $meta_value) {
				$html = '已提交快速收录';
			} else {
				if( in_array('daily', $this->options['leseo-submit-type']) ) {
					$is_daily_html = '<input type="checkbox" name="daily_submit" ';
					if ( $remain_daily === False ) { $is_daily_html .= ' />快速收录  (剩余配额：10条)';
					} else if ( $remain_daily > 0 ) { $is_daily_html .= ' />快速收录  (剩余配额：' . $remain_daily . '条)';
					} else { $is_daily_html = '<span>快速收录配额已用完，请明天再试!</span>'; }
				}
				if( in_array('normal', $this->options['leseo-submit-type']) ) {
					$is_normal_html = '<input type="checkbox" name="normal_submit" checked="TRUE" ';
					if ( $remain === False ) { $is_normal_html .= ' />普通收录  (剩余配额：99999条)';
					} else if ( $remain>0 ) { $is_normal_html .= ' />普通收录  (剩余配额：' . $remain . '条)';
					} else { $is_normal_html = '<span>普通收录配额已用完，请明天再试!</span>'; }
				}
				$html = $is_daily_html . '<br />' . $is_normal_html;
				if ( strlen($meta_value) > 0 ) { $html .= '<br /><p>' . $meta_value . '</p>'; }
			}
			echo esc_html( $html );
		}

		public function leseo_save_baidu_submitter_post_data( $post_id ) {
			# 处理
			if ( !isset($this->options['leseo-submit-switch']) || !$this->options['leseo-submit-switch']) return False;
			// 如果是系统自动保存，则不操作
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

			$post = get_post($post_id);
			if ($post->post_status != 'publish') return $post_id;

			$types = isset($this->options['leseo-resource-type']) && $this->options['leseo-resource-type']
				? $this->options['leseo-resource-type'] : array();
			if ( !in_array($post->post_type, $types, True) ) return $post_id;

			// 检查nonce是否设置
			if (!isset($_POST[$this->leseo_meta_box_info['nonce']['name']]))  return $post_id;

			$nonce = $_POST[$this->leseo_meta_box_info['nonce']['name']];
			// 验证nonce是否正确
			if (!wp_verify_nonce( $nonce, $this->leseo_meta_box_info['nonce']['action'])) return $post_id;

			// 检查用户权限
			if ($_POST['post_type'] == 'post') {
				if (!current_user_can('edit_post', $post_id )) return $post_id;
			}

			if ( isset($_POST['normal_submit']) ) $meta_value = 'normal';
			if ( isset($_POST['daily_submit']) ) $meta_value = 'daily';

			# 生成页面时，提交推送成功的将不会生成meta_value, 省去get_post_meta验证，若有问题需严谨地取值验证。
			if ( isset($meta_value) ) {
				$post_link = get_permalink($post_id);
				if ($post_link) {
					if (isset($this->options['leseo-submit-bdtoken']) && $this->options['leseo-submit-bdtoken'] ) {
						$urls_array = array($post_link);
						$baidu   = new BaiduSubmit\LeoBaiduSubmitter(['token' => $this->options['leseo-submit-bdtoken']], site_url());
						$resp = $baidu->request($meta_value, $urls_array);

						if ( !is_wp_error( $resp ) ) {
							if ($resp->error === Null) {
								// 更新数据，第四个参数pre_value，用于指定之前的值替换，暂时先不添加
								update_post_meta( $post_id, $this->leseo_submit_meta_key, $meta_value );

								$data = $meta_value == 'daily' ? $resp->remain_daily : $resp->remain;
								$cache = new inc\Cache\LeCache('remain');
								$cache->set($meta_value, $data);
							}  else {
								update_post_meta( $post_id, $this->leseo_submit_meta_key, $resp->message );
							}
						} else {
							update_post_meta( $post_id, $this->leseo_submit_meta_key, show_message($resp) );
						}
					} else {
						update_post_meta( $post_id, $this->leseo_submit_meta_key, 'API TOKEN未设置，无法推送！' );
					}
				}
			}
		}

		public function leseo_seo() {
			global $post, $wp_query;
			$keywords    = '';
			$description = '';
			$seo         = '';
			if ( isset( $this->options['leseo-selfseotdk'] ) && $this->options['leseo-selfseotdk'] ) {
				$open_graph = ! isset( $this->options['leseo-opengraph'] ) || $this->options['leseo-opengraph'];

				if ( is_singular() && ! is_front_page() ) {
					# 是内容页 && 不是第一页
					global $paged;
					if ( ! $paged ) { $paged = 1; }

					$singular_meta_options = get_post_meta( $post->ID, 'leseo_singular_meta_options', true );  // 必须带true，下面tax参数那里一样

					if ( isset($singular_meta_options['leseo-singular-meta-switcher'])
					     && $singular_meta_options['leseo-singular-meta-switcher'] ) {
						$keywords    = str_replace( '，', ',',
							trim( strip_tags( $singular_meta_options['leseo-singular-meta-keywords'] ?? $keywords ) )
						);
						$description = trim( strip_tags( $singular_meta_options['leseo-singular-meta-description'] ?? $description ) );

						if ( $keywords ) {
							$seo .= '<meta name="keywords" content="' . esc_attr( $keywords ) . '" />' . "\n";
						}
						if ( $description ) {
							$seo .= '<meta name="description" content="' . esc_attr( trim( strip_tags( $description ) ) ) . '" />' . "\n";
						}
					}

					$url = get_pagenum_link( $paged );

					$meta_image_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail');
					$image = $meta_image_url ? $meta_image_url[0] : false;

					$type = is_singular( 'page' ) ? 'page' : 'post';

					// OG
					if ( $open_graph ) {
						$post_title = $wp_query->get( 'qa_cat' ) && $wp_query->get( 'title' )
							? $wp_query->get( 'title' ) : $post->post_title;
						$seo       .= '<meta property="og:type" content="' . $type . '" />' . "\n";
						$seo       .= '<meta property="og:url" content="' . $url . '" />' . "\n";
						$seo       .= '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( "name" ) ) . '" />' . "\n";
						$seo       .= '<meta property="og:title" content="' . esc_attr( $post_title ) . '" />' . "\n";
						if ( $image ) {
							$seo   .= '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
						}
						if ( $description ) {
							$seo   .= '<meta property="og:description" content="' . esc_attr( trim( strip_tags( $description ) ) ) . '" />' . "\n";
						}
					}
				} else if ( is_home() || is_front_page() ) {
					# 首页 or 第一页
					global $page;
					if ( ! $page ) { $page = 1; }

					$keywords    = $this->options['leseo-selfindexkeywords'] ?? '';
					$description = $this->options['leseo-selfindexdesc'] ?? get_bloginfo( 'description' );
					$keywords    = str_replace( '，', ',', trim( strip_tags( $keywords ) ) );
					$description = trim( strip_tags( $description ) );

					if ( $keywords ) {
						$seo    .= '<meta name="keywords" content="' . esc_attr( $keywords ) . '" />' . "\n";
					}
					if ( $description ) {
						$seo    .= '<meta name="description" content="' . esc_attr( trim( strip_tags( $description ) ) ) . '" />' . "\n";
					}

					$url = get_pagenum_link( $page );

					$image = '';
					$title = $this->options['leseo-selfindextitle'] ?? '';

					if ( $title == '' ) {
						$desc = get_bloginfo( 'description' );
						if ( $desc ) {
							$title = get_option( 'blogname' ) . ( $options['leseo-linkmark'] ?? ' - ' ) . $desc;
						} else {
							$title = get_option( 'blogname' );
						}
					}

					if ( $open_graph ) {
						$seo .= '<meta property="og:type" content="webpage" />' . "\n";
						$seo .= '<meta property="og:url" content="' . $url . '" />' . "\n";
						$seo .= '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( "name" ) ) . '" />' . "\n";
						$seo .= '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
						if ( $image ) {
							$seo .= '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
						}
						if ( $description ) {
							$seo .= '<meta property="og:description" content="' . esc_attr( trim( strip_tags( $description ) ) ) . '" />' . "\n";
						}
					}
				} else if ( is_category() || is_tag() ) {  # || is_tax()
					global $paged, $cat;
					if ( ! $paged ) { $paged = 1; }

					# single_cat_title( '', false )  single_tag_title('', false) 两个函数等效

					if ( is_category() ) {
						$taxonomy_meta_options = get_term_meta( $cat, 'leseo_taxonomy_meta_options', true );  # 如果只需要分类, $cat 变量获取最简单
					}
					if ( is_tag() ) {
						$term = single_tag_title('', false)
							? get_term_by('name', single_tag_title('', false), 'post_tag')
							: '';
						$taxonomy_meta_options = get_term_meta( $term->term_id, 'leseo_taxonomy_meta_options', true );
					}

					if ( isset($taxonomy_meta_options['leseo-taxonomy-meta-switcher'])
					     && $taxonomy_meta_options['leseo-taxonomy-meta-switcher'] ) {
						$keywords    = str_replace( '，', ',', trim( strip_tags( $taxonomy_meta_options['leseo-taxonomy-meta-keywords'] ?? $keywords ) ) );
						$description = trim( strip_tags( $taxonomy_meta_options['leseo-taxonomy-meta-description'] ?? $description ) );
						if ( $keywords ) {
							$seo .= '<meta name="keywords" content="' . esc_attr( $keywords ) . '" />' . "\n";
						}
						if ( $description ) {
							$seo .= '<meta name="description" content="' . esc_attr( trim( strip_tags( $description ) ) ) . '" />' . "\n";
						}
					}

					$url   = get_pagenum_link( $paged );
					$image = '';
					if ( $open_graph ) {
						$seo .= '<meta property="og:type" content="article" />' . "\n";
						$seo .= '<meta property="og:url" content="' . $url . '" />' . "\n";
						$seo .= '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( "name" ) ) . '" />' . "\n";
						$seo .= '<meta property="og:title" content="' . esc_attr( single_cat_title( '', false ) ) . '" />' . "\n";
						if ( $image ) {
							$seo .= '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
						}
						if ( $description ) {
							$seo .= '<meta property="og:description" content="' . esc_attr( trim( strip_tags( $description ) ) ) . '" />' . "\n";
						}
					}
				}
			}

			// 开启Canonical
			if ( isset( $this->options['leseo-canonical'] ) && $this->options['leseo-canonical'] && is_singular() ) {
				$id = get_queried_object_id();
				if ( 0 !== $id && $url = wp_get_canonical_url( $id ) ) {
					$seo .= '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
				}
			}

			echo $seo;
		}

		public function leseo_document_title_separator ($sep) {
			return $this->options['leseo-linkmark'];
		}

		public function leseo_pre_get_document_title ( $title ) {
			global $paged, $page, $post;
			if ( ( is_home() || is_front_page() ) && isset( $this->options['leseo-selfindextitle'] ) ) {
				return $this->options['leseo-selfindextitle'];
			}
			if ( is_singular() && $post->post_title) {
				if ( isset($this->options['leseo-pageandsitename']) && $this->options['leseo-pageandsitename'] )  {
					$title = $post->post_title;
				}

				$singular_meta_options = get_post_meta( $post->ID, 'leseo_singular_meta_options', true );  // 带true 少一层数组嵌套
				if ( isset( $singular_meta_options['leseo-singular-meta-switcher'] )
				     && $singular_meta_options['leseo-singular-meta-switcher']
				     && isset( $singular_meta_options['leseo-singular-meta-title']) ) {
					$title =  $singular_meta_options['leseo-singular-meta-title'];
				}
			}
			if ( is_category() || is_tag() ) {
				if ( ! $paged ) { $paged = 1; }

				$current_tax_name = single_cat_title( '', false );
				if ( $current_tax_name ) {
					$current_taxonomy      = is_tag() ? 'post_tag' : 'category';  # 默认category
					$term                  =  get_term_by( 'name', $current_tax_name, $current_taxonomy );
					$taxonomy_meta_options = get_term_meta( $term->term_id, 'leseo_taxonomy_meta_options', true );

					if ( isset($taxonomy_meta_options['leseo-taxonomy-meta-switcher'])
					     && $taxonomy_meta_options['leseo-taxonomy-meta-switcher']
					     && isset($taxonomy_meta_options['leseo-taxonomy-meta-title']) ) {

						if ( $paged >= 2 || $page >= 2 ) // 增加页数
						{
							$sep = $options['leseo-linkmark'] ?? ' - ';
							$taxonomy_meta_options['leseo-taxonomy-meta-title'] .= $sep . sprintf( __( 'Page %s', 'LeSEO' ), max( $paged, $page ) );
						}

						$title = $taxonomy_meta_options['leseo-taxonomy-meta-title'];
					}
				}
			}
			return $title;
		}


		// 页头页尾CSS代码插入（附加功能）


	}

	global $LESEO;
	$LESEO = new LESEO();
}
