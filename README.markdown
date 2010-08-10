# Paradox Database PHP Library

A PHP library for SQL-like access to a Paradox database file.

## Requirements

- PHP >= 5.0 <http://php.net/>
- pxlib >= 0.4.4 <http://pxlib.sourceforge.net/>
- Paradox PECL Extention <http://pecl.php.net/package/paradox>

## Usage

	<?php
		
		$pdox = new Paradox_Database();
		
		$pdox->open('database.db');
		
		$pdox
			->select('field1, field2, field3')
			->where('field1', '>', '6')
			->limit(5);
		
		$results = $pdox->get();
		
	?>