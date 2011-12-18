<?php

require_once ("zn_pgsql.php");

try
{
	/*** Соединение ***/
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname");	   // классическое соединение
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname", "main_schema");   // соединение с схемой по умолчанию main_schema
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", null, 15432); // соединение по порту 15432
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", "/site1/cache"); // соединение с использованием кэша
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", null, 5432, true); // постоянное соединение
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", null, 5432, false, true); // соединение через SSL
	
	$pgsql = clone $pgsql_new;					// клонирование объекта без создания нового соединения
	$pgsql_new->set_schema("main_schema");		// изменение схемы для нового объекта

	$pgsql->connect();		// процесс соединения
	$pgsql->reconnect();	   // пересоединение

	if ($pgsql->is_connect())	  // проверка соединения
	{
		echo "Соединение установлено";
	}

	/*** Общие ***/
	$pgsql->set_schema("main_schema");	// сменить схему по умолчанию
	echo $pgsql->get_schema();	  // узнать текущую схему
	
	echo $pgsql->escape("Строка ' символами для ' экранирования");  // Экранирование

	if ($pgsql->is_table("news"))		  // Поиск таблицы
	{
		$pgsql->query("DROP TABLE \"news\"");
	}
	
	if($pgsql->is_column("news", "Name"))		// Поиск столбца
	{
		$pgsql->query("CREATE UNIQUE INDEX \"news_Name_index\" ON \"news\"(\"Name\")");
	}

	/*** Запросы ***/
	$query =
<<<SQL
UPDATE "news" 
SET "Name" = 'Тест'
WHERE "ID" = '1'
SQL;
	$pgsql->query($query);	// Запрос

	$query =
<<<SQL
UPDATE "news" 
SET "Name" = 'Тест'
WHERE "ID" = $1
SQL;
	$pgsql->query($query, $_GET['id']);		// Запрос с параметрами (предпочтилен). $1 = 'escape($_GET['id'])'

	$query =
<<<SQL
SELECT "ID", "Name"
FROM "news"
SQL;
	$array = $pgsql->query_assoc($query);	// Запрос с возвращением ассоциативного массива

	$query =
<<<SQL
SELECT "Name"
FROM "news"
SQL;
	$array = $pgsql->query_column($query);	// Запрос с возвращение столбца

	$query =
<<<SQL
SELECT *
FROM "news"
WHERE "ID" = $1
OR "Name" = $2
LIMIT 1
SQL;
	$array = $pgsql->query_line($query, array($_GET['id'], "Тест"));	// Запрос с возвращение первой строки

	$query =
<<<SQL
SELECT COUNT(*) as count
FROM "news"
SQL;
	$count = $pgsql->query_one($query);		// Запрос с возвращение результат из первой ячейки

	$pgsql->multi_query(file_get_contents("news.sql"));	// Множественный запрос

	
	
	/*** Кэширование ***/
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname");
	$pgsql->cache_enable("/site1/cache");		// Включить кэширование
	
	$pgsql = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", "/site1/cache");
	
	$query = 
<<<SQL
SELECT "ID", "Name"
FROM "news"
SQL;
	$news = $pgsql->query_assoc($query, null, "news");	// выполняется запрос и создаётся кэш
	var_dump($pgsql->is_connect());		// true
	
	$news = $pgsql->query_assoc($query, null, "news");	// данные взяты из кэша
	var_dump($pgsql->is_connect());		// false

	$query_update = 
<<<SQL
UPDATE "news"
SET "Name" = 'Тест'
WHERE "ID" = '1'
SQL;
	
	$pgsql->query($query_update);						// выполняется запрос без учёта кэша
	$news = $pgsql->query_assoc($query, 6, "news");		// неверный результат
	
	$pgsql->query($query_update, null, "news", true);	// выполняется запрос и стирается кэш относящийся к таблице news
	$news = $pgsql->query_assoc($query, 6, "news");		// правильный результат
	
	$query = 
<<<SQL
SELECT "ID", "Name"
FROM "news"
WHERE EXTRACT(MONTH FROM "Date") = EXTRACT(MONTH FROM NOW())
ORDER BY "Date" DESC
SQL;
	$pgsql->query_assoc($query, null, "news", false, "+10 day");	// указание времени хранения кэша (10 дней)
	$pgsql->query_assoc($query, null, "news", false, "01.01.2012");	// указание времени хранения кэша (до 01.01.2012)
	
	$pgsql->cache_disable();	// отключить кэширование
	
}
catch (Exception $e)
{
	echo "Код: " . $e->getCode() . ". " . $e->getMessage();
}
?>
