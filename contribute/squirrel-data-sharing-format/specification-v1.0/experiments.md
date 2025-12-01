---
description: JSON array
---

# experiments

Experiments describe how data was collected from the participant. In other words, the methods used to get the data. This does not describe how to analyze the data once it’s collected.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFrgzAUx7-KpAgKdZThLg562i5jbLDehpdX86xZ1UgSt0rpd1-iRqvtofVg3j_5_fOS99AjSThFEpGdgCpz3r_i0tGP4Fx5b5vPjzbyg2BNQYFnXv7ziOj5CpI97NDrx_kqqzBnJUpviGYEHioUrMBSSe8snlEmcUBZohgvQTTeTPsd3M4G653gdQUl5I3UiVvlWGn37VFZb38w0altYNetNoyqKdMb9eMVgm8lil8wh5HeubjCslLpZX3FFp6oge4SmdS6HCZzO1wud5di0rOBRVy3swQPpkECCpmy3PTIhBa6RE1RDCgnjXbds74YbJQdPGqnnfCtb-h5e45edB6rZg57EWOwcWewamIYrqCaHJ3h-IbJo0WapktdLcH3GFCQGQgBTfQ4NU2y3GOcVeEe66QUtxhn9qGjt3jnpx0T4tNqtexs0SIMwz4O_hhVWRRWB7IkBYoCGNU_iKPZLiYqwwJjEumQYgp1rmISlyeN1pVuAL5SprggUQq5xCWBWvFNUyYkUqJGC70w0P-bYqD01_rNudWnf_V5k6g?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required\
:yellow\_circle: Computed (squirrel writer/reader should handle these variables)

<table data-full-width="true"><thead><tr><th width="198" align="right">Variable</th><th width="98.00000000000003">Type</th><th width="93">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>ExperimentName</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Unique name of the experiment.</td></tr><tr><td align="right"><code>FileCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of files contained in the experiment.</td></tr><tr><td align="right"><code>Size</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Size, in bytes, of the experiment files.</td></tr><tr><td align="right"><code>VirtualPath</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Path to the experiment within the squirrel package.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. Where `ExperimentName` is the unique name of the experiment.

> `/experiments/<ExperimentName>`
