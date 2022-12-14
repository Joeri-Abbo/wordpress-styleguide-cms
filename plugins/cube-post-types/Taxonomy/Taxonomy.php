<?php

// Set the namespace
namespace Cube\Taxonomy;

class Taxonomy {

	/**
	 * Default arguments for custom taxonomies.
	 * Several of these differ from the defaults in WordPress' register_taxonomy() function.
	 *
	 * @var array
	 */
	protected $defaults = [
		'public'            => true,
		'show_ui'           => true,
		'hierarchical'      => true,
		'query_var'         => true,
		'exclusive'         => false, # Custom arg
		'allow_hierarchy'   => false, # Custom arg
	];

	/**
	 * Some other member variables you don't need to worry about:
	 */
	public $taxonomy;
	public $object_type;
	public $tax_slug;
	public $tax_singular;
	public $tax_plural;
	public $tax_singular_low;
	public $tax_plural_low;
	public $args;

	/**
	 * Class constructor.
	 *
	 * @see register_extended_taxonomy()
	 *
	 * @param string       $taxonomy    The taxonomy name.
	 * @param array|string $object_type Name(s) of the object type(s) for the taxonomy.
	 * @param array        $args        Optional. The taxonomy arguments.
	 * @param array        $names       Optional. An associative array of the plural, singular, and slug names.
	 */
	public function __construct( $taxonomy, $object_type, array $args = [], array $names = [] ) {

		/**
		 * Filter the arguments for this taxonomy.
		 *
		 * @since 2.0.0
		 *
		 * @param array $args The taxonomy arguments.
		 */
		$args  = apply_filters( "cube-taxos/{$taxonomy}/args", $args );
		/**
		 * Filter the names for this taxonomy.
		 *
		 * @since 2.0.0
		 *
		 * @param array $names The plural, singular, and slug names (if any were specified).
		 */
		$names = apply_filters( "cube-taxos/{$taxonomy}/names", $names );

		if ( isset( $names['singular'] ) ) {
			$this->tax_singular = $names['singular'];
		} else {
			$this->tax_singular = ucwords( str_replace( [ '-', '_' ], ' ', $taxonomy ) );
		}

		if ( isset( $names['slug'] ) ) {
			$this->tax_slug = $names['slug'];
		} elseif ( isset( $names['plural'] ) ) {
			$this->tax_slug = $names['plural'];
		} else {
			$this->tax_slug = $taxonomy . 's';
		}

		if ( isset( $names['plural'] ) ) {
			$this->tax_plural = $names['plural'];
		} else {
			$this->tax_plural = ucwords( str_replace( [ '-', '_' ], ' ', $this->tax_slug ) );
		}

		$this->object_type = (array) $object_type;
		$this->taxonomy    = strtolower( $taxonomy );
		$this->tax_slug    = strtolower( $this->tax_slug );

		# Build our base taxonomy names:
		$this->tax_singular_low = strtolower( $this->tax_singular );
		$this->tax_plural_low   = strtolower( $this->tax_plural );

		# Build our labels:
		$this->defaults['labels'] = [
			'menu_name'                  => $this->tax_plural,
			'name'                       => $this->tax_plural,
			'singular_name'              => $this->tax_singular,
			'search_items'               => sprintf( 'Search %s', $this->tax_plural ),
			'popular_items'              => sprintf( 'Popular %s', $this->tax_plural ),
			'all_items'                  => sprintf( 'All %s', $this->tax_plural ),
			'parent_item'                => sprintf( 'Parent %s', $this->tax_singular ),
			'parent_item_colon'          => sprintf( 'Parent %s:', $this->tax_singular ),
			'edit_item'                  => sprintf( 'Edit %s', $this->tax_singular ),
			'view_item'                  => sprintf( 'View %s', $this->tax_singular ),
			'update_item'                => sprintf( 'Update %s', $this->tax_singular ),
			'add_new_item'               => sprintf( 'Add New %s', $this->tax_singular ),
			'new_item_name'              => sprintf( 'New %s Name', $this->tax_singular ),
			'separate_items_with_commas' => sprintf( 'Separate %s with commas', $this->tax_plural_low ),
			'add_or_remove_items'        => sprintf( 'Add or remove %s', $this->tax_plural_low ),
			'choose_from_most_used'      => sprintf( 'Choose from most used %s', $this->tax_plural_low ),
			'not_found'                  => sprintf( 'No %s found', $this->tax_plural_low ),
			'no_terms'                   => sprintf( 'No %s', $this->tax_plural_low ),
			'items_list_navigation'      => sprintf( '%s list navigation', $this->tax_plural ),
			'items_list'                 => sprintf( '%s list', $this->tax_plural ),
			'no_item'                    => sprintf( 'No %s', $this->tax_singular_low ), # Custom label
		];

		# Only set rewrites if we need them
		if ( isset( $args['public'] ) && ! $args['public'] ) {
			$this->defaults['rewrite'] = false;
		} else {
			$this->defaults['rewrite'] = [
				'slug'         => $this->tax_slug,
				'with_front'   => false,
				'hierarchical' => isset( $args['allow_hierarchy'] ) ?
					$args['allow_hierarchy'] :
					$this->defaults['allow_hierarchy'],
			];
		}

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# This allows the 'labels' arg to contain some, none or all labels:
		if ( isset( $args['labels'] ) ) {
			$this->args['labels'] = array_merge( $this->defaults['labels'], $args['labels'] );
		}

		# Rewrite testing:
		if ( $this->args['rewrite'] ) {
			add_filter( 'rewrite_testing_tests', [ $this, 'rewrite_testing_tests' ], 1 );
		}

		# Register taxonomy when WordPress initialises:
		if ( 'init' === current_filter() ) {
			call_user_func( [ $this, 'register_taxonomy' ] );
		} else {
			add_action( 'init', [ $this, 'register_taxonomy' ], 9 );
		}

	}

	/**
	 * Registers our taxonomy.
	 *
	 * @return null
	 */
	public function register_taxonomy() {

		if ( true === $this->args['query_var'] ) {
			$query_var = $this->taxonomy;
		} else {
			$query_var = $this->args['query_var'];
		}

		if ( $query_var && count( get_post_types( [ 'query_var' => $query_var ] ) ) ) {
			trigger_error( esc_html( sprintf(
				__( 'Taxonomy query var "%s" clashes with a post type query var of the same name', 'ext_taxos' ),
				$query_var
			) ), E_USER_ERROR );
		} elseif ( in_array( $query_var, [ 'type', 'tab' ] ) ) {
			trigger_error( esc_html( sprintf(
				__( 'Taxonomy query var "%s" is not allowed', 'ext_taxos' ),
				$query_var
			) ), E_USER_ERROR );
		} else {
			register_taxonomy( $this->taxonomy, $this->object_type, $this->args );
		}

	}

}

