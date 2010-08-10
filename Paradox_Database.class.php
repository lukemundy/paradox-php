<?php
/**
 * Paradox Database Class
 *
 * Makes use of PHP's Paradox functions (via pxlib) to conveniently access data
 * in a Paradox database with SQL-style functions.
 * @package Paradox DB
 * @author Luke Mundy
 */
class Paradox_Database
{
	private $_px = NULL;
	private $_fp = NULL;
	
	private $_file = '';
	private $_mode = '';
	
	private $_select = array();
	private $_where = array();
	private $_limit = 0;
	private $_offset = 0;
	
	/**
	 * Constructor
	 *
	 * Set some default values
	 * @return void
	 */
	public function __construct()
	{
		// Create PHP's Paradox database object
		$this->_px = new paradox_db();
	}
	
	/**
	 * Destructor
	 *
	 * Close the database and file pointer
	 * @return void
	 */
	public function __destruct()
	{
		$this->_px->close();
		if ($this->_fp) fclose($this->_fp);
	}
	
	/**
	 * Select which fields to return
	 * @return void
	 */
	public function select()
	{
		$args = func_get_args();
		
		// Was an array of fields supplied?
		if (is_array($args[0]))
		{
			foreach ($args[0] as $field)
			{
				// Make sure it is a string and not already in the list
				if (is_string($field) && ! in_array($field, $this->_select)) $this->_select[] = $field;
			}
		}
		// Or a comma seperated list?
		elseif (is_string($args[0]))
		{
			$fields = explode(', ', $args[0]);
			
			foreach ($fields as $field)
			{
				$field = trim($field);
				
				// Make sure field isn't already in the list
				if ( ! in_array($field, $this->_select)) $this->_select[] = $field;
			}
		}
		
		// Return $this so we can do some cool method chaining
		return $this;
	}
	
	/**
	 * Filter returned rows by a certain criteria
	 * @return void
	 */
	public function where()
	{
		$args = func_get_args();
		
		if (is_array($args[0]))
		{
			foreach ($args[0] as $test)
			{
				$this->_where[] = array(
					'field' => $test[0],
					'operator' => $test[1],
					'value' => escapeshellarg($test[2])
				);
			}
		}
		elseif (is_string($args[0]))
		{
			$this->_where[] = array(
				'field' => $args[0],
				'operator' => $args[1],
				'value' => escapeshellarg($args[2])
			);
		}
		
		// Return $this so we can do some cool method chaining
		return $this;
	}
	
	/**
	 * Limit the amount of return results
	 * @return void
	 */
	public function limit()
	{
		if (func_num_args() == 1) $this->_limit = func_get_arg(0);
		else
		{
			$this->_offset = func_get_arg(0);
			$this->_limit = func_get_arg(1);
		}
		
		// Return $this so we can do some cool method chaining
		return $this;
	}
	
	/**
	 * Get records
	 * @return array Matched records
	 */
	public function get()
	{
		// Start with an empty array
		$ret = array();
		
		// Loop through all records in the database
		for ($x = 0; ($x < $this->num_records() && count($ret) < $this->_limit); $x++)
		{			
			$row = $this->_px->retrieve_record($x);
			
			if ($this->_test($row))
			{
				foreach ($row as $key => $val)
				{
					// Find all fields not in the select array
					if ( ! in_array($key, $this->_select) && ! empty($this->_select)) unset($row[$key]);
				}
				
				$ret[] = $row;
			}
		}
		
		return $ret;
	}
	
	/**
	* Open the database
	* @param string $file Path to the database file
	* @param string $mode File open mode
	* @return bool TRUE on success, FALSE otherwise
	*/
	public function open($file, $mode = 'r')
	{
		$this->_file = $file;
		$this->_mode = $mode;
		
		return $this->_open();
	}
	
	/**
	* Tests the supplied row
	* @param array $row
	* @return bool TRUE is row passes all tests
	*/
	private function _test($row)
	{
		$pass = TRUE;
		
		// If there are no tests, all rows will pass
		if ( ! empty($this->_where))
		{
			foreach ($this->_where as $test)
			{
				$field = escapeshellarg($row[$test['field']]);			
			
				$txt = "return ({$field} {$test['operator']} {$test['value']});";
				
				// Check for failure
				if ( ! eval($txt))
				{
					$pass = FALSE;
					
					// No need to try other tests
					break;
				}
			}
		}
		
		return $pass;
	}
	
	/**
	* Private open function
	* @return bool TRUE on success, FALSE otherwise
	*/
	public function _open()
	{
		$ret = FALSE;
		
		// Check file exists
		if (file_exists($this->_file))
		{
			$this->_fp = fopen($this->_file, $this->_mode);
			
			// Opened successfully?
			if ($this->_fp)
			{
				// Database opened successfully?
				if ($this->_px->open_fp($this->_fp))
				{
					$ret = TRUE;
				}
			}
		}
		
		return $ret;
	}
	
	
	/** ------------------------------------------------------------------------
	 * Accessor Functions
	 */
	
	public function get_file_pointer() { return $this->_fp; }
	public function get_paradox_object() { return $this->_px; }
	public function num_records() { return $this->_px->numrecords(); }
	public function num_fields() { return $this->_px->numfields(); }
	
	public function debug()
	{
		echo 'SELECT '. print_r($this->_select, TRUE) ."\n";
		echo 'WHERE '. print_r($this->_where, TRUE) ."\n";
	}
}

// END - class Paradox_Database