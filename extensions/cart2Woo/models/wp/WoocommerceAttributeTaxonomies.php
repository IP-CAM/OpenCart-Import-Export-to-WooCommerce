<?php

/**
 * This is the model class for table "{{woocommerce_attribute_taxonomies}}".
 *
 * The followings are the available columns in table '{{woocommerce_attribute_taxonomies}}':
 * @property string $attribute_id
 * @property string $attribute_name
 * @property string $attribute_label
 * @property string $attribute_type
 * @property string $attribute_orderby
 * @property integer $attribute_public
 */
class WoocommerceAttributeTaxonomies extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{woocommerce_attribute_taxonomies}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('attribute_name, attribute_type, attribute_orderby', 'required'),
			array('attribute_public', 'numerical', 'integerOnly'=>true),
			array('attribute_name, attribute_type, attribute_orderby', 'length', 'max'=>200),
			array('attribute_label', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('attribute_id, attribute_name, attribute_label, attribute_type, attribute_orderby, attribute_public', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'attribute_id' => 'Attribute',
			'attribute_name' => 'Attribute Name',
			'attribute_label' => 'Attribute Label',
			'attribute_type' => 'Attribute Type',
			'attribute_orderby' => 'Attribute Orderby',
			'attribute_public' => 'Attribute Public',
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

		$criteria->compare('attribute_id',$this->attribute_id,true);
		$criteria->compare('attribute_name',$this->attribute_name,true);
		$criteria->compare('attribute_label',$this->attribute_label,true);
		$criteria->compare('attribute_type',$this->attribute_type,true);
		$criteria->compare('attribute_orderby',$this->attribute_orderby,true);
		$criteria->compare('attribute_public',$this->attribute_public);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return WoocommerceAttributeTaxonomies the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
