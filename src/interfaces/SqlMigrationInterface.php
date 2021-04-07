<?php

declare(strict_types = 1);

namespace mepihindeveloper\components\interfaces;

use mepihindeveloper\components\exceptions\SqlMigrationException;
use RuntimeException;

/**
 * Interface SqlMigrationInterface
 *
 * Декларирует методы обязательные для реализации компонента SqlMigration
 *
 * @package mepihindeveloper\components\interfaces
 */
interface SqlMigrationInterface {
	
	/**
	 * Применяет указанное количество миграций
	 *
	 * @param int $count Количество миграция (0 - относительно всех)
	 *
	 * @return array Возвращает список применения и ошибочных миграций. Список может иметь вид:
	 * 1. Случай, когда отсутствуют миграции для выполнения, то возвращается пустой массив
	 * 2. Когда присутствуют миграции для выполнения:
	 * [
	 *  'success' => [...],
	 *  'error' => [...]
	 * ]
	 * Ключ error добавляется только в случае ошибки выполнения миграции.
	 *
	 * @throws SqlMigrationException
	 */
	public function up(int $count = 0): array;
	
	/**
	 * Отменяет указанное количество миграций
	 *
	 * @param int $count Количество миграция (0 - относительно всех)
	 *
	 * @return array Возвращает список отменных и ошибочных миграций. Список может иметь вид:
	 * 1. Случай, когда отсутствуют миграции для выполнения, то возвращается пустой массив
	 * 2. Когда присутствуют миграции для выполнения:
	 * [
	 *  'success' => [...],
	 *  'error' => [...]
	 * ]
	 * Ключ error добавляется только в случае ошибки выполнения миграции.
	 *
	 * @throws SqlMigrationException
	 */
	public function down(int $count = 0): array;
	
	/**
	 * Возвращает список сообщений о примененных миграций
	 *
	 * @param int $limit Ограничение длины списка (null - полный список)
	 *
	 * @return array
	 */
	public function history(int $limit = 0): array;
	
	/**
	 * Создает новую миграцию и возвращает сообщение об успешном создании миграции
	 *
	 * @param string $name Название миграции
	 *
	 * @return bool Возвращает true, если миграция была успешно создана. В остальных случаях выкидывает исключение
	 *
	 * @throws RuntimeException|SqlMigrationException
	 */
	public function create(string $name): bool;
}