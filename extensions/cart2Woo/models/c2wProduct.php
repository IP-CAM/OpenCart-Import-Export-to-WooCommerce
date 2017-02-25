<?php

class c2wProduct {

    private $exampleData = array (
	'product_id' => 714,
	'categories' => array (
		0 => '112',
	),
	'sku' => 'RFSV65055_RIGATOBLU',
	'upc' => NULL,
	'ean' => NULL,
	'jan' => NULL,
	'isbn' => NULL,
	'mpn' => NULL,
	'location' => NULL,
	'quantity' => 3,
	'model' => 'RFSV65055_RIGATOBLU',
	'manufacturer' => 'Rosso Fiorentino',
	'image_name' => 'data/snimki/italy/store/20161610/17-1.jpg',
	'shipping' => 'yes',
	'price' => 112.4602,
	'points' => 0,
	'date_added' => '2016-10-16 16:48:52',
	'date_modified' => '2016-10-17 00:20:49',
	'date_available' => '2016-10-16',
	'weight' => 1,
	'weight_unit' => 'кг',
	'length' => 0,
	'width' => 0,
	'height' => 0,
	'length_unit' => 'см',
	'status' => 'true',
	'tax_class_id' => 11,
	'seo_keyword' => 'rosso_fiorentino_v_tshirt',
	'stock_status_id' => 9,
	'store_ids' => 0,
	'layout' => '0:',
	'related_ids' => array (),
	'sort_order' => 1,
	'subtract' => 'true',
	'minimum' => 1,
	'bg' => array (
		'name' => 'Rosso Fiorentino мъжка тениска райе',
		'description' => ' Man V-neck Jersey
50% WO 50% PC
dry-fit <
wash 40°',
		'meta_title' => 'Rosso Fiorentino мъжка тениска райе',
		'meta_description' => 'Rosso Fiorentino мъжка тениска райе',
		'meta_keywords' => 'Rosso Fiorentino мъжка тениска райе',
		'tags' => 'Rosso Fiorentino мъжка тениска райе',
		'categories' => array (
			0 => 16,
		),
	),
	'en-gb' => array (
		'name' => 'Rosso Fiorentino men t-shirt striped',
		'description' => ' Man V-neck Jersey
50% WO 50% PC
dry-fit <
wash 40°',
		'meta_title' => 'Rosso Fiorentino men t-shirt striped',
		'meta_description' => 'Rosso Fiorentino men t-shirt striped',
		'meta_keywords' => 'Rosso Fiorentino men t-shirt striped',
		'tags' => 'Rosso Fiorentino men t-shirt striped',
		'categories' => array (
			0 => 80,
		),
	),
	'variations' => array (
		0 => array (
			'required' => 'true',
			'quantity' => 0,
			'taxonomy' => 'pa_size-eu',
			'term' => array (
				'bg' => 'm-bg47',
				'en-gb' => 'm-en47',
			),
		),
		1 => array (
			'required' => 'true',
			'quantity' => 1,
			'taxonomy' => 'pa_size-eu',
			'term' => array (
				'bg' => 'l-bg48',
				'en-gb' => 'l-en48',
			),
		),
		2 => array (
			'required' => 'true',
			'quantity' => 1,
			'taxonomy' => 'pa_size-eu',
			'term' => array (
				'bg' => 'xl-bg60',
				'en-gb' => 'xl-en60',
			),
		),
		3 => array (
			'required' => 'true',
			'quantity' => 1,
			'taxonomy' => 'pa_size-eu',
			'term' => array (
				'bg' => 'xxl-bg61',
				'en-gb' => 'xxl-en61',
			),
		),
	),
	'images' => array (
		0 => 'data/snimki/italy/store/20161610/17-2.jpg',
	),
	'sale_price' => 40.75,
);

    private $data;
    private $post = array();
    private $images = array();
    private $variations = array();

