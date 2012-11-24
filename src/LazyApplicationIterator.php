<?php

require_once dirname(__FILE__) . '/LazyApplicator.php';

/**
 * This iterator is used to facilitate filter and transform stacking
 * on a single iteration. The application is handled through it's
 * LazyApplicator.
 *
 * The LazyApplicationIterator implements the Monadic interface
 * and supports method chaining through it's filter and map methods.
 *
 * The LazyApplicationIterator is an immutable datastructure.
 */
class LazyApplicationIterator implements Iterator, Monadic {
	private $evaled;

	private $iterator;
	private $applicator;

	/**
	 * Creates a new LazyApplicationIterator with an optional applicator
	 *
	 * @param Traversable $iterator
	 * @param LazyApplicator $applicator
	 * @return LazyApplicationIterator
	 */
	public function __construct(Traversable $iterator, LazyApplicator $applicator = null) {
		$this->iterator = $iterator;
		$this->applicator = $applicator ?: new LazyApplicator();
	}

	/**
	 * Factory method to facilitate method chaining
	 *
	 * @param Traversable $iterator
	 * @return LazyApplicationIterator
	 */
	public static function on(Traversable $iterator) {
		return new LazyApplicationIterator($iterator);
	}

	/**
	 * Returns the LazyApplicator used for iteratees
	 *
	 * @return LazyApplicator
	 */
	public function getApplicator() {
		return $this->applicator;
	}

	/**
	 * @see parent
	 *
	 * @param callable $callback
	 * @return LazyApplicationIterator
	 */
	public function filter($callback) {
		$applicator = $this->applicator->filter($callback);
		return new LazyApplicationIterator($this->iterator, $applicator);
	}

	/**
	 * @see parent
	 *
	 * @param callable $callback
	 * @return LazyApplicationIterator
	 */
	public function map($callback) {
		$applicator = $this->applicator->map($callback);
		return new LazyApplicationIterator($this->iterator, $applicator);
	}

	/**
	 * @see parent
	 * @return mixed LazyApplicator transformed value
	 */
	public function current() {
		if ($this->evaled) {
			return $this->evaled;
		} else {
			$applicator = $this->applicator;
			return $applicator($this->iterator->current(), $this->iterator->key());
		}
	}

	/**
	 * @see parent
	 * @return scalar Underlying $iterator->key()
	 */
	public function key() {
		return $this->iterator->key();
	}

	/**
	 * @see parent
	 */
	public function next() {
		$this->iterator->next();
	}

	/**
	 * @see parent
	 */
	public function rewind() {
		unset($this->evaled);
		$this->iterator->rewind();
	}

	/**
	 * @see parent
	 * @return boolean
	 */
	public function valid() {
		$valid = $this->iterator->valid();
		$this->evaled = ($valid and empty($this->evaled)) ? $this->current() : null;

		while ($valid and is_null($this->evaled)) {
			$this->iterator->next();

			$valid = $this->iterator->valid();
			if ($valid) {
				$this->evaled = $this->current();
			}
		}

		return $valid;
	}

	/**
	 * Convenient method to reduce the iterator to a single value
	 *
	 * @param callable $callback Function used to fold the values
	 * @param mixed $initial The first value in the fold.
	 */
	public function reduce($callback, $initial = null) {
		$result = $initial;
		foreach ($this as $key => $value) {
			$result = $callback($result, $value, $key);
		}
		return $result;
	}

	/**
	 * Pumps this iterator into an array collection
	 *
	 * @return array LazyApplicator values
	 */
	public function toArray() {
		return iterator_to_array($this);
	}
}

