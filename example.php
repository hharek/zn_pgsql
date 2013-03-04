<?php
header("Content-Type: text/plain");
require_once ("zn_pgsql.php");

try
{
	/*-------------------- Общее ---------------------*/
	$db = new ZN_Pgsql("localhost", "user", "pass", "dbname");  /* классическое соединение */
	$db = new ZN_Pgsql("localhost", "user", "pass", "dbname", "main"); /* соединение со схемой по умолчанию main */
	$db = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", 15432); /* соединение по порту 15432 */
	$db = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", 5432, true); /* постоянное соединение */
	$db = new ZN_Pgsql("localhost", "user", "pass", "dbname", "public", 5432, false, "require"); /* соединение через SSL */
	
	$db_main = clone $db;				/* клонирование объекта без создания нового соединения */
	$db_main->set_schema("main");		/* изменение схемы на main для нового объекта */
	
	$db->connect();						/* соединение с СУБД, по умолчанию соединение будет создаваться только когда будет выполнятся хоть один запрос */
	$db->reconnect();					/* повторное соединение */
	
	if ($db->is_connect())				/* проверка соединения */
	{
		echo "Соединение установлено.\n\n";
	}

	$db->set_schema("main");			/* сменить схему по умолчанию на main */
	echo "Текущая схема: ".$db->get_schema().".\n\n";				/* узнать текущую схему */

	
	/*---------------- Работа с таблицами ---------------*/
	
	/* Множественный запрос */
	$query =
<<<SQL
CREATE SEQUENCE "category_seq" START 1;
CREATE TABLE "category"
(
  "ID" integer NOT NULL DEFAULT nextval('category_seq'),
  "Name" character varying(255) NOT NULL,
  CONSTRAINT "category_PK" PRIMARY KEY ("ID" ),
  CONSTRAINT "category_UN_Name" UNIQUE ("Name" )
);
ALTER SEQUENCE "category_seq" OWNED BY "category"."ID";

CREATE SEQUENCE "tovar_seq" START 1;
CREATE TABLE "tovar"
(
  "ID" integer NOT NULL DEFAULT nextval('tovar_seq'),
  "Name" character varying(255) NOT NULL,
  "Count" integer NOT NULL DEFAULT 0,
  "Price" numeric(15,2) NOT NULL DEFAULT 0.00,
  "Category_ID" integer,
  CONSTRAINT "tovar_PK" PRIMARY KEY ("ID"),
  CONSTRAINT "tovar_FK_Category_ID" FOREIGN KEY ("Category_ID")
      REFERENCES "category" ("ID"),
  CONSTRAINT "tovar_UN_Name" UNIQUE ("Name")
);
ALTER SEQUENCE "tovar_seq" OWNED BY "tovar"."ID";
SQL;
	$db->multi_query($query);					
	
	/* Поиск таблицы */
	if ($db->is_table("category"))			
	{
		echo "Таблица category существует.\n\n";
	}
	
	/* Поиск столбца */
	if($db->is_column("tovar", "Name"))
	{
		echo "Столбец tovar.Name существует.\n\n";
	}
	
	/* Вставка данных в таблицу */
	$db->insert("category", array("Name" => "Категория 1"));
	$db->insert("category", array("Name" => "Категория 2"));
	$db->insert("category", array("Name" => "Категория 3"));
	
	$db->insert("tovar", array("Name" => "Товар 1", "Count" => 10, "Price" => 10.12, "Category_ID" => 1));
	$db->insert("tovar", array("Name" => "Товар 2", "Count" => 3, "Price" => 201.00, "Category_ID" => 1));
	
	/* Изменение данных в таблице */
	$db->update("category", array("Name" => "Категория 2 изменена"), array("ID" => 2));
	
	/* Удалить строку из таблицы */
	$db->delete("category", array("ID" => 3));
	
	/* Обычный запрос */
	$tovar_name = $db->escape("Товар' с 'символомами");
	$query = 
<<<SQL
INSERT INTO "tovar" ("Name", "Count", "Price", "Category_ID")
VALUES ('{$tovar_name}', 10, 23.11, 1)
SQL;
	$db->query($query);
	
	/* Запрос с параметрами */
	$query = 
<<<SQL
INSERT INTO "tovar" ("Name", "Count", "Price", "Category_ID")
VALUES ($1, $2, $3, $4)
SQL;
	$db->query($query, array("Товар' 4", 11, 240, 1));
	
	/*** Запросы ***/
	$query = 
<<<SQL
SELECT "t"."Name", "t"."Price", "c"."Name" as "Category_Name"
FROM "tovar" as "t", "category" as "c"
WHERE "t"."Category_ID" = "c"."ID"
SQL;
	
	/* Запросы которые возвращают результат */
	$assoc = $db->query_assoc($query);			/* Массив ассоциативных массивов */
	var_dump($assoc); echo "\n\n\n";
	
	$column = $db->query_column($query);		/* Список */
	var_dump($column); echo "\n\n\n";
	
	$line = $db->query_line($query);			/* Ассоциативный массив */
	var_dump($line); echo "\n\n\n";
	
	$one = $db->query_one($query);				/* Строка */
	var_dump($one); echo "\n\n\n";
	
	$object = $db->query_object($query);		/* Объект */
	var_dump($object); echo "\n\n\n";
	
	$object_ar = $db->query_object_ar($query);	/* Массив объектов */
	var_dump($object_ar); echo "\n\n\n";
	
	$resource = $db->query_result($query);		/* Ресурс результата запроса */
	var_dump($resource); 
}
catch (Exception $e)
{
	echo "Ошибка. Код: " . $e->getCode() . ". Сообщение: " . $e->getMessage();
}
?>