/**
 * Editor UI for commercebuild Velocity blocks.
 *
 * Dependency-free (no build step): dynamic blocks previewed with
 * ServerSideRender so the editor shows exactly what the frontend renders.
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps, MediaUpload, MediaUploadCheck, InnerBlocks } = wp.blockEditor;
	const { PanelBody, TextControl, RangeControl, ToggleControl, SelectControl, Button } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	const orderbyOptions = [
		{ label: __( 'Newest', 'commercebuild-velocity' ), value: 'date' },
		{ label: __( 'Title', 'commercebuild-velocity' ), value: 'title' },
		{ label: __( 'Price', 'commercebuild-velocity' ), value: 'price' },
		{ label: __( 'Popularity', 'commercebuild-velocity' ), value: 'popularity' },
		{ label: __( 'Menu order', 'commercebuild-velocity' ), value: 'menu_order' },
		{ label: __( 'Random', 'commercebuild-velocity' ), value: 'rand' }
	];

	function preview( blockName, attributes ) {
		return el( ServerSideRender, { block: blockName, attributes: attributes } );
	}

	function headingControl( attributes, setAttributes ) {
		return el( TextControl, {
			label: __( 'Heading', 'commercebuild-velocity' ),
			value: attributes.heading,
			onChange: function ( value ) {
				setAttributes( { heading: value } );
			}
		} );
	}

	function rangeControl( label, attr, attributes, setAttributes, min, max ) {
		return el( RangeControl, {
			label: label,
			value: attributes[ attr ],
			min: min,
			max: max,
			onChange: function ( value ) {
				setAttributes( ( function () {
					const update = {};
					update[ attr ] = value;
					return update;
				} )() );
			}
		} );
	}

	function toggleControl( label, attr, attributes, setAttributes ) {
		return el( ToggleControl, {
			label: label,
			checked: attributes[ attr ],
			onChange: function ( value ) {
				const update = {};
				update[ attr ] = value;
				setAttributes( update );
			}
		} );
	}

	registerBlockType( 'commercebuild/product-grid', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Products', 'commercebuild-velocity' ) },
						headingControl( attributes, setAttributes ),
						rangeControl( __( 'Products', 'commercebuild-velocity' ), 'limit', attributes, setAttributes, 1, 48 ),
						rangeControl( __( 'Columns', 'commercebuild-velocity' ), 'columns', attributes, setAttributes, 1, 6 ),
						el( TextControl, {
							label: __( 'Category IDs (comma-separated)', 'commercebuild-velocity' ),
							value: attributes.categoryIds,
							onChange: function ( value ) {
								setAttributes( { categoryIds: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Specific product IDs (comma-separated)', 'commercebuild-velocity' ),
							value: attributes.includeIds,
							onChange: function ( value ) {
								setAttributes( { includeIds: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Order by', 'commercebuild-velocity' ),
							value: attributes.orderby,
							options: orderbyOptions,
							onChange: function ( value ) {
								setAttributes( { orderby: value } );
							}
						} ),
						toggleControl( __( 'Featured only', 'commercebuild-velocity' ), 'featured', attributes, setAttributes ),
						toggleControl( __( 'On sale only', 'commercebuild-velocity' ), 'onSale', attributes, setAttributes ),
						toggleControl( __( 'Show price', 'commercebuild-velocity' ), 'showPrice', attributes, setAttributes ),
						toggleControl( __( 'Show SKU', 'commercebuild-velocity' ), 'showSku', attributes, setAttributes ),
						toggleControl( __( 'Show unit of measure (UOM)', 'commercebuild-velocity' ), 'showUom', attributes, setAttributes ),
						toggleControl( __( 'Show add to cart', 'commercebuild-velocity' ), 'showAddToCart', attributes, setAttributes )
					)
				),
				preview( 'commercebuild/product-grid', attributes )
			);
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'commercebuild/related-products', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Related products', 'commercebuild-velocity' ) },
						headingControl( attributes, setAttributes ),
						rangeControl( __( 'Products', 'commercebuild-velocity' ), 'limit', attributes, setAttributes, 1, 12 ),
						rangeControl( __( 'Columns', 'commercebuild-velocity' ), 'columns', attributes, setAttributes, 1, 6 ),
						toggleControl( __( 'Show price', 'commercebuild-velocity' ), 'showPrice', attributes, setAttributes ),
						toggleControl( __( 'Show add to cart', 'commercebuild-velocity' ), 'showAddToCart', attributes, setAttributes )
					)
				),
				preview( 'commercebuild/related-products', attributes )
			);
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'commercebuild/hot-deals', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;

			const controls = [
				headingControl( attributes, setAttributes ),
				el( SelectControl, {
					label: __( 'Product source', 'commercebuild-velocity' ),
					value: attributes.source,
					options: [
						{ label: __( 'On sale', 'commercebuild-velocity' ), value: 'on_sale' },
						{ label: __( 'Best selling (most purchased)', 'commercebuild-velocity' ), value: 'best_selling' },
						{ label: __( 'Top rated', 'commercebuild-velocity' ), value: 'top_rated' },
						{ label: __( 'Newest', 'commercebuild-velocity' ), value: 'newest' },
						{ label: __( 'Pick products manually', 'commercebuild-velocity' ), value: 'manual' }
					],
					onChange: function ( value ) {
						setAttributes( { source: value } );
					}
				} )
			];

			if ( 'manual' === attributes.source ) {
				controls.push( el( TextControl, {
					label: __( 'Product IDs (comma-separated, in display order)', 'commercebuild-velocity' ),
					value: attributes.includeIds,
					onChange: function ( value ) {
						setAttributes( { includeIds: value } );
					}
				} ) );
			}

			controls.push(
				rangeControl( __( 'Products', 'commercebuild-velocity' ), 'limit', attributes, setAttributes, 1, 48 ),
				rangeControl( __( 'Columns', 'commercebuild-velocity' ), 'columns', attributes, setAttributes, 1, 6 ),
				toggleControl( __( 'Show price', 'commercebuild-velocity' ), 'showPrice', attributes, setAttributes ),
				toggleControl( __( 'Show SKU', 'commercebuild-velocity' ), 'showSku', attributes, setAttributes ),
				toggleControl( __( 'Show add to cart', 'commercebuild-velocity' ), 'showAddToCart', attributes, setAttributes )
			);

			return el(
				'div',
				useBlockProps(),
				el( InspectorControls, null, el( PanelBody, { title: __( 'Hot Deals', 'commercebuild-velocity' ) }, controls ) ),
				preview( 'commercebuild/hot-deals', attributes )
			);
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'commercebuild/hero-slider', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const slides = attributes.slides || [];

			function updateSlide( index, patch ) {
				const next = slides.map( function ( slide, i ) {
					return i === index ? Object.assign( {}, slide, patch ) : slide;
				} );
				setAttributes( { slides: next } );
			}

			function moveSlide( index, dir ) {
				const target = index + dir;
				if ( target < 0 || target >= slides.length ) {
					return;
				}
				const next = slides.slice();
				const tmp = next[ index ];
				next[ index ] = next[ target ];
				next[ target ] = tmp;
				setAttributes( { slides: next } );
			}

			function slideText( label, index, field ) {
				return el( TextControl, {
					label: label,
					value: slides[ index ][ field ] || '',
					onChange: function ( value ) {
						const patch = {};
						patch[ field ] = value;
						updateSlide( index, patch );
					}
				} );
			}

			const slidePanels = slides.map( function ( slide, index ) {
				return el(
					PanelBody,
					{
						title: __( 'Slide', 'commercebuild-velocity' ) + ' ' + ( index + 1 ) + ( slide.heading ? ' - ' + slide.heading : '' ),
						initialOpen: false,
						key: 'slide-' + index
					},
					el( MediaUploadCheck, null, el( MediaUpload, {
						allowedTypes: [ 'image', 'video' ],
						value: slide.mediaId,
						onSelect: function ( media ) {
							updateSlide( index, {
								mediaId: media.id,
								mediaUrl: media.url,
								type: 0 === media.mime.indexOf( 'video' ) ? 'video' : 'image'
							} );
						},
						render: function ( args ) {
							return el( Button, { variant: 'secondary', onClick: args.open },
								slide.mediaUrl
									? __( 'Replace media', 'commercebuild-velocity' )
									: __( 'Select image or video', 'commercebuild-velocity' ) );
						}
					} ) ),
					slide.mediaUrl ? el( 'p', { style: { fontSize: '11px', wordBreak: 'break-all' } }, slide.mediaUrl ) : null,
					slideText( __( 'Heading', 'commercebuild-velocity' ), index, 'heading' ),
					slideText( __( 'Text', 'commercebuild-velocity' ), index, 'text' ),
					slideText( __( 'Button text', 'commercebuild-velocity' ), index, 'buttonText' ),
					slideText( __( 'Button URL', 'commercebuild-velocity' ), index, 'buttonUrl' ),
					el( RangeControl, {
						label: __( 'Overlay darkness (%)', 'commercebuild-velocity' ),
						value: slide.overlay === undefined ? 30 : slide.overlay,
						min: 0,
						max: 80,
						onChange: function ( value ) {
							updateSlide( index, { overlay: value } );
						}
					} ),
					el( 'div', { style: { display: 'flex', gap: '8px' } },
						el( Button, { variant: 'secondary', disabled: 0 === index, onClick: function () { moveSlide( index, -1 ); } }, '↑' ),
						el( Button, { variant: 'secondary', disabled: index === slides.length - 1, onClick: function () { moveSlide( index, 1 ); } }, '↓' ),
						el( Button, {
							variant: 'secondary',
							isDestructive: true,
							onClick: function () {
								setAttributes( { slides: slides.filter( function ( unused, i ) { return i !== index; } ) } );
							}
						}, __( 'Remove', 'commercebuild-velocity' ) )
					)
				);
			} );

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Slider settings', 'commercebuild-velocity' ) },
						toggleControl( __( 'Autoplay', 'commercebuild-velocity' ), 'autoplay', attributes, setAttributes ),
						rangeControl( __( 'Autoplay interval (seconds)', 'commercebuild-velocity' ), 'interval', attributes, setAttributes, 2, 30 ),
						rangeControl( __( 'Minimum height (px)', 'commercebuild-velocity' ), 'minHeight', attributes, setAttributes, 200, 900 ),
						toggleControl( __( 'Show arrows', 'commercebuild-velocity' ), 'showArrows', attributes, setAttributes ),
						toggleControl( __( 'Show dots', 'commercebuild-velocity' ), 'showDots', attributes, setAttributes ),
						el( Button, {
							variant: 'primary',
							onClick: function () {
								setAttributes( { slides: slides.concat( [ { type: 'image', mediaId: 0, mediaUrl: '', heading: '', text: '', buttonText: '', buttonUrl: '', overlay: 30 } ] ) } );
							}
						}, __( 'Add slide', 'commercebuild-velocity' ) )
					),
					slidePanels
				),
				slides.length
					? preview( 'commercebuild/hero-slider', attributes )
					: el( 'div', { style: { padding: '48px', textAlign: 'center', background: '#f6f7f9', border: '1px dashed #cbd5e1' } },
						__( 'Hero Slider: add slides from the block settings panel.', 'commercebuild-velocity' ) )
			);
		},
		save: function () {
			return null;
		}
	} );

	// Header component blocks: simple inspector controls + live server preview.
	[
		{
			name: 'commercebuild/header-search',
			panel: __( 'Search', 'commercebuild-velocity' ),
			controls: [
				[ 'text', 'placeholder', __( 'Placeholder', 'commercebuild-velocity' ) ],
				[ 'text', 'buttonText', __( 'Button text', 'commercebuild-velocity' ) ],
				[ 'toggle', 'showButton', __( 'Show button', 'commercebuild-velocity' ) ],
				[ 'select', 'buttonStyle', __( 'Button style', 'commercebuild-velocity' ), [
					{ label: __( 'Text label', 'commercebuild-velocity' ), value: 'text' },
					{ label: __( 'Magnifier icon', 'commercebuild-velocity' ), value: 'icon' }
				] ],
				[ 'toggle', 'showFieldIcon', __( 'Icon inside the field', 'commercebuild-velocity' ) ]
			]
		},
		{
			name: 'commercebuild/header-cart',
			panel: __( 'Cart', 'commercebuild-velocity' ),
			controls: [
				[ 'text', 'label', __( 'Label', 'commercebuild-velocity' ) ],
				[ 'select', 'icon', __( 'Icon', 'commercebuild-velocity' ), [
					{ label: __( 'Cart', 'commercebuild-velocity' ), value: 'cart' },
					{ label: __( 'Shopping bag', 'commercebuild-velocity' ), value: 'bag' },
					{ label: __( 'Basket', 'commercebuild-velocity' ), value: 'basket' },
					{ label: __( 'None', 'commercebuild-velocity' ), value: 'none' }
				] ],
				[ 'toggle', 'showLabel', __( 'Show label', 'commercebuild-velocity' ) ],
				[ 'toggle', 'showCount', __( 'Show item count', 'commercebuild-velocity' ) ],
				[ 'toggle', 'showTotal', __( 'Show cart total', 'commercebuild-velocity' ) ]
			]
		},
		{
			name: 'commercebuild/header-account',
			panel: __( 'Account', 'commercebuild-velocity' ),
			controls: [
				[ 'text', 'label', __( 'Label', 'commercebuild-velocity' ) ],
				[ 'select', 'icon', __( 'Icon', 'commercebuild-velocity' ), [
					{ label: __( 'Person', 'commercebuild-velocity' ), value: 'user' },
					{ label: __( 'None', 'commercebuild-velocity' ), value: 'none' }
				] ],
				[ 'toggle', 'showLabel', __( 'Show label', 'commercebuild-velocity' ) ],
				[ 'toggle', 'showGreeting', __( 'Greet logged-in customers by name', 'commercebuild-velocity' ) ]
			]
		},
		{
			name: 'commercebuild/header-nav',
			panel: __( 'Menu', 'commercebuild-velocity' ),
			controls: [
				[ 'select', 'location', __( 'Menu location', 'commercebuild-velocity' ), [
					{ label: __( 'Primary', 'commercebuild-velocity' ), value: 'primary' },
					{ label: __( 'Footer', 'commercebuild-velocity' ), value: 'footer' }
				] ],
				[ 'select', 'orientation', __( 'Orientation', 'commercebuild-velocity' ), [
					{ label: __( 'Horizontal', 'commercebuild-velocity' ), value: 'horizontal' },
					{ label: __( 'Vertical', 'commercebuild-velocity' ), value: 'vertical' }
				] ],
				[ 'range', 'depth', __( 'Levels', 'commercebuild-velocity' ), 1, 4 ]
			]
		}
	].forEach( function ( config ) {
		registerBlockType( config.name, {
			edit: function ( props ) {
				const { attributes, setAttributes } = props;

				const fields = config.controls.map( function ( control ) {
					const type = control[ 0 ];
					const attr = control[ 1 ];
					const label = control[ 2 ];

					if ( 'toggle' === type ) {
						return toggleControl( label, attr, attributes, setAttributes );
					}

					if ( 'select' === type ) {
						return el( SelectControl, {
							label: label,
							value: attributes[ attr ],
							options: control[ 3 ],
							onChange: function ( value ) {
								const update = {};
								update[ attr ] = value;
								setAttributes( update );
							}
						} );
					}

					if ( 'range' === type ) {
						return rangeControl( label, attr, attributes, setAttributes, control[ 3 ], control[ 4 ] );
					}

					return el( TextControl, {
						label: label,
						value: attributes[ attr ],
						onChange: function ( value ) {
							const update = {};
							update[ attr ] = value;
							setAttributes( update );
						}
					} );
				} );

				return el(
					'div',
					useBlockProps(),
					el( InspectorControls, null, el( PanelBody, { title: config.panel }, fields ) ),
					preview( config.name, attributes )
				);
			},
			save: function () {
				return null;
			}
		} );
	} );

	function portalTab( title, shortcode, active ) {
		return [ 'commercebuild/account-tab', { title: title, active: active }, [ [ 'core/shortcode', { text: shortcode } ] ] ];
	}

	// Only offer portal tabs whose shortcodes exist on this site, so a plain
	// WooCommerce install never renders literal shortcode text.
	const availablePortalTabs = ( window.cbvAccountTabs && window.cbvAccountTabs.tabs ) || [];

	const accountTabsTemplate = availablePortalTabs
		.map( function ( tab ) {
			return portalTab( tab.title, tab.shortcode, tab.active );
		} )
		.concat( [
			[ 'commercebuild/account-tab', { title: 'Account', active: true }, [ [ 'core/shortcode', { text: '[woocommerce_my_account]' } ] ] ]
		] );

	registerBlockType( 'commercebuild/account-tabs', {
		edit: function () {
			return el(
				'div',
				useBlockProps( { className: 'cbv-atabs-editor' } ),
				el( 'div', { className: 'cbv-atabs-editor__label' },
					__( 'Account Tabs — each tab below is its own editor. Use each tab\'s Active toggle to deactivate it without deleting it.', 'commercebuild-velocity' ) ),
				el( InnerBlocks, {
					allowedBlocks: [ 'commercebuild/account-tab' ],
					template: accountTabsTemplate,
					renderAppender: InnerBlocks.ButtonBlockAppender
				} )
			);
		},
		save: function () {
			return el( InnerBlocks.Content );
		}
	} );

	registerBlockType( 'commercebuild/account-tab', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;

			return el(
				'div',
				useBlockProps( { className: 'cbv-atab-editor' + ( attributes.active ? '' : ' is-inactive' ) } ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Tab settings', 'commercebuild-velocity' ) },
						el( TextControl, {
							label: __( 'Tab title', 'commercebuild-velocity' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							}
						} ),
						toggleControl( __( 'Active (shown on the site)', 'commercebuild-velocity' ), 'active', attributes, setAttributes )
					)
				),
				el( 'div', { className: 'cbv-atab-editor__bar' },
					el( 'input', {
						className: 'cbv-atab-editor__title',
						type: 'text',
						value: attributes.title,
						placeholder: __( 'Tab title…', 'commercebuild-velocity' ),
						onChange: function ( event ) {
							setAttributes( { title: event.target.value } );
						}
					} ),
					attributes.active
						? null
						: el( 'span', { className: 'cbv-atab-editor__badge' }, __( 'Deactivated', 'commercebuild-velocity' ) )
				),
				el( 'div', { className: 'cbv-atab-editor__content' },
					el( InnerBlocks, { template: [ [ 'core/paragraph' ] ] } ) )
			);
		},
		save: function () {
			return el( InnerBlocks.Content );
		}
	} );

	registerBlockType( 'commercebuild/category-tiles', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Categories', 'commercebuild-velocity' ) },
						headingControl( attributes, setAttributes ),
						el( TextControl, {
							label: __( 'Parent category ID (0 = top level)', 'commercebuild-velocity' ),
							type: 'number',
							value: attributes.parent,
							onChange: function ( value ) {
								setAttributes( { parent: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: __( 'Specific category IDs (comma-separated)', 'commercebuild-velocity' ),
							value: attributes.includeIds,
							onChange: function ( value ) {
								setAttributes( { includeIds: value } );
							}
						} ),
						rangeControl( __( 'Tiles', 'commercebuild-velocity' ), 'limit', attributes, setAttributes, 1, 48 ),
						rangeControl( __( 'Columns', 'commercebuild-velocity' ), 'columns', attributes, setAttributes, 1, 6 ),
						toggleControl( __( 'Show product counts', 'commercebuild-velocity' ), 'showCount', attributes, setAttributes )
					)
				),
				preview( 'commercebuild/category-tiles', attributes )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp );
