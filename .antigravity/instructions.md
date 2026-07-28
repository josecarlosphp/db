Este proyecto es una librería de clases PHP para interactuar con base de datos.

Tengo una clase base DbConnection que representa una conexión a la base de datos.
De ella heredan estas clases:

- DbConnection_MySQL.- Usa internamente las funciones mysql de PHP.
- DbConnection_MySqli.- Usa internamente las funciones mysqli de PHP.
- DbConnection_PDO.- Usa internamente PDO.

También tengo una clase base DbResultSet que representa un conjunto de resultados de una consulta.
De esta heredan:

- DbResultSet_MySQL.- Usa internamente las funciones mysql de PHP.
- DbResultSet_MySqli.- Usa internamente las funciones mysqli de PHP.
- DbResultSet_PDO.- Usa internamente PDO.

Finalmente, tengo la clase SqlSelectQuery, que representa una consulta de selección SQL simple, y la clase SqlSelectQueryComponent, que representa un componente de una consulta de selección SQL.
