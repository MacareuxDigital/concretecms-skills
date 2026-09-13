---
name: working-with-database
description: Connect to databases, execute queries, and manipulate data in Concrete CMS projects. Use this skill when the user asks to build custom block types or connect with database.
---

# Working with Database

## How to connect

Use `$db = $this->app->make(\Concrete\Core\Database\Connection\Connection::class);` Do not use the `Database` facade, it is deprecated.

## XML file format for database schemas

Concrete CMS uses "Doctrine XML" file format to define database schema. Ref: https://concretecms.github.io/doctrine-xml/doctrine-xml-0.5.xsd

Here's an example with all the features offered by Doctrine XML:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<schema xmlns="http://www.concrete5.org/doctrine-xml/0.5"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.concrete5.org/doctrine-xml/0.5
    https://concretecms.github.io/doctrine-xml/doctrine-xml-0.5.xsd"
>

    <table name="Companies" comment="List of companies">
        <field name="Id" type="integer" comment="Record identifier">
            <unsigned/>
            <autoincrement/>
            <key/>
        </field>
        <field name="Name" type="string" size="50" comment="Company name">
            <notnull/>
            <opt for="mysql" collation="utf8_bin"/>
        </field>
        <opt for="mysql" engine="InnoDB" charset="utf8" collate="utf8_unicode_ci" row_format="compact"/>
    </table>

    <table name="Employees">
        <field name="Id" type="integer">
            <unsigned/>
            <autoincrement/>
            <key/>
        </field>
        <field name="IdentificationCode" type="string" size="20">
            <fixed/>
        </field>
        <field name="Company" type="integer">
            <unsigned/>
            <notnull/>
        </field>
        <field name="FirstName" type="string" size="50">
            <default value=""/>
            <notnull/>
        </field>
        <field name="LastName" type="string" size="50">
            <notnull/>
        </field>
        <field name="Income" type="decimal" size="10.2">
            <default value="1000"/>
        </field>
        <field name="HiredOn" type="datetime">
            <deftimestamp/>
        </field>
        <index>
            <fulltext/>
            <col>FirstName</col>
        </index>
        <index name="IX_EmployeesIdentificationCode">
            <unique/>
            <col>IdentificationCode</col>
        </index>
        <references table="Companies" onupdate="cascade" ondelete="restrict">
            <column local="Company" foreign="Id"/>
        </references>
    </table>

</schema>
```

AXMLS (Adodb-xmlschema, using `<schema version="0.3"></schema>`) is a legacy format, so do not use it for new code. No need to refactor XML files already exists.

## Best Practices

- Avoid using SQL reserved words (especially in MySQL 8+) for column names. For example, `lead` is reserved in MySQL 8 — do not use it as a column name. Prefer alternatives like `summary`.
