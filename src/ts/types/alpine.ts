/**
 * Alpine 注入到组件 `this` 上的 magics。
 * 业务 state 类型应与之交叉，避免手写 `as` 强转。
 */
export type AlpineMagics = {
	$el: HTMLElement;
	$nextTick: (callback: () => void) => void;
};
