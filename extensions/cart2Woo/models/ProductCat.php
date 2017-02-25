<?php
class ProductCat extends TermTaxonomy {
    
    public function defaultScope()
    {
        return array(
            'condition' => "taxonomy = 'product_cat'",
        );
    }

    public static function findByName($name, $parent = 0) {
        return self::model()->with('term')->find('t.parent=:parent AND term.name = :name',
            array(':name' => $name, ':parent' => $parent)
        );
    }

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}