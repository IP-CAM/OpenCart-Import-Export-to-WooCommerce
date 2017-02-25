<?php

class c2wUtils {
    private static $_dict = array();

    public static function query2array($query) {
        global $columns;
        $parser = new \PHPSQL\Parser($query, true);
        $parsed = $parser->parsed;
        $columns = array_map(function($c) {
            return trim($c['base_expr'], '`');
        }, reset($parsed['INSERT'])['columns']);
        
        $rows = array_map(function($r) {
            global $columns;
            $row = array_map(function($d) {
                return trim($d['base_expr'], "'");
            }, $r['data']);
            return array_combine( $columns, $row);
        }, $parsed['VALUES']);
        return $rows;
    }

    public static function arrayFlaten($array, $key, $value) {
        $res = array();
        foreach($array as $row) {
            $res[$row[$key]] = $row[$value];
        }
        return $res;
    }
    
    public static function extractQueryLog() {
        $logFile = Yii::getPathOfAlias('application.data') . '/query-log.txt';
        $queryFile = Yii::getPathOfAlias('application.data') . '/query.sql';
        $lines = file($logFile);
        foreach($lines as $line) {
            $raw = json_decode($line, true);
            eval('$data = ' . $raw['data'] .';');
            if (!isset($data['sql'])) continue;
            $sql = $data['sql'];
            $sql = preg_replace('/(?|( )+|(\\n)+)/', '$1', $sql);
            $sql = preg_replace('/[\\n]+/', ' ', $sql);
            $sql = preg_replace('/\s*$^\s*/m', " ", $sql);
            if (preg_match("#^(SHOW TABLES|SELECT option_value AS version)#", $sql, $matches)) continue;
            error_log(trim($sql) . "\n", 3, $queryFile);
        }
    }
    
    public static function uniqueStr($group, $str, $separator = '_') {
        if(!isset(self::$_dict[$group])) {
            self::$_dict[$group] = array();
        }

        $str = trim($str);
        if (isset(self::$_dict[$group][$str])) {
            $str .= $separator . '1';
            while(isset(self::$_dict[$group][$str])) {
                $returnValue = preg_match('#^(.*)'.$separator.'(\\d)$#', $str, $matches);
                $str = $matches[1] . $separator . (1 + (int)$matches[2]);
            }
        }
        return self::$_dict[$group][$str] = $str;
    }



    public static function addPostMedia($post_id, $options) {
        $filename = $options['path'];
        require_once Yii::getPathOfAlias('wordpress') . "/wp-admin/includes/image.php";
        
        $name = basename( $filename );
        //rename the file... alternatively, you could explode on "/" and keep the original file name
        $name_parts = explode(".", $name);
        $ext = array_pop($name_parts);
        $new_filename = implode('.',$name_parts) . '-'.$post_id.".".$ext; //if your post has multiple files, you may need to add a random number to the file name to prevent overwrites
        
        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype( $new_filename, null );

        // Get the path to the upload directory.
        $wp_upload_dir = wp_upload_dir($options['date']);

        if (@fclose(@fopen($filename, "r"))) { //make sure the file actually exists
            copy($filename, $wp_upload_dir['path'] . "/" . $new_filename);
        } else {
            echo "File '$filename' does not exists.";
            return false;
        }
        $filename = $wp_upload_dir['path'] . "/" . $new_filename;

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
            'post_mime_type' => $filetype['type'],
            'post_title'     => $options['title'],
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Insert the attachment.
        $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        
        switch ($options['type']) {
            case 'thumbnail':
                update_post_meta( $post_id, '_thumbnail_id', $attach_id );
                break;
            case 'gallery':
                update_post_meta( $post_id, '_product_image_gallery', $attach_id);
                break;
        }
        return $attach_id;
    }

