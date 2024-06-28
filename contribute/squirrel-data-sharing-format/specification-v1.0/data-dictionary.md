---
description: JSON object
---

# data-dictionary

The data-dictionary object stores information describing mappings or any other descriptive information about the data. This can also contain any information that doesn't fit elsewhere in the squirrel package, such as project descriptions.

Examples include mapping numeric values (1,2,3,...) to descriptions (right, left, ambi, ...)

<figure><img src="https://mermaid.ink/img/pako:eNqVVE1v2zAM_SuBigAOYAdO4KaOCvTUXYZhA9bb4Itm0YlW2zL0gcUL8t8nyZYSuz20OkiP5nskRUI-o5JTQBgdBOmOi28_i3ZhluBcJclTR8pXcoBoPFePV2_09eXHd4dWhkiJIpHdbik2AOugZi3IKKAZA04dCNZAq2R0g2csG5qyUrkciUWMt0T0q4HlviZPUv_-A6UJ5IGPMvoPguuOtKTuJZORsxJveqqX2nBKU2ZKH893GA0QqYWhePAOhwp9kJHbg3cIaFOY-9oM7njrDrXOq1wuB0mytkMSpJEVq-2cLPSkt1TbB0uUk1ktlzeNt7SrOZCv9sJ9WHldGKqrYzQGjbdmCn8RK_B4EHhrIghXUH0Ni1C-5dT4rqqq2HRL8FdIKJFHIgTp8XYqmmT5jHDWhc9IJ634iHAmDxP9iPbaG_suggLu0zQeNPguy7IRJ38ZVUecdScUowZEQxg1z_9sYxVIHaGBAmEDKVRE16pARXsxVN2ZDPCFMsUFwhWpJcSIaMVf-rZEWAkNnvTMiPmbNIFlntwvzic2wmd0QjiNUY_wNt2td3n2kO_yzcM232f5JUb_nCJd74eV3-83m902zy__AQQDlFA?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required

_**data-dictionary**_

<table data-full-width="true"><thead><tr><th width="256" align="right">Variable</th><th width="131.00000000000003">Type</th><th width="89">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>NumFiles</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Number of files contained in the experiment.</td></tr><tr><td align="right"><code>Size</code></td><td>number</td><td></td><td>Size, in bytes, of the experiment files.</td></tr><tr><td align="right"><code>VirtualPath</code></td><td>string</td><td></td><td>Path to the data-dictionary within the squirrel package.</td></tr><tr><td align="right"><mark style="color:blue;">data-dictionary-item</mark></td><td>JSON array</td><td></td><td>Array of data dictionary items. See next table.</td></tr></tbody></table>

_**data-dictionary-item**_

<table data-full-width="true"><thead><tr><th width="240" align="right">Variable</th><th width="98.00000000000003">Type</th><th width="87">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>VariableType</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Type of variable.</td></tr><tr><td align="right"><code>VariableName</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Name of the variable.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td></td><td>Description of the variable.</td></tr><tr><td align="right"><code>KeyValueMapping</code></td><td>string</td><td></td><td>List of possible key/value mappings in the format <code>key1=value1, key2=value2</code>. Example <code>1=Female, 2=Male</code></td></tr><tr><td align="right"><code>ExpectedTimepoints</code></td><td>number</td><td></td><td>Number of expected timepoints. Example, the study is expected to have 5 records of a variable.</td></tr><tr><td align="right"><code>RangeLow</code></td><td>number</td><td></td><td>For numeric values, the lower limit.</td></tr><tr><td align="right"><code>RangeHigh</code></td><td>number</td><td></td><td>For numeric values, the upper limit.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory.

> `/data-dictionary`
