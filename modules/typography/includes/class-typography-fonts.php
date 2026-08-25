<?php
declare(strict_types=1);

/**
 * 字体 manifest：目录 + uploads 磁盘检测、@font-face / 分组 choices。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 站外字体注册表。
 */
class Tailwindscore_Typography_Fonts {

	public const UPLOAD_SUBDIR = 'tailwindscore-fonts';

	/**
	 * @return array{basedir: string, baseurl: string}|null
	 */
	public static function get_fonts_root(): ?array {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}

		return array(
			'basedir' => trailingslashit( (string) $uploads['basedir'] ) . self::UPLOAD_SUBDIR,
			'baseurl' => trailingslashit( (string) $uploads['baseurl'] ) . self::UPLOAD_SUBDIR,
		);
	}

	/**
	 * @param string   $slug   字体 slug（须与 uploads 子目录名一致）。
	 * @param string   $file   目录登记的文件名（GWFH 原名）。
	 * @param int|null $weight 字重；精确文件不存在时按字重在目录内回退匹配。
	 * @return array{path: string, url: string}|null
	 */
	public static function resolve_face_asset( string $slug, string $file, ?int $weight = null ): ?array {
		$root = self::get_fonts_root();
		if ( null === $root ) {
			return null;
		}

		$slug = sanitize_title( $slug );
		$file = basename( $file );
		if ( '' === $slug ) {
			return null;
		}

		$dir = trailingslashit( $root['basedir'] ) . $slug;
		if ( ! is_dir( $dir ) ) {
			return null;
		}

		$resolved = '';
		if ( '' !== $file ) {
			$path = $dir . '/' . $file;
			if ( is_readable( $path ) ) {
				$resolved = $file;
			}
		}

		// GWFH 版本号常变：按字重后缀在目录内回退查找。
		if ( '' === $resolved && null !== $weight ) {
			$found = self::find_file_by_weight( $dir, $weight );
			if ( null !== $found ) {
				$resolved = $found;
			}
		}

		if ( '' === $resolved ) {
			return null;
		}

		return array(
			'path' => $dir . '/' . $resolved,
			'url'  => trailingslashit( $root['baseurl'] ) . $slug . '/' . rawurlencode( $resolved ),
		);
	}

	/**
	 * 在字体目录内按字重匹配 woff2（忽略版本号）。
	 *
	 * @param string $dir    绝对目录。
	 * @param int    $weight 字重。
	 * @return string|null 文件名。
	 */
	public static function find_file_by_weight( string $dir, int $weight ): ?string {
		$suffixes = array();
		if ( 400 === $weight ) {
			$suffixes = array( '-latin-regular.woff2', '-latin-400.woff2' );
		} elseif ( 600 === $weight ) {
			// 无 600 时可用 700（如 Lato 仅提供 700）。
			$suffixes = array( '-latin-600.woff2', '-latin-700.woff2' );
		} elseif ( 700 === $weight ) {
			$suffixes = array( '-latin-700.woff2', '-latin-600.woff2' );
		} else {
			$suffixes = array( '-latin-' . $weight . '.woff2' );
		}

		$files = glob( trailingslashit( $dir ) . '*.woff2' );
		if ( ! is_array( $files ) ) {
			return null;
		}

		foreach ( $suffixes as $suffix ) {
			foreach ( $files as $path ) {
				$name = basename( $path );
				if ( str_ends_with( strtolower( $name ), $suffix ) && is_readable( $path ) ) {
					return $name;
				}
			}
		}

		return null;
	}

	/**
	 * 目录项是否在磁盘上可用（system 始终可用）。
	 *
	 * @param string               $slug  slug。
	 * @param array<string, mixed> $entry 目录条目。
	 */
	public static function is_available( string $slug, array $entry ): bool {
		if ( 'system' === $slug ) {
			return true;
		}

		$faces = isset( $entry['faces'] ) && is_array( $entry['faces'] ) ? $entry['faces'] : array();
		if ( array() === $faces ) {
			return false;
		}

		foreach ( $faces as $face ) {
			if ( ! is_array( $face ) ) {
				continue;
			}
			$file   = isset( $face['file'] ) ? (string) $face['file'] : '';
			$weight = isset( $face['weight'] ) ? (int) $face['weight'] : 400;
			if ( null !== self::resolve_face_asset( $slug, $file, $weight ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 选项文案：`Label — style（remark）`。
	 *
	 * @param array<string, mixed> $entry 目录条目。
	 */
	public static function format_choice_label( array $entry ): string {
		$label  = isset( $entry['label'] ) ? (string) $entry['label'] : '';
		$style  = isset( $entry['style'] ) ? (string) $entry['style'] : '';
		$remark = isset( $entry['remark'] ) ? (string) $entry['remark'] : '';

		if ( '' === $style && '' === $remark ) {
			return $label;
		}
		if ( '' === $remark ) {
			return $label . ' — ' . $style;
		}
		if ( '' === $style ) {
			return $label . ' — ' . $remark;
		}

		return $label . ' — ' . $style . '（' . $remark . '）';
	}

	/**
	 * 仅含磁盘可用字体的 manifest。
	 *
	 * @return array<string, array{label: string, group: string, style: string, remark: string, family: string, faces: list<array{family: string, weight: int, style: string, file: string}>}>
	 */
	public static function get_manifest(): array {
		$catalog  = tailwindscore_typography_font_catalog();
		$manifest = array();

		foreach ( $catalog as $slug => $entry ) {
			if ( ! is_array( $entry ) || ! self::is_available( $slug, $entry ) ) {
				continue;
			}
			$manifest[ $slug ] = $entry;
		}

		/**
		 * 注册或覆盖可用字体清单。
		 *
		 * @param array<string, array{label: string, group: string, style: string, remark: string, family: string, faces: list<array{family: string, weight: int, style: string, file: string}>}> $manifest
		 */
		return apply_filters( 'tailwindscore_typography_font_manifest', $manifest );
	}

	/**
	 * @return array<string, string> slug => 标签
	 */
	public static function get_choices(): array {
		$choices = array();
		foreach ( self::get_manifest() as $slug => $font ) {
			$choices[ $slug ] = self::format_choice_label( $font );
		}
		return $choices;
	}

	/**
	 * optgroup 分组 choices。
	 *
	 * @return array<string, array<string, string>> 分组标题 => ( slug => 选项文案 )
	 */
	public static function get_choices_grouped(): array {
		$group_labels = tailwindscore_typography_font_group_labels();
		$grouped      = array();

		foreach ( self::get_manifest() as $slug => $font ) {
			$group_id = isset( $font['group'] ) ? (string) $font['group'] : 'body-sans';
			$title    = isset( $group_labels[ $group_id ] )
				? $group_labels[ $group_id ]
				: $group_id;

			if ( ! isset( $grouped[ $title ] ) ) {
				$grouped[ $title ] = array();
			}
			$grouped[ $title ][ $slug ] = self::format_choice_label( $font );
		}

		// 按 group_labels 顺序输出。
		$ordered = array();
		foreach ( $group_labels as $group_id => $title ) {
			if ( isset( $grouped[ $title ] ) ) {
				$ordered[ $title ] = $grouped[ $title ];
				unset( $grouped[ $title ] );
			}
		}
		foreach ( $grouped as $title => $items ) {
			$ordered[ $title ] = $items;
		}

		return $ordered;
	}

	/**
	 * @param string $slug 字体 slug。
	 * @return array{label: string, group: string, style: string, remark: string, family: string, faces: list<array{family: string, weight: int, style: string, file: string}>}|null
	 */
	public static function get_font( string $slug ): ?array {
		$manifest = self::get_manifest();
		return isset( $manifest[ $slug ] ) ? $manifest[ $slug ] : null;
	}

	/**
	 * @param string[] $slugs 要加载的字体 slug。
	 * @return string CSS @font-face 规则。
	 */
	public static function build_font_face_css( array $slugs ): string {
		$css         = '';
		$loaded_keys = array();

		foreach ( array_unique( $slugs ) as $slug ) {
			if ( 'system' === $slug ) {
				continue;
			}

			$font = self::get_font( $slug );
			if ( null === $font || empty( $font['faces'] ) ) {
				continue;
			}

			foreach ( $font['faces'] as $face ) {
				$file   = isset( $face['file'] ) ? (string) $face['file'] : '';
				$weight = isset( $face['weight'] ) ? (int) $face['weight'] : 400;
				$asset  = self::resolve_face_asset( $slug, $file, $weight );
				if ( null === $asset ) {
					continue;
				}

				$key = md5( $asset['url'] );
				if ( isset( $loaded_keys[ $key ] ) ) {
					continue;
				}
				$loaded_keys[ $key ] = true;

				$family = isset( $face['family'] ) ? (string) $face['family'] : '';
				$style  = isset( $face['style'] ) ? (string) $face['style'] : 'normal';

				$css .= sprintf(
					"@font-face{font-family:%s;font-style:%s;font-weight:%d;font-display:swap;src:url('%s') format('woff2');}",
					self::css_string( $family ),
					self::css_string( $style ),
					$weight,
					esc_url_raw( $asset['url'] )
				);
			}
		}

		return $css;
	}

	/**
	 * @param string[] $slugs 字体 slug。
	 * @return string[] preload URL。
	 */
	public static function get_preload_urls( array $slugs ): array {
		$urls = array();

		foreach ( array_unique( $slugs ) as $slug ) {
			if ( 'system' === $slug ) {
				continue;
			}
			$font = self::get_font( $slug );
			if ( null === $font || empty( $font['faces'] ) ) {
				continue;
			}
			foreach ( $font['faces'] as $face ) {
				$file   = isset( $face['file'] ) ? (string) $face['file'] : '';
				$weight = isset( $face['weight'] ) ? (int) $face['weight'] : 400;
				$asset  = self::resolve_face_asset( $slug, $file, $weight );
				if ( null !== $asset ) {
					$urls[] = $asset['url'];
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * @param string $body_slug    正文字体 slug。
	 * @param string $heading_slug 标题字体 slug。
	 * @return string :root CSS 变量。
	 */
	public static function build_css_variables( string $body_slug, string $heading_slug ): string {
		return sprintf(
			':root{--ts-font-body:%s;--ts-font-heading:%s;}',
			self::resolve_family( $body_slug ),
			self::resolve_family( $heading_slug )
		);
	}

	/**
	 * @param string $slug 字体 slug。
	 * @return string CSS font-family。
	 */
	private static function resolve_family( string $slug ): string {
		$font = self::get_font( $slug );
		if ( null === $font ) {
			$system = self::get_font( 'system' );
			return null !== $system ? (string) $system['family'] : 'ui-sans-serif, system-ui, sans-serif';
		}
		return (string) $font['family'];
	}

	/**
	 * @param string $value 原始 CSS 字符串。
	 */
	private static function css_string( string $value ): string {
		if ( preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
			return $value;
		}
		return '"' . str_replace( '"', '\\"', $value ) . '"';
	}
}