    public static function fetchMedia($file_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        global $wpdb;

        if (!$post_id) {
            return false;
        }

        //directory to import to	
        $artDir = 'wp-content/uploads/importedmedia/';

        //if the directory doesn't exist, create it	
        if(!file_exists(ABSPATH.$artDir)) {
            mkdir(ABSPATH.$artDir);
        }

        //rename the file... alternatively, you could explode on "/" and keep the original file name
        $ext = array_pop(explode(".", $file_url));
        $new_filename = 'blogmedia-'.$post_id.".".$ext; //if your post has multiple files, you may need to add a random number to the file name to prevent overwrites

        if (@fclose(@fopen($file_url, "r"))) { //make sure the file actually exists
            copy($file_url, ABSPATH.$artDir.$new_filename);

            $siteurl = get_option('siteurl');
            $file_info = getimagesize(ABSPATH.$artDir.$new_filename);

            //create an array of attachment data to insert into wp_posts table
            $artdata = array();
            $artdata = array(
                'post_author' => 1, 
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql'),
                'post_title' => $new_filename, 
                'post_status' => 'inherit',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_name' => sanitize_title_with_dashes(str_replace("_", "-", $new_filename)),
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql'),
                'post_parent' => $post_id,
                'post_type' => 'attachment',
                'guid' => $siteurl.'/'.$artDir.$new_filename,
                'post_mime_type' => $file_info['mime'],
                'post_excerpt' => '',
                'post_content' => ''
            );

            $uploads = wp_upload_dir();
            $save_path = $uploads['basedir'].'/importedmedia/'.$new_filename;

            //insert the database record
            $attach_id = wp_insert_attachment( $artdata, $save_path, $post_id );

            //generate metadata and thumbnails
            if ($attach_data = wp_generate_attachment_metadata( $attach_id, $save_path)) {
                wp_update_attachment_metadata($attach_id, $attach_data);
            }

            //optional make it the featured image of the post it's attached to
            $rows_affected = $wpdb->insert($wpdb->prefix.'postmeta', array('post_id' => $post_id, 'meta_key' => '_thumbnail_id', 'meta_value' => $attach_id));
        }
        else {
            return false;
        }
        return true;
    }

