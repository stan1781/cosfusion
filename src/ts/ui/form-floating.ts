/**
 * Floating label state for `.form-layout-floating` forms.
 * Reusable on MailNest, checkout, or any markup using `.field__float`.
 * Also binds full-field click → showPicker() for date / time inputs.
 */

const FILLED_CLASS = 'is-filled';
const BOUND_ATTR = 'data-ts-float-bound';
const PICKER_BOUND_ATTR = 'data-ts-picker-bound';

const TEMPORAL_INPUT_SELECTOR =
	'input[type="date"], input[type="time"], input[type="datetime-local"], input[type="month"], input[type="week"]';

type FloatControl = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

function getFloatControl(wrapper: HTMLElement): FloatControl | null {
	return wrapper.querySelector<FloatControl>('input, textarea, select');
}

function isControlFilled(control: FloatControl): boolean {
	if (control instanceof HTMLSelectElement) {
		return control.value !== '';
	}

	return control.value.trim() !== '';
}

export function syncFormFloatingField(wrapper: HTMLElement): void {
	const control = getFloatControl(wrapper);
	if (!control) {
		return;
	}

	wrapper.classList.toggle(FILLED_CLASS, isControlFilled(control));
}

function openTemporalPicker(control: HTMLInputElement): void {
	if (control.disabled || control.readOnly) {
		return;
	}

	if (typeof control.showPicker !== 'function') {
		return;
	}

	try {
		void control.showPicker();
	} catch {
		// 已打开、非用户手势或不支持时忽略。
	}
}

/**
 * stacked 上标签通过 for= 激活控件时，浏览器会再派一次 click 到 input。
 * floating 标签 pointer-events:none，命中仍是 input，不会误判。
 */
function isAssociatedLabelClick(control: HTMLInputElement, event: MouseEvent): boolean {
	const hit = document.elementFromPoint(event.clientX, event.clientY);
	if (!(hit instanceof Element)) {
		return false;
	}

	const label = hit.closest('label');
	return label instanceof HTMLLabelElement && label.control === control;
}

function bindTemporalPicker(control: HTMLInputElement): void {
	if (control.hasAttribute(PICKER_BOUND_ATTR)) {
		return;
	}

	control.setAttribute(PICKER_BOUND_ATTR, 'true');
	control.addEventListener('click', (event) => {
		if (isAssociatedLabelClick(control, event)) {
			return;
		}

		openTemporalPicker(control);
	});
}

function bindFormFloatingField(wrapper: HTMLElement): void {
	const control = getFloatControl(wrapper);
	if (!control || control.hasAttribute(BOUND_ATTR)) {
		return;
	}

	control.setAttribute(BOUND_ATTR, 'true');
	syncFormFloatingField(wrapper);

	const sync = (): void => {
		syncFormFloatingField(wrapper);
	};

	control.addEventListener('input', sync);
	control.addEventListener('change', sync);

	if (control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement) {
		control.addEventListener('animationstart', (event: Event) => {
			if (event instanceof AnimationEvent && event.animationName === 'textfield-autofill') {
				sync();
			}
		});
	}
}

/**
 * Wire floating-label filled state and temporal input pickers under `root`.
 */
export function initFormFloatingFields(root: ParentNode = document): void {
	root.querySelectorAll<HTMLElement>('.form-layout-floating .field__float').forEach(bindFormFloatingField);
	root.querySelectorAll<HTMLInputElement>(TEMPORAL_INPUT_SELECTOR).forEach(bindTemporalPicker);
}
