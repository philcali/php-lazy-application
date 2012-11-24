<?php

abstract class DomTraversable implements RecursiveIterator {
	protected $position = 0;
	protected $level = 0;
	protected $node;

	abstract public function nextNode();

	abstract public function resetIterator();

	public function current() {
		return $this->node;
	}

	public function key() {
		return $this->position;
	}

	public function next() {
		$this->position++;
		$this->node = $this->nextNode();
	}

	public function rewind() {
		$this->position = 0;
		$this->resetIterator();
	}

	public function valid() {
		return !empty($this->node);
	}

	public function hasChildren() {
		return $this->node->hasChildNodes();
	}

	public function getChildren() {
		return new DomNodeListIterator($this->node->childNodes);
	}
}

class DomNodeIterator extends DomTraversable {
	private $parent;

	public function __construct(DomNode $node) {
		$this->parent = $node;
		$this->resetIterator();
	}

	public function nextNode() {
		return $this->node->nextSibling;
	}

	public function resetIterator() {
		$this->node = $this->parent;
	}
}

class DomNodeListIterator extends DomTraversable {
	private $nodes;

	public function __construct(DomNodeList $nodes) {
		$this->nodes = $nodes;
		$this->resetIterator();
	}

	public function nextNode() {
		return $this->nodes->item($this->position);
	}

	public function resetIterator() {
		$this->node = $this->nextNode();
	}
}
