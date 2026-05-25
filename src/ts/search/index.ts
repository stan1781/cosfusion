if (document.querySelector('form[role="search"]')) {
	document.documentElement.classList.add('has-search-ui');
}

import { initAjaxLiveSearch } from './ajax-live';

if (document.querySelector('form[data-ts-ajax-search]') && window.tailwindscoreAjaxSearch) {
	initAjaxLiveSearch();
}
