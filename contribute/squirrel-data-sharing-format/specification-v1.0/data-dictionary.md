---
description: JSON object
---

# data-dictionary

The data-dictionary object stores information describing mappings or any other descriptive information about the data. This can also contain any information that doesn't fit elsewhere in the squirrel package, such as project descriptions.

Examples include mapping numeric values (1,2,3,...) to descriptions (F, M, O, ...)

<figure><img src="https://mermaid.ink/img/pako:eNptksFqwzAMhl8leBRcaEYZ2cWDnrbLGBust5GLGiuN18Q2trM1lL777KRO26w5xL-sT5Jl-UAKxZEwsjWgq-TtM5eJ_4xSjr6uP957NU_TFQcHNPzmT2fE72sodrBFelqnXqGxFhItHdWEwL1GIxqUztILPaFC4ZSLwgklwXR0Ys8HuN9NV1ujWg0S6s4KS3sriWbMe0Jtu_nGwpeOIvqjHRjXcuE7OK03CLWxaH4gHMbSS-MGK6Tzbt9iD19ZIz0UCqX9dYTK_fLfPfY47W42G0LS-zAgA40tRR1mFOQZGmcSuHAj1qG2w-X2MqJjVtfVmIxk4rPW7K4s8XG5XPiDGbXDlIOtwBjo2EM8cIy6GNl17BDK7rIsO-n0V3BXsUzvyYI0aBoQ3D_TQ0iZE1dhgzlhXnIsoa1dTnJ59GirfSF84cIpQ1gJtcUFgdapdScLwpxpMULPAvyrb0bKv5kvpaJ9_AP3KBDt?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required\
:yellow\_circle: Computed (squirrel writer/reader should handle these variables)

_**data-dictionary**_

<table data-full-width="true"><thead><tr><th width="256" align="right">Variable</th><th width="131.00000000000003">Type</th><th width="89">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>NumFiles</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of files contained in the experiment.</td></tr><tr><td align="right"><code>Size</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Size, in bytes, of the experiment files.</td></tr><tr><td align="right"><code>VirtualPath</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Path to the data-dictionary within the squirrel package.</td></tr><tr><td align="right"><mark style="color:blue;">data-dictionary-item</mark></td><td>JSON array</td><td></td><td>Array of data dictionary items. See next table.</td></tr></tbody></table>

_**data-dictionary-item**_

<table data-full-width="true"><thead><tr><th width="240" align="right">Variable</th><th width="98.00000000000003">Type</th><th width="87">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>VariableType</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Type of variable.</td></tr><tr><td align="right"><code>VariableName</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Name of the variable.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td></td><td>Description of the variable.</td></tr><tr><td align="right"><code>KeyValueMapping</code></td><td>string</td><td></td><td>List of possible key/value mappings in the format <code>key1=value1, key2=value2</code>. Example <code>1=Female, 2=Male</code></td></tr><tr><td align="right"><code>ExpectedTimepoints</code></td><td>number</td><td></td><td>Number of expected timepoints. Example, the study is expected to have 5 records of a variable.</td></tr><tr><td align="right"><code>RangeLow</code></td><td>number</td><td></td><td>For numeric values, the lower limit.</td></tr><tr><td align="right"><code>RangeHigh</code></td><td>number</td><td></td><td>For numeric values, the upper limit.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory.

> `/data-dictionary`
