<?php

/**
 * A FunctionIterator allows for step by step function application
 * and introspection. Ideally, this definition would be inside the
 * LazyApplicator, but this unfortunate limitation of PHP yields
 * quite a coupling to the parent class.
 */
class FunctionIterator implements Iterator {
	private $position = 0;
	private $functions;

	private $original;
	private $memoized;

	/**
	 * Creates an iterator
	 *
	 * @param array $functions
	 * @param mixed $item
	 * @param mixed $extra
	 */
	public function __construct(array $functions, $item, $extra = null) {
		$this->functions = $functions;
		$this->original = $item;
		$this->memoized = $this->original;
		$this->extra = $extra;
	}

	/**
	 * Returns the original $item before any application
	 *
	 * @return mixed $original
	 */
	public function getOriginal() {
		return $this->original;
	}

	/**
	 * Returns the fully applied function sequence on the original $item
	 *
	 * @param int $breakAt; Allows the sequence to be halted
	 * @return mixed $memoized; The internal applied $item
	 */
	public function apply($breakAt = -1) {
		foreach ($this as $index => $value) {
			if ($index === $breakAt) {
				break;
			}
		}
		return $this->memoized;
	}

	/**
	 * @see parent
	 * @return boolean
	 */
	public function valid() {
		do {
			$valid = isset($this->functions[$this->position]);
			if ($valid) {
				list($type, $function) = $this->functions[$this->position];

				if ($type == 'filter') {
					$valid = $function($this->memoized, $this->extra);
					$this->next();
					if (empty($valid)) {
						unset($this->memoized);
					}
				} else {
					break;
				}
			}
		} while ($valid and isset($this->functions[$this->position]));

		return $valid;
	}

	/**
	 * @see parent
	 */
	public function next() {
		$this->position++;
	}

	/**
	 * @see parent
	 */
	public function rewind() {
		$this->position = 0;
		$this->memoized = $this->original;
	}

	/**
	 * @see parent
	 * @return int; Current function index
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * @see parent
	 * @return mixed; The memoized return object
	 */
	public function current() {
		list($type, $function) = $this->functions[$this->position];

		$value = $function($this->memoized, $this->extra);
		if ($type !== 'filter') {
			$this->memoized = $value;
		}

		return $value;
	}

	/**
	 * Creates a new LazyApplicator based on the function series
	 *
	 * @return LazyApplicator
	 */
	public function toLazyApplicator() {
		$filters = array();
		$transforms = array();

		foreach ($this->functions as $index => $value) {
			list($type, $function) = $value;
			${$type . 's'}[$index] = $function;
		}

		return new LazyApplicator($filters, $transforms);
	}
}

