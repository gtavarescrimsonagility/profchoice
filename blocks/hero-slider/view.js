/**
 * Hero Slider behavior. Loaded only on pages containing the block.
 *
 * Motion starts paused for reduced-motion users, pauses during interaction or
 * when offscreen, and remains user-controllable for both slides and videos.
 */
( function () {
	'use strict';

	const reduced_motion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function sync_videos( slides, active_index, motion_allowed ) {
		slides.forEach( function ( slide, i ) {
			const video = slide.querySelector( 'video' );

			if ( ! video ) {
				return;
			}

			if ( i === active_index && motion_allowed ) {
				video.play().catch( function () {} );
			} else {
				video.pause();
			}
		} );
	}

	function init_slider( root ) {
		const track = root.querySelector( '.cbv-hero__track' );
		const slides = Array.prototype.slice.call( root.querySelectorAll( '.cbv-hero__slide' ) );
		const dots = Array.prototype.slice.call( root.querySelectorAll( '.cbv-hero__dot' ) );
		const motion_toggle = root.querySelector( '.cbv-hero__motion-toggle' );

		if ( ! track || ! slides.length ) {
			return;
		}

		let index = 0;
		let timer = null;
		let user_paused = reduced_motion;
		let interaction_paused = false;
		let visible_in_viewport = true;
		let visible = ! document.hidden;
		const autoplay = '1' === root.dataset.autoplay && slides.length > 1;
		const interval = ( parseInt( root.dataset.interval, 10 ) || 6 ) * 1000;

		function motion_allowed() {
			return ! user_paused && ! interaction_paused && visible;
		}

		function update_motion_control() {
			if ( ! motion_toggle ) {
				return;
			}

			const paused = user_paused;
			const label = paused ? motion_toggle.dataset.playLabel : motion_toggle.dataset.pauseLabel;
			const label_element = motion_toggle.querySelector( '.cbv-hero__motion-label' );

			motion_toggle.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );

			if ( label_element ) {
				label_element.textContent = label;
			}
		}

		function set_slide_active( slide, active ) {
			slide.classList.toggle( 'is-active', active );
			slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			slide.inert = ! active;
		}

		function go_to( next ) {
			index = ( next + slides.length ) % slides.length;
			track.style.transform = 'translateX(-' + index * 100 + '%)';

			slides.forEach( function ( slide, i ) {
				set_slide_active( slide, i === index );
			} );

			dots.forEach( function ( dot, i ) {
				const active = i === index;

				dot.classList.toggle( 'is-active', active );
				dot.setAttribute( 'aria-current', active ? 'true' : 'false' );
			} );

			sync_videos( slides, index, motion_allowed() );
		}

		function schedule() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}

			sync_videos( slides, index, motion_allowed() );

			if ( autoplay && motion_allowed() ) {
				timer = window.setInterval( function () {
					go_to( index + 1 );
				}, interval );
			}
		}

		const prev = root.querySelector( '.cbv-hero__arrow--prev' );
		const next = root.querySelector( '.cbv-hero__arrow--next' );

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				go_to( index - 1 );
				schedule();
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				go_to( index + 1 );
				schedule();
			} );
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				go_to( parseInt( dot.dataset.slide, 10 ) || 0 );
				schedule();
			} );
		} );

		if ( motion_toggle ) {
			motion_toggle.addEventListener( 'click', function () {
				user_paused = ! user_paused;
				interaction_paused = false;
				update_motion_control();
				schedule();
			} );
		}

		root.addEventListener( 'mouseenter', function () {
			interaction_paused = true;
			schedule();
		} );

		root.addEventListener( 'mouseleave', function () {
			interaction_paused = false;
			schedule();
		} );

		root.addEventListener( 'focusin', function () {
			interaction_paused = true;
			schedule();
		} );

		root.addEventListener( 'focusout', function () {
			window.setTimeout( function () {
				interaction_paused = root.contains( document.activeElement );
				schedule();
			}, 0 );
		} );

		document.addEventListener( 'visibilitychange', function () {
			visible = ! document.hidden && visible_in_viewport;
			schedule();
		} );

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) {
				visible_in_viewport = entries[ 0 ].isIntersecting;
				visible = ! document.hidden && visible_in_viewport;
				schedule();
			} ).observe( root );
		}

		// Touch swipe.
		let touch_x = null;

		root.addEventListener( 'pointerdown', function ( event ) {
			if ( 'touch' === event.pointerType ) {
				touch_x = event.clientX;
			}
		} );

		root.addEventListener( 'pointerup', function ( event ) {
			if ( 'touch' === event.pointerType && null !== touch_x ) {
				const delta = event.clientX - touch_x;

				if ( Math.abs( delta ) > 40 ) {
					go_to( delta < 0 ? index + 1 : index - 1 );
				}

				touch_x = null;
				schedule();
			}
		} );

		window.addEventListener( 'pagehide', function () {
			if ( timer ) {
				window.clearInterval( timer );
			}

			sync_videos( slides, index, false );
		} );

		update_motion_control();
		go_to( 0 );
		schedule();
	}

	document.querySelectorAll( '.cbv-hero' ).forEach( init_slider );
} )();
