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

<table data-header-hidden><thead><tr><th width="150" align="right"></th><th width="377"></th><th></th></tr></thead><tbody><tr><td align="right"><strong>Type</strong></td><td><strong>Notes</strong></td><td><strong>Example</strong></td></tr><tr><td align="right">string</td><td>Regular string</td><td>“My string of text”</td></tr><tr><td align="right">number</td><td>Any JSON acceptable number</td><td>3.14159 or 1000000</td></tr><tr><td align="right">datetime</td><td>Datetime is formatted as <code>YYYY-MM-DD HH:MI:SS</code>where all numbers are zero-padded and use a 24-hour clock. Datetime is stored as a JSON string datatype</td><td>“2022-12-03 15:34:56”</td></tr><tr><td align="right">date</td><td>Date is formatted as <code>YYYY-MM-DD</code></td><td>“1990-01-05”</td></tr><tr><td align="right">char</td><td>A single character</td><td>F</td></tr><tr><td align="right">bool</td><td>true or false</td><td>true</td></tr><tr><td align="right">JSON array</td><td>Item is a JSON array of any data type</td><td> </td></tr><tr><td align="right">JSON object</td><td>Item is a JSON object</td><td> </td></tr></tbody></table>

**Directory Structure**

The JSON file `squirrel.json` is stored in the root directory. A directory called `data` contains any data described in the JSON file. Files can be of any type, with file any extension. Because of the broad range of environments in which squirrel files are used, filenames must only contain alphanumeric characters. Filenames cannot contain special characters or spaces and must be less than 255 characters in length.

**Squirrel Package**

A squirrel package becomes a package once the entire directory structure is combined into a zip file. The compression level does not matter, as long as the file is a .zip archive. Once created, this package can be distributed to other instances of NiDB, squirrel readers, or simply unzipped and manually extracted. Packages can be created manually or exported using NiDB or squirrel converters.

## Package Specification

<figure><img src="https://mermaid.ink/img/pako:eNqVVE2PmzAQ_SuRV5GIBBGJ2CxxpT21l6pqpe6t4uLFQ-IuYOQPNTTKf69tsBPYPTQ-2G-Y92bGM8hnVHIKCKODIN1x8e1n0S7MEpyrJHnuSPlGDhCN5-rT1Rt9ffnx3aGVIVKiSGS3W4oNwDqoWQsyCmjGgFMHgjXQKhnd4BnLhqasVC5HYhHjLRH9amC5r8mz1K-_oTSBPPBRRv9BcN2RltS9ZDJyVuJNT_VSG05pykzp4_kBowEitTAUDz7gUKEPMnJ78A4BbQpzX5vBHe_dodZ5lcvlIEnWdkiCNLJitZ2ThZ70nmr7YIlyMqvl8qbxlnY1B_LVXrgPK68LQ3V1jMag8dZM4S9iBR4PAm9NBOEKqq9hEcq3nBo_VFUVm24J_gYJJfJIhCA93k5Fkyz3CGdduEc6acX_CGfyMNF7kk7-7iCExzSNByl-yLJsxMkfRtURZ90JxagB0RBGzStwtiELpI7QQIGwgRQqomtVoKK9GKruzBDgC2WKC4QrUkuIEdGKv_RtibASGjzpMyPmUWkCy9T2i_OJjfAZnRBOY9QjvE13612ePeW7fPO0zfdZfonRX6dI1_th5Y_7zWa3zfPLP3NDlu0?type=png" alt=""><figcaption></figcaption></figure>
