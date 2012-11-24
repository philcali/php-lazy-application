<?php

require_once dirname(__FILE__) . '/../src/LazyApplicationIterator.php';
require_once dirname(__FILE__) . '/DomTraversable.php';

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

echo $lazily->reduce(function($in, $value) {
	return $in . "$value\n";
});

$continuation = $lazily->getApplicator()->partial(1, true);
echo $continuation(8, 8)->apply() . "\n";

$document = new DomDocument();
@$document->loadHTMLFile(dirname(__FILE__) . '/test.html');

$nodes = new RecursiveIteratorIterator(new DomNodeIterator($document), 1);

$starttime = microtime(true);

// The old school way
$text = "\nFound the following URLs:\n";
foreach ($nodes as $element) {
	if ($element->nodeName !== 'a') {
		continue;
	}

	$attr = $element->attributes->getNamedItem('href');
	if (empty($attr)) {
		continue;
	}

	$parts = parse_url($attr->nodeValue);
	if (empty($parts['scheme']) || empty($parts['host'])) {
		continue;
	}

	$link = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
	$text .= "$link\n";
}

$total = microtime(true) - $starttime;

echo "$text\nTook: $total\n\n";

// The flattened Map / reduce way
$lazily = LazyApplicationIterator::on($nodes)
	->filter(function($element) { return $element->nodeName == 'a'; })
	->map(function($element) { return $element->attributes->getNamedItem('href'); })
	->map(function($attr) { return parse_url($attr->nodeValue); })
	->filter(function($parts) { return !empty($parts['scheme']) && !empty($parts['host']); })
	->map(function($parts) { return $parts['scheme'] . '://' . $parts['host'] . $parts['path'];});

$starttime = microtime(true);

$urls = $lazily->reduce(function($in, $text) {
		return $in . "{$text}\n";
	}, "Found the following URLs:\n");

$total = microtime(true) - $starttime;

echo "$urls\nTook: $total";
