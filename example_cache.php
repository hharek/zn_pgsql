<?php
header("Content-Type: text/plain");
require_once ("zn_pgsql.php");

try
{
	/* Соединение */
	$db = new ZN_Pgsql("localhost", "user", "pass", "dbname");
	
	/* Включить кэширование с использованием файлов */
	$db->cache_enable("file", "Секретная фраза 1", "/path/to/cache");
	
	/* Включить кэширование с использованием Memcache */
	$memcache_obj = new Memcache;
	$memcache_obj->connect("localhost", 11211);
	$db->cache_enable("memcache", "Секретная фраза 2", $memcache_obj);
	
	/* Включить и выключить кэширование */
	$db->cache_disable();
	$db->cache_enable();
	
	/* Закрыть соединение и запретить неявные соединения (используется только кэш) */
	$db->close();
	
	/* Открыть соединение и разрешить неявные соединения */
	$db->connect();
	
	/* Очистить весь кэш по этому соединению */
	$db->cache_truncate();
	
	/* Удалить кэш запросов по таблице */
	$db->cache_delete("tovar");
	
	/* Запросы с кэшированием */
	$query = 
<<<SQL
SELECT "ID", "Name"
FROM "tovar"
SQL;
	$db->query_assoc($query);								/* Кэширование не используется (не указана таблица) */
	$db->query_assoc($query, null, "tovar");				/* Кэширование используется (время жизни кэша 1 месяц) */
	$db->query_assoc($query, null, "tovar", "1 hour");		/* Создаётся кэш на 1 час */
	$db->query_assoc($query, null, "tovar", "10.04.2013");	/* Создаётся кэш до 10.04.2013 00:00:00 */
	
	/* Проверка кэширования */
	$query =
<<<SQL
SELECT "ID", "Name"
FROM "category"
SQL;
	$db->query_line($query, null, "category");	/* Кэш создался но ещё не использовалься */
	var_dump($db->is_connect()); /* true */
	
	$db->query_line($query, null, "category");	/* Используется кэш */
	var_dump($db->is_connect()); /* false */
	
	/* Изменение */
	$query =
<<<SQL
UPDATE "category"
SET "Name" = 'Категория изменена'
WHERE "ID" = 1
SQL;
	
	$db->query($query);							/* Кэширование не используется (таблица не указана) */
	$db->query_line($query, null, "category");	/* Результат будет неверный, т.к. UPDATE прошло без использования кэша */
	
	$db->query($query, null, "category");		/* Старый кэш удалиться */
	$db->query_line($query, null, "category");	/* Результат правильный */
}
catch (Exception $e)
{
	echo "Ошибка. Код: " . $e->getCode() . ". Сообщение: " . $e->getMessage();
}
?>