<?php

declare( strict_types = 1 );
namespace WaughJ\WPPostType;

use WaughJ\TestHashItem\TestHashItem;
use WaughJ\VerifiedArguments\VerifiedArguments;
use WaughJ\WPMetaBox\WPMetaBox;

class WPPostType
{
	//
	//  PUBLIC
	//
	/////////////////////////////////////////////////////////

		public function __construct( string $slug, string $name, array $args = [] )
		{
			$this->slug = sanitize_title( $slug );
			$this->name = sanitize_text_field( $name );
			$this->otherArguments = new VerifiedArguments( $args, $this->generateDefaultArguments() );
			$this->registerPostType();
			$this->registerMessages();
			$this->registerTableOfContents();
			$this->registerMetaBoxes();
		}

		public function getSlug() : string
		{
			return $this->slug;
		}

		public function getMetaBox( string $slug ) : ?WPMetaBox
		{
			return $this->metaBoxes[ $slug ] ?? null;
		}

		public function getName() : string
		{
			return $this->name;
		}

		public function getSingularName() : string
		{
			return $this->otherArguments->get( 'singular_name' );
		}



	//
	//  PRIVATE
	//
	/////////////////////////////////////////////////////////

		private function registerPostType() : void
		{
			add_action
			(
				'init',
				function()
				{
					register_post_type
					(
						$this->slug,
						$this->generateArguments()
					);
				}
			);
		}

		private function registerMessages() : void
		{
			add_action
			(
				'post_updated_messages',
				fn( array $messages ) : array => array_merge( $messages, [ $this->slug => $this->generateMessagesList() ] )
			);
		}
	
		private function registerTableOfContents() : void
		{
			// If custom_toc set:
			if ( !empty( $this->otherArguments->get( 'custom_toc' ) ) )
			{
				add_filter
				(
					"manage_{$this->slug}_posts_columns",
					fn( array $columns ) : array => $this->sortColumns( $this->addCustomColumns( $this->removeUnsetItems( $columns ) ) ),
					10,
					2
				);

				add_action( "manage_{$this->slug}_posts_custom_column", $this->generateTableOfContentsRow(), 10, 2 );
			}
		}

		private function registerMetaBoxes() : void
		{
			$this->metaBoxes = [];
			foreach ( $this->otherArguments->get( 'meta_boxes' ) as $dataItem )
			{
				$slug = TestHashItem::getString( $dataItem, 'slug', null );
				$name = TestHashItem::getString( $dataItem, 'name', null );
				if ( $name && $slug )
				{
					// Remove slug & name from $dataItem, as we will be using it for extra arguments to the WPMetaBox
					// constructor, which doesn’t take in the slug or name.
					unset( $dataItem[ 'slug' ], $dataItem[ 'name' ] );

					// Add post type’s prefix to keep these meta boxes from conflicting with other type’s meta boxes.
					// Think of this as adding a namespace to these meta boxes.
					$fullSlug = $this->otherArguments->get( 'meta_box_prefix' ) . $slug;
					$dataItem[ 'post-type' ] = ( isset( $dataItem[ 'post-type' ] ) ) ? $dataItem[ 'post-type' ] : $this->slug;
					$this->metaBoxes[ $slug ] = new WPMetaBox( $fullSlug, $name, $dataItem );
				}
			}
		}
	
		private function generateTableOfContentsRow() : callable
		{
			return function( $column, int $post_id ) : void
			{
				foreach ( $this->otherArguments->get( 'custom_toc' ) as $item )
				{
					$full_col_name = $this->slug . '-' . $item[ 'slug' ];
					if ( $column == $full_col_name )
					{
						// If custom render function exists, just call it.
						if ( isset( $item[ 'function' ] ) && is_callable( $item[ 'function' ] ) )
						{
							$item[ 'function' ]( $column, $post_id );
						}
						else
						{
							// Else, just try to render the meta value for that key, if it exists.
							echo get_post_meta( $post_id, $full_col_name, true );
						}
					}
				}
			};
		}

