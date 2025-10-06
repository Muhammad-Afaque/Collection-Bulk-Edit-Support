<?php
/**
 * Plugin Name: Collection Bulk Edit Support
 * Description: Adds Collection taxonomy to WooCommerce bulk and quick edit UI with hierarchical checkbox tree. Also displays and filters by Collection in the All Products page.
 * Version: 2.1
 * Author: Muhammad Afaque
 * Author URI: https://wpninja.org
 */

// 1. Add 'Collection' column to the products table
add_filter( 'manage_edit-product_columns', 'add_collection_column' );
function add_collection_column( $columns ) {
    $columns['collection'] = __( 'Collection' );
    return $columns;
}

// 2. Show assigned collections in the column using wp_get_object_terms
add_action( 'manage_product_posts_custom_column', 'show_collection_column_content', 10, 2 );
function show_collection_column_content( $column, $post_id ) {
    if ( $column === 'collection' ) {
        $terms = wp_get_object_terms( $post_id, 'collection', array( 'fields' => 'all' ) );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $term_names = wp_list_pluck( $terms, 'name' );
            $term_ids   = wp_list_pluck( $terms, 'term_id' );
            echo '<div class="collection-list" data-collections=\'' . esc_attr( json_encode( $term_ids ) ) . '\'>';
            echo esc_html( implode( ', ', $term_names ) );
            echo '</div>';
        } else {
            echo '<div class="collection-list" data-collections="[]">&nbsp;</div>';
        }
    }
}

// 3. Add filter dropdown by collection in product list
add_action( 'restrict_manage_posts', 'filter_products_by_collection' );
function filter_products_by_collection() {
    global $typenow;
    if ( $typenow === 'product' ) {
        $taxonomy = 'collection';
        wp_dropdown_categories( array(
            'show_option_all' => __( 'All Collections', 'woocommerce' ),
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : '',
            'hierarchical'    => true,
            'depth'           => 3,
            'show_count'      => true,
            'hide_empty'      => false,
        ) );
    }
}

// 4. ✅ Restored: Handle collection filter query using query_vars
add_filter( 'parse_query', 'filter_products_by_collection_query' );
function filter_products_by_collection_query( $query ) {
    global $pagenow;
    $taxonomy = 'collection';

    if (
        $pagenow === 'edit.php' &&
        isset( $_GET['post_type'] ) &&
        $_GET['post_type'] === 'product' &&
        isset( $_GET[$taxonomy] ) &&
        is_numeric( $_GET[$taxonomy] ) &&
        $_GET[$taxonomy] != 0
    ) {
        $term = get_term_by( 'id', $_GET[$taxonomy], $taxonomy );
        if ( $term ) {
            $query->query_vars[$taxonomy] = $term->slug;
        }
    }
}

// 5. Render checkbox tree in bulk edit
add_action( 'bulk_edit_custom_box', 'render_bulk_edit_collection_tree', 10, 2 );
function render_bulk_edit_collection_tree( $column_name, $post_type ) {
    if ( $column_name !== 'collection' || $post_type !== 'product' ) return;
    ?>
    <fieldset class="inline-edit-col-left">
        <div class="inline-edit-col">
            <span class="title"><?php _e( 'Collection' ); ?></span>
            <ul class="cat-checklist collection-checklist">
                <?php
                wp_terms_checklist( 0, array(
                    'taxonomy' => 'collection',
                    'walker'   => new Walker_Category_Checklist(),
                    'checked_ontop' => false,
                ) );
                ?>
            </ul>
        </div>
    </fieldset>
    <?php
}

// 6. JS for bulk edit collection selection
add_action( 'admin_footer-edit.php', 'bulk_edit_collection_js' );
function bulk_edit_collection_js() {
    global $post_type;
    if ( $post_type !== 'product' ) return;
    ?>
    <script type="text/javascript">
    jQuery(function($){
        $('#bulk_edit').on('click', function(){
            var collection_ids = [];
            $('.collection-checklist input:checked').each(function(){
                collection_ids.push($(this).val());
            });

            $('tr.inline-edit-row').each(function(){
                var $row = $(this);
                for (var i = 0; i < collection_ids.length; i++) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'collection_bulk_edit[]',
                        value: collection_ids[i]
                    }).appendTo($row);
                }
            });
        });
    });
    </script>
    <?php
}

