<?php
declare(strict_types=1);

/**
 * 站外字体目录：分组、短风格/备注、woff2 文件名（与 GWFH 下载名一致）。
 *
 * 放置：uploads/tailwindscore-fonts/{slug}/{file}
 * 下载：https://gwfh.mranftl.com/fonts （latin、woff2；字重 400+600，衬线标题可加 700）
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, string> group_id => 分组标题
 */
function tailwindscore_typography_font_group_labels(): array {
	return array(
		'system'        => __( '系统', 'tailwindscore' ),
		'body-sans'     => __( '正文 Sans', 'tailwindscore' ),
		'heading-sans'  => __( '标题 Sans', 'tailwindscore' ),
		'heading-serif' => __( '标题衬线', 'tailwindscore' ),
		'mono'          => __( '等宽', 'tailwindscore' ),
	);
}

/**
 * 完整字体目录（未下载的条目不会出现在下拉中）。
 *
 * @return array<string, array{label: string, group: string, style: string, remark: string, family: string, faces: list<array{family: string, weight: int, style: string, file: string}>}>
 */
function tailwindscore_typography_font_catalog(): array {
	return array(
		'system' => array(
			'label'  => __( 'System UI', 'tailwindscore' ),
			'group'  => 'system',
			'style'  => '系统默认',
			'remark' => '无额外下载',
			'family' => 'ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(),
		),
		'inter' => array(
			'label'  => 'Inter',
			'group'  => 'body-sans',
			'style'  => '现代无衬线',
			'remark' => '产品站常用正文',
			'family' => '"Inter", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'inter-v20-latin-regular.woff2' ),
				array( 'family' => 'Inter', 'weight' => 600, 'style' => 'normal', 'file' => 'inter-v20-latin-600.woff2' ),
			),
		),
		'roboto' => array(
			'label'  => 'Roboto',
			'group'  => 'body-sans',
			'style'  => '中性无衬线',
			'remark' => '通用正文备选',
			'family' => '"Roboto", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Roboto', 'weight' => 400, 'style' => 'normal', 'file' => 'roboto-v51-latin-regular.woff2' ),
				array( 'family' => 'Roboto', 'weight' => 600, 'style' => 'normal', 'file' => 'roboto-v51-latin-600.woff2' ),
			),
		),
		'open-sans' => array(
			'label'  => 'Open Sans',
			'group'  => 'body-sans',
			'style'  => '中性、可读',
			'remark' => '电商站最稳妥之一',
			'family' => '"Open Sans", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Open Sans', 'weight' => 400, 'style' => 'normal', 'file' => 'open-sans-v40-latin-regular.woff2' ),
				array( 'family' => 'Open Sans', 'weight' => 600, 'style' => 'normal', 'file' => 'open-sans-v40-latin-600.woff2' ),
			),
		),
		'lato' => array(
			'label'  => 'Lato',
			'group'  => 'body-sans',
			'style'  => '偏圆润',
			'remark' => '友好、偏营销感',
			'family' => '"Lato", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Lato', 'weight' => 400, 'style' => 'normal', 'file' => 'lato-v24-latin-regular.woff2' ),
				array( 'family' => 'Lato', 'weight' => 600, 'style' => 'normal', 'file' => 'lato-v24-latin-600.woff2' ),
			),
		),
		'source-sans-3' => array(
			'label'  => 'Source Sans 3',
			'group'  => 'body-sans',
			'style'  => '偏技术、清晰',
			'remark' => '屏幕阅读好',
			'family' => '"Source Sans 3", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Source Sans 3', 'weight' => 400, 'style' => 'normal', 'file' => 'source-sans-3-v15-latin-regular.woff2' ),
				array( 'family' => 'Source Sans 3', 'weight' => 600, 'style' => 'normal', 'file' => 'source-sans-3-v15-latin-600.woff2' ),
			),
		),
		'nunito-sans' => array(
			'label'  => 'Nunito Sans',
			'group'  => 'body-sans',
			'style'  => '稍圆',
			'remark' => '比 Nunito 更适长文',
			'family' => '"Nunito Sans", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Nunito Sans', 'weight' => 400, 'style' => 'normal', 'file' => 'nunito-sans-v15-latin-regular.woff2' ),
				array( 'family' => 'Nunito Sans', 'weight' => 600, 'style' => 'normal', 'file' => 'nunito-sans-v15-latin-600.woff2' ),
			),
		),
		'dm-sans' => array(
			'label'  => 'DM Sans',
			'group'  => 'body-sans',
			'style'  => '现代几何',
			'remark' => '产品站很常见',
			'family' => '"DM Sans", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'DM Sans', 'weight' => 400, 'style' => 'normal', 'file' => 'dm-sans-v15-latin-regular.woff2' ),
				array( 'family' => 'DM Sans', 'weight' => 600, 'style' => 'normal', 'file' => 'dm-sans-v15-latin-600.woff2' ),
			),
		),
		'plus-jakarta-sans' => array(
			'label'  => 'Plus Jakarta Sans',
			'group'  => 'body-sans',
			'style'  => '偏品牌感',
			'remark' => '近年很流行',
			'family' => '"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Plus Jakarta Sans', 'weight' => 400, 'style' => 'normal', 'file' => 'plus-jakarta-sans-v8-latin-regular.woff2' ),
				array( 'family' => 'Plus Jakarta Sans', 'weight' => 600, 'style' => 'normal', 'file' => 'plus-jakarta-sans-v8-latin-600.woff2' ),
			),
		),
		'barlow' => array(
			'label'  => 'Barlow',
			'group'  => 'body-sans',
			'style'  => '略紧、中性',
			'remark' => '配 Barlow Condensed 最佳',
			'family' => '"Barlow", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Barlow', 'weight' => 400, 'style' => 'normal', 'file' => 'barlow-v13-latin-regular.woff2' ),
				array( 'family' => 'Barlow', 'weight' => 600, 'style' => 'normal', 'file' => 'barlow-v13-latin-600.woff2' ),
			),
		),
		'ibm-plex-sans' => array(
			'label'  => 'IBM Plex Sans',
			'group'  => 'body-sans',
			'style'  => '偏技术、清晰',
			'remark' => '参数/说明文好读',
			'family' => '"IBM Plex Sans", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'IBM Plex Sans', 'weight' => 400, 'style' => 'normal', 'file' => 'ibm-plex-sans-v23-latin-regular.woff2' ),
				array( 'family' => 'IBM Plex Sans', 'weight' => 600, 'style' => 'normal', 'file' => 'ibm-plex-sans-v23-latin-600.woff2' ),
			),
		),
		'work-sans' => array(
			'label'  => 'Work Sans',
			'group'  => 'body-sans',
			'style'  => '中性、耐看',
			'remark' => '通用电商正文',
			'family' => '"Work Sans", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Work Sans', 'weight' => 400, 'style' => 'normal', 'file' => 'work-sans-v24-latin-regular.woff2' ),
				array( 'family' => 'Work Sans', 'weight' => 600, 'style' => 'normal', 'file' => 'work-sans-v24-latin-600.woff2' ),
			),
		),
		'manrope' => array(
			'label'  => 'Manrope',
			'group'  => 'body-sans',
			'style'  => '现代几何',
			'remark' => 'athleisure / 生活方式',
			'family' => '"Manrope", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Manrope', 'weight' => 400, 'style' => 'normal', 'file' => 'manrope-v20-latin-regular.woff2' ),
				array( 'family' => 'Manrope', 'weight' => 600, 'style' => 'normal', 'file' => 'manrope-v20-latin-600.woff2' ),
			),
		),
		'poppins' => array(
			'label'  => 'Poppins',
			'group'  => 'heading-sans',
			'style'  => '几何标题',
			'remark' => '和 Inter/Roboto 正文很搭',
			'family' => '"Poppins", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Poppins', 'weight' => 400, 'style' => 'normal', 'file' => 'poppins-v21-latin-regular.woff2' ),
				array( 'family' => 'Poppins', 'weight' => 600, 'style' => 'normal', 'file' => 'poppins-v21-latin-600.woff2' ),
			),
		),
		'montserrat' => array(
			'label'  => 'Montserrat',
			'group'  => 'heading-sans',
			'style'  => '几何、醒目',
			'remark' => '强标题；正文略挤',
			'family' => '"Montserrat", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Montserrat', 'weight' => 400, 'style' => 'normal', 'file' => 'montserrat-v26-latin-regular.woff2' ),
				array( 'family' => 'Montserrat', 'weight' => 600, 'style' => 'normal', 'file' => 'montserrat-v26-latin-600.woff2' ),
			),
		),
		'raleway' => array(
			'label'  => 'Raleway',
			'group'  => 'heading-sans',
			'style'  => '细高、时尚',
			'remark' => '时装/生活方式类',
			'family' => '"Raleway", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Raleway', 'weight' => 400, 'style' => 'normal', 'file' => 'raleway-v34-latin-regular.woff2' ),
				array( 'family' => 'Raleway', 'weight' => 600, 'style' => 'normal', 'file' => 'raleway-v34-latin-600.woff2' ),
			),
		),
		'oswald' => array(
			'label'  => 'Oswald',
			'group'  => 'heading-sans',
			'style'  => '紧缩标题',
			'remark' => '只适合短标题',
			'family' => '"Oswald", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Oswald', 'weight' => 400, 'style' => 'normal', 'file' => 'oswald-v53-latin-regular.woff2' ),
				array( 'family' => 'Oswald', 'weight' => 600, 'style' => 'normal', 'file' => 'oswald-v53-latin-600.woff2' ),
			),
		),
		'bebas-neue' => array(
			'label'  => 'Bebas Neue',
			'group'  => 'heading-sans',
			'style'  => '全大写海报风',
			'remark' => '只适合短 headline',
			'family' => '"Bebas Neue", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Bebas Neue', 'weight' => 400, 'style' => 'normal', 'file' => 'bebas-neue-v16-latin-regular.woff2' ),
			),
		),
		'barlow-condensed' => array(
			'label'  => 'Barlow Condensed',
			'group'  => 'heading-sans',
			'style'  => '紧缩、运动感',
			'remark' => '运动站首选标题；配 Barlow',
			'family' => '"Barlow Condensed", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Barlow Condensed', 'weight' => 400, 'style' => 'normal', 'file' => 'barlow-condensed-v13-latin-regular.woff2' ),
				array( 'family' => 'Barlow Condensed', 'weight' => 600, 'style' => 'normal', 'file' => 'barlow-condensed-v13-latin-600.woff2' ),
			),
		),
		'teko' => array(
			'label'  => 'Teko',
			'group'  => 'heading-sans',
			'style'  => '更紧、海报感',
			'remark' => '短标题 / 分类名 / 促销',
			'family' => '"Teko", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Teko', 'weight' => 400, 'style' => 'normal', 'file' => 'teko-v23-latin-regular.woff2' ),
				array( 'family' => 'Teko', 'weight' => 600, 'style' => 'normal', 'file' => 'teko-v23-latin-600.woff2' ),
			),
		),
		'saira-condensed' => array(
			'label'  => 'Saira Condensed',
			'group'  => 'heading-sans',
			'style'  => '现代紧缩',
			'remark' => '跑鞋 / 户外装备',
			'family' => '"Saira Condensed", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Saira Condensed', 'weight' => 400, 'style' => 'normal', 'file' => 'saira-condensed-v12-latin-regular.woff2' ),
				array( 'family' => 'Saira Condensed', 'weight' => 600, 'style' => 'normal', 'file' => 'saira-condensed-v12-latin-600.woff2' ),
			),
		),
		'anton' => array(
			'label'  => 'Anton',
			'group'  => 'heading-sans',
			'style'  => '粗显示',
			'remark' => '只适合短 Hero',
			'family' => '"Anton", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Anton', 'weight' => 400, 'style' => 'normal', 'file' => 'anton-v27-latin-regular.woff2' ),
			),
		),
		'exo-2' => array(
			'label'  => 'Exo 2',
			'group'  => 'heading-sans',
			'style'  => '科技几何',
			'remark' => '智能穿戴 / 功能运动',
			'family' => '"Exo 2", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Exo 2', 'weight' => 400, 'style' => 'normal', 'file' => 'exo-2-v26-latin-regular.woff2' ),
				array( 'family' => 'Exo 2', 'weight' => 600, 'style' => 'normal', 'file' => 'exo-2-v26-latin-600.woff2' ),
			),
		),
		'rajdhani' => array(
			'label'  => 'Rajdhani',
			'group'  => 'heading-sans',
			'style'  => '竞速 / 电竞感',
			'remark' => '硬核、性能向标题',
			'family' => '"Rajdhani", ui-sans-serif, system-ui, sans-serif',
			'faces'  => array(
				array( 'family' => 'Rajdhani', 'weight' => 400, 'style' => 'normal', 'file' => 'rajdhani-v17-latin-regular.woff2' ),
				array( 'family' => 'Rajdhani', 'weight' => 600, 'style' => 'normal', 'file' => 'rajdhani-v17-latin-600.woff2' ),
			),
		),
		'playfair-display' => array(
			'label'  => 'Playfair Display',
			'group'  => 'heading-serif',
			'style'  => '优雅衬线',
			'remark' => '品牌/故事标题',
			'family' => '"Playfair Display", Georgia, "Times New Roman", serif',
			'faces'  => array(
				array( 'family' => 'Playfair Display', 'weight' => 400, 'style' => 'normal', 'file' => 'playfair-display-v40-latin-regular.woff2' ),
				array( 'family' => 'Playfair Display', 'weight' => 700, 'style' => 'normal', 'file' => 'playfair-display-v40-latin-700.woff2' ),
			),
		),
		'merriweather' => array(
			'label'  => 'Merriweather',
			'group'  => 'heading-serif',
			'style'  => '可读衬线',
			'remark' => '也可当正文',
			'family' => '"Merriweather", Georgia, "Times New Roman", serif',
			'faces'  => array(
				array( 'family' => 'Merriweather', 'weight' => 400, 'style' => 'normal', 'file' => 'merriweather-v30-latin-regular.woff2' ),
				array( 'family' => 'Merriweather', 'weight' => 700, 'style' => 'normal', 'file' => 'merriweather-v30-latin-700.woff2' ),
			),
		),
		'lora' => array(
			'label'  => 'Lora',
			'group'  => 'heading-serif',
			'style'  => '稍优雅',
			'remark' => '博客/品牌故事',
			'family' => '"Lora", Georgia, "Times New Roman", serif',
			'faces'  => array(
				array( 'family' => 'Lora', 'weight' => 400, 'style' => 'normal', 'file' => 'lora-v35-latin-regular.woff2' ),
				array( 'family' => 'Lora', 'weight' => 700, 'style' => 'normal', 'file' => 'lora-v35-latin-700.woff2' ),
			),
		),
		'cormorant-garamond' => array(
			'label'  => 'Cormorant Garamond',
			'group'  => 'heading-serif',
			'style'  => '偏精致',
			'remark' => '高端视觉标题',
			'family' => '"Cormorant Garamond", Georgia, "Times New Roman", serif',
			'faces'  => array(
				array( 'family' => 'Cormorant Garamond', 'weight' => 400, 'style' => 'normal', 'file' => 'cormorant-garamond-v16-latin-regular.woff2' ),
				array( 'family' => 'Cormorant Garamond', 'weight' => 600, 'style' => 'normal', 'file' => 'cormorant-garamond-v16-latin-600.woff2' ),
			),
		),
		'libre-baskerville' => array(
			'label'  => 'Libre Baskerville',
			'group'  => 'heading-serif',
			'style'  => '经典书刊',
			'remark' => '正式、偏传统',
			'family' => '"Libre Baskerville", Georgia, "Times New Roman", serif',
			'faces'  => array(
				array( 'family' => 'Libre Baskerville', 'weight' => 400, 'style' => 'normal', 'file' => 'libre-baskerville-v14-latin-regular.woff2' ),
				array( 'family' => 'Libre Baskerville', 'weight' => 700, 'style' => 'normal', 'file' => 'libre-baskerville-v14-latin-700.woff2' ),
			),
		),
		'roboto-mono' => array(
			'label'  => 'Roboto Mono',
			'group'  => 'mono',
			'style'  => '等宽',
			'remark' => '和 Roboto 配套',
			'family' => '"Roboto Mono", ui-monospace, monospace',
			'faces'  => array(
				array( 'family' => 'Roboto Mono', 'weight' => 400, 'style' => 'normal', 'file' => 'roboto-mono-v23-latin-regular.woff2' ),
				array( 'family' => 'Roboto Mono', 'weight' => 600, 'style' => 'normal', 'file' => 'roboto-mono-v23-latin-600.woff2' ),
			),
		),
		'source-code-pro' => array(
			'label'  => 'Source Code Pro',
			'group'  => 'mono',
			'style'  => '等宽、技术感',
			'remark' => '代码/价格号',
			'family' => '"Source Code Pro", ui-monospace, monospace',
			'faces'  => array(
				array( 'family' => 'Source Code Pro', 'weight' => 400, 'style' => 'normal', 'file' => 'source-code-pro-v31-latin-regular.woff2' ),
				array( 'family' => 'Source Code Pro', 'weight' => 600, 'style' => 'normal', 'file' => 'source-code-pro-v31-latin-600.woff2' ),
			),
		),
		'jetbrains-mono' => array(
			'label'  => 'JetBrains Mono',
			'group'  => 'mono',
			'style'  => '等宽、舒适',
			'remark' => '开发者向',
			'family' => '"JetBrains Mono", ui-monospace, monospace',
			'faces'  => array(
				array( 'family' => 'JetBrains Mono', 'weight' => 400, 'style' => 'normal', 'file' => 'jetbrains-mono-v18-latin-regular.woff2' ),
				array( 'family' => 'JetBrains Mono', 'weight' => 600, 'style' => 'normal', 'file' => 'jetbrains-mono-v18-latin-600.woff2' ),
			),
		),
	);
}

