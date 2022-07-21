---
description: Format specification for v1.0
---

# Specification v1.0

## Overview

A squirrel contains a JSON file with meta-data about all of the data in the package, and a directory structure to store files. While many data items are optional, a squirrel package must contain a JSON file and a data directory.

**JSON File**

JSON is JavaScript object notation, and many tutorials are available for how to read and write JSON files. Within the squirrel format, keys are camel-case; for example dayNumber or dateOfBirth, where each word in the key is capitalized except the first word. The JSON file should be manually editable. JSON resources:

* JSON tutorial - [https://www.w3schools.com/js/js\_json\_intro.asp](https://www.w3schools.com/js/js\_json\_intro.asp)
* Wiki - [https://en.wikipedia.org/wiki/JSON](https://en.wikipedia.org/wiki/JSON)
* JSON specification - [https://www.json.org/json-en.html](https://www.json.org/json-en.html)

**Squirrel data types**

The JSON specification includes several data types, but squirrel uses some derivative data types: string, number, date, datetime, char. Date, datetime, and char are stored as the JSON string datatype and should be enclosed in double quotes.

|    **Type** | **Notes**                                                                                                                                             | **Example**           |
| ----------: | ----------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------- |
|      string | Regular string                                                                                                                                        | “My string of text”   |
|      number | Any JSON acceptable number                                                                                                                            | 3.14159 or 1000000    |
|    datetime | Datetime is formatted as `YYYY-MM-DD HH:MI:SS`where all numbers are zero-padded and use a 24-hour clock. Datetime is stored as a JSON string datatype | “2022-12-03 15:34:56” |
|        date | Date is formatted as `YYYY-MM-DD`                                                                                                                     | “1990-01-05”          |
|        char | A single character                                                                                                                                    | F                     |
|        bool | true or false                                                                                                                                         | true                  |
|  JSON array | Item is a JSON array of any data type                                                                                                                 |                       |
| JSON object | Item is a JSON object                                                                                                                                 |                       |

**Directory Structure**

The JSON file `squirrel.json` is stored in the root directory. A directory called `data` contains any data described in the JSON file. Files can be of any type, with file any extension. Because of the broad range of environments in which squirrel files are used, filenames must only contain alphanumeric characters. Filenames cannot contain special characters or spaces and must be less than 255 characters in length.

**Squirrel Package**

A squirrel package becomes a package once the entire directory structure is combined into a zip file. The compression level does not matter, as long as the file is a .zip archive. Once created, this package can be distributed to other instances of NiDB, squirrel readers, or simply unzipped and manually extracted. Packages can be created manually or exported using NiDB or squirrel converters.

## Package Specification

![](https://mermaid.ink/img/pako:eNptkctqAzEMRX8laOVA8gNTyKrdlNJCZmsoqq1M3I4f-AEJIf9eeTqeljReSNe-R5aRL6C8JuhgiBiOq5e9dCte0fssnvu310mtt9udxoyihvXDL8Ln7wHVFw4kmrjxgwk0GkdJLOqGoFOgaCy5nMQf3ajak6lUPj5JMdJE89u-Mrlow53mfIewhKlERpq4w-hYhiSmuLg\_F9YW\_LzaYUr\_bXQ4npNJookFmQrqPDCi5WFMqbnLaOZB94GUaKJCsAFL0aLR\_FWXWiQhH8mShI6lpgOWMUuQ7spoCVxLT9pkH6E74JhoA1iy789OQZdjoQY9GuSftzN1\_QYK8bNv)
