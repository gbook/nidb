---
description: JSON array
---

# subjects

This object is an **array** of subjects, with information about each subject.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFvgjAUx78KqTGBBBazuAtLPG2XZdmSeVu4POlDOoGStmwS43dfSykKelAO9P3b37-vfU85kJRTJDHZCqhz7_0rqTz9CM6V_7b-_OiiIIpWFBT45hU8nxA9X0O6gy36_ThdZTUWrELpD9GEwH2NgpVYKemfxRPKJI4oSxXjFYjWn-jAwt1stNoK3tRQQdFKnbhTnpNu3x6VzeYHU53aBW7dacOohjK9UT9eIfhGovgFcxjpn4srLKuUXtZX7OCRGmibyKTW5TCZu-Fy2V6KSd8FDpnPrSV6MA0SUMqMFaZHJnTQJWqKYkA5avR8ftYXg52khU_a6yYC5xt63p2jF9bj1MThLmIMLrYGp0aG4QqqLdAbjm-YIp5lWRbqagm-w4iCzEEIaOPHsWmU5R7jpAr3WEeluMU4sQ8dvcVrPe4XODjwabEIrSeeLZfLPo7-GFV5vKz3JCQlihIY1V-Hg9krISrHEhMS65BiBk2hEpJUR402ta4-vlKmuCBxBoXEkECj-LqtUhIr0aCDXhjoj005UPqv-s2508d_DN-SVw?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required\
:yellow\_circle: Computed (squirrel writer/reader should handle these variables)

<table data-full-width="true"><thead><tr><th width="224.0144927536232" align="right">Variable</th><th width="152.00000000000003">Type</th><th width="95">Default</th><th>Description (and possible values)</th></tr></thead><tbody><tr><td align="right"><code>AlternateIDs</code></td><td>JSON array</td><td></td><td>List of alternate IDs. Comma separated.</td></tr><tr><td align="right"><code>DateOfBirth</code></td><td>date</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> </td><td>Subject’s date of birth. Used to calculate age-at-study. Value can be <code>YYYY-00-00</code> to store year only, or <code>YYYY-MM-00</code> to store year and month only.</td></tr><tr><td align="right"><code>Gender</code></td><td>char</td><td></td><td>Gender.</td></tr><tr><td align="right"><code>GUID</code></td><td>string</td><td></td><td>Globally unique identifier, from the NIMH Data Archive (<a href="https://nda.nih.gov/">NDA</a>).</td></tr><tr><td align="right"><code>Ethnicity1</code></td><td>string</td><td></td><td>NIH defined ethnicity: Usually <code>hispanic</code>, <code>non-hispanic</code></td></tr><tr><td align="right"><code>Ethnicity2</code></td><td>string</td><td></td><td>NIH defined race: <code>americanindian</code>, <code>asian</code>, <code>black</code>, <code>hispanic</code>, <code>islander</code>, <code>white</code></td></tr><tr><td align="right"><code>Sex</code></td><td>char</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> </td><td>Sex at birth (F,M,O,U).</td></tr><tr><td align="right"><code>SubjectID</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Unique ID of this subject. Each subject ID must be unique within the package.</td></tr><tr><td align="right"><code>InterventionCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of intervention objects.</td></tr><tr><td align="right"><code>ObservationCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of observation objects.</td></tr><tr><td align="right"><code>StudyCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of studies.</td></tr><tr><td align="right"><code>VirtualPath</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Relative path to the data within the package.</td></tr><tr><td align="right"><a href="studies/">studies</a></td><td>JSON array</td><td></td><td>Array of imaging studies/sessions.</td></tr><tr><td align="right"><a href="observations.md">observations</a></td><td>JSON array</td><td></td><td>Array of observations.</td></tr><tr><td align="right"><a href="interventions.md">interventions</a></td><td>JSON array</td><td></td><td>Array of interventions.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory

> `/data/<SubjectID>`
