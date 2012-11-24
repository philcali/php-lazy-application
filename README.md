# Lazy Application and Introductory CPS

This proof of concept bit, is an attempt to make a lazily
applied series of filter and transform functions flattened
on each applicable item in a PHP Traversable.

This powerful iterator `LazyApplicationIterator`, composes
the underlying functions, and applies the functions in an
orderly fashion. For example:

```
<?php

require_once dirname(__FILE__) . '/LazyApplicationIterator.php';

$arr = range(0, 10);

$lazily = LazyApplicationIterator::on(new ArrayIterator($arr))
	->filter(function($number) { return $number % 2 === 0; })
	->map(function($number) { return $number * $number; })
	->filter(function($number) { return $number > 50; })
	->map(function($number, $index) {
		return "{$index} Tell me about it: {$number}";
	});

foreach ($lazily as $key => $value) {
	echo "$value\n";
}

/**
 * Outputs:
8 Tell me about it: 64
10 Tell me about it: 100
*/

echo $lazily->reduce(function($in, $value) {
	return $in . "$value\n";
});

/**
 * Outputs:
8 Tell me about it: 64
10 Tell me about it: 100
*/
```


