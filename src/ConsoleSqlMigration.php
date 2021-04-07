<?php

declare(strict_types = 1);

namespace mepihindeveloper\components;

use mepihindeveloper\components\exceptions\SqlMigrationException;
use mepihindeveloper\components\interfaces\DatabaseInterface;
use RuntimeException;

/**
 * Class ConsoleSqlMigration
 *
 * Класс предназначен для работы с SQL миграциями с выводом сообщений в консоль (терминал)
 *
 * @package mepihindeveloper\components
 */
class ConsoleSqlMigration extends SqlMigration {
	
	public function __construct(DatabaseInterface $database, array $settings) {
		parent::__construct($database, $settings);
		
		try {
			$this->initSchemaAndTable();
			
			Console::writeLine('Схема и таблица для миграции были успешно созданы', Console::FG_GREEN);
		} catch (SqlMigrationException $exception) {
			Console::writeLine($exception->getMessage(), Console::FG_RED);
			
			exit;
		}
	}
	
	public function up(int $count = 0): array {
		$migrations = parent::up($count);
		
		if (empty($migrations)) {
			Console::writeLine("Нет миграций для применения");
			
			exit;
		}
		
		foreach ($migrations['success'] as $successMigration) {
			Console::writeLine("Миграция {$successMigration['name']} успешно применена", Console::FG_GREEN);
		}
		
		if (array_key_exists('error', $migrations)) {
			foreach ($migrations['error'] as $errorMigration) {
				Console::writeLine("Ошибка применения миграции {$errorMigration['name']}", Console::FG_RED);
			}
			
			exit;
		}
		
		return $migrations;
	}
	
	public function down(int $count = 0): array {
		$migrations = parent::down($count);
		
		if (empty($migrations)) {
			Console::writeLine("Нет миграций для отмены");
			
			exit;
		}
		
		foreach ($migrations['success'] as $successMigration) {
			Console::writeLine("Миграция {$successMigration['name']} успешно отменена", Console::FG_GREEN);
		}
		
		if (array_key_exists('error', $migrations)) {
			foreach ($migrations['error'] as $errorMigration) {
				Console::writeLine("Ошибка отмены миграции {$errorMigration['name']}", Console::FG_RED);
			}
			
			exit;
		}
		
		return $migrations;
	}
	
	public function create(string $name): bool {
		try {
			parent::create($name);
			
			Console::writeLine("Миграция {$name} успешно создана");
		} catch (RuntimeException | SqlMigrationException $exception) {
			Console::writeLine($exception->getMessage(), Console::FG_RED);
			
			return false;
		}
		
		return true;
	}
	
	public function history(int $limit = 0): array {
		$historyList = parent::history($limit);
		
		foreach ($historyList as $historyRow) {
			Console::writeLine($historyRow);
		}
		
		return $historyList;
	}
}