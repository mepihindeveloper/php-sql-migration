# php-sql-migration

Компонент для работы с SQL миграциями.

В компоненте реализован основной класс `SqlMigration`, который выполняет базовые операции по работе с миграциями.
Большинство методов возвращает наборы данных или логические значения. Данный подход позволит написать практически любую
обертку для собственных нужд.

Как пример реализации обертки был реализован класс `ConsoleSqlMigration`, которые наследуется от `SqlMigration` и
переопределяет его методы. Переопределение первоначально вызывает `parent::` после чего реализует дополнительную логику
в выводе сообщений в консоль (терминал).

Для реализации компонента необходимо передать класс реализующий интерфейс `DatabaseInterface` и массив настроек.
Обязательными параметрами в настройках являются:

- **schema** - схема в базе данных для миграций
- **table** - таблица в базе данных для миграций
- **path** - путь в файловой структуре для папки с миграциями

Компонент самостоятельно проверяет и создает необходимые (указанные) схемы, таблицы и папки при наличии заранее
определенных разрешений (прав). Для корректной работы с базой данных необходимо заранее установить соединение с ней.

# Структура

```
src/
--- exceptions/
--- interfaces/
--- SqlMigration.php
--- ConsoleSqlMigration.php
```

В директории `exceptions` хранятся специальные исключения компонента: `SqlMigrationException`.

В директории `interfaces` хранятся необходимые интерфейсы, которые необходимо имплементировать в при реализации
собственного класса `SqlMigration`.

Класс `SqlMigration` реализует интерфейс `SqlMigrationInterface` для управления SQL миграциями.

# Доступные методы

| Метод                                                             | Аргументы                                                                                      | Возвращаемые данные | Исключения                              | Описание                                                                    |
|-------------------------------------------------------------------|------------------------------------------------------------------------------------------------|---------------------|-----------------------------------------|-----------------------------------------------------------------------------|
| public function up(int $count = 0)                                | $count Количество миграция (0 - относительно всех)                                             | array               | SqlMigrationException                   | Применяет указанное количество миграций                                     |
| public function down(int $count = 0)                              | $count Количество миграция (0 - относительно всех)                                             | array               | SqlMigrationException                   | Отменяет указанное количество миграций                                      |
| public function history(int $limit = 0)                           | $limit Ограничение длины списка (null - полный список)                                         | array               |                                         | Возвращает список сообщений о примененных миграций                          |
| public function create(string $name)                              | $name Название миграции                                                                        | bool                | RuntimeException\|SqlMigrationException | Создает новую миграцию и возвращает сообщение об успешном создании миграции |
| __construct(DatabaseInterface $database, array $settings)         | $database Компонент работы с базой данных; array $settings Массив настроек                     |                     | SqlMigrationException                   | Конструктор класса                                                          |
| public function initSchemaAndTable()                              |                                                                                                | bool                | SqlMigrationException                   | Создает схему и таблицу в случае их отсутствия                              |
| public function __destruct()                                      |                                                                                                |                     |                                         | Деструктор класса                                                           |
| protected function getNotAppliedList()                            |                                                                                                | array               |                                         | Возвращает список не примененных миграций                                   |
| protected function getHistoryList(int $limit = 0)                 | $limit Ограничение длины списка (null - полный список)                                         | array               |                                         | Возвращает список примененных миграций                                      |
| protected function execute(array $list, int $count, string $type) | $list Массив миграций; $count Количество миграций для применения; $type Тип миграции (up/down) | array               | RuntimeException                        | Выполняет миграции                                                          |
| protected function addHistory(string $name)                       | $name Наименование миграции                                                                    | bool                | SqlMigrationException                   | Добавляет запись в таблицу миграций                                         |
| protected function removeHistory(string $name)                    | $name Наименование миграции                                                                    | bool                | SqlMigrationException                   | Удаляет миграцию из таблицы миграций                                        |
| protected function validateName(string $name)                     | $name Название миграции                                                                        | void                | SqlMigrationException                   | Проверяет имя миграции на корректность                                      |
| protected function generateName(string $name)                     | string $name Название миграции                                                                 | string              |                                         | Создает имя миграции по шаблону: m{дата в формате Ymd_His}_name             |

# Контакты

Вы можете связаться со мной в социальной сети ВКонтакте: [ВКонтакте: Максим Епихин](https://vk.com/maximepihin)

Если удобно писать на почту, то можете воспользоваться этим адресом: mepihindeveloper@gmail.com

Мой канал на YouTube, который посвящен разработке веб и игровых
проектов: [YouTube: Максим Епихин](https://www.youtube.com/channel/UCKusRcoHUy6T4sei-rVzCqQ)

Поддержать меня можно переводом на Яндекс.Деньги: [Денежный перевод](https://yoomoney.ru/to/410012382226565)