    public function __construct( $data ) {
        $this->data = $data;
        $this->exampleData = null;
        $this->generateData();
    }

    private function generateData() {
        
        // product attributes
        $attributes = array (
            '_importer_product_id' => $this->data['product_id'],
            '_importer_related_ids' => implode(',', $this->data['related_ids']),

            '_sku' => $this->data['sku'],
            '_price' => $this->data['sale_price'],
            '_regular_price' => $this->data['price'],
            '_sale_price' => $this->data['sale_price'],

            '_sale_price_dates_from' => '',
            '_sale_price_dates_to' => '',

            '_downloadable' => 'no',
            '_virtual' => 'no',
            
            '_backorders' => 'no',
            '_stock_status' => (($this->data['status'] == 'true') ? 'instock' : 'outofstock'),
            '_manage_stock' => (count($this->data['variations']) > 0) ? 'no' : 'yes',
            '_stock' => number_format((float)$this->data['quantity'], 6, '.', ''),

            '_upsell_ids' => array(),
            '_crosssell_ids' => array(),

            '_visibility' => 'visible',
            '_featured' => 'no',
            
            '_weight' => ((int)$this->data['weight'] * 1000),

            '_length' => '0',
            '_width' => '0',
            '_height' => '0',
            
            '_product_attributes' => $this->generateProductAttributes(),
            '_wc_review_count' => '0',

            '_wpml_media_duplicate' =>  '1',
            '_wpml_media_featured' => '1',

            'total_sales' => '0',
            '_tax_status' => 'taxable',
            '_tax_class' => 'vat-20',

        );

        $this->post = array (
            /* Language specific */
            'post_title' => null,
            'post_content' => null,
            'guid' => null, // ??
            'post_category' => array (),
            // defaults
            'post_author' => 1,
            'post_name' => $this->data['seo_keyword'],
            'post_date' => $this->data['date_added'],
            'post_modified' => $this->data['date_modified'],
            'post_status' => 'publish',
            'post_type' => 'product',
            'post_parent' => 0,
            'meta_input' => $attributes
        );

        $this->generateVariations();

        $this->generateImages();

        $this->generateLanguages();
    }

    public function generateVariations() {
        foreach($this->data['variations'] as $i => $variation) {
            $quantity = $variation["quantity"];
            $meta = $this->post['meta_input'];
            $this->variations[$i] = array (
                'guid' => null,
                'post_name' => null,
                'post_parent' => null, // To be defined after post insertion
                'post_title' => null,
                'post_status' => 'publish',
                'post_type' => 'product_variation',
                '_wpml_media_duplicate' =>  '1',
                '_wpml_media_featured' => '1',
                'meta_input' => array (

                    '_sku' => '',
                    '_price' => $meta['_price'],
                    '_regular_price' => $meta['_regular_price'],
                    '_sale_price' => $meta['_sale_price'],

                    '_sale_price_dates_from' => '',
                    '_sale_price_dates_to' => '',

                    '_virtual' => $meta['_virtual'],
                    '_downloadable' => $meta['_downloadable'],
                    '_manage_stock' => 'yes',
                    '_stock' => number_format((float)$quantity, 6, '.', ''),
                    '_stock_status' => (($quantity > 0 ) ? 'instock' : 'outofstock'),
                )
            );
        }
    }

    private function generateImages() {
        $date = $this->post['post_date'];
        $image_root = Yii::getPathOfAlias('cart.image') . "/";
        $this->images = array(
            array(
                'date' => $date,
                'path' => $image_root . $this->data['image_name'],
                'type' => 'thumbnail',
                'title' => null,
            )
        );

        foreach($this->data['images'] as $image) {
            $this->images[] = array(
                'date' => $date,
                'path' => $image_root . $image,
                'type' => 'gallery',
                'title' => null,
            );
        }
    }

