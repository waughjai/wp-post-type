WP Post Type
=========================

Simple class for easily creating new post types for a WordPress site.

## Use

Just call constructor before admin loads. Constructor automatically calls all the WordPress functions needed to set it up, while you only need to pass in data.

1st 2 mandatory arguments to the constructor are the identifying slug & the public name for the post type group. The optional 3rd argument is a hash map o' extra arguments:

* "singular_name": Public name for single entry o' post type. ( If post type is "Boxes", you would make this "Box". ) Defaults to group name.
* "supports": Editors this post type will show in the post type editor ( whether it will show the big body editor or the thumbnails link ). Defaults to showing just title & content editor. For the WordPress default, just set to false.
* "has_archive": A boolean that decides if post type has an archive page. Defaults to true.
* "public": A boolean that decides whether post type is public. Defaults to true.
* "meta_boxes": Array o' hash maps representing data for meta boxes that will automatically be setup. Hash maps should have values for "slug" & "name" keys to be valid. Other optional key values are the same as the optional extra arguments for meta boxes listed below. "post_type" value for each meta box will automatically be set to this post type 'less specifically set. ( This is mainly meant for backward compatibility with already-made meta boxes with slugs that don't fit pattern. 'Less a certain full slug is needed for meta boxes, I would recommend not bothering to o'erride this. )
* "rewrite": Sets archive permalink. Hash map with "slug" key set to value. Defaults to using slug.
* "meta_box_prefix": Overrides prefix that goes before slug o' each meta box slug when setting their full slug. Defaults to post type slug plus a hyphen separator.*
* "custom_toc": Array o' hash maps for columns to add to table view o' posts o' this type. Keys given values should be "slug" & "name". "Slug" should refer to the meta box slug & "name" should refer to the heading given to that column in the view.
* "unset_toc": Array o' default columns for admin list view to take off table.
* "taxonomies": Array o’ tags/categories, which must be registered manually later.

## Example

	use WaughJ\WPPostType\WPPostType;

	new WPPostType
	(
		'news',
		'News',
		[
			'singular_name' => 'News Article',
			'supports' => [ 'title', 'editor', 'thumbnails' ],
			'meta_boxes' =>
			[
				[
					'slug' => 'url',
					'name' => 'URL'
				],
				[
					'slug' => 'order',
					'name' => 'Order',
					'input-type' => 'number'
				]
			],
			'unset_toc": [ 'date' ],
			'custom_toc':
			[
				[
					'slug' => 'order',
					'name' => 'Order'
				]
			],
			'taxonomies' => [ 'cat_news' ]
		]
	);

## Changelog

### 0.4.0
* Add simple way to add taxonomies to type

### 0.3.2
* Update TestHashItem dependency

### 0.3.1
* Make method getMetaBoxes not break due to missing function use statement

### 0.3.0
* Make table of contents row render function send column & post ID to custom function

### 0.2.0
* Add getMetaBox Method
	* Add method for getting a meta box object by slug.

### 0.1.5
* Fix TestHashItemString Use Statement Typo

### 0.1.4
* Add TestHashItem Dependency

### 0.1.3
* Update Dependencies
	* Require non-buggy version o' WPMetaBox.

### 0.1.2
* Fix Use Statement Bug
	* Was missing use statement for TestHashItemString, causing function to break.

### 0.1.1
* Fix Meta Box Implementation Bug & Update Readme
	* Called Meta Box class using ol' interface, which is no longer in use. This fixes that.
	* Adds mo' detailed instructions to readme.

### 0.1.0
* Initial Version
