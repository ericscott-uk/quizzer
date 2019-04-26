<?php

declare(strict_types = 1);

namespace Core;

use \PDO;
use App\Config;

/**
 * Model class
 *
 * PHP version 7.0
 */
abstract class Model {
	protected static function getDB() {
		static $db = null;

		if($db === null) {
      $db = new DB(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);
		}

		return $db;
	}

	/**
	 * Checks if a column in a table is unique or not.
	 *
	 * @param string $table  - the table name to check
	 * @param string $column - the column name to check
	 * @param string $val    - the value to check against
	 *
	 * @throws \Exception
	 *
	 * @return boolean - true if it's unique, false if not
	 */
	public static function isUnique(string $table, string $column, string $val) : bool {
		$sql = "
			SELECT
				*
			FROM
				$table
			WHERE
				$column = :val;
		";

		$db   = static::getDB();
		$stmt = $db->prepare($sql);

		$stmt->bindValue(':val', $val, PDO::PARAM_STR);
		$stmt->execute();

		return Utilities::isEmpty($stmt->fetch()) ? true : false;
	}
}
