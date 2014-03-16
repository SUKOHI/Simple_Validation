<?php

class Simple_Validation {

	private $_check_values = array();
	private $_rules = array();
	private $_custom_functions = array();
	private $_error_messages = array();
	private $_error_count = 0;
	private $_encoding;
	private $_error_message_template = '';
	const ERROR_MESSAGE_REPLACE = '[{message}]';
	
	public function __construct($encoding='utf-8') {
		
		if(!empty($encoding)) {

			$this->setEncoding($encoding);
			
		}
		
	}
	
	public function setCheckValues($check_values) {
		
		$this->_check_values = $check_values;
		
	}
	
	public function setRules($rules) {
		
		$this->_rules = $rules;
		
	}
	
	public function setEncoding($encoding) {
		
		$this->_encoding = $encoding;
		
	}
	
	public function setErrorMessageTag($tag_name, $property='') {
		
		if($property != '') {
			
			$property = ' '. $property;
			
		}
		
		$this->_error_message_template = '<'. $tag_name . $property .'>'. self::ERROR_MESSAGE_REPLACE .'</'. $tag_name .'>';
		
	}
	
	public function setCustomRule($name, $custom_function) {
		
		if(gettype($custom_function) == 'object') {
			
			$this->_custom_functions[$name] = $custom_function;
			
		}
		
	}
	
	public function length($str, $length) {
		
		return (mb_strlen($str, $this->_encoding) === $length);
		
	}
	
	public function minLength($str, $min_length) {
		
		return (mb_strlen($str, $this->_encoding) >= $min_length);
		
	}
	
	public function maxLength($str, $max_length) {
		
		return (mb_strlen($str, $this->_encoding) <= $max_length);
		
	}
	
	public function betweenLength($str, $min_length, $max_length) {
		
		return ($this->minLength($str, $min_length) && $this->maxLength($str, $max_length));
		
	}
	
	public function equal($value, $equal_value) {
		
		return ($value === $equal_value);
		
	}
	
	public function minValue($value, $min_value) {
		
		return ($this->numeric($value) && $value >= $min_value);
		
	}
	
	public function maxValue($value, $max_value) {

		return ($this->numeric($value) && $value <= $max_value);
		
	}
	
	public function betweenValue($value, $min_value, $max_value) {
		
		return ($this->numeric($value) 
					&& $this->minValue($value, $min_value)
					&& $this->maxValue($value, $max_value));
		
	}
	
	public function notEmpty($str_or_array) {
		
		return (!empty($str_or_array));
		
	}
	
	public function alpha($str) {
		
		return (ctype_alpha($str));
		
	}
	
	public function alphaNumeric($str) {
		
		return (ctype_alnum($str));
		
	}
	
	public function numeric($value) {
		
		return (is_numeric($value));
		
	}
	
	public function email($email) {
		
		return (preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/", $email));
		
	}
	
	public function url($url) {
		
		return (preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $url));
		
	}
	
	public function date($year, $month, $day) {
		
		return (checkdate($month, $day, $year)
					&& $this->length($year, 4)
					&& $this->betweenValue($month, 1, 12)
					&& $this->betweenValue($day, 1, 31));
		
	}
	
	public function validate() {
		
		$this->_error_messages = array();
		$this->_error_count = 0;
		
		foreach ($this->_rules as $value_key => $rule_values) {
			
			foreach ($rule_values as $rule_name => $rule_params) {
				
				$error_message = $rule_params[count($rule_params)-1];
				array_pop($rule_params);
				array_unshift($rule_params, $this->_check_values[$value_key]);
				
				if(!method_exists($this, $rule_name)) {
					
					if(!call_user_func_array($this->_custom_functions[$rule_name], $rule_params)) {
						
						$this->setErrorMessage($value_key, $rule_name, $error_message);
						$this->_error_count++;
						
					}
					
				} else if(!call_user_func_array(array($this, $rule_name), $rule_params)) {
					
					$this->setErrorMessage($value_key, $rule_name, $error_message);
					$this->_error_count++;
					
				}
				
			}
			
		}
		
		return ($this->_error_count == 0);
		
	}
	
	private function setErrorMessage($value_key, $rule_name, $error_message) {
		
		if($this->_error_message_template != '') {
			
			$error_message = str_replace(self::ERROR_MESSAGE_REPLACE, $error_message, $this->_error_message_template);
			
		}
		
		$this->_error_messages[$value_key][$rule_name] = $error_message;
		
	}
	
	public function getErrorMessages() {
		
		return $this->_error_messages;
		
	}
	
	public function getErrorMessage($value_key, $rule_name) {
		
		if(isset($this->_error_messages[$value_key][$rule_name])) {
			
			return $this->_error_messages[$value_key][$rule_name];
			
		}
		
		return '';
		
	}
	
	public function getErrorCount() {
		
		return $this->_error_count;
		
	}
	
	public function getRuleNames() {
		
		return array(
				
			'length', 
			'minLength', 
			'maxLength', 
			'betweenLength', 
			'equal', 
			'minValue', 
			'maxValue', 
			'betweenValue', 
			'notEmpty', 
			'alpha', 
			'alphaNumeric', 
			'numeric', 
			'email', 
			'url', 
			'date'
				
		);		
		
	}
	
}
/*** Example

	require 'simple_validation.php';
	
	$sv = new Simple_Validation();	// or $sv = new Simple_Validation('utf-8');
	
	$sv->setEncoding('utf-8');									// Skippable
	$sv->setErrorMessageTag('p', 'class="p_error"');			// Skippable
	$sv->setCustomRule('xxx', function($str, $arg_1, $arg_2){	// Skippable
	
		return true;
	
	});
	
	$sv->setCheckValues(array(
			
			'name' => 'my name', 
			'email_address' => 'test@example.com', 
			'password' => '1234567890', 
			'custom' => 'xxx'
			
	));
	$sv->setRules(array(
			
			'name' => array(
			
				'notEmpty' => array('Error message 1'), 
				'maxLength' => array(7, 'Error message 2')
			
			), 
			'email_address' => array(
			
				'email' => array('Error message 3')
			
			), 
			'password' => array(
			
				'betweenLength' => array(5, 10, 'Error message 4')
			
			), 
			'custom' => array(
			
				'xxx' => array('arg_1', 'arg_2', 'Error message 5')
			
			)
			
	));
	
	if(!$sv->validate()) {
		
		print_r($sv->getErrorMessages());
		echo $sv->getErrorMessage('email_address', 'email');
		echo $sv->getErrorCount();
		
	}

// Rules

length
minLength
maxLength
betweenLength
equal
minValue
maxValue
betweenValue
notEmpty
alpha
alphaNumeric
numeric
email
url
date

***/
