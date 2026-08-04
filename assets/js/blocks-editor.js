/**
 * Init Live Search — Block Editor integration.
 *
 * Viết bằng vanilla JS (không JSX, không build step) để deploy trực tiếp lên
 * SVN của WordPress.org mà không cần Node/webpack. Mỗi block dùng
 * ServerSideRender để xem trước, và PHP render.php tương ứng (đăng ký qua
 * "render" trong block.json) để xuất HTML — dùng lại 100% logic shortcode
 * đã có, không lặp lại code.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender;

	function toInt( value, fallback ) {
		var parsed = parseInt( value, 10 );
		return isNaN( parsed ) ? fallback : parsed;
	}

	// Best-effort helper: while editing a post, default the related-posts
	// preview to the current post so "0 = current post" isn't empty in the
	// editor canvas. Never overwrites the saved attribute — postId stays 0
	// unless the user explicitly sets it in the sidebar.
	function getEditedPostIdForPreview() {
		if ( ! wp.data || ! wp.data.select || ! wp.data.select( 'core/editor' ) ) {
			return 0;
		}
		var id = wp.data.select( 'core/editor' ).getCurrentPostId();
		return id ? id : 0;
	}

	// ---------------------------------------------------------------------
	// init-live-search/search-box
	// ---------------------------------------------------------------------
	registerBlockType( 'init-live-search/search-box', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Search Box Settings', 'init-live-search' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Type', 'init-live-search' ),
							value: attributes.boxType,
							options: [
								{ label: __( 'Icon', 'init-live-search' ), value: 'icon' },
								{ label: __( 'Input', 'init-live-search' ), value: 'input' },
							],
							onChange: function ( value ) {
								setAttributes( { boxType: value } );
							},
						} ),
						'input' === attributes.boxType &&
							el( TextControl, {
								label: __( 'Placeholder (input mode)', 'init-live-search' ),
								value: attributes.placeholder,
								onChange: function ( value ) {
									setAttributes( { placeholder: value } );
								},
							} ),
						'icon' === attributes.boxType &&
							el( TextControl, {
								label: __( 'Label (icon mode)', 'init-live-search' ),
								value: attributes.label,
								onChange: function ( value ) {
									setAttributes( { label: value } );
								},
							} ),
						el( TextControl, {
							label: __( 'Custom CSS class', 'init-live-search' ),
							value: attributes.htmlClass,
							onChange: function ( value ) {
								setAttributes( { htmlClass: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Element ID', 'init-live-search' ),
							value: attributes.htmlId,
							onChange: function ( value ) {
								setAttributes( { htmlId: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'ARIA Label', 'init-live-search' ),
							value: attributes.ariaLabel,
							onChange: function ( value ) {
								setAttributes( { ariaLabel: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Layout', 'init-live-search' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Width', 'init-live-search' ),
							value: attributes.width,
							placeholder: __( 'e.g. 320px, 100%, 20rem', 'init-live-search' ),
							onChange: function ( value ) {
								setAttributes( { width: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Max Width', 'init-live-search' ),
							value: attributes.maxWidth,
							placeholder: __( 'e.g. 480px', 'init-live-search' ),
							onChange: function ( value ) {
								setAttributes( { maxWidth: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Align', 'init-live-search' ),
							value: attributes.align,
							options: [
								{ label: __( 'Default', 'init-live-search' ), value: '' },
								{ label: __( 'Left', 'init-live-search' ), value: 'left' },
								{ label: __( 'Center', 'init-live-search' ), value: 'center' },
								{ label: __( 'Right', 'init-live-search' ), value: 'right' },
							],
							onChange: function ( value ) {
								setAttributes( { align: value } );
							},
						} ),
						'input' === attributes.boxType &&
							el( TextControl, {
								label: __( 'Border Radius (input mode)', 'init-live-search' ),
								value: attributes.radius,
								onChange: function ( value ) {
									setAttributes( { radius: value } );
								},
							} ),
						el( TextControl, {
							label: __( 'Stroke Width', 'init-live-search' ),
							value: attributes.strokeWidth,
							onChange: function ( value ) {
								setAttributes( { strokeWidth: value } );
							},
						} ),
						'input' === attributes.boxType &&
							el( TextControl, {
								label: __( 'Input name (input mode)', 'init-live-search' ),
								value: attributes.inputName,
								onChange: function ( value ) {
									setAttributes( { inputName: value } );
								},
							} ),
						'input' === attributes.boxType &&
							el( ToggleControl, {
								label: __( 'Show Search Button (input mode)', 'init-live-search' ),
								checked: !! attributes.showButton,
								onChange: function ( value ) {
									setAttributes( { showButton: value } );
								},
							} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'init-live-search/search-box',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	// ---------------------------------------------------------------------
	// init-live-search/related-posts
	// ---------------------------------------------------------------------
	registerBlockType( 'init-live-search/related-posts', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var previewAttributes = Object.assign( {}, attributes );

			if ( ! previewAttributes.postId ) {
				previewAttributes.postId = getEditedPostIdForPreview();
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Related Posts Settings', 'init-live-search' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Post ID (0 = current post)', 'init-live-search' ),
							type: 'number',
							value: attributes.postId,
							onChange: function ( value ) {
								setAttributes( { postId: toInt( value, 0 ) } );
							},
						} ),
						el( TextControl, {
							label: __( 'Number of Posts', 'init-live-search' ),
							type: 'number',
							value: attributes.count,
							onChange: function ( value ) {
								setAttributes( { count: toInt( value, 5 ) } );
							},
						} ),
						el( TextControl, {
							label: __( 'Keyword (override)', 'init-live-search' ),
							value: attributes.keyword,
							onChange: function ( value ) {
								setAttributes( { keyword: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Post Type', 'init-live-search' ),
							value: attributes.postType,
							onChange: function ( value ) {
								setAttributes( { postType: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Template', 'init-live-search' ),
							value: attributes.template,
							help: __( 'default, classic, compact, grid, or thumbright', 'init-live-search' ),
							onChange: function ( value ) {
								setAttributes( { template: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Load CSS', 'init-live-search' ),
							checked: !! attributes.loadCss,
							onChange: function ( value ) {
								setAttributes( { loadCss: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Output Schema', 'init-live-search' ),
							checked: !! attributes.showSchema,
							onChange: function ( value ) {
								setAttributes( { showSchema: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'init-live-search/related-posts',
						attributes: previewAttributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	// ---------------------------------------------------------------------
	// init-live-search/related-ai
	// ---------------------------------------------------------------------
	registerBlockType( 'init-live-search/related-ai', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var previewAttributes = Object.assign( {}, attributes );

			if ( ! previewAttributes.postId ) {
				previewAttributes.postId = getEditedPostIdForPreview();
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'AI Related Posts Settings', 'init-live-search' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Post ID (0 = current post)', 'init-live-search' ),
							type: 'number',
							value: attributes.postId,
							onChange: function ( value ) {
								setAttributes( { postId: toInt( value, 0 ) } );
							},
						} ),
						el( TextControl, {
							label: __( 'Number of Posts', 'init-live-search' ),
							type: 'number',
							value: attributes.count,
							onChange: function ( value ) {
								setAttributes( { count: toInt( value, 5 ) } );
							},
						} ),
						el( TextControl, {
							label: __( 'Post Type', 'init-live-search' ),
							value: attributes.postType,
							onChange: function ( value ) {
								setAttributes( { postType: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Template', 'init-live-search' ),
							value: attributes.template,
							help: __( 'default, classic, compact, grid, or thumbright', 'init-live-search' ),
							onChange: function ( value ) {
								setAttributes( { template: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Load CSS', 'init-live-search' ),
							checked: !! attributes.loadCss,
							onChange: function ( value ) {
								setAttributes( { loadCss: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Output Schema', 'init-live-search' ),
							checked: !! attributes.showSchema,
							onChange: function ( value ) {
								setAttributes( { showSchema: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'init-live-search/related-ai',
						attributes: previewAttributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
