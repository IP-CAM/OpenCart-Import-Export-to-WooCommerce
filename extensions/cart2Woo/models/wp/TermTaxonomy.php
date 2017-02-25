<?php

/**
 * This is the model class for table "{{term_taxonomy}}".
 *
 * The followings are the available columns in table '{{term_taxonomy}}':
 * @property string $term_taxonomy_id
 * @property string $term_id
 * @property string $taxonomy
 * @property string $description
 * @property string $parent
 * @property string $count
 */
class TermTaxonomy extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{term_taxonomy}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('description', 'required'),
			array('term_id, parent, count', 'length', 'max'=>20),
			array('taxonomy', 'length', 'max'=>32),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('term_taxonomy_id, term_id, taxonomy, description, parent, count', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
            'term' => array(self::BELONGS_TO, 'Terms', 'term_id'),
			'parent' => array(self::BELONGS_TO, 'TermTaxonomy', 'parent'), //the column in 'parent' containing 'this' id
            'children' => array(self::HAS_MANY, 'TermTaxonomy', 'parent'), //the column in 'this' containing the child id
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'term_taxonomy_id' => 'Term Taxonomy',
			'term_id' => 'Term',
			'taxonomy' => 'Taxonomy',
			'description' => 'Description',
			'parent' => 'Parent',
			'count' => 'Count',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('term_taxonomy_id',$this->term_taxonomy_id,true);
		$criteria->compare('term_id',$this->term_id,true);
		$criteria->compare('taxonomy',$this->taxonomy,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('parent',$this->parent,true);
		$criteria->compare('count',$this->count,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return TermTaxonomy the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
