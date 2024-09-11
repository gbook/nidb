---
description: JSON array
---

# interventions

Interventions represent any substances or procedures **administered to** a participant; through a clinical trial or the participant’s use of prescription or recreational drugs. Detailed variables are available to record exactly how much and when a drug is administered. This allows searching by dose amount, or other variable.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFvgjAUx78KqTGBBBazuAtLPG2XZdmSeVu4POlDOoGStmwS43dfSykKelAO9P3b37-vfU85kJRTJDHZCqhz7_0rqTz9CM6V_7b-_OiiIIpWFBT45hU8nxA9X0O6gy36_ThdZTUWrELpD9GEwH2NgpVYKemfxRPKJI4oSxXjFYjWn-jAwt1stNoK3tRQQdFKnbhTnpNu3x6VzeYHU53aBW7dacOohjK9UT9eIfhGovgFcxjpn4srLKuUXtZX7OCRGmibyKTW5TCZu-Fy2V6KSd8FDpnPrSV6MA0SUMqMFaZHJnTQJWqKYkA5avR8ftYXg52khU_a6yYC5xt63p2jF9bj1MThLmIMLrYGp0aG4QqqLdAbjm-YIp5lWRbqagm-w4iCzEEIaOPHsWmU5R7jpAr3WEeluMU4sQ8dvcVrPaMf2mDDp8UitMZ4tlwu-zj6Y1Tl8bLek5CUKEpgVH8iDmbDhKgcS0xIrEOKGTSFSkhSHTXa1LoF-EqZ4oLEGRQSQwKN4uu2SkmsRIMOemGgvzjlQOn_6zfnTh__AT_IlIw?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required

<table data-full-width="true"><thead><tr><th width="240" align="right">Variable</th><th width="120">Type</th><th width="102">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>AdministrationRoute</code></td><td>string</td><td></td><td>Drug entry route (oral, IV, unknown, etc).</td></tr><tr><td align="right"><code>DateRecordCreate</code></td><td>string</td><td></td><td>Date the record was created in the current database. The original record may have been imported from another database.</td></tr><tr><td align="right"><code>DateRecordEntry</code></td><td>string</td><td></td><td>Date the record was first entered into a database.</td></tr><tr><td align="right"><code>DateRecordModify</code></td><td>string</td><td></td><td>Date the record was modified in the current database.</td></tr><tr><td align="right"><code>DateEnd</code></td><td>datetime</td><td></td><td>Datetime the intervention was stopped.</td></tr><tr><td align="right"><code>DateStart</code></td><td>datetime</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Datetime the intervention was started.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td></td><td>Longer description.</td></tr><tr><td align="right"><code>DoseString</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Full dosing string. Examples <code>tylenol 325mg twice daily by mouth</code>, or <code>5g marijuana inhaled by volcano</code></td></tr><tr><td align="right"><code>DoseAmount</code></td><td>number</td><td></td><td>In combination with other dose variables, the quantity of the drug.</td></tr><tr><td align="right"><code>DoseFrequency</code></td><td>string</td><td></td><td>Description of the frequency of administration.</td></tr><tr><td align="right"><code>DoseKey</code></td><td>string</td><td></td><td>For clinical trials, the dose key.</td></tr><tr><td align="right"><code>DoseUnit</code></td><td>string</td><td></td><td>mg, g, ml, tablets, capsules, etc.</td></tr><tr><td align="right"><code>InterventionClass</code></td><td>string</td><td></td><td>Drug class.</td></tr><tr><td align="right"><code>InterventionName</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Name of the intervention.</td></tr><tr><td align="right"><code>Notes</code></td><td>string</td><td></td><td>Notes about drug.</td></tr><tr><td align="right"><code>Rater</code></td><td>string</td><td></td><td>Rater/experimenter name.</td></tr></tbody></table>

### Recording drug administration

The following examples convert between common language and the squirrel storage format

> esomeprazole 20mg capsule by mouth daily



| Variable      | Value        |
| ------------- | ------------ |
| DrugClass     | PPI          |
| DrugName      | esomeprazole |
| DoseAmount    | 20mg         |
| DoseFrequency | daily        |
| Route         | oral         |
| DoseUnit      | mg           |



> 2 puffs atrovent inhaler every 6 hours



| Variable            | Value          |
| ------------------- | -------------- |
| DrugName            | ipratropium    |
| DrugClass           | bronchodilator |
| DoseAmount          | 2              |
| DoseFrequency       | every 6 hours  |
| AdministrationRoute | inhaled        |
| DoseUnit            | puffs          |

