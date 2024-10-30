<?php
//WordPress csf options
if ( ! class_exists( 'CSF' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'inc/codestar-framework/codestar-framework.php';
}


/**
 * Custom function for get an option
 */
if ( ! function_exists( 'get_lezaiyun_leseo_opt' ) ) {
	function get_lezaiyun_leseo_opt( $option = '', $default = null ) {
		$options_meta = '_lezaiyun_leseo_option';
		$options      = get_option( $options_meta );

		return ( isset( $options[ $option ] ) ) ? $options[ $option ] : $default;
	}
}


function lezaiyun_leseo_option_init( $params ) {

	$params['framework_title'] = 'LeSEO - 一个有温度的WP性能优化插件';
	$params['menu_title']      = 'LeSEO设置';
	$params['theme']           = 'light'; //  light OR dark
	$params['show_bar_menu']   = false;
	$params['enqueue_webfont'] = false;
	$params['enqueue']         = false;
	$params['show_search']     = false;

	return $params;
}
add_filter( 'csf__lezaiyun_leseo_option_args', 'lezaiyun_leseo_option_init' );


$leseo_robots_filename = 'robots.txt';
function leseo_robots_switch( $params, $leseo_robots_filename = 'robots.txt') {
	if ( isset($params['leseo-robots-switch']) && $params['leseo-robots-switch'] ) {
		if ( isset($params['leseo-robots-content']) ) {
			file_put_contents(join(DIRECTORY_SEPARATOR, [ $_SERVER['DOCUMENT_ROOT'], $leseo_robots_filename ]),
				sanitize_textarea_field( $params['leseo-robots-content']) );
			$params['leseo-robots-content'] = null;
		}
	}
	return $params;
}
add_filter( 'csf__lezaiyun_leseo_option_save', 'leseo_robots_switch' );


// Control core classes for avoid errors
if ( class_exists( 'CSF' ) ) {

	// Set a unique slug-like ID
	$prefix = '_lezaiyun_leseo_option';

	// Create options
	CSF::createOptions( $prefix, array(
		'menu_title' => 'LeSeo插件',
		'menu_slug'  => 'lezaiyun-leseo-options',
	) );

	// 菜单清单
	// 基础优化
	CSF::createSection( $prefix, array(
		'title'  => '基础优化',
		'icon'   => 'fa fa-clipboard',
		'fields' => array(
			array(
				'type'    => 'heading',
				'content' => 'WP基础优化设置',
			),
			array(
				'type'    => 'notice',
				'style'   => 'info',
				'content' => '通过简单的开关键有选择的优化WP程序自身的功能',
			),

			array(
				'id'         => 'leseo-gutenberg',
				'type'       => 'switcher',
				'title'      => '禁止古登堡编辑器',
				'label'      => '默认启用WP自带古登堡编辑器，不喜欢可以关闭',
				'text_off'   => '点击关闭古登堡',
				'text_on'    => '点击开启古登堡',
				'text_width' => 140,
			),
			array(
				'id'         => 'leseo-autosave',
				'type'       => 'switcher',
				'title'      => '禁止文章自动保存',
				'label'      => '编辑文章会自动保存修订版本，不喜欢可以关闭',
				'text_off'   => '点击关闭自动保存',
				'text_on'    => '点击开启自动保存',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-autoupgrade',
				'type'       => 'switcher',
				'title'      => '禁止自动升级版本',
				'label'      => '禁止WordPress自动升级新版本，我们可选择手动升级',
				'text_off'   => '点击关闭自动升级',
				'text_on'    => '点击开启自动升级',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-rssfeed',
				'type'       => 'switcher',
				'title'      => '禁止RSS订阅网站',
				'label'      => '禁止WP网站被RSS阅读器订阅和采集',
				'text_off'   => '点击关闭RSS订阅',
				'text_on'    => '点击开启RSS订阅',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-wptexturize',
				'type'       => 'switcher',
				'title'      => '禁止字符转码',
				'label'      => '禁止纯文本字符转换成格式化的HTML符号',
				'text_off'   => '点击关闭字符转码',
				'text_on'    => '点击开启字符转码',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-wpjson',
				'type'       => 'switcher',
				'title'      => '禁止JSON',
				'label'      => '禁止WP-JSON和REST API外部调用，减少爬虫抓取',
				'text_off'   => '点击关闭JSON',
				'text_on'    => '点击开启JSON',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-widgets-block-editor',
				'type'       => 'switcher',
				'title'      => '禁止新小工具样式',
				'label'      => '禁止新小工具样式，恢复原始小工具',
				'text_off'   => '点击关闭小工具样式',
				'text_on'    => '点击开启小工具样式',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-xmlrpc',
				'type'       => 'switcher',
				'title'      => '禁止XML-RPC',
				'label'      => '禁止XML-RPC减少网站被爬虫和软件抓取占用负载',
				'text_off'   => '点击关闭RPC',
				'text_on'    => '点击开启RPC',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-wlwmanifest',
				'type'       => 'switcher',
				'title'      => '禁止离线编辑端口',
				'label'      => '禁止且移除移除离线编辑器端口，防止外部推送文章',
				'text_off'   => '点击关闭离线编辑',
				'text_on'    => '点击开启离线编辑',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-emoji',
				'type'       => 'switcher',
				'title'      => '禁止EMOJI表情',
				'label'      => '禁止EMOJI表情表情包，减少站内体积',
				'text_off'   => '点击关闭EMOJI表情',
				'text_on'    => '点击开启EMOJI表情',
				'text_width' => 140,

			),


		)
	) );

	// 功能优化
	CSF::createSection( $prefix, array(
		'title'  => '功能优化',
		'icon'   => 'fa fa-rocket',
		'fields' => array(
			array(
				'type'    => 'heading',
				'content' => 'WP功能优化设置',
			),
			array(
				'type'    => 'notice',
				'style'   => 'info',
				'content' => 'WP简单的功能优化设置，减少站内负载，加速优化代码提速',
			),

			array(
				'id'         => 'leseo-renameimg',
				'type'       => 'switcher',
				'title'      => '上传图片重命名',
				'label'      => '上传图片按照时间戳重命名，防止用户中文名上传',
				'text_off'   => '点击开启图片重命名',
				'text_on'    => '点击关闭图片重命名',
				'text_width' => 140,
			),

			array(
				'id'         => 'leseo-cropimage',
				'type'       => 'switcher',
				'title'      => '禁止裁剪大图',
				'label'      => '禁止上传大图的时候被WP自动裁剪（大于2560）',
				'text_off'   => '点击关闭裁剪大图',
				'text_on'    => '点击开启裁剪大图',
				'text_width' => 140,
			),
			array(
				'id'         => 'leseo-spamcomments',
				'type'       => 'switcher',
				'title'      => '禁止垃圾评论',
				'label'      => '通过特定的限制来阻止被人工和机器自动评论，减少数据库和页面负载',
				'text_off'   => '点击关闭垃圾评论',
				'text_on'    => '点击开启垃圾评论',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-autothumbnail',
				'type'       => 'switcher',
				'title'      => '禁止生成缩略图',
				'label'      => '禁止WP自动生成上传图片后的缩略图，减少服务器资源占用',
				'text_off'   => '点击关闭生成缩略图',
				'text_on'    => '点击开启生成缩略图',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-compress-html',
				'type'       => 'switcher',
				'title'      => '压缩网站HTML',
				'label'      => '压缩网站前端HTML代码，降低网站体积提高访问速度',
				'text_off'   => '点击开启压缩HTML',
				'text_on'    => '点击关闭压缩HTML',
				'text_width' => 140,

			),
			/****
			 * CSS和JS压缩放到后期的加速插件里
			 * array(
			 * 'id' => 'leseo-compress-css',
			 * 'type' => 'switcher',
			 * 'title' => '压缩CSS样式',
			 * 'label' => '压缩网站CSS样式降低CSS样式表体积',
			 * 'text_off' => '点击开启压缩CSS',
			 * 'text_on' => '点击关闭压缩CSS',
			 * 'text_width' => 140,
			 *
			 * ),array(
			 * 'id' => 'leseo-compress-js',
			 * 'type' => 'switcher',
			 * 'title' => '压缩JS体积',
			 * 'label' => '压缩网站JS脚本体积，降低整体网站的体积提高速度',
			 * 'text_off' => '点击开启压缩JS',
			 * 'text_on' => '点击关闭压缩JS',
			 * 'text_width' => 140,
			 *
			 * ),
			 */
			array(
				'id'         => 'leseo-remove-header',
				'type'       => 'switcher',
				'title'      => '精简头部代码',
				'label'      => '一键精简头部不必须的代码，降低网站的体积',
				'text_off'   => '点击开启精简代码',
				'text_on'    => '点击关闭精简代码',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-remove-cssjsversion',
				'type'       => 'switcher',
				'title'      => '移除CSS/JS版本号',
				'label'      => '移除CSS、JS后缀版本号和WP版本号，精简代码',
				'text_off'   => '点击关闭版本号',
				'text_on'    => '点击开启版本号',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-disable-search',
				'type'       => 'switcher',
				'title'      => '禁止前端搜索',
				'label'      => '禁止用户前端搜索站内内容，根据需要改用站外搜索接口',
				'text_off'   => '点击关闭站内搜索',
				'text_on'    => '点击开启站内搜索',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-disable-uploadimg',
				'type'       => 'switcher',
				'title'      => '图片自动本地',
				'label'      => '复制外部图片粘贴到编辑器自动本地化上传',
				'text_off'   => '点击关闭',
				'text_on'    => '点击开启',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-disable-copyrights',
				'type'       => 'switcher',
				'title'      => '禁止复制右键',
				'label'      => '禁止内容被复制和右键',
				'text_off'   => '点击关闭',
				'text_on'    => '点击开启',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-disable-wpblock-librarycss',
				'type'       => 'switcher',
				'title'      => '移除编辑器样式',
				'label'      => '移除 wp-block-library-css 样式，提高加载速度',
				'text_off'   => '点击关闭',
				'text_on'    => '点击开启',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-disable-widthheight',
				'type'       => 'switcher',
				'title'      => '图片限制高度',
				'label'      => '解除默认图片限制高度和宽度',
				'text_off'   => '点击关闭',
				'text_on'    => '点击开启',
				'text_width' => 140,

			),
			array(
				'id'         => 'leseo-disable-srcsetsize',
				'type'       => 'switcher',
				'title'      => '响应图片尺寸',
				'label'      => '移除图片响应式尺寸 srcset 和 Size 标签',
				'text_off'   => '点击关闭',
				'text_on'    => '点击开启',
				'text_width' => 140,

			),

		)
	) );


	// SEO优化
	CSF::createSection( $prefix, array(
		'id'    => 'leseo-seo-fields',
		'title' => 'SEO优化',
		'icon'  => 'fa fa-square',
	) );

//
// SEO基础优化
//
	CSF::createSection( $prefix, array(
		'parent' => 'leseo-seo-fields',
		'title'  => '基础优化',
		'icon'   => 'far fa-square',
		'fields' => array(

			array(
				'type'    => 'heading',
				'content' => '基础SEO设置',
			),

			array(
				'type'    => 'notice',
				'style'   => 'info',
				'content' => '简单的勾选和设置，完成部分通用的SEO设置',
			),

			array(
				'id'         => 'leseo-backslash',
				'type'       => 'switcher',
				'title'      => '页面添加反斜杠',
				'label'      => '分类和别名文章添加反斜杠URL结尾，提高SEO体验',
				'text_off'   => '点击开启反斜杠',
				'text_on'    => '点击关闭反斜杠',
				'text_width' => 140,

			),

			array(
				'id'         => 'leseo-remove-category',
				'type'       => 'switcher',
				'title'      => '隐藏分类Category',
				'label'      => '隐藏分类Category分类字符，缩短SEO URL的长度',
				'text_off'   => '点击隐藏Category',
				'text_on'    => '点击开启Category',
				'text_width' => 140,

			),

			array(
				'id'         => 'leseo-autoimgalt',
				'type'       => 'switcher',
				'title'      => '图片自动添加ALT',
				'label'      => '自动图片添加ALT描述文本，提高图片的SEO效果',
				'text_off'   => '点击开启图片ALT',
				'text_on'    => '点击关闭图片ALT',
				'text_width' => 140,

			),

			array(
				'id'         => 'leseo-autotaglink',
				'type'       => 'switcher',
				'title'      => '开启自动TAG内链',
				'label'      => '自动为文章篇幅中的TAG关键字添加内链（默认频率2次）',
				'text_off'   => '点击开启自动内链',
				'text_on'    => '点击关闭自动内链',
				'text_width' => 140,

			),

		)
	) );

//
// TDK SEO
//
	CSF::createSection( $prefix, array(
		'parent' => 'leseo-seo-fields',
		'title'  => 'TDK设置',
		'icon'   => 'far fa-square',
		'fields' => array(

			array(
				'type'    => 'heading',
				'content' => 'SEO TDK设置',
			),

			array(
				'type'    => 'notice',
				'style'   => 'info',
				'content' => '通过开启自定义设置首页、分类、页面TDK设置',
			),

			array(
				'id'         => 'leseo-selfseotdk',
				'type'       => 'switcher',
				'title'      => '开启自定义SEO',
				'text_off'   => '点击开启自定义SEO',
				'text_on'    => '点击关闭自定义SEO',
				'text_width' => 140,

			),

			array(
				'id'    => 'leseo-linkmark',
				'type'  => 'text',
				'title' => '全站连接符号',
			),

			array(
				'id'    => 'leseo-pageandsitename',
				'type'  => 'checkbox',
				'title' => '页面不跟随网站名称',
				'label' => '文章页面Title采用文章标题还是带上网站名称',
			),

			array(
				'id'    => 'leseo-selfindextitle',
				'type'  => 'text',
				'title' => '自定义首页标题',
			),

			array(
				'id'    => 'leseo-selfindexkeywords',
				'type'  => 'text',
				'title' => '自定义首页关键字',
				'desc'  => '关键字中间用英文逗号","隔开',
			),

			array(
				'id'    => 'leseo-selfindexdesc',
				'type'  => 'textarea',
				'title' => '自定义首页描述',
				'help'  => '建议不要超过150个字符',
			),

			array(
				'id'    => 'leseo-opengraph',
				'type'  => 'checkbox',
				'title' => '开启Open Graph',

			),

			array(
				'id'    => 'leseo-canonical',
				'type'  => 'checkbox',
				'title' => '开启Canonical',

			),


		)
	) );


//
// 网站地图
//
	CSF::createSection( $prefix, array(
		'parent' => 'leseo-seo-fields',
		'title'  => '网站地图',
		'icon'   => 'far fa-square',
		'fields' => array(

			array(
				'type'    => 'heading',
				'content' => '网站地图优化',
			),

			array(
				'type'    => 'notice',
				'style'   => 'info',
				'content' => '禁止自带地图和Robots.txt设置',
			),

			array(
				'id'         => 'leseo-selfsitemap',
				'type'       => 'switcher',
				'title'      => '关闭自带SiteMap',
				'label'      => '如果采用第三方SitaMap插件这个可以关闭',
				'text_off'   => '点击关闭自带地图',
				'text_on'    => '点击开启自带地图',
				'text_width' => 140,

			),

			array(
				'id'         => 'leseo-robots-switch',
				'type'       => 'switcher',
				'title'      => '自定义Robots.txt',
				'label'      => '设置自定义Robots协议',
				'text_off'   => '点击开启协议',
				'text_on'    => '点击关闭协议',
				'text_width' => 140,

			),

			array(
				'id'    => 'leseo-robots-content',
				'type'  => 'textarea',
				'title' => 'Robots.txt',
				'help'  => '如果根目录没有生成可以自定义创建rebots.txt复制进',
				'value' => file_exists(join(DIRECTORY_SEPARATOR, [ $_SERVER['DOCUMENT_ROOT'], $leseo_robots_filename ])) ?
					esc_textarea( file_get_contents(join(DIRECTORY_SEPARATOR, [ $_SERVER['DOCUMENT_ROOT'], $leseo_robots_filename ])) ) :
					'',
				'dependency' => array( 'leseo-robots-switch', '==', 'true' ),
			),

		)
	) );

	// 搜索推送
	CSF::createSection( $prefix, array(
		'title'  => '搜索推送',
		'icon'   => 'fa fa-circle',
		'fields' => array(
			array(
				'type'    => 'heading',
				'content' => '搜索引擎推送',
			),
			array(
				'type'    => 'notice',
				'style'   => 'info',
				'content' => '主动推送网站内容至搜索引擎，加速内容抓取和收录概率',
			),

			array(
				'id'         => 'leseo-submit-switch',
				'type'       => 'switcher',
				'title'      => '开启推送',
				'label'      => '打开开启推送开关，才可以推送文章',
				'text_off'   => '点击开启推送',
				'text_on'    => '点击关闭推送',
				'text_width' => 100,
			),

			array(
				'id'      => 'leseo-resource-type',
				'type'    => 'checkbox',
				'title'   => '资源类型',
				'inline'  => true,
				'options' => array(
					'post'    => '文章',
					'page'    => '页面',

				),
			),

			array(
				'type'    => 'subheading',
				'content' => '百度推送设置',
			),

			array(
				'id'    => 'leseo-submit-bdtoken',
				'type'  => 'text',
				'title' => '百度推送API TOKEN',
				'label' => '填写TOKEN',
			),

			array(
				'id'      => 'leseo-submit-type',
				'type'    => 'checkbox',
				'title'   => '百度推送类型',
				'inline'  => true,
				'options' => array(
					'normal' => '普通推送',
					'daily'  => '快速推送',
				),
			),

		)
	) );

// 附加功能
	CSF::createSection( $prefix, array(
		'id'    => 'leseo-add-func',
		'title' => '附加功能',
		'icon'  => 'fa fa-adjust',
	) );

//
// SEO基础优化
//
	CSF::createSection( $prefix, array(
		'parent' => 'leseo-add-func',
		'title'  => '插入代码',
		'icon'   => 'far fas fa-code',
		'fields' => array(

			array(
				'type'    => 'heading',
				'content' => '自定义插入代码',
			),

			array(
				'type'    => 'notice',
				'style'   => 'info',
				'content' => '自定义给网站头部、底部和CSS添加外部代码',
			),

			array(
      			'id'       => 'leseo-code-header',
      			'type'     => 'code_editor',
      			'title'    => '自定义头部代码',
     			'subtitle' => '位于</head>之前，通常是CSS样式、自定义的标签、头部JS等需要提前加载的代码',
   			 ),

			array(
      			'id'       => 'leseo-code-footer',
      			'type'     => 'code_editor',
      			'title'    => '自定义底部代码',
     			'subtitle' => '位于</body>之前，这部分代码是在主要内容加载完毕加载，通常是JS代码',
   			 ),

			array(
      			'id'       => 'leseo-code-cssjs',
      			'type'     => 'code_editor',
      			'title'    => '自定义CSS样式',
     			'subtitle' => '直接写样式代码，不用添加标签',
   			 'settings' => array(
        'theme'  => 'mbo',
        'mode'   => 'css',
      ),
      'default' =>'无需加入<style>标签',
    ),

		)
	) );

	// Create a section
	CSF::createSection( $prefix, array(
		'title'       => '关于插件',
		'icon'        => 'fa fa-bell',
		'description' => '<div class="groups_title"><h3>LeSeo插件</h3><p>LeSeo，一款有温度的从底层优化WordPress插件。</p><h3>关于开发者</h3><p>开发者：老蒋和他的伙伴们</p><p>邮箱：info@lezaiyun.com</p><p>公众号：老蒋朋友圈</p><h3>开发引用</h3><p>插件框架采用Codestar Framework且获得授权，插件功能代码有参考网上热心网友分享的代码。</p><h3>帮助支持</h3><p><a href="https://www.lezaiyun.com/" class="button button-secondary" target="_blank">插件官方</a> <a href="https://www.lezaiyun.com/leseo.html" class="button button-secondary" target="_blank">更新文档</a> <a href="#" class="button button-secondary" target="_blank">交流社群</a></p></div>',
		'fields'      => array()

	) );


####### Options End #######
$options = get_option('_lezaiyun_leseo_option');
if ( isset($options['leseo-selfseotdk']) && $options['leseo-selfseotdk'] ) {
	// 文章页面MetaBox设置
	$prefix_post_opts = 'leseo_singular_meta_options';

	CSF::createMetabox( $prefix_post_opts, array(
		'title'        => '自定义TDK',
		'post_type'    => array('post', 'page'),
		# 'show_restore' => true,  # 恢复默认按钮
	) );

	CSF::createSection( $prefix_post_opts, array(
		'fields' => array(
			array(
				'id'    => 'leseo-singular-meta-switcher',
				'type'  => 'switcher',
				'title' => '自定义SEO TDK',
				'label' => '是否开启自定义TDK',
			),
			array(
				'id'    => 'leseo-singular-meta-title',
				'type'  => 'text',
				'title' => 'Title',
				'dependency' => array( 'leseo-singular-meta-switcher', '==', 'true' ),
			),
			array(
				'id'    => 'leseo-singular-meta-description',
				'type'  => 'textarea',
				'title' => 'Description',
				# 'help'  => 'The help text of the field.',
				'dependency' => array( 'leseo-singular-meta-switcher', '==', 'true' ),
			),
			array(
				'id'    => 'leseo-singular-meta-keywords',
				'type'  => 'text',
				'title' => 'Keywords',
				'dependency' => array( 'leseo-singular-meta-switcher', '==', 'true' ),
			),
		)
	) );


############# Post & Page MetaBox End #############
	$prefix_tax_opts = 'leseo_taxonomy_meta_options';

	// Create taxonomy options
	CSF::createTaxonomyOptions( $prefix_tax_opts, array(
		'title'    => '自定义TDK',
		'taxonomy' => array('post_tag', 'category'),
	) );
	CSF::createSection( $prefix_tax_opts, array(
		'fields' => array(
			array(
				'id'    => 'leseo-taxonomy-meta-switcher',
				'type'  => 'switcher',
				'title' => '自定义SEO TDK',
				'label' => '是否开启自定义TDK',
			),
			array(
				'id'    => 'leseo-taxonomy-meta-title',
				'type'  => 'text',
				'title' => 'Title',
				'dependency' => array( 'leseo-taxonomy-meta-switcher', '==', 'true' ),
			),
			array(
				'id'    => 'leseo-taxonomy-meta-description',
				'type'  => 'textarea',
				'title' => 'Description',
				# 'help'  => 'The help text of the field.',
				'dependency' => array( 'leseo-taxonomy-meta-switcher', '==', 'true' ),
			),
			array(
				'id'    => 'leseo-taxonomy-meta-keywords',
				'type'  => 'text',
				'title' => 'Keywords',
				'dependency' => array( 'leseo-taxonomy-meta-switcher', '==', 'true' ),
			),
		)
	) );
	}
}
