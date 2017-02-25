<?php

class c2wOptionsMapper
{
    private static $_attribites = null;
    private static $_productOptions = null;

/*
    static $sql = 'SELECT op.option_id, op_value.option_value_id, op_en.name AS `name(en-gb)`, op_bg.name AS `name(bg)`, val_en.name AS `value(en-gb)`, val_bg.name AS `value(bg)`
        FROM `oc_option` AS op
        LEFT JOIN oc_option_value AS op_value using(option_id)
        LEFT JOIN oc_option_description AS op_en ON op_en.option_id = op.option_id AND op_en.language_id = 1
        LEFT JOIN oc_option_description AS op_bg ON op_bg.option_id = op.option_id AND op_bg.language_id = 2
        LEFT JOIN oc_option_value_description AS val_en ON val_en.option_value_id = op_value.option_value_id AND val_en.language_id = 1
        LEFT JOIN oc_option_value_description AS val_bg ON val_bg.option_value_id = op_value.option_value_id AND val_bg.language_id = 2
        ORDER BY op.sort_order, op_value.sort_order';
*/  
    
    
    private static function ensureData() {
        if (self::$_productOptions === null) {
            self::$_productOptions = self::loadProductOptions();
        }

        if (self::$_attribites === null) {
            self::$_attribites = self::loadAttributes();
        }
    }

    private static function loadProductOptions() {
        
        $productOptionValues    = c2wUtils::xlsxToArray(Yii::getPathOfAlias('application.data.xlsx') . '/products.xlsx', 'ProductOptionValues');
        $productOptions         = c2wUtils::xlsxToArray(Yii::getPathOfAlias('application.data.xlsx') . '/products.xlsx', 'ProductOptions');
        $_productOptions        = array();
        $productRequired        = array();
        foreach ($productOptions as $productOption) {
            $productRequired[$productOption['product_id']] = $productOption['required'];
        }

        foreach ($productOptionValues as $productOptionValue) {
            $product_id = $productOptionValue['product_id'];
            $_productOptions[$product_id][] = array(
                'option_id' => $productOptionValue['option_id'],
                'option_value' => $productOptionValue['option_value'],
                'required' => $productRequired[$product_id],
                'quantity' => $productOptionValue['quantity']
            );
        }
        return $_productOptions;
    }

    private static function loadAttributes() {

        global $wpdb, $_REQUEST;
        $options = c2wUtils::findi18n(c2wUtils::loadJson(Yii::getPathOfAlias('application.data') . '/options.json'));

        $result = array();

        foreach ($options as $option) {
            $option_id = $option['option_id'];
            $option_value_id = $option['option_value_id'];
            if (!isset($result[$option_id])) {
                $taxonomy_name = wc_sanitize_taxonomy_name($option['en-gb']['name']);
                $taxonomy = wc_attribute_taxonomy_name($taxonomy_name);

                if ( !$wpdb->get_var( $wpdb->prepare( "SELECT 1=1 FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $taxonomy_name ) ) ) {
                    $attribute = array(
                        'attribute_name' => $taxonomy_name,
                        'attribute_label' => $option['en-gb']['name'],
                        'attribute_type' => 'select',
                        'attribute_orderby' => 'menu_order',
                        'attribute_public' => 0
                    );
                    $wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
                    echo "Insert woocommerce attribute taxonomy '$taxonomy_name' with id:".$wpdb->insert_id ."\n";
                    do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );
                    flush_rewrite_rules();
                    delete_transient('wc_attribute_taxonomies');
                    register_taxonomy(
                        $taxonomy,
                        'product',
                        array(
                            'label' => $option['en-gb']['name']
                        )
                    );

                    $_wcml_settings = get_option('_wcml_settings');
                    $_wcml_settings['untranstaled_terms']['pa_size-shoes'] = array (
                        'count' => 0,
                        'status' => 0,
                    );
                    $_wcml_settings['attributes_settings']['pa_size-shoes'] = 1;
                    update_option('_wcml_settings', $_wcml_settings);

                    $icl_sitepress_settings = get_option('icl_sitepress_settings');
                    $icl_sitepress_settings['taxonomies_sync_option']['pa_size-shoes'] = 1;
                    update_option('icl_sitepress_settings', $icl_sitepress_settings);
                };

                $result[$option_id] = array(
                    'name' => $option['en-gb']['name'],
                    'taxonomy' => $taxonomy,
                    'values' => array()
                );
            }

            $translations = array();
            $value = array();
            foreach (c2wImport::$langs as $lang_key => $lang) {
                $name = $option[$lang_key]['value'];
                $slug = sanitize_title( $name . "-{$lang}{$option_value_id}" );
                $term = term_exists( $slug, $taxonomy );
                if (!$term) {
                    $_REQUEST['lang'] = $lang;
                    $term = wp_insert_term($name, $taxonomy, compact('slug'));
                    echo "Inserted new term '$name' with term_id:" . $term['term_id'] . "\n";
                    $translations[$lang] = $term['term_id'];
                }
                $term_id = $term['term_id'];
                $value[$lang] = compact( 'name' ,'slug', 'term_id' );
            }
            
            if (!empty($translations)) {
                om_i18n_save_object($translations['en'], $translations['bg'], "tax_" . $taxonomy);
            }
            $result[$option_id]['values'][$option_value_id] = $value;
        }
        return $result;
    }

    public static function findAttributeValuesByOptionValue($attribute, $option_id, $option_value, $lang = 'bg') {
        foreach ($attribute['values'] as $option_value_id => $values) {
            if ($values[$lang]['name'] == $option_value) {
                return $values;
            }
        }
    }
    public static function getProductVariations($product_id) {
        $options = self::getProductOptions($product_id);
        $result = array();
        foreach($options as $option) {
            $option_id = $option['option_id'];
            $option_value = $option['option_value'];
            $attribute = self::getAttribite($option_id);
            $values = self::findAttributeValuesByOptionValue($attribute, $option_id, $option_value);
            $result[] = array(
                "required" => $option["required"],
                "quantity" => $option["quantity"],
                "taxonomy" => $attribute['taxonomy'],
                "name" => array(
                    'bg' => $values['bg']['name'],
                    'en-gb' => $values['en']['name'],
                ),
                "term" => array(
                    'bg' => $values['bg']['slug'],
                    'en-gb' => $values['en']['slug'],
                )
            );
        }
        return $result;
    }

    public static function getProductOptions($product_id) {
        self::ensureData();

        if (!isset(self::$_productOptions[$product_id])) {
            return array();
        }
        return self::$_productOptions[$product_id];
    }

    public static function getAttribite($option_id, $option_value_id = null, $lang = 'en') {
        
        self::ensureData();

        if (!isset(self::$_attribites[$option_id])) {
            return false;
        }
        if (empty($option_value_id)) {
            return self::$_attribites[$option_id];
        }
        if (!isset(self::$_attribites[$option_id]['values'][$option_value_id])) {
            return false;
        }
        // tyrsim go w bg wersiqta 
        return  self::$_attribites[$option_id]['values'][$option_value_id][$lang];
    }
}