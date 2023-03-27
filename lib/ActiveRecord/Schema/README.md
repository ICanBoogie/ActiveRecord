# Schema Attributes

## Data Types

### Characters

| T1                           | T2                                                                     | DB                                               |
|------------------------------|------------------------------------------------------------------------|--------------------------------------------------|
| `Boolean`                    | `Integer(1, unsigned: true, unique: false)`                            | `TINYINT(1)`                                     |
| `Serial`                     | `Integer(BIG, unsigned: true, null: false, serial: true unique: true)` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE` |
| `Integer`                    | --                                                                     | `INTEGER`                                        |
| `Decimal`                    | --                                                                     | `DECIMAL`                                        |
| `Text`                       | --                                                                     | `VARCHAR`                                        |
| `Text(size: 80)`             | --                                                                     | `VARCHAR(80)`                                    |
| `Text(size: 2)`              | --                                                                     | `VARCHAR(2)`                                     |
| `Text(size: 2, fixed: true)` | --                                                                     | `CHAR(2)`                                        |
| `Text(size: TINY)`           | --                                                                     | `VARCHAR(255)`                                   |
| `Text(size: MEDIUM)`         | --                                                                     | `MEDIUMTEXT`                                     |
| `Text(size: BIG)`            | --                                                                     | `LONGTEXT`                                       |

`Boolean`:

https://dev.mysql.com/doc/refman/8.0/en/other-vendor-data-types.html

https://dev.mysql.com/doc/refman/8.0/en/numeric-type-syntax.html

>  SERIAL is an alias for BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE.

https://www.postgresql.org/docs/current/datatype-numeric.html#DATATYPE-SERIAL

>








`VARCHAR(n)` vs. `TEXT`

> PostgreSQL: The only difference between TEXT and VARCHAR(n) is that you can limit the maximum length of a VARCHAR column, for example, VARCHAR(255) does not allow inserting a string more than 255 characters long.

| DB Type                    | AR Type T. | AR Type C. | AR Type |
|----------------------------|------------| ---------- | ------- |
| **SQL Lite**               |
| INT                        |            | | |
| INTEGER                    |            | | |
| (TINY SMALL MEDIUM BIG)INT |          | | |
| UNSGINED BIGINT            |            | | |
| INT2                       |            | | |
| INT8                       |            | | |
