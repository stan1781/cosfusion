/**
 * 解析 data 属性上的列数（0.5 步进），夹在 [1, max]。
 */
export function parseSlideCount(value: string | undefined, fallback: number, max = 6): number {
	const parsed = Number.parseFloat(value ?? '');

	if (Number.isNaN(parsed)) {
		return fallback;
	}

	return Math.max(1, Math.min(max, Math.round(parsed * 2) / 2));
}

/**
 * 解析间距（px），夹在 [0, 48]。
 */
export function parseGap(value: string | undefined, fallback = 16): number {
	const parsed = Number.parseInt(value ?? '', 10);

	if (Number.isNaN(parsed)) {
		return fallback;
	}

	return Math.max(0, Math.min(48, parsed));
}