// 7. Save bulk edit changes
add_action( 'save_post_product', 'save_bulk_edit_collection_terms' );
function save_bulk_edit_collection_terms( $post_id ) {
    if ( ! isset( $_REQUEST['collection_bulk_edit'] ) || ! is_array( $_REQUEST['collection_bulk_edit'] ) ) {
        return;
    }

    $terms = array_map( 'intval', $_REQUEST['collection_bulk_edit'] );
    wp_set_object_terms( $post_id, $terms, 'collection', false );
}

// 8. Add Collection field to Quick Edit panel
add_action( 'quick_edit_custom_box', 'add_quick_edit_collection_field', 10, 2 );
function add_quick_edit_collection_field( $column_name, $post_type ) {
    if ( $column_name !== 'collection' || $post_type !== 'product' ) return;
    ?>
    <fieldset class="inline-edit-col-left">
        <div class="inline-edit-col">
            <span class="title"><?php _e( 'Collection' ); ?></span>
            <ul class="cat-checklist collection-quick-edit">
                <?php
                wp_terms_checklist( 0, array(
                    'taxonomy' => 'collection',
                    'walker'   => new Walker_Category_Checklist(),
                    'checked_ontop' => false,
                ) );
                ?>
            </ul>
        </div>
    </fieldset>
    <?php
}

// 9. Save Quick Edit changes
add_action( 'save_post_product', 'save_quick_edit_collection_terms', 20 );
function save_quick_edit_collection_terms( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    if ( isset( $_POST['collection'] ) && is_array( $_POST['collection'] ) ) {
        $terms = array_map( 'intval', $_POST['collection'] );
        wp_set_object_terms( $post_id, $terms, 'collection', false );
    } elseif ( isset( $_POST['collection'] ) && empty( $_POST['collection'] ) ) {
        wp_set_object_terms( $post_id, array(), 'collection', false );
    }
}

// 10. Ensure 'collection' taxonomy is associated with 'product' post type and registered with query_var
add_action( 'init', function() {
    $taxonomy = 'collection';

    if ( taxonomy_exists( $taxonomy ) ) {
        register_taxonomy( $taxonomy, 'product', array(
            'hierarchical' => true,
            'label' => 'Collections',
            'query_var' => true,
            'rewrite' => array( 'slug' => 'collection' ),
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'show_in_quick_edit' => false,
        ) );
    }
}, 11 );

// 11. Copy collection terms when duplicating a product
add_action( 'woocommerce_product_duplicate', 'copy_collection_terms_on_duplicate', 10, 2 );
function copy_collection_terms_on_duplicate( $duplicate, $original ) {
    $taxonomy = 'collection';
    if ( taxonomy_exists( $taxonomy ) ) {
        $terms = wp_get_object_terms( $original->get_id(), $taxonomy, array( 'fields' => 'ids' ) );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            wp_set_object_terms( $duplicate->get_id(), $terms, $taxonomy );
        }
    }
}

// 12. JavaScript to populate Quick Edit form with assigned collections
add_action( 'admin_footer-edit.php', 'quick_edit_collection_script' );
function quick_edit_collection_script() {
    global $post_type;
    if ( $post_type !== 'product' ) return;
    ?>
    <script type="text/javascript">
    jQuery(function($){
        var $editRow = $('#inline-edit');

        window.inlineEditPost.edit = function( id ) {
            var post_id = 0;
            if ( typeof(id) === 'object' ) {
                post_id = parseInt(this.getId(id));
            }
            if ( post_id === 0 ) return;

            this.revert();

            var $tr = $('#post-' + post_id),
                $checkboxes = $editRow.find('.collection-quick-edit input[type="checkbox"]');

            $checkboxes.prop('checked', false); // reset

            var collections = $tr.find('.collection-list').data('collections');
            if ( collections && collections.length > 0 ) {
                collections.forEach(function(termId){
                    $checkboxes.filter('[value="' + termId + '"]').prop('checked', true);
                });
            }

            return inlineEditPost.edit(id);
        };
    });
    </script>
    <?php
}
