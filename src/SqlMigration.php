<?php

declare(strict_types = 1);

namespace mepihindeveloper\components;

use DateTime;
use mepihindeveloper\components\exceptions\SqlMigrationException;
use mepihindeveloper\components\interfaces\{DatabaseInterface, SqlMigrationInterface};
use PDOException;
use RuntimeException;

/**
 * Class SqlMigration
 *
 * Класс предназначен для выполнения работ по SQL миграциям.
 *
 * @package mepihindeveloper\components
 */
class SqlMigration implements SqlMigrationInterface {
	
	/**
	 * Константы для определения типа миграции
	 */
	public const UP = 'up';
	public const DOWN = 'down';
	/**
	 * @var array Массив настроек
	 */
	protected array $settings;
	/**
	 * @var null|DatabaseInterface Компонент работы с базой данных
	 */
	protected ?DatabaseInterface $database;
	
	/**
	 * SqlMigration constructor.
	 *
	 * @param DatabaseInterface $database Компонент работы с базой данных
	 * @param array $settings Массив настроек
	 *
	 * @throws SqlMigrationException
	 */
	public function __construct(DatabaseInterface $database, array $settings) {
		$this->database = $database;
		$this->settings = $settings;
		
		foreach (['schema', 'table', 'path'] as $settingsKey) {
			if (!array_key_exists($settingsKey, $settings)) {
				throw new SqlMigrationException("Отсутствуют {$settingsKey} настроек.");
			}
		}
	}
	
	/**
	 * Создает схему и таблицу в случае их отсутствия
	 *
	 * @return bool Возвращает true, если схема и таблица миграции была создана успешно. В остальных случаях выкидывает
	 * исключение
	 *
	 * @throws SqlMigrationException
	 */
	public function initSchemaAndTable(): bool {
		$schemaSql = <<<SQL
			CREATE SCHEMA IF NOT EXISTS {$this->settings['schema']};
		SQL;
		
		if (!$this->database->execute($schemaSql)) {
			throw new SqlMigrationException('Ошибка создания схемы миграции');
		}
		
		$tableSql = <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->settings['schema']}.{$this->settings['table']} (
				"name" varchar(180) COLLATE "default" NOT NULL,
				apply_time int4,
				CONSTRAINT {$this->settings['table']}_pk PRIMARY KEY ("name")
			) WITH (OIDS=FALSE)
		SQL;
		
		if (!$this->database->execute($tableSql)) {
			throw new SqlMigrationException('Ошибка создания таблицы миграции');
		}
		
