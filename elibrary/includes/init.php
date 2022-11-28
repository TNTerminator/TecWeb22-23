<?php
/**
 * init.php
 * 
 * This script defines the general parameters and behaviours of the project.
 * It includes also some utility function.
 * 
 */





/* *****
 * GLOBAL FUNCTIONS 
 * */

/**
 * This function extracts a substring from a parent string, using two marker strings for the start and the end of the substring.
 * If some marker is empty, the beginning or the end of string will be used.
 * If both markers are empty, $source will be returned.
 * 
 * @param $source source string
 * @param $start_delimiter starter marker string
 * @param $end_delimiter final marker string
 *
 * @return string The substring included between $start_delimiter and $end_delimiter.
 */
function substr_with_delimiters($source, $start_delimiter, $end_delimiter)
{
	$start_pos = strpos($source, $start_delimiter);
	if($start_pos === false)
		$start_pos = 0;
	else
		$start_pos += strlen($start_delimiter);
	$end_pos = strpos($string, $end_delimiter);
	if($end_pos === false)
		return substr($source, $start_pos);
	else
		return substr($source, $start_pos, $end_pos - $start_pos);
}


function cleanInput($value, $clearHtml = true)
{
	$value = trim($value);
	if($clearHtml)
		$value = strip_tags($value);
	$value = htmlentities($value);
	return $value;
}