    private function generateLanguages() {
        foreach (c2wImport::$langs as $lang_key => $lang) {
            $post = $this->post;
            $variations = $this->variations;
            $images = $this->images;
            $data = $this->data[$lang_key];
            $post_name = $lang . '-' . $post['post_name'];

            // translate post
            $post['post_name'] = $post_name;
            $post['post_title'] = $data['name'];
            $post['post_content'] = trim($data['description']);
            $post['post_categories'] = $data['categories'];
            $post['post_variations'] = array();
            $post['guid'] = home_url() . '/product/' . "$post_name/";

            foreach($this->data['variations'] as $i => $variation) {
                $taxonomy = $variation['taxonomy'];
                $variation_name = $variation['name'][$lang_key];
                $term = $variation['term'][$lang_key];
                $v_post_name = $post_name . '-' . $variation_name;

                $variations[$i]['guid'] =  home_url() . '/?product_variation=' . $v_post_name;
                $variations[$i]['post_name'] = $v_post_name;
                $variations[$i]['post_title'] = $post['post_title'];
                $variations[$i]['meta_input']['attribute_' . $taxonomy] = $term;
                $variations[$i]['meta_input']['_variation_description'] = $data['name'];

                if(!isset($post['post_variations'][$taxonomy])) {
                    $post['post_variations'][$taxonomy] = array();
                }
                $post['post_variations'][$taxonomy][] = $term;
            }

            foreach ($images as $i => $image) {
                $images[$i]['title'] =  $post['post_title'];
            }

            $this->languages[$lang] = compact('post', 'variations', 'images');
        }
    }


    public function generateProductAttributes() {
        $attributes = array ();
        foreach($this->data['variations'] as $variation) {
            $attributes[$variation['taxonomy']] = array (
                'name' => $variation['taxonomy'],
                'value' => '',
                'position' => 0,
                'is_visible' => 0,
                'is_variation' => 1,
                'is_taxonomy' => 1,
            );
        }
        return $attributes;
    }

    public function save() {
        $tr_dict = array(
            'post_product' => array(),
            'post_attachment' => array(),
            'post_product_variation' => array(),
        );


        foreach ($this->languages as $lang => $data) {
            $tr_dict['post_product'][$lang] = array();
            $tr_dict['post_attachment'][$lang] = array();
            $tr_dict['post_product_variation'][$lang] = array();
            
            $post = $data['post'];
            $post_id = wp_insert_post($data['post']);
            $variations = $data['variations'];
            
            $tr_dict['post_product'][$lang][] = $post_id;

            wp_set_object_terms($post_id, $post['post_categories'], 'product_cat');
            if (count($variations) > 0) {
                wp_set_object_terms($post_id, 'variable', 'product_type');
                foreach($post['post_variations'] as $taxonomy => $values) {
                    wp_set_object_terms($post_id, $values, $taxonomy);
                }
                foreach ($variations as $variation) {
                    $variation['post_parent'] = $post_id;
                    $tr_dict['post_product_variation'][$lang][] = wp_insert_post($variation);
                }
            }
            foreach ($data['images'] as $image) {
                $tr_dict['post_attachment'][$lang][] = c2wUtils::addPostMedia($post_id, $image);
            }
        }

        foreach ($tr_dict as $object_type => $translations) {
            foreach (reset($translations) as $i => $post) {

                $translation = array();
                foreach (c2wImport::$langs as $lang_key => $lang) {
                    $translation[$lang] = $translations[$lang][$i];
                }

                $trid = om_i18n_save_object($translation['en'], $translation['bg'], $object_type);
            }
            if ($object_type == 'post_product') {
                $producti18n = array_merge($translation, array('trid' => $trid));
            }
        }
        echo "create new product " . $this->data['sku'] . "... pending translation: ";
        if (isset($producti18n)) {
            woocommerce_wpml::instance()->translation_editor->create_product_translation_package($producti18n['en'], $producti18n['trid'], 'bg', ICL_TM_COMPLETE);
            echo "Sucsessfuly translated!";
        }
        echo "\n";
    }
}