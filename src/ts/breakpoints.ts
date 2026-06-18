/** Match `src/scss/abstracts/_variables.scss` breakpoint tokens. */
export const TS_BREAKPOINT_SM_REM = 40;
export const TS_BREAKPOINT_MD_REM = 48;
export const TS_BREAKPOINT_LG_REM = 64;
export const TS_BREAKPOINT_XL_REM = 80;
export const TS_BREAKPOINT_2XL_REM = 96;

export const TS_MEDIA_UP = {
	sm: `(width >= ${TS_BREAKPOINT_SM_REM}rem)`,
	md: `(width >= ${TS_BREAKPOINT_MD_REM}rem)`,
	lg: `(width >= ${TS_BREAKPOINT_LG_REM}rem)`,
	xl: `(width >= ${TS_BREAKPOINT_XL_REM}rem)`,
	'2xl': `(width >= ${TS_BREAKPOINT_2XL_REM}rem)`,
} as const;

export const TS_MEDIA_DOWN = {
	sm: `(width < ${TS_BREAKPOINT_SM_REM}rem)`,
	md: `(width < ${TS_BREAKPOINT_MD_REM}rem)`,
	lg: `(width < ${TS_BREAKPOINT_LG_REM}rem)`,
	xl: `(width < ${TS_BREAKPOINT_XL_REM}rem)`,
	'2xl': `(width < ${TS_BREAKPOINT_2XL_REM}rem)`,
} as const;
