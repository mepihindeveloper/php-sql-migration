<?php

declare(strict_types = 1);

namespace mepihindeveloper\components\exceptions;

use Exception;
use Throwable;

/**
 * Class SqlMigrationException
 *
 * Исключение при работе с SqlMigration
 *
 * @package mepihindeveloper\components\exceptions
 */
class SqlMigrationException extends Exception {
	
	/**
	 * @inheritDoc
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}