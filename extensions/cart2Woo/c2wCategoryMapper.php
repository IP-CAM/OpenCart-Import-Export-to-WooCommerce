<?php

class c2wCategoryMapper {

    private static $_mappings = null;

    private static function findPaths($id, $all) {
        $path_en = '';
        $path_bg = '';
        while (isset($all[$id])) {
            $cat = $all[$id];
            $path_bg = $cat['bg']['name'] . '/' . $path_bg;
            $path_en = $cat['en-gb']['name'] .'/'. $path_en;
            $id = $cat['parent_id'];
        }
        return array(
            'bg' => array( 'path' => preg_replace("#(^[^\/]+\/|\/$)#", "", $path_bg) ),
            'en-gb' => array( 'path' => preg_replace("#(^[^\/]+\/|\/$)#", "", $path_en) )
        );
    }
    
    private static function createMappings() {
        $categories = c2wUtils::xlsxToArray(Yii::getPathOfAlias('application.data.xlsx') . '/categories.xlsx', 'Categories');
        $categories = c2wUtils::findi18n($categories);

        $mappings = array();

        foreach($categories as $category) {
            $mappings[$category['category_id']] = $category;
        }

        foreach ($mappings as $category) {
            $mappings[$category['category_id']] = array_merge_recursive($category, self::findPaths($category['category_id'], $mappings));
        }

        foreach ($mappings as $category) {
            $mappings[$category['category_id']] = new c2wCategory($category);
        }

        return $mappings;
    }
    
    private static function ensureMappings() {
        if (self::$_mappings === null) {
            self::$_mappings = self::createMappings();
        }
    }

    public static function getId($shop_category_id, $lang = 'en') {
        self::ensureMappings();
        return isset(self::$_mappings[$shop_category_id]) ? self::$_mappings[$shop_category_id]->getId($lang) : 0;
    }
}
