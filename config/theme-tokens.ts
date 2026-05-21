/** Responsive breakpoints — single source for Tailwind, CSS @custom-media, and :root vars. */
export const themeBreakpoints = {
	sm: '40rem',
	md: '48rem',
	lg: '64rem',
	xl: '80rem',
	'2xl': '96rem',
} as const;

export type ThemeBreakpointName = keyof typeof themeBreakpoints;

export const themeTokens = {
	breakpoint: themeBreakpoints,
	colors: {
		background: '#ffffff',
		surface: '#ffffff',
		surfaceMuted: '#fafafa',
		text: '#18181b',
		textMuted: '#52525b',
		border: '#e4e4e7',
		borderStrong: '#d4d4d8',
		primary: '#18181b',
		primaryForeground: '#ffffff',
	},
	radius: {
		sm: '0.5rem',
		md: '0.75rem',
	},
	space: {
		xs: '0.75rem',
		sm: '1rem',
		md: '1.5rem',
		lg: '2.5rem',
	},
	form: {
		paddingY: '0.5rem',
		paddingX: '0.75rem',
		/** Unified control height: 38px at 16px root (2.375rem). */
		minHeight: '2.375rem',
	},
	layout: {
		container: '72rem',
		search: '32rem',
	},
	type: {
		body: 'ui-sans-serif, system-ui, sans-serif',
		brandSize: '1.25rem',
		headingSize: '1.875rem',
		headingLineHeight: '1.2',
	},
	shadow: {
		card: '0 1px 2px rgb(24 24 27 / 0.06)',
	},
	woocommerce: {
		noticeBackground: '#fafafa',
		price: '#85bb65',
	},
} as const;

type FlatTokenMap = Record<string, string>;

function toKebabCase(value: string): string {
	return value.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
}

function flattenTokens(
	source: Record<string, string | Record<string, string>>,
	prefix: string
): FlatTokenMap {
	const entries: FlatTokenMap = {};

	for (const [group, value] of Object.entries(source)) {
		if (typeof value === 'string') {
			entries[`--${prefix}-${toKebabCase(group)}`] = value;
			continue;
		}

		for (const [name, token] of Object.entries(value)) {
			entries[`--${prefix}-${toKebabCase(group)}-${toKebabCase(name)}`] = token;
		}
	}

	return entries;
}

function buildBreakpointThemeBlock(): string {
	const lines = Object.entries(themeBreakpoints).map(
		([name, value]) => `\t--breakpoint-${name}: ${value};`
	);

	return `@theme {\n${lines.join('\n')}\n}`;
}

export function buildThemeTokenCss(): string {
	const variables = flattenTokens(themeTokens as Record<string, string | Record<string, string>>, 'ts');
	const rootLines = Object.entries(variables).map(([name, value]) => `\t${name}: ${value};`);

	return [
		'/* Auto-generated from config/theme-tokens.ts */',
		buildBreakpointThemeBlock(),
		`:root {\n${rootLines.join('\n')}\n}`,
	].join('\n\n');
}

export const tailwindTheme = {
	screens: { ...themeBreakpoints },
	colors: {
		background: themeTokens.colors.background,
		surface: themeTokens.colors.surface,
		'surface-muted': themeTokens.colors.surfaceMuted,
		text: themeTokens.colors.text,
		'text-muted': themeTokens.colors.textMuted,
		border: themeTokens.colors.border,
		'border-strong': themeTokens.colors.borderStrong,
		primary: themeTokens.colors.primary,
		'primary-foreground': themeTokens.colors.primaryForeground,
	},
	borderRadius: {
		sm: themeTokens.radius.sm,
		md: themeTokens.radius.md,
	},
	spacing: {
		'section': themeTokens.space.lg,
		'stack-sm': themeTokens.space.sm,
		'stack-md': themeTokens.space.md,
	},
	maxWidth: {
		container: themeTokens.layout.container,
		search: themeTokens.layout.search,
	},
	fontFamily: {
		sans: [themeTokens.type.body],
	},
	fontSize: {
		brand: themeTokens.type.brandSize,
		heading: [themeTokens.type.headingSize, { lineHeight: themeTokens.type.headingLineHeight }],
	},
	boxShadow: {
		card: themeTokens.shadow.card,
	},
} as const;
