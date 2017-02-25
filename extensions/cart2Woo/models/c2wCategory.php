<?php

class c2wCategory {
    
    public $data;
    public $terms = null;

    public function __construct( $data ) {
        $this->data = $data;
        $this->init();
    }

    public function init() {

        if ($this->data['parent_id'] == 0) return ;

        $en = $this->getCategoryByPath($this->data['en-gb']['path']);
        $bg = $this->getCategoryByPath($this->data['bg']['path']);

        $this->terms = compact('bg', 'en');
        // make sure that all terms are translated
        om_i18n_save_object($en->term_id, $bg->term_id, 'tax_product_cat');
    }

    public function getCategoryByPath($path) {
        $names = explode('/', $path);
        $parent = 0;
        $term = null;
        foreach($names as $name) {
            $term = ProductCat::findByName($name, $parent);
            if (empty($term)) {
                die("Category " . $path . " -> (" . $name . ") is not found.\n");
            }
            $parent = $term->term_id;
        }
        return $term;
    }

    public function getId($lang = 'en') {
        if ($this->terms === null) return 0;
        return (int)$this->terms[$lang]->term_id;
    }
}