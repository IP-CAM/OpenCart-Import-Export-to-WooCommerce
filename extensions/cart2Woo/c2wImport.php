<?php
Yii::setPathOfAlias('c2w', dirname(__FILE__));

Yii::import("c2w.models.wp.*");
Yii::import("c2w.models.*");

class c2wImport {
    private static $_additionalImages = null;
    private static $_Specials = null;

    public static $langs = array(
        'en-gb' => 'en',
        'bg' => 'bg'
    );

    public function __construct() {

        $data = self::loadProducts();
        
        foreach ($data as $product) {
            $c2wProduct = new c2wProduct($product);
            //$c2wProduct->save();
        }
        
        //$this->updateCrosssellIds();

    }

    private static function getAdditionalImages($id) {
        if (self::$_additionalImages === null) {
            self::$_additionalImages = c2wUtils::xlsxToArray(Yii::getPathOfAlias('application.data.xlsx') . '/products.xlsx', 'AdditionalImages');
        }
        $result = array();
        foreach(self::$_additionalImages as $row) {
            if ($row['product_id'] == $id) {
                $result[] = $row['image'];
            }
        }
        return $result;
    }
    
    private static function getSalePrice($id, $default) {
        if (self::$_Specials === null) {
            self::$_Specials = c2wUtils::xlsxToArray(Yii::getPathOfAlias('application.data.xlsx') . '/products.xlsx', 'Specials');
        }
        $result = array();
        foreach(self::$_Specials as $row) {
            if ($row['product_id'] == $id) {
                $result[] = array(
                    'priority' => $row['priority'],
                    'price' => $row['price']
                );
            }
        }
        return (empty($result)) ? $default : reset($result)['price'];
    }
    private function mapCrosssellIds($rows) {
        
        $result = array();
        $post_by_product = array();

        foreach ($rows as $row) {
            $post_by_product[$row->product_id] = $row->post_id;
        }
        foreach ($rows as $row) {
            if ($row->related == '') continue;

            if (empty($result[$row->post_id])) {
                $result[$row->post_id] = array();
            }
            foreach (explode(',', $row->related) as $product_id) {
                if (!isset($post_by_product[$product_id])) {
                    continue;
                }
                $result[$row->post_id][] = $post_by_product[$product_id];
            }
        }
        return $result;
    }
    public function updateCrosssellIds() {
        global $wpdb;

        $sql = "SELECT p1.post_id, p1.meta_value as product_id, p2.meta_value AS related  FROM `wpqs_postmeta` AS p1
            INNER JOIN wpqs_icl_translations AS p3 ON p3.element_type = 'post_product' AND p3.element_id = p1.post_id
            LEFT JOIN `wpqs_postmeta` AS p2 ON p2.post_id = p1.post_id AND p2.meta_key = '_importer_related_ids'
            WHERE p3.language_code = %s AND p1.`meta_key` = '_importer_product_id'";

        foreach(self::$langs as $key => $lang) {
            $rows = $wpdb->get_results($wpdb->prepare($sql, $lang));
            $result = $this->mapRelatedIds($rows);
            foreach($result as $post_id => $crosssell_ids) {
                update_post_meta($post_id, '_crosssell_ids', $crosssell_ids);
            }
        }
    }

    public static function loadProducts() {
        $data = c2wUtils::xlsxToArray(Yii::getPathOfAlias('application.data.xlsx') . '/products.xlsx', 'Products');
        $data = c2wUtils::findi18n($data);
        foreach($data as $key => $value) {
            $product_id = $value['product_id'];

            if ($processRelations = true) {
                $data[$key]['sku'] = c2wUtils::uniqueStr('sku', empty($value['sku']) ? $value['model'] : $value['sku']);
                $data[$key]['seo_keyword'] = c2wUtils::uniqueStr('seo', $value['seo_keyword'], '-');

                // process categories
                $categories = empty($value['categories']) ? array() : explode(',', $value['categories']);
                foreach(self::$langs as $lang_key => $lang) {
                    $data[$key][$lang_key]['categories'] = array();
                    foreach($categories as $category) {
                        $data[$key][$lang_key]['categories'][] = c2wCategoryMapper::getId($category, $lang);
                    }
                }
                $data[$key]['categories'] = $categories;
                
                // process categories and related_ids
                $data[$key]['related_ids'] = empty($value['related_ids']) ? array() : explode(',', $value['related_ids']);
            
                // ProductOptions and ProductOptionValues
                $data[$key]['variations'] = c2wOptionsMapper::getProductVariations($product_id);

                // AdditionalImages
                $data[$key]['images'] = self::getAdditionalImages($product_id);

                // Specials
                $data[$key]['sale_price'] = self::getSalePrice($product_id, $data[$key]['price']);

            }
        }

        return $data;
    }
}