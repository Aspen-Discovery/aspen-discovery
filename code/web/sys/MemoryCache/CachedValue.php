<?php

class CachedValue extends DataObject {
	public $__table = 'cached_values';
	public $__primaryKey = 'cacheKey';
	public $cacheKey;
	public $valueType;
	public $value;
	public $expirationTime;
}