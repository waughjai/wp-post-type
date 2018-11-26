<?php

declare( strict_types = 1 );
namespace WaughJ\WPPostType
{
	use WaughJ\TestHashItem\TestHashItemString;
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

			add_action( 'init', [ $this, 'register' ] );
			add_action( 'post_updated_messages', [ $this, 'getMessages' ] );

			if ( !empty( $this->other_arguments->get( 'custom_toc' ) ) )
			{
				add_filter( "manage_{$slug}_posts_columns",       [ $this, 'getTableOfContents' ], 10, 2 );
				add_action( "manage_{$slug}_posts_custom_column", [ $this, 'getTableOfContentsRow' ], 10, 2 );
			}

			$this->registerMetaBoxes();
		}

		public function register() : void
		{
			register_post_type
			(
				$this->slug,
				[
					'labels' =>
					[
						'name' => __( $this->name ),
						'singular_name' => __( $this->other_arguments->get( 'singular_name' ) )
					],
					'public' => $this->other_arguments->get( 'public' ),
					'has_archive' => $this->other_arguments->get( 'has_archive' ),
					'supports' => $this->getSupports(),
					'rewrite' => $this->other_arguments->get( 'rewrite' )
				]
			);
		}

		public function getMessages( array $messages ) : array
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
		}

		public function getTableOfContents( $columns ) : array
		{
			$unset_list = $this->other_arguments->get( 'unset_toc' );
			foreach ( $unset_list as $unset_item )
			{
				unset( $columns[ $unset_item ] );
			}

			$rows = [];
			$custom_toc_list = $this->other_arguments->get( 'custom_toc' );
			foreach ( $custom_toc_list as $custom_toc_item )
			{
				$rows[ $this->slug . '-' . $custom_toc_item[ 'slug' ] ] = __( $custom_toc_item[ 'name' ] );
			}

			return array_merge( $columns, $rows );
		}

		public function getTableOfContentsRow( $column, int $post_id ) : void
		{
			$custom_toc_list = $this->other_arguments->get( 'custom_toc' );
			foreach ( $custom_toc_list as $custom_toc_item )
			{
				$full_col_name = $this->slug . '-' . $custom_toc_item[ 'slug' ];
				if ( $column == $full_col_name )
				{
					if ( isset( $custom_toc_item[ 'function' ] ) && is_callable( $custom_toc_item[ 'function' ] ) )
					{
						$custom_toc_item[ 'function' ]();
					}
					else
					{
						echo get_post_meta( $post_id, $full_col_name, true );
					}
				}
			}
		}

		public function getSlug() : string
		{
			return $this->slug;
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

			private function registerMetaBoxes() : void
			{
				$this->meta_boxes = [];
				$meta_box_data = $this->other_arguments->get( 'meta_boxes' );
				foreach ( $meta_box_data as $meta_box_item )
				{
					$slug = TestHashItemString( $meta_box_item, 'slug', null );
					$name = TestHashItemString( $meta_box_item, 'name', null );
					unset( $meta_box_item[ 'slug' ], $meta_box_item[ 'name' ] );
					if ( $name && $slug )
					{
						$full_slug = $this->other_arguments->get( 'meta_box_prefix' ) . $slug;
						$meta_box_item[ 'post-type' ] = ( isset( $meta_box_item[ 'post-type' ] ) ) ? $meta_box_item[ 'post-type' ] : $this->slug;
						array_push( $this->meta_boxes, new WPMetaBox( $full_slug, $name, $meta_box_item ) );
					}
				}
			}

			private function getSupports()
			{
				$supports = $this->other_arguments->get( 'supports' );
				return ( empty( $supports ) ) ? false : $supports;
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
					'unset_toc' => [ 'value' => [] ]
				];
			}

			private $slug;
			private $name;
			private $meta_boxes;
			private $other_arguments;
	}
}