		return true;
	}
	
	public function __destruct() {
		$this->database->closeConnection();
		$this->database = null;
	}
	
	/**
	 * @inheritDoc
	 */
	public function up(int $count = 0): array {
		$executeList = $this->getNotAppliedList();
		
		if (empty($executeList)) {
			return [];
		}
		
		$executeListCount = count($executeList);
		$executeCount = $count === 0 ? $executeListCount : min($count, $executeListCount);
		
		return $this->execute($executeList, $executeCount, self::UP);
	}
	
	/**
	 * Возвращает список не примененных миграций
	 *
	 * @return array
	 */
	protected function getNotAppliedList(): array {
		$historyList = $this->getHistoryList();
		$historyMap = [];
		
		foreach ($historyList as $item) {
			$historyMap[$item['name']] = true;
		}
		
		$notApplied = [];
		$directoryList = glob("{$this->settings['path']}/m*_*_*");
		
		foreach ($directoryList as $directory) {
			if (!is_dir($directory)) {
				continue;
			}
			
			$directoryParts = explode('/', $directory);
			preg_match('/^(m(\d{8}_?\d{6})\D.*?)$/is', end($directoryParts), $matches);
			$migrationName = $matches[1];
			
			if (!isset($historyMap[$migrationName])) {
				$migrationDateTime = DateTime::createFromFormat('Ymd_His', $matches[2])->format('Y-m-d H:i:s');
				$notApplied[] = [
					'path' => $directory,
					'name' => $migrationName,
					'date_time' => $migrationDateTime
				];
			}
		}
		
		ksort($notApplied);
		
		return $notApplied;
	}
	
	/**
	 * Возвращает список примененных миграций
	 *
	 * @param int $limit Ограничение длины списка (null - полный список)
	 *
	 * @return array
	 */
	protected function getHistoryList(int $limit = 0): array {
		$limitSql = $limit === 0 ? '' : "LIMIT {$limit}";
		$historySql = <<<SQL
			SELECT "name", apply_time
			FROM {$this->settings['schema']}.{$this->settings['table']}
			ORDER BY apply_time DESC, "name" DESC {$limitSql}
		SQL;
		
		return $this->database->queryAll($historySql);
	}
	
	/**
	 * Выполняет миграции
	 *
	 * @param array $list Массив миграций
	 * @param int $count Количество миграций для применения
	 * @param string $type Тип миграции (up/down)
	 *
	 * @return array Список выполненных миграций
	 *
	 * @throws RuntimeException
	 */
	protected function execute(array $list, int $count, string $type): array {
		$migrationInfo = [];
		
		for ($index = 0; $index < $count; $index++) {
			$migration = $list[$index];
			$migration['path'] = array_key_exists('path', $migration) ? $migration['path'] :
				"{$this->settings['path']}/{$migration['name']}";
			$migrationContent = file_get_contents("{$migration['path']}/{$type}.sql");
			
			if ($migrationContent === false) {
				throw new RuntimeException('Ошибка поиска/чтения миграции');
			}
			
			try {
				if (!empty($migrationContent)) {
					$this->database->beginTransaction();
					$this->database->execute($migrationContent);
					$this->database->commit();
				}
				
				if ($type === self::UP) {
					$this->addHistory($migration['name']);
				} else {
					$this->removeHistory($migration['name']);
				}
				
				$migrationInfo['success'][] = $migration;
			} catch (SqlMigrationException | PDOException $exception) {
				$migrationInfo['error'][] = array_merge($migration, ['errorMessage' => $exception->getMessage()]);
				
				break;
			}
		}
		
		return $migrationInfo;
	}
	
	/**
	 * Добавляет запись в таблицу миграций
	 *
	 * @param string $name Наименование миграции
	 *
	 * @return bool Возвращает true, если миграция была успешно применена (добавлена в таблицу миграций).
	 * В остальных случаях выкидывает исключение.
	 *
	 * @throws SqlMigrationException
	 */
	protected function addHistory(string $name): bool {
		$sql = <<<SQL
			INSERT INTO {$this->settings['schema']}.{$this->settings['table']} ("name", apply_time) VALUES(:name, :apply_time);
		SQL;
		
		if (!$this->database->execute($sql, ['name' => $name, 'apply_time' => time()])) {
			throw new SqlMigrationException("Ошибка применения миграция {$name}");
		}
		
		return true;
	}
	
	/**
	 * Удаляет миграцию из таблицы миграций
	 *
	 * @param string $name Наименование миграции
	 *
	 * @return bool Возвращает true, если миграция была успешно отменена (удалена из таблицы миграций).
	 * В остальных случаях выкидывает исключение.
	 *
	 * @throws SqlMigrationException
	 */
	protected function removeHistory(string $name): bool {
		$sql = <<<SQL
			DELETE FROM {$this->settings['schema']}.{$this->settings['table']} WHERE "name" = :name;
		SQL;
		
		if (!$this->database->execute($sql, ['name' => $name])) {
			throw new SqlMigrationException("Ошибка отмены миграции {$name}");
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function down(int $count = 0): array {
		$executeList = $this->getHistoryList();
		
		if (empty($executeList)) {
			return [];
		}
		
		$executeListCount = count($executeList);
		$executeCount = $count === 0 ? $executeListCount : min($count, $executeListCount);
		
		return $this->execute($executeList, $executeCount, self::DOWN);
	}
	
	/**
	 * @inheritDoc
	 */
	public function history(int $limit = 0): array {
		$historyList = $this->getHistoryList($limit);
		
		if (empty($historyList)) {
			return ['История миграций пуста'];
		}
		
		$messages = [];
		
		foreach ($historyList as $historyRow) {
			$messages[] = "Миграция {$historyRow['name']} от " . date('Y-m-d H:i:s', $historyRow['apply_time']);
		}
		
		return $messages;
	}
	
	/**
	 * @inheritDoc
	 *
	 * @throws RuntimeException|SqlMigrationException
	 */
	public function create(string $name): bool {
		$this->validateName($name);
		
		$migrationMame = $this->generateName($name);
		$path = "{$this->settings['path']}/{$migrationMame}";
		
		if (!mkdir($path, 0775, true) && !is_dir($path)) {
			throw new RuntimeException("Ошибка создания директории. Директория {$path}не была создана");
		}
		
		if (file_put_contents($path . '/up.sql', '') === false) {
			throw new RuntimeException("Ошибка создания файла миграции {$path}/up.sql");
		}
		
		if (!file_put_contents($path . '/down.sql', '') === false) {
			throw new RuntimeException("Ошибка создания файла миграции {$path}/down.sql");
		}
		
		return true;
	}
	
	/**
	 * Проверяет имя миграции на корректность
	 *
	 * @param string $name Название миграции
	 *
	 * @throws SqlMigrationException
	 */
	protected function validateName(string $name): void {
		if (!preg_match('/^[\w]+$/', $name)) {
			throw new SqlMigrationException('Имя миграции должно содержать только буквы, цифры и символы подчеркивания.');
		}
	}
	
	/**
	 * Создает имя миграции по шаблону: m{дата в формате Ymd_His}_name
	 *
	 * @param string $name Название миграции
	 *
	 * @return string
	 */
	protected function generateName(string $name): string {
		return 'm' . gmdate('Ymd_His') . "_{$name}";
	}
}