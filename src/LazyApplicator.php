<?php

require_once dirname(__FILE__) . '/FunctionIterator.php';

/**
 * An identitiy function is a function that returns the value passed
 * into it. Ideally, the identity function represent the initial
 * function in a fold, to be composed with other functions.
 */
class IdentityFunction {
	/**
	 * Implementation of the function
	 *
	 * @param mixed $item
	 * @return mixed $item
	 */
	public function __invoke($item, $extra = null) {
		return $item;
	}
}

interface Monadic {
	public function filter($callback);
	public function map($callback);
}

/**
 * The LazyApplicator lazily applies it's flatttened functions in an orderly
 * fashion. The LazyApplicator is an immutable data structure.
 *
 * The lazy application can be iterated over supply the correct order
 * of function applications.
 */
class LazyApplicator implements IteratorAggregate, Monadic {
	private $position = 0;

	private $filters;
	private $transforms;
	private $memoized;

	/**
	 * Supplies programmer the ability to iterate through functions.
	 *
	 * @return ArrayIterator $functions
	 */
	public function getIterator() {
		return new ArrayIterator($this->getFunctions());
	}

	/**
	 * Creates a new LazyApplicator to facilitate method chaining
	 *
	 * @return LazyApplicator
	 */
	public static function one() {
		return new LazyApplicator();
	}

	/**
	 * Creates a new LazyApplicator with supplied filters and transforms
	 *
	 * @return LazyApplicator
	 */
	public function __construct($filters = array(), $transforms = array()) {
		$this->filters = $filters;
		$this->transforms = $transforms;
		$this->position = count($filters) + count($transforms);

		$this->memoized = $this->partial();
	}

	/**
	 * Gets the internal functions flattened in order in tagged in tuple
	 * notation, it correct type.
	 *
	 * @return array callables
	 */
	public function getFunctions() {
		if (empty($this->functions)) {
			$functions = array();
			foreach (range(0, $this->position) as $index) {
				if (isset($this->filters[$index])) {
					$functions[$index] = array('filter', $this->filters[$index]);
				}

				if (isset($this->transforms[$index])) {
					$functions[$index] = array('transform', $this->transforms[$index]);
				}
			}

			$this->functions = $functions;
		}

		return $this->functions;
	}

	/**
	 * Returns a length of the internal function count in constant time.
	 *
	 * @param int number of flattened functions
	 */
	public function getFunctionCount() {
		return $this->position;
	}

	/**
	 * Invokes the LazyApplicator with its filters and transforms
	 *
	 * @param mixed $item; Item to evaluate
	 * @param mixed $extra; An extra param to be used upond evaluation
	 * @return mixed transformed item or null
	 */
	public function __invoke($item, $extra = null) {
		$partial = $this->memoized;
		return $partial($item, $extra);
	}

	/**
	 * Returns function application iterator which allows step
	 * by step analysis of function applications
	 *
	 * @param mixed $item
	 * @param mixed $extra
	 * @return FunctionIterator
	 */
	public function getFunctionIterator($item, $extra = null) {
		return new FunctionIterator($this->getFunctions(), $item, $extra);
	}

	/**
	 * Returns a partially applied invokation optionally returning
	 * a function at the desired level. If a break is specified,
	 * then you can optionally obtain a continuation, which allows an
	 * in-depth introspection of function applications.
	 *
	 * @param $breakAt optionally break this partial at a certain depth
	 * @return callable
	 */
	public function partial($breakAt = -1, $continuation = false) {
		$result = new IdentityFunction();
		$passing = array();

		foreach ($this as $index => $value) {
			$useContinues = ($index > $breakAt and $continuation);

			if ($useContinues) {
				$passing[] = $value;
				continue;
			}

			list($type, $function) = $value;

			switch ($type) {
			case "filter":
				$result = function($item, $extra=null) use ($result, $function) {
					$passed = $result($item, $extra);
					if (empty($passed)) return null;
					if ($function($passed, $extra)) {
						return $passed;
					}
					return null;
				};
				break;
			default:
				$result = function($item, $extra=null) use ($result, $function) {
					$passed = $result($item, $extra);
					if (empty($passed)) return null;
					return $function($passed, $extra);
				};
			}

			if ($index === $breakAt) {
				if ($continuation) {
					$passing[] = array($type, $result);
				} else {
					break;
				}
			}
		}

		if ($continuation) {
			return function($item, $extra = null) use ($passing) {
				return new FunctionIterator($passing, $item, $extra);
			};
		}

		return $result;
	}

	/**
	 * Returns the filter functions used in this applicator
	 *
	 * @return array callable
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * Returns the transform function used in this applicator
	 *
	 * @return array callable
	 */
	public function getTransforms() {
		return $this->transforms;
	}

	/**
	 * Composes all of the transform functions into a single function
	 *
	 * @return callable
	 */
	public function getComposedTransform() {
		$composed = function($in, $transform) {
			return function($item, $extra = null) use ($in, $transform) {
				return $transform($in($item, $extra));
			};
		};

		return array_reduce($this->transforms, $composed, new IdentityFunction());
	}

	/**
	 * Tests that this input passes
	 *
	 * @param mixed $item
	 * @param mixed $extra; Extra argument to validate against
	 */
	public function isValid($item, $extra = null) {
		$value = $this($item, $extra);
		return !empty($value);
	}

	/**
	 * Add a new filter method to the mix
	 *
	 * @param callable $callback
	 * @return LazyApplicator
	 */
	public function filter($callback) {
		$filters = $this->filters;
		$filters[$this->position] = function ($item, $index) use ($callback) {
			return $callback($item, $index);
		};

		return new LazyApplicator($filters, $this->transforms);
	}

	/**
	 * Add a new transform method to the mix
	 *
	 * @param callable $callback
	 * @return LazyApplicator
	 */
	public function map($callback) {
		$transforms = $this->transforms;
		$transforms[$this->position] = function ($item, $index) use ($callback) {
			return $callback($item, $index);
		};

		return new LazyApplicator($this->filters, $transforms);
	}
}