/**
 * Customizer「一点应用」推荐组合（两边字体均在磁盘可用时才展示）。
 *
 * @return list<array{id: string, group: string, label: string, detail: string, heading: string, body: string}>
 */
function tailwindscore_typography_font_presets(): array {
	return array(
		// —— 运动 ——
		array(
			'id'      => 'sports-default',
			'group'   => __( '运动', 'tailwindscore' ),
			'label'   => __( '运动站默认', 'tailwindscore' ),
			'detail'  => 'Barlow Condensed + Barlow',
			'heading' => 'barlow-condensed',
			'body'    => 'barlow',
		),
		array(
			'id'      => 'sports-promo',
			'group'   => __( '运动', 'tailwindscore' ),
			'label'   => __( '促销 / 硬核', 'tailwindscore' ),
			'detail'  => 'Teko + IBM Plex Sans',
			'heading' => 'teko',
			'body'    => 'ibm-plex-sans',
		),
		array(
			'id'      => 'sports-tech',
			'group'   => __( '运动', 'tailwindscore' ),
			'label'   => __( '科技运动', 'tailwindscore' ),
			'detail'  => 'Exo 2 + Manrope',
			'heading' => 'exo-2',
			'body'    => 'manrope',
		),
		array(
			'id'      => 'sports-classic',
			'group'   => __( '运动', 'tailwindscore' ),
			'label'   => __( '经典运动', 'tailwindscore' ),
			'detail'  => 'Oswald + Source Sans 3',
			'heading' => 'oswald',
			'body'    => 'source-sans-3',
		),
		// —— 时尚 ——
		array(
			'id'      => 'fashion-elegant',
			'group'   => __( '时尚', 'tailwindscore' ),
			'label'   => __( '优雅时装', 'tailwindscore' ),
			'detail'  => 'Playfair Display + Lato',
			'heading' => 'playfair-display',
			'body'    => 'lato',
		),
		array(
			'id'      => 'fashion-lifestyle',
			'group'   => __( '时尚', 'tailwindscore' ),
			'label'   => __( '生活方式', 'tailwindscore' ),
			'detail'  => 'Raleway + Open Sans',
			'heading' => 'raleway',
			'body'    => 'open-sans',
		),
		array(
			'id'      => 'fashion-modern',
			'group'   => __( '时尚', 'tailwindscore' ),
			'label'   => __( '现代成衣', 'tailwindscore' ),
			'detail'  => 'Montserrat + DM Sans',
			'heading' => 'montserrat',
			'body'    => 'dm-sans',
		),
		array(
			'id'      => 'fashion-haute',
			'group'   => __( '时尚', 'tailwindscore' ),
			'label'   => __( '高级成衣', 'tailwindscore' ),
			'detail'  => 'Cormorant Garamond + Inter',
			'heading' => 'cormorant-garamond',
			'body'    => 'inter',
		),
		// —— 手表 / 奢侈 ——
		array(
			'id'      => 'watch-luxury',
			'group'   => __( '手表 / 奢侈', 'tailwindscore' ),
			'label'   => __( '奢华表款', 'tailwindscore' ),
			'detail'  => 'Cormorant Garamond + Source Sans 3',
			'heading' => 'cormorant-garamond',
			'body'    => 'source-sans-3',
		),
		array(
			'id'      => 'watch-classic',
			'group'   => __( '手表 / 奢侈', 'tailwindscore' ),
			'label'   => __( '经典正装', 'tailwindscore' ),
			'detail'  => 'Libre Baskerville + Open Sans',
			'heading' => 'libre-baskerville',
			'body'    => 'open-sans',
		),
		array(
			'id'      => 'watch-modern',
			'group'   => __( '手表 / 奢侈', 'tailwindscore' ),
			'label'   => __( '现代精品', 'tailwindscore' ),
			'detail'  => 'Playfair Display + Inter',
			'heading' => 'playfair-display',
			'body'    => 'inter',
		),
		// —— 通用电商 ——
		array(
			'id'      => 'shop-neutral',
			'group'   => __( '通用电商', 'tailwindscore' ),
			'label'   => __( '中性产品站', 'tailwindscore' ),
			'detail'  => 'Poppins + Inter',
			'heading' => 'poppins',
			'body'    => 'inter',
		),
		array(
			'id'      => 'shop-friendly',
			'group'   => __( '通用电商', 'tailwindscore' ),
			'label'   => __( '亲和营销', 'tailwindscore' ),
			'detail'  => 'Poppins + Nunito Sans',
			'heading' => 'poppins',
			'body'    => 'nunito-sans',
		),
		array(
			'id'      => 'shop-clean',
			'group'   => __( '通用电商', 'tailwindscore' ),
			'label'   => __( '干净品牌感', 'tailwindscore' ),
			'detail'  => 'Montserrat + Plus Jakarta Sans',
			'heading' => 'montserrat',
			'body'    => 'plus-jakarta-sans',
		),
		array(
			'id'      => 'shop-work',
			'group'   => __( '通用电商', 'tailwindscore' ),
			'label'   => __( '耐看电商', 'tailwindscore' ),
			'detail'  => 'Montserrat + Work Sans',
			'heading' => 'montserrat',
			'body'    => 'work-sans',
		),
		// —— 内容 / 博客 ——
		array(
			'id'      => 'editorial-story',
			'group'   => __( '内容 / 博客', 'tailwindscore' ),
			'label'   => __( '品牌故事', 'tailwindscore' ),
			'detail'  => 'Lora + Source Sans 3',
			'heading' => 'lora',
			'body'    => 'source-sans-3',
		),
		array(
			'id'      => 'editorial-read',
			'group'   => __( '内容 / 博客', 'tailwindscore' ),
			'label'   => __( '长文阅读', 'tailwindscore' ),
			'detail'  => 'Merriweather + Open Sans',
			'heading' => 'merriweather',
			'body'    => 'open-sans',
		),
		// —— 科技 ——
		array(
			'id'      => 'tech-product',
			'group'   => __( '科技', 'tailwindscore' ),
			'label'   => __( '科技产品', 'tailwindscore' ),
			'detail'  => 'Exo 2 + IBM Plex Sans',
			'heading' => 'exo-2',
			'body'    => 'ibm-plex-sans',
		),
		array(
			'id'      => 'tech-racing',
			'group'   => __( '科技', 'tailwindscore' ),
			'label'   => __( '竞速 / 电竞', 'tailwindscore' ),
			'detail'  => 'Rajdhani + Roboto',
			'heading' => 'rajdhani',
			'body'    => 'roboto',
		),
	);
}
