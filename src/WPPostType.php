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
			$this->slug = $slug;
			$this->name = $name;
			$this->other_arguments = new VerifiedArguments( $args, self::argumentDefaults( $slug, $name ) );

			add_action( 'init', $this->generateRegistrar() );
			add_action( 'post_updated_messages', $this->generateMessager() );
			if ( !empty( $this->other_arguments->get( 'custom_toc' ) ) )
			{
				add_filter( "manage_{$slug}_posts_columns",       $this->generateTableOfContents(), 10, 2 );
				add_action( "manage_{$slug}_posts_custom_column", $this->generateTableOfContentsRow(), 10, 2 );
			}
			$this->registerMetaBoxes();
		}

		public function getSlug() : string
		{
			return $this->slug;
		}

		public function getMetaBox( string $slug ) : ?WPMetaBox
		{
			return $this->meta_boxes[ $slug ] ?? null;
		}

		public function getName() : string
		{
			return $this->name;
		}

		public function getSingularName() : string
		{
			return $this->other_arguments->get( 'singular_name' );
		}



	//
	//  PRIVATE
	//
	/////////////////////////////////////////////////////////

		private function generateRegistrar() : callable
		{
			return function()
			{
				register_post_type
				(
					$this->slug,
					[
						'labels' => $this->generateLabels(),
						'public' => $this->other_arguments->get( 'public' ),
						'has_archive' => $this->other_arguments->get( 'has_archive' ),
						'supports' => $this->getSupports(),
						'rewrite' => $this->other_arguments->get( 'rewrite' ),
						'taxonomies' => $this->other_arguments->get( 'taxonomies' ),
						'capabilities' => $this->other_arguments->get( 'capabilities' )
					]
				);
			};
		}
	
		private function generateMessager() : callable
		{
			return function( array $messages ) : array
			{
				global $post, $post_ID;
				$messages[ $this->slug ] =
				[
					0 => '', // Unused. Messages start @ index 1.
					1 => sprintf( __( $this->other_arguments->get( 'singular_name' ) . ' updated. <a href="%s">View ' . $this->other_arguments->get( 'singular_name' ) . '</a>' ), esc_url( get_permalink( $post_ID ) ) ),
					2 => __( $this->other_arguments->get( 'singular_name' ) . ' updated.' ),
					3 => __( $this->other_arguments->get( 'singular_name' ) . ' deleted.' ),
					4 => __( $this->other_arguments->get( 'singular_name' ) . ' updated.' ),
					5 => isset( $_GET['revision'] ) ? sprintf( __( $this->other_arguments->get( 'singular_name' ) . ' restored to revision from %s' ), wp_post_revision_title( ( int )( $_GET[ 'revision' ] ), false ) ) : false,
					6 => sprintf( __( $this->other_arguments->get( 'singular_name' ) . ' published. <a href="%s">View ' . $this->other_arguments->get( 'singular_name' ) . '</a>'), esc_url( get_permalink( $post_ID ) ) ),
					7 => __( $this->other_arguments->get( 'singular_name' ) . ' saved.' ),
					8 => sprintf( __( $this->other_arguments->get( 'singular_name' ) . ' submitted. <a target="_blank" href="%s">Preview ' . $this->other_arguments->get( 'singular_name' ) . '</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
					9 => sprintf( __( $this->other_arguments->get( 'singular_name' ) . ' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview ' . $this->other_arguments->get( 'singular_name' ) . '</a>'),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
					10 => sprintf( __( $this->other_arguments->get( 'singular_name' ) . ' draft updated. <a target="_blank" href="%s">Preview ' . $this->other_arguments->get( 'singular_name' ) . '</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
				];
				return $messages;
			};
		}

		private function registerMetaBoxes() : void
		{
			$this->meta_boxes = [];
			$meta_box_data = $this->other_arguments->get( 'meta_boxes' );
			foreach ( $meta_box_data as $meta_box_item )
			{
				$slug = TestHashItem::getString( $meta_box_item, 'slug', null );
				$name = TestHashItem::getString( $meta_box_item, 'name', null );
				unset( $meta_box_item[ 'slug' ], $meta_box_item[ 'name' ] );
				if ( $name && $slug )
				{
					$full_slug = $this->other_arguments->get( 'meta_box_prefix' ) . $slug;
					$meta_box_item[ 'post-type' ] = ( isset( $meta_box_item[ 'post-type' ] ) ) ? $meta_box_item[ 'post-type' ] : $this->slug;
					$this->meta_boxes[ $slug ] = new WPMetaBox( $full_slug, $name, $meta_box_item );
				}
			}
		}
	
		private function generateTableOfContents() : callable
		{
			return function( array $columns ) : array
			{
				return $this->sortColumns( $this->addCustomColumns( $this->removeUnsetItems( $columns ) ) );
			};
		}
	
		private function generateTableOfContentsRow() : callable
		{
			return function( $column, int $post_id ) : void
			{
				$custom_toc_list = $this->other_arguments->get( 'custom_toc' );
				foreach ( $custom_toc_list as $custom_toc_item )
				{
					$full_col_name = $this->slug . '-' . $custom_toc_item[ 'slug' ];
					if ( $column == $full_col_name )
					{
						// If custom render function exists, just call it.
						if ( isset( $custom_toc_item[ 'function' ] ) && is_callable( $custom_toc_item[ 'function' ] ) )
						{
							$custom_toc_item[ 'function' ]( $column, $post_id );
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

		private function getSupports()
		{
			$supports = $this->other_arguments->get( 'supports' );
			return ( empty( $supports ) ) ? false : $supports;
		}

		private function removeUnsetItems( array $columns ) : array
		{
			$unset_list = $this->other_arguments->get( 'unset_toc' );
			foreach ( $unset_list as $unset_key )
			{
				unset( $columns[ $unset_key ] );
			}
			return $columns;
		}

		private function addCustomColumns( array $columns ) : array
		{
			$custom_toc_list = $this->other_arguments->get( 'custom_toc' );
			foreach ( $custom_toc_list as $custom_toc_item )
			{
				$columns[ $this->slug . '-' . $custom_toc_item[ 'slug' ] ] = __( $custom_toc_item[ 'name' ] );
			}
			return $columns;
		}

		private function sortColumns( array $columns ) : array
		{
			if ( !empty( $this->other_arguments->get( 'toc_order' ) ) )
			{
				$ordered_keys = $this->other_arguments->get( 'toc_order' );
				$sorted = [];

				// If checkbox was not explicitly placed in order box,
				// assume caller forgot it & still wants it @ the start,
				// since it looks awkward anywhere else.
				if ( !in_array( 'cb', $ordered_keys ) && array_key_exists( 'cb', $columns ) )
				{
					$sorted[ 'cb' ] = $columns[ 'cb' ];
					unset( $columns[ 'cb' ] ); // Remove so we don’t place in sorted multiple times.
				}

				// Position ordered keys.
				foreach ( $ordered_keys as $key )
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

		private function generateLabels() : array
		{
			// Merge default labels with user-specified labels.
			// User-specified labels latter so they override defaults.
			$singularName = $this->other_arguments->get( 'singular_name' );
			return array_merge
			(
				[
					'name' => __( $this->name ),
					'singular_name' => __( $this->other_arguments->get( 'singular_name' ) ),
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
				$this->other_arguments->get( 'labels' )
			);
		}

		private static function argumentDefaults( string $slug, string $name ) : array
		{
			return
			[
				'singular_name' => [ 'value' => $name ],
				'supports' => [ 'value' => [ 'title', 'editor' ] ],
				'has_archive' => [ 'value' => true ],
				'public' => [ 'value' => true ],
				'meta_boxes' => [ 'value' => [] ],
				'rewrite' => [ 'value' => [ 'slug' => $slug ] ],
				'meta_box_prefix' => [ 'value' => $slug . '-' ],
				'custom_toc' => [ 'value' => [] ],
				'unset_toc' => [ 'value' => [] ],
				'taxonomies' => [ 'value' => [] ],
				'capabilities' => [ 'value' => [] ],
				'labels' => [ 'value' => [] ]
			];
		}

		private $slug;
		private $name;
		private $meta_boxes;
		private $other_arguments;
}
