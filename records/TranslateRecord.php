<?php

namespace Craft;

class TranslateRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'translate';
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
            'locale'       => array(
                'type'     => AttributeType::String,
                'required' => true,
            ),
            'translations' => array(
                'type'     => AttributeType::Mixed,
                'required' => true,
                'column' => ColumnType::MediumText,
            ),
        );
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $class = get_class($this);
        $record = new $class();
        return $record;
    }
}