<?php
/**
 * Custom Post Type Registration & Admin Columns.
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_Post_Type {

	const POST_TYPE = 'product_group';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	/**
	 * Register 'product_group' post type.
	 */
	public static function register() {
		$labels = array(
			'name'                  => __( 'گروه محصولات', 'product-groups' ),
			'singular_name'         => __( 'گروه محصول', 'product-groups' ),
			'menu_name'             => __( 'گروه محصولات', 'product-groups' ),
			'name_admin_bar'        => __( 'گروه محصول', 'product-groups' ),
			'add_new'               => __( 'افزودن گروه جدید', 'product-groups' ),
			'add_new_item'          => __( 'افزودن گروه محصول جدید', 'product-groups' ),
			'new_item'              => __( 'گروه محصول جدید', 'product-groups' ),
			'edit_item'             => __( 'ویرایش گروه محصول', 'product-groups' ),
			'view_item'             => __( 'مشاهده گروه محصول', 'product-groups' ),
			'all_items'             => __( 'همه گروه‌ها', 'product-groups' ),
			'search_items'          => __( 'جستجوی گروه‌ها', 'product-groups' ),
			'not_found'             => __( 'گروهی یافت نشد', 'product-groups' ),
			'not_found_in_trash'    => __( 'در زباله‌دان گروهی یافت نشد', 'product-groups' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-screenoptions',
			'supports'           => array( 'title' ),
			'show_in_rest'       => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Define custom admin columns.
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['pg_count']     = __( 'تعداد محصولات', 'product-groups' );
				$new_columns['pg_api_type']  = __( 'نوع API', 'product-groups' );
				$new_columns['pg_shortcode'] = __( 'کد کوتاه', 'product-groups' );
			}
		}

		return $new_columns;
	}

	/**
	 * Output custom column content.
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'pg_count':
				$products = get_post_meta( $post_id, '_pg_products', true );
				$count    = is_array( $products ) ? count( $products ) : 0;
				echo esc_html( PG_Utils::to_persian_number( $count ) );
				break;

			case 'pg_api_type':
				$api_type = get_post_meta( $post_id, '_pg_api_type', true );
				if ( 'supermarket' === $api_type ) {
					echo '<span class="dashicons dashicons-cart" style="color:#00a32a;vertical-align:middle;margin-left:4px;"></span> ' . esc_html__( 'سوپرمارکتی', 'product-groups' );
				} else {
					echo '<span class="dashicons dashicons-tag" style="color:#2271b1;vertical-align:middle;margin-left:4px;"></span> ' . esc_html__( 'معمولی', 'product-groups' );
				}
				break;

			case 'pg_shortcode':
				$shortcode = '[product_group id="' . absint( $post_id ) . '"]';
				?>
				<div class="pg-shortcode-copy-wrapper">
					<code class="pg-shortcode-code"><?php echo esc_html( $shortcode ); ?></code>
					<button type="button" class="button button-small pg-copy-btn pg-copy-shortcode-btn" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">
						<?php esc_html_e( 'کپی', 'product-groups' ); ?>
					</button>
				</div>
				<?php
				break;
		}
	}
}