    function insertProduct ($product_data) {

        $post = array( // Set up the basic post data to insert for our product
            'post_author'  => 1,
            'post_content' => $product_data['description'],
            'post_status'  => 'publish',
            'post_title'   => $product_data['name'],
            'post_parent'  => '',
            'post_type'    => 'product'
        );

        $post_id = wp_insert_post($post); // Insert the post returning the new post id

        if (!$post_id) // If there is no post id something has gone wrong so don't proceed
        {
            return false;
        }

        update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
        update_post_meta( $post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end

        wp_set_object_terms($post_id, $product_data['categories'], 'product_cat'); // Set up its categories
        wp_set_object_terms($post_id, 'variable', 'product_type'); // Set it to a variable product type

        self::insertProductAttributes($post_id, $product_data['available_attributes'], $product_data['variations']); // Add attributes passing the new post id, attributes & variations
        self::insertProductVariations($post_id, $product_data['variations']); // Insert variations passing the new post id & variations   
    }

    function insertProductAttributes($post_id, $available_attributes, $variations) {

        foreach ($available_attributes as $attribute) // Go through each attribute
        {   
            $values = array(); // Set up an array to store the current attributes values.

            foreach ($variations as $variation) // Loop each variation in the file
            {
                $attribute_keys = array_keys($variation['attributes']); // Get the keys for the current variations attributes

                foreach ($attribute_keys as $key) // Loop through each key
                {
                    if ($key === $attribute) // If this attributes key is the top level attribute add the value to the $values array
                    {
                        $values[] = $variation['attributes'][$key];
                    }
                }
            }

            // Essentially we want to end up with something like this for each attribute:
            // $values would contain: array('small', 'medium', 'medium', 'large');

            $values = array_unique($values); // Filter out duplicate values

            // Store the values to the attribute on the new post, for example without variables:
            // wp_set_object_terms(23, array('small', 'medium', 'large'), 'pa_size');
            wp_set_object_terms($post_id, $values, 'pa_' . $attribute);
        }

        $product_attributes_data = array(); // Setup array to hold our product attributes data

        foreach ($available_attributes as $attribute) // Loop round each attribute
        {
            $product_attributes_data['pa_'.$attribute] = array( // Set this attributes array to a key to using the prefix 'pa'

                'name'         => 'pa_'.$attribute,
                'value'        => '',
                'is_visible'   => '1',
                'is_variation' => '1',
                'is_taxonomy'  => '1'

            );
        }

        update_post_meta($post_id, '_product_attributes', $product_attributes_data); // Attach the above array to the new posts meta data key '_product_attributes'
    }

    function insertProductVariations($post_id, $variations) {
        foreach ($variations as $index => $variation)
        {
            $variation_post = array( // Setup the post data for the variation

                'post_title'  => 'Variation #'.$index.' of '.count($variations).' for product#'. $post_id,
                'post_name'   => 'product-'.$post_id.'-variation-'.$index,
                'post_status' => 'publish',
                'post_parent' => $post_id,
                'post_type'   => 'product_variation',
                'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
            );

            $variation_post_id = wp_insert_post($variation_post); // Insert the variation

            foreach ($variation['attributes'] as $attribute => $value) // Loop through the variations attributes
            {   
                $attribute_term = get_term_by('name', $value, 'pa_'.$attribute); // We need to insert the slug not the name into the variation post meta

                update_post_meta($variation_post_id, 'attribute_pa_'.$attribute, $attribute_term->slug);
            // Again without variables: update_post_meta(25, 'attribute_pa_size', 'small')
            }

            update_post_meta($variation_post_id, '_price', $variation['price']);
            update_post_meta($variation_post_id, '_regular_price', $variation['price']);
        }
    }


    public static function findi18n($data) {
        
        foreach($data as $i => $row) {
            foreach($row as $key => $value) {
                preg_match( "#([^\(]+)(\(([^\)]+)\))?#", $key, $match);
                if (isset($match[3])) {
                    unset($data[$i][$key]);
                    $key = $match[1];
                    $lang = $match[3];
                    if (!isset($data[$i][$lang])) {
                        $data[$i][$lang] = array();
                    }
                    $data[$i][$lang][$key] = $value;
                }
            }
        }
        return $data;
    }

    public static function loadJson($path) {
        return json_decode(file_get_contents($path), true);
    }

    private static $_cache = array ();

    public static function xlsxToArray($xlsxPath, $worksheetTitle) {
        
        if (!isset(self::$_cache[$xlsxPath])) {
            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            self::$_cache[$xlsxPath] = $objReader->load($xlsxPath);
        }
        $objWorksheet       = self::$_cache[$xlsxPath]->setActiveSheetIndexbyName($worksheetTitle);
        $highestRow         = $objWorksheet->getHighestRow(); // e.g. 10
        $highestColumn      = $objWorksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);

        $data = array();
        for ($row = 1; $row <= $highestRow; ++$row) {
            if ($row == 1) {
                $columns = array();
                for ($col = 0; $col < $highestColumnIndex; ++$col) {
                    $columns[$col] = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                }
                continue;
            }
            $rowData = array();
            for ($col = 0; $col < $highestColumnIndex; ++$col) {
                $rowData[$columns[$col]] = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
            $data[] = $rowData;
        }
        return $data;
    }


    public function resetShop() {
        /*
TRUNCATE TABLE `wpqs_woocommerce_tax_rates`
TRUNCATE TABLE `wpqs_woocommerce_tax_rate_locations`
UPDATE  wpqs_options  SET  option_value = 'Reduced Rate\r\nZero Rate'  WHERE  option_name = 'woocommerce_tax_classes'
DELETE  t, tt, wc_tm, p, pm  FROM  wpqs_terms AS t  JOIN  wpqs_term_taxonomy AS tt  ON t.term_id = tt.term_id  LEFT JOIN  wpqs_termmeta AS wc_tm  ON t.term_id = wc_tm.term_id  LEFT JOIN  wpqs_posts AS p  ON wc_tm.meta_value = p.ID  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  WHERE  tt.taxonomy = 'product_cat'
DELETE  FROM wpqs_icl_translations  WHERE  element_type = 'tax_product_cat'
DELETE  t, tt, wc_tm, p, pm  FROM  wpqs_terms AS t  JOIN  wpqs_term_taxonomy AS tt  ON t.term_id = tt.term_id  LEFT JOIN  wpqs_termmeta AS wc_tm  ON t.term_id = wc_tm.term_id  LEFT JOIN  wpqs_posts AS p  ON wc_tm.meta_value = p.ID  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  WHERE  tt.taxonomy = 'product_brand'
SELECT  ID  FROM  wpqs_posts  WHERE  post_type = 'product'
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 36  OR p.post_parent = 36
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 79  OR p.post_parent = 79
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 107  OR p.post_parent = 107
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 144  OR p.post_parent = 144
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 97  OR p.post_parent = 97
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 168  OR p.post_parent = 168
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 55  OR p.post_parent = 55
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 174  OR p.post_parent = 174
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 57  OR p.post_parent = 57
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 176  OR p.post_parent = 176
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 177  OR p.post_parent = 177
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 179  OR p.post_parent = 179
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 180  OR p.post_parent = 180
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 200  OR p.post_parent = 200
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 203  OR p.post_parent = 203
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 205  OR p.post_parent = 205
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 209  OR p.post_parent = 209
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 216  OR p.post_parent = 216
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 217  OR p.post_parent = 217
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 218  OR p.post_parent = 218
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 224  OR p.post_parent = 224
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 228  OR p.post_parent = 228
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 231  OR p.post_parent = 231
DELETE  p, pm, tr  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  WHERE  p.ID = 233  OR p.post_parent = 233
SELECT  attribute_name  FROM  wpqs_woocommerce_attribute_taxonomies
DELETE  tt, t , tr  FROM  wpqs_terms AS t  LEFT JOIN  wpqs_term_taxonomy AS tt  ON t.term_id = tt.term_id LEFT JOIN wpqs_icl_translations tr  ON tt.taxonomy = tr.element_type  WHERE  tt.taxonomy IN ('pa_size-shoes', 'size-shoes')
DELETE  tt, t , tr  FROM  wpqs_terms AS t  LEFT JOIN  wpqs_term_taxonomy AS tt  ON t.term_id = tt.term_id LEFT JOIN wpqs_icl_translations tr  ON tt.taxonomy = tr.element_type  WHERE  tt.taxonomy IN ('pa_size-clothes', 'size-clothes')
DELETE  tt, t  FROM  wpqs_terms AS t  LEFT JOIN  wpqs_term_taxonomy AS tt  ON t.term_id = tt.term_id  WHERE  tt.taxonomy = 'product_tag'
TRUNCATE TABLE wpqs_woocommerce_attribute_taxonomies
DELETE  FROM wpqs_icl_translations  WHERE  element_type LIKE 'tax_pa_%' OR  element_type IN ('post_product', 'post_product_variation')
DELETE  tr, pm  FROM wpqs_icl_translations tr  LEFT JOIN wpqs_posts p  ON tr.element_id = p.id  LEFT JOIN wpqs_postmeta pm  ON tr.element_id = pm.post_id  WHERE  tr.element_type = 'post_attachment'  AND p.id IS NULL
DELETE  u, um  FROM  wpqs_users AS u  JOIN  wpqs_usermeta AS um  ON u.ID = um.user_id  WHERE  meta_key = 'wpqs_capabilities'  AND meta_value NOT LIKE '%administrator%'  AND meta_value NOT LIKE '%editor%'  AND meta_value NOT LIKE '%author%'  AND meta_value NOT LIKE '%shop_manager%'
DELETE  FROM  wpqs_usermeta  WHERE  user_id NOT IN(SELECT id FROM wpqs_users)
DELETE  p, pm  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  WHERE  post_type = 'shop_coupon'
DELETE  p, tr, pm, c, cm  FROM  wpqs_posts AS p  LEFT JOIN  wpqs_term_relationships AS tr  ON p.ID = tr.object_id  JOIN  wpqs_postmeta AS pm  ON p.ID = pm.post_id  LEFT JOIN  wpqs_comments AS c  ON p.ID = c.comment_post_ID  LEFT JOIN  wpqs_commentmeta cm  ON c.comment_ID = cm.comment_id  LEFT JOIN  wpqs_woocommerce_downloadable_product_permissions od  ON p.ID = od.order_id  WHERE  p.post_type = 'shop_order'
SELECT  option_value  FROM  wpqs_options  WHERE  option_name = 'woocommerce_version' LIMIT 1
TRUNCATE TABLE wpqs_woocommerce_order_items
TRUNCATE TABLE wpqs_woocommerce_order_itemmeta
TRUNCATE TABLE wpqs_woocommerce_downloadable_product_permissions
        */

        return false;
    }
}