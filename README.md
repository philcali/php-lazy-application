# Lazy Application and Introductory CPS

This proof of concept bit, is an attempt to make a lazily
applied series of filter and transform functions flattened
on each applicable item in a PHP Traversable.

## Lazy Application on Traversables

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

The above example was rather trivial. In the `test` folder, there's
an example of traversing an entire DomDocument recursively, filtering
and transforming the elements without having to bother with
intermediate collections.

```
<?php

$document = new DomDocument();
@$document->loadHTMLFile(dirname(__FILE__) . '/test.html');

$nodes = new RecursiveIteratorIterator(new DomNodeIterator($document), 1);

$lazily = LazyApplicationIterator::on($nodes)
	->filter(function($element) { return $element->nodeName == 'a'; })
	->map(function($element) { return $element->attributes->getNamedItem('href'); })
	->map(function($attr) { return parse_url($attr->nodeValue); })
	->filter(function($parts) { return !empty($parts['scheme']) && !empty($parts['host']); })
	->map(function($parts) { return $parts['scheme'] . '://' . $parts['host'] . $parts['path'];});
```

## Function Iterative Application

In the first example, it's possible to yank a portion of the internal functions
into a partial, yet continue the processing for later.

```
<?php

$startContinuation = $lazily->getApplicator()->partial(1, true);

$continuation = $startContinuation(8);

while ($continuation->valid()) {
	echo $continuation->current();
	$continuation->next();
}

echo $continuation->getOriginal();

// 64
// true
//  Tell me about it: 64
// 8
```

In the above example, `$continuation` is a `FunctionIterator`, who can
incrementally apply functions on the number 8. It is very important to note
here, that function application is in full control of the programmer. Simply
calling `apply` on the `FunctionIterator` will complete the remainder of
applications without having to hand-write the continuing logic.
