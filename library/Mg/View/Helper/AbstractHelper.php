<?php

class Mg_View_Helper_AbstractHelper extends Zend_View_Helper_Abstract
{
    private $_options;
    private $_attributes;

    public function setOptions(array $options)
    {
        $this->_options = $options;
    }

    public function getOption($key, $defaultValue = null)
    {
        if (isset($this->_options[$key]) && null !== $this->_options[$key]) {
            return $this->_options[$key];
        } else {
            return $defaultValue;
        }
    }

    public function addAttribute($name, $value = '')
    {
        $this->_attributes[$name] = $value;
    }

    public function addAttributes($attributes)
    {
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $this->addAttribute($key, $value);
            }
        }
    }

    public function getAttribute($key, $defaultValue = null)
    {
        if (isset($this->_attributes[$key]) && null !== $this->_attributes[$key]) {
            return $this->_attributes[$key];
        } else {
            return $defaultValue;
        }
    }

    public function setAttribute($name, $value)
    {
        $this->_attributes = array();
        $this->addATtribute($name, $value);
    }

    public function setAttributes(array $attributes)
    {
        $this->_attributes = $attributes;
    }

    public function renderAttributes()
    {
        if (is_array($this->_attributes)) {

            $output = array();

            foreach ($this->_attributes as $name => $value) {
                if (empty($value)) {
                    $output[] = $name;
                } else {
                    $output[] = " {$name}=\"{$value}\"";
                }
            }

            return implode(' ', $output);
        } else {
            return '';
        }
    }
}