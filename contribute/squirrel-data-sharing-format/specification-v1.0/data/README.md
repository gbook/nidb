---
description: JSON object
---

# data

This data object contains information about the subjects, and potential future data.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFrgzAUx7-KpAgKdZThLg562i5jbLDehpdX86xZ1UgSt0rpd1-iRqvtofVg3j_5_fOS99AjSThFEpGdgCpz3r_i0tGP4Fx5b5vPjzbyg2BNQYFnXv7ziOj5CpI97NDrx_kqqzBnJUpviGYEHioUrMBSSe8snlEmcUBZohgvQTTeTPsd3M4G653gdQUl5I3UiVvlWGn37VFZb38w0altYNetNoyqKdMb9eMVgm8lil8wh5HeubjCslLpZX3FFp6oge4SmdS6HCZzO1wud5di0rOBRVy3swQPpkECCpmy3PTIhBa6RE1RDCgnjXbds74YbJQdPGqnnfCtb-h5e45edB6rZg57EWOwcWewamIYrqCaHJ3h-IbJo0WapktdLcH3GFCQGQgBTfQ4NU2y3GOcVeEe66QUtxhn9qGjt3jH2gw0Pq1Wy46PFmEY9nHwx6jKorA6kCUpUBTAqP4zHM0-MVEZFhiTSIcUU6hzFZO4PGm0rvTu-EqZ4oJEKeQSlwRqxTdNmZBIiRot9MJA_2iKgdKf6TfnVp_-AYI_kI4?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:yellow\_circle: Computed (squirrel writer/reader should handle these variables)

<table data-full-width="true"><thead><tr><th width="254">Variable</th><th width="168.33333333333331">Type</th><th width="123">Default</th><th>Description</th></tr></thead><tbody><tr><td><code>GroupAnalysisCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of group analyses.</td></tr><tr><td><code>SubjectCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of subjects in the package.</td></tr><tr><td><a href="subjects/">subjects</a></td><td>JSON array</td><td></td><td>Array containing the subjects.</td></tr><tr><td><a href="group-analysis.md">group-analysis</a></td><td>JSON array</td><td></td><td>Array containing group analyses.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory, but actual binary data should be stored in the subjects or group-analysis sub directories.

> `/data`
