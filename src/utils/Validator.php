<?php
/**
 * VelocityPhp Validator
 * Input validation and sanitization
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Validator
{
    private $data;
    private $rules;
    private $errors = [];
    private $customMessages = [];
    
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
    }
    
    public static function make(array $data, array $rules, array $messages = [])
    {
        $validator = new self($data, $rules, $messages);
        return $validator;
    }
    
    public function validate()
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            
            foreach ($rules as $rule) {
                $this->validateRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    public function fails()
    {
        return !$this->validate();
    }
    
    public function errors()
    {
        if (empty($this->errors)) {
            $this->validate();
        }
        return $this->errors;
    }
    
    public function error($field)
    {
        return $this->errors[$field] ?? null;
    }
    
    private function validateRule($field, $rule)
    {
        $value = $this->data[$field] ?? null;
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleValue = $ruleParts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, 'required');
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                }
                break;
                
            case 'min':
                $min = (int)$ruleValue;
                if (strlen($value) < $min) {
                    $this->addError($field, 'min', ['min' => $min]);
                }
                break;
                
            case 'max':
                $max = (int)$ruleValue;
                if (strlen($value) > $max) {
                    $this->addError($field, 'max', ['max' => $max]);
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, 'numeric');
                }
                break;
                
            case 'integer':
                if ($value && !is_int((int)$value) || (string)(int)$value !== (string)$value) {
                    $this->addError($field, 'integer');
                }
                break;
                
            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'url');
                }
                break;
                
            case 'alpha':
                if ($value && !ctype_alpha(str_replace(' ', '', $value))) {
                    $this->addError($field, 'alpha');
                }
                break;
                
            case 'alphanumeric':
                if ($value && !ctype_alnum(str_replace(' ', '', $value))) {
                    $this->addError($field, 'alphanumeric');
                }
                break;
                
            case 'regex':
                if ($value && !preg_match($ruleValue, $value)) {
                    $this->addError($field, 'regex');
                }
                break;
                
            case 'same':
                $otherValue = $this->data[$ruleValue] ?? null;
                if ($value !== $otherValue) {
                    $this->addError($field, 'same', ['other' => $ruleValue]);
                }
                break;
                
            case 'in':
                $allowed = explode(',', $ruleValue);
                if ($value && !in_array($value, $allowed)) {
                    $this->addError($field, 'in');
                }
                break;

            case 'not_in':
                $disallowed = explode(',', $ruleValue);
                if ($value && in_array($value, $disallowed)) {
                    $this->addError($field, 'not_in');
                }
                break;

            case 'date':
                if ($value) {
                    $d = \DateTime::createFromFormat('Y-m-d', $value);
                    if (!$d || $d->format('Y-m-d') !== $value) {
                        $this->addError($field, 'date');
                    }
                }
                break;

            case 'before':
                if ($value && strtotime($value) >= strtotime($ruleValue)) {
                    $this->addError($field, 'before', ['date' => $ruleValue]);
                }
                break;

            case 'after':
                if ($value && strtotime($value) <= strtotime($ruleValue)) {
                    $this->addError($field, 'after', ['date' => $ruleValue]);
                }
                break;

            case 'confirmed':
                $otherValue = $this->data["{$field}_confirmation"] ?? null;
                if ($value !== $otherValue) {
                    $this->addError($field, 'confirmed');
                }
                break;

            case 'between':
                [$min, $max] = explode(',', $ruleValue);
                $len = strlen((string) $value);
                if ($len < (int) $min || $len > (int) $max) {
                    $this->addError($field, 'between', ['min' => $min, 'max' => $max]);
                }
                break;

            case 'nullable':
                // Allow null/empty to pass all subsequent rules
                if (empty($value) && $value !== '0') {
                    // Remove any errors already added for this field and stop processing
                    unset($this->errors[$field]);
                }
                break;

            case 'json':
                if ($value) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->addError($field, 'json');
                    }
                }
                break;
        }
    }
    
    private function addError($field, $rule, $params = [])
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $message = $this->getMessage($field, $rule, $params);
        $this->errors[$field][] = $message;
    }
    
    private function getMessage($field, $rule, $params = [])
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }
        
        $messages = [
            'required'   => "The {$field} field is required.",
            'email'      => "The {$field} must be a valid email address.",
            'min'        => "The {$field} must be at least {$params['min']} characters.",
            'max'        => "The {$field} may not be greater than {$params['max']} characters.",
            'numeric'    => "The {$field} must be a number.",
            'integer'    => "The {$field} must be an integer.",
            'url'        => "The {$field} must be a valid URL.",
            'alpha'      => "The {$field} may only contain letters.",
            'alphanumeric' => "The {$field} may only contain letters and numbers.",
            'regex'      => "The {$field} format is invalid.",
            'same'       => "The {$field} and {$params['other']} must match.",
            'in'         => "The selected {$field} is invalid.",
            'not_in'     => "The selected {$field} is not allowed.",
            'date'       => "The {$field} must be a valid date (YYYY-MM-DD).",
            'before'     => "The {$field} must be a date before {$params['date']}.",
            'after'      => "The {$field} must be a date after {$params['date']}.",
            'confirmed'  => "The {$field} confirmation does not match.",
            'between'    => "The {$field} must be between {$params['min']} and {$params['max']} characters.",
            'json'       => "The {$field} must be valid JSON.",
        ];
        
        return $messages[$rule] ?? "The {$field} field is invalid.";
    }
    
    public static function sanitize($value, $type = 'string')
    {
        switch ($type) {
            case 'string':
                return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'special':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            default:
                return $value;
        }
    }
}

