/**
 * Collapse Kirki repeater rows on Customizer load.
 *
 * Kirki expands every repeater row in addRow(), including defaults.
 */
( function () {
	'use strict';

	function collapseRepeaterRow( row ) {
		if ( ! row || row.classList.contains( 'minimized' ) ) {
			return;
		}

		row.classList.add( 'minimized' );

		var icon = row.querySelector( '.repeater-row-header .dashicons' );

		if ( icon ) {
			icon.classList.remove( 'dashicons-arrow-up' );
			icon.classList.add( 'dashicons-arrow-down' );
		}
	}

	function collapseRepeaterRows( root ) {
		var scope = root || document;

		scope.querySelectorAll( '.customize-control-repeater .repeater-row' ).forEach( collapseRepeaterRow );
	}

	function scheduleInitialCollapse() {
		window.setTimeout( function () {
			collapseRepeaterRows();
		}, 100 );

		window.setTimeout( function () {
			collapseRepeaterRows();
		}, 500 );
	}

	if ( typeof wp !== 'undefined' && wp.customize ) {
		wp.customize.bind( 'ready', scheduleInitialCollapse );
	}
}() );
