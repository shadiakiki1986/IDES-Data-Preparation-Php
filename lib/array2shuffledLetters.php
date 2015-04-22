<?php

function array2shuffledLetters($di,$exceptFields=array()) {
# di: 2D array
# exceptFields: array of strings of field names that should not be shuffled
#
# Example: var_dump(array2shuffledLetters(array(array('bla'=>'bla','bli'=>'bli'))));

	if(!is_array($di)) throw new Exception("Only arrays of arrays supported");
	array_map(function($x) { if(!is_array($x)) throw new Exception("Only arrays of arrays supported"); }, $di);

	return array_map(function($x) use($exceptFields) {
		$k=array_keys($x);
		$k=array_diff($k,$exceptFields);
		foreach($k as $k2) {
			$x[$k2]=str_shuffle($x[$k2]);
		}
		return $x;
	}, $di);
}


