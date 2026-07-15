/**
 * Floating label state for `.form-layout-floating` forms.
 * Reusable on MailNest, checkout, or any markup using `.field__float`.
 */

const FILLED_CLASS = 'is-filled';
const BOUND_ATTR = 'data-ts-float-bound';

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
 * Wire floating-label filled state for all float wrappers under `root`.
 */
export function initFormFloatingFields(root: ParentNode = document): void {
	root.querySelectorAll<HTMLElement>('.form-layout-floating .field__float').forEach(bindFormFloatingField);
}