		private function generateMessagesList() : array
		{
			// Merge default messages with user-specified messages.
			// User-specified messages latter so they override defaults.
			global $post, $post_ID;
			$singularName = $this->otherArguments->get( 'singular_name' );
			return array_merge
			(
				[
					0 => '', // Unused. Messages start @ index 1.
					1 => sprintf( __( $singularName . ' updated. <a href="%s">View ' . $singularName . '</a>', 'textdomain' ), esc_url( get_permalink( $post_ID ) ) ),
					2 => __( "$singularName updated.", 'textdomain' ),
					3 => __( "$singularName deleted.", 'textdomain' ),
					4 => __( "$singularName updated.", 'textdomain' ),
					5 => isset( $_GET[ 'revision' ] ) ? sprintf( __( $singularName . ' restored to revision from %s', 'textdomain' ), wp_post_revision_title( ( int )( $_GET[ 'revision' ] ), false ) ) : false,
					6 => sprintf( __( $singularName . ' published. <a href="%s">View ' . $singularName . '</a>', 'textdomain' ), esc_url( get_permalink( $post_ID ) ) ),
					7 => __( "$singularName saved.", 'textdomain' ),
					8 => sprintf( __( $singularName . ' submitted. <a target="_blank" href="%s">Preview ' . $singularName . '</a>', 'textdomain' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
					9 => sprintf( __( $singularName . ' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview ' . $singularName . '</a>', 'textdomain' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'textdomain' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
					10 => sprintf( __( $singularName . ' draft updated. <a target="_blank" href="%s">Preview ' . $singularName . '</a>', 'textdomain' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
				],
				$this->otherArguments->get( 'messages' )
			);
		}

		private function getSupports()
		{
			$supports = $this->otherArguments->get( 'supports' );
			return ( empty( $supports ) ) ? false : $supports;
		}

		private function removeUnsetItems( array $columns ) : array
		{
			foreach ( $this->otherArguments->get( 'unset_toc' ) as $item )
			{
				unset( $columns[ $item ] );
			}
			return $columns;
		}

		private function addCustomColumns( array $columns ) : array
		{
			foreach ( $this->otherArguments->get( 'custom_toc' ) as $item )
			{
				$columns[ $this->slug . '-' . $item[ 'slug' ] ] = __( $item[ 'name' ], "textdomain" );
			}
			return $columns;
		}

		private function sortColumns( array $columns ) : array
		{
			if ( !empty( $this->otherArguments->get( 'toc_order' ) ) )
			{
				$orderedKeys = $this->otherArguments->get( 'toc_order' );
				$sorted = [];

				// If checkbox was not explicitly placed in order box,
				// assume caller forgot it & still wants it @ the start,
				// since it looks awkward anywhere else.
				if ( !in_array( 'cb', $orderedKeys ) && array_key_exists( 'cb', $columns ) )
				{
					$sorted[ 'cb' ] = $columns[ 'cb' ];
					unset( $columns[ 'cb' ] ); // Remove so we don’t place in sorted multiple times.
				}

				// Position ordered keys.
				foreach ( $orderedKeys as $key )
				{
					if ( array_key_exists( $key, $columns ) )
					{
						$sorted[ $key ] = $columns[ $key ];
						unset( $columns[ $key ] ); // Remove so we don’t place in sorted multiple times.
					}
				}

				// Finally, add whatever columns still remain @ the end.
				foreach ( $columns as $key => $value )
				{
					$sorted[ $key ] = $value;
				}

				return $sorted;
			}
			return $columns;
		}

		private function generateArguments() : array
		{
			$args =
			[
				'labels' => $this->generateLabels(),
				'supports' => $this->getSupports()
			];

			// Always add whatever main arguments are.
			$mainOptions =
			[
				'description',
				'public',
				'hierarchical',
				'rest_base',
				'menu_position',
				'register_meta_box_cb',
				'has_archive',
				'rewrite',
				'query_var',
				'can_export'
			];
			foreach ( $mainOptions as $type )
			{
				$args[ $type ] = $this->otherArguments->get( $type );
			}

			// Only add other options if set.
			$otherOptions =
			[
				'label',
				'exclude_from_search',
				'publicly_queryable',
				'show_ui',
				'show_in_menu',
				'show_in_nav_menus',
				'show_in_admin_bar',
				'show_in_rest',
				'map_meta_cap',
				'rest_controller_class',
				'menu_icon',
				'capability_type',
				'capabilities',
				'taxonomies',
				'delete_with_user'
			];
			foreach ( $otherOptions as $type )
			{
				if ( $this->otherArguments->get( $type ) !== null )
				{
					$args[ $type ] = $this->otherArguments->get( $type );
				}
			}

			return $args;
		}

		private function generateLabels() : array
		{
			// Merge default labels with user-specified labels.
			// User-specified labels latter so they override defaults.
			$singularName = $this->otherArguments->get( 'singular_name' );
			return array_merge
			(
				[
					'name' => __( $this->name ),
					'singular_name' => __( $singularName ),
					'menu_name'             => _x( $this->name, 'Admin Menu text', 'textdomain' ),
					'name_admin_bar'        => _x( $singularName, 'Add New on Toolbar', 'textdomain' ),
					'add_new'               => __( 'Add New', 'textdomain' ),
					'add_new_item'          => __( 'Add New ' . $singularName, 'textdomain' ),
					'new_item'              => __( 'New ' . $singularName, 'textdomain' ),
					'edit_item'             => __( 'Edit ' . $singularName, 'textdomain' ),
					'view_item'             => __( 'View ' . $singularName, 'textdomain' ),
					'all_items'             => __( 'All ' . $this->name, 'textdomain' ),
					'search_items'          => __( 'Search ' . $this->name, 'textdomain' ),
					'parent_item_colon'     => __( 'Parent ' . $this->name . ':', 'textdomain' ),
					'not_found'             => __( 'No ' . strtolower( $this->name ) . ' found.', 'textdomain' ),
					'not_found_in_trash'    => __( 'No ' . strtolower( $this->name ) . ' found in Trash.', 'textdomain' ),
					'featured_image'        => _x( $singularName . ' Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain' ),
					'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
					'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
					'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
					'archives'              => _x( $singularName . ' archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'textdomain' ),
					'insert_into_item'      => _x( 'Insert into ' . strtolower( $singularName ), 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'textdomain' ),
					'uploaded_to_this_item' => _x( 'Uploaded to this ' . strtolower( $singularName ), 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'textdomain' ),
					'filter_items_list'     => _x( 'Filter ' . strtolower( $this->name ) . ' list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'textdomain' ),
					'items_list_navigation' => _x( $this->name . ' list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'textdomain' ),
					'items_list'            => _x( $this->name . ' list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'textdomain' )
				],
				$this->otherArguments->get( 'labels' )
			);
		}

		private function generateDefaultArguments() : array
		{
			return
			[
				'label' => [
					'value' => null,
					'type' => 'string',
					'sanitizer' => 'sanitize_text_field'
				],
				'labels' => [
					'value' => [],
					'type' => 'array',
					'sanitizer' => fn( array $list ) => array_map( 'sanitize_text_field', $list )
				],
				'description' => [
					'value' => '',
					'type' => 'string',
					'sanitizer' => 'sanitize_text_field'
				],
				'public' => [ 'value' => true, 'type' => 'boolean' ], 
				'hierarchical' => [ 'value' => false, 'type' => 'boolean' ],
				'exclude_from_search' => [ 'value' => null, 'type' => 'boolean' ],
				'publicly_queryable' => [ 'value' => null, 'type' => 'boolean' ],
				'show_ui' => [ 'value' => null, 'type' => 'boolean' ],
				'show_in_menu' =>
				[
					'value' => null,
					'type' => [ 'boolean', 'string' ],
					'sanitizer' => fn( $value ) => ( gettype( $value ) === 'string' ) ? sanitize_title( $value ) : $value // Only sanitize if string.
				],
				'show_in_nav_menus' => [ 'value' => null, 'type' => 'boolean' ],
				'show_in_admin_bar' => [ 'value' => null, 'type' => 'boolean' ],
				'show_in_rest' => [ 'value' => null, 'type' => 'boolean' ],
				'rest_base' =>
				[
					'value' => $this->slug,
					'type' => 'string',
					'sanitizer' => 'sanitize_title'
				],
				'rest_controller_class' =>
				[
					'value' => null,
					'type' => 'string'
				],
				'menu_position' => [ 'value' => null, 'type' => 'integer' ],
				'menu_icon' => [ 'value' => null, 'type' => 'string' ],
				'capability_type' => [ 'value' => null, 'type' => 'string', 'sanitizer' => 'sanitize_title' ],
				'capabilities' =>
				[
					'value' => null,
					'type' => 'array',
					'sanitizer' => fn( array $list ) => array_map( 'sanitize_title', $list )
				],
				'map_meta_cap' => [ 'value' => null, 'type' => 'boolean' ],
				'supports' =>
				[
					'value' => [ 'title', 'editor' ],
					'type' => 'array',
					'sanitizer' => fn( array $list ) => array_map( 'sanitize_title', $list )
				],
				'register_meta_box_cb' => [ 'value' => null, 'type' => 'callable' ],
				'taxonomies' =>
				[
					'value' => null,
					'type' => 'array',
					'sanitizer' => fn( array $list ) => array_map( 'sanitize_title', $list )
				],
				'has_archive' => [ 'value' => true, 'type' => 'boolean' ],
				'rewrite' =>
				[
					'value' => [ 'slug' => $this->slug ],
					'type' => [ 'boolean', 'array' ],
					'sanitizer' => function( $list ) {
						if ( gettype( $list ) === 'array' && array_key_exists( 'slug', $list ) ) {
							$list[ 'slug' ] = sanitize_title( $list[ 'slug' ] );
						}
					}
				],
				'query_var' =>
				[
					'value' => $this->slug,
					'type' => [ 'boolean', 'string' ],
					'sanitizer' => fn( $value ) => ( gettype( $value ) === 'string' ) ? sanitize_title( $value ) : $value // Only sanitize if string.
				],
				'can_export' => [ 'value' => true, 'type' => 'boolean' ],
				'delete_with_user' => [ 'value' => null, 'type' => 'boolean' ],
				'singular_name' => [ 'value' => $this->name, 'type' => 'string', 'sanitizer' => 'sanitize_text_field' ],
				'meta_boxes' => [ 'value' => [], 'type' => 'array' ],
				'meta_box_prefix' => [ 'value' => $this->slug . '-', 'type' => 'string', 'sanitizer' => 'sanitize_title' ],
				'custom_toc' => [ 'value' => [], 'type' => 'array' ],
				'unset_toc' => [ 'value' => [], 'type' => 'array' ],
				'messages' => [ 'value' => [], 'type' => 'array' ]
			];
		}

		private string $slug;
		private string $name;
		private array $metaBoxes;
		private VerifiedArguments $otherArguments;
}
