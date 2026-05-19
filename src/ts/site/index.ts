document.documentElement.classList.add('js');

document.addEventListener('alpine:init', () => {
	window.Alpine.data('siteShell', () => ({
		menuOpen: false,
		searchOpen: false,
		toggleMenu() {
			this.menuOpen = !this.menuOpen;

			if (this.menuOpen) {
				this.searchOpen = false;
			}
		},
		toggleSearch() {
			this.searchOpen = !this.searchOpen;

			if (this.searchOpen) {
				this.menuOpen = false;
			}
		},
		closeMenu() {
			this.menuOpen = false;
		},
		closeSearch() {
			this.searchOpen = false;
		},
	}));
});
