<?php

namespace Craft;

class TranslateModel extends BaseElementModel
{
    const DONE = 'live';
    const PENDING = 'pending';

    /** @var string $elementType */
    protected $elementType = 'Translate';

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->original;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        if ($this->original != $this->translation) {
            return static::DONE;
        } else {
            return static::PENDING;
        }
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(
            parent::defineAttributes(), array(
            'id'          => AttributeType::String,
            'original'    => AttributeType::String,
            'translation' => AttributeType::String,
            'source'      => AttributeType::Mixed,
            'file'        => AttributeType::String,
            'locale'      => array(AttributeType::String, 'default' => 'en_us'),
            'field'       => AttributeType::Mixed,
        )
        );
    }
}
