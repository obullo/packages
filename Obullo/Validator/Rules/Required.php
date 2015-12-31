<?php

namespace Obullo\Validator\Rules;

use Obullo\Validator\FieldInterface as Field;

/**
 * Required
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Required
{
    /**
     * Call next
     * 
     * @param Field $next object
     * 
     * @return object
     */
    public function __invoke(Field $next)
    {
        $field = $next;
        if ($this->isValid($field->getValue())) {
            return $next();
        }
        return false;
    }

    /**
     * Empty or not
     * 
     * @param string $value value
     * 
     * @return bool
     */    
    public function isValid($value)
    {        
        if (is_object($value) || is_null($value)) {
            return false;
        }
        if (is_string($value) && empty($value)) {
            return false;
        }
        if (is_array($value) && ($value == array())) {
            return false;
        }
        if (is_string($value) && ($value == '0')) {
            return false;
        }        
        if (is_string($value) && ($value == '')) {
            return false;
        }        
        if (is_float($value) && ($value == 0.0)) {
            return false;
        }        
        if (is_int($value) && ($value == 0)) {
            return false;
        }
        if (is_bool($value) && ($value == false)) {
            return false;
        }
        return true;
    }
}

