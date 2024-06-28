---
description: JSON array
---

# interventions

**Interventions** represent any substances or procedures **administered to** a participant; through a clinical trial or the participant’s use of prescription or recreational drugs. Detailed variables are available to record exactly how much and when a drug is administered. This allows searching by dose amount, or other variable.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8TFwRiWngDA6CNIdF99-Fu3CXIJzlSRPHSlfyQGicVw9Xmejry8_vju1MiAlikT2dovYBKyDmrUgo6BmBJw6EKyBVsnoRs8om5qyUrk1EqsYb4noVwPlniZPUv_-A6VJ5IXPMs4fBNcdaUndSyYjFyU-9Ki32nRKU2ZKH8d3iAaI1MIgXrzDUKEPMnL3MDsktEuY_doV3PB2OtQ6r3K5HCzJ2h6SII2sWG3PyUoPvUVtHywoJ2e1XN403mLXcICv8cI9WHlfOFRXxxgMHh_NHH4j1uD1YPDRxBC2oPoaFqF8y9T4rqqq2HRL8FdIKJFHIgTp8XZqmqzyGeOsC5-xTlrxEePMHk70I96xN_YFCzjcp2k8GPBdlmWjTv4yqo44604oRg2IhjBqvv2zTVQgdYQGCoSNpFARXasCFe3FoLozrYcvlCkuEK5ILSFGRCv-0rclwkpo8NAzI-ZX0gTKfG-_OJ_ECJ_RCeE0Rj3C23S33uXZQ77LNw_bfJ_llxj9c450vR-u_H6_2ey2eX75DyVukzc?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th width="244" align="right"></th><th width="120"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><code>DrugName</code></td><td>string</td><td>Name of the drug. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>DateStart</code></td><td>datetime</td><td>Datetime the drug was started. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>DateEnd</code></td><td>datetime</td><td>Datetime the drug was stopped.</td></tr><tr><td align="right"><code>DoseString</code></td><td>string</td><td>Full dosing string. Examples <code>tylenol 325mg twice daily by mouth</code>, or <code>5g marijuana inhaled by volcano</code></td></tr><tr><td align="right"><code>DoseAmount</code></td><td>number</td><td>In combination with other dose variables, the quantity of the drug. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>DoseFrequency</code></td><td>string</td><td>Description of the frequency of administration. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>AdministrationRoute</code></td><td>string</td><td>Drug entry route (oral, IV, unknown, etc).</td></tr><tr><td align="right"><code>DrugClass</code></td><td>string</td><td>Drug class.</td></tr><tr><td align="right"><code>DoseKey</code></td><td>string</td><td>For clinical trials, the dose key.</td></tr><tr><td align="right"><code>DoseUnit</code></td><td>string</td><td>mg, g, ml, tablets, capsules, etc.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td>Longer description.</td></tr><tr><td align="right"><code>Rater</code></td><td>string</td><td>Rater/experimenter name.</td></tr><tr><td align="right"><code>Notes</code></td><td>string</td><td>Notes about drug.</td></tr><tr><td align="right"><code>DateRecordEntry</code></td><td>string</td><td>Date the record was first entered into a database.</td></tr><tr><td align="right"><code>DateRecordCreate</code></td><td>string</td><td>Date the record was created in the current database. The original record may have been imported from another database.</td></tr><tr><td align="right"><code>DateRecordModify</code></td><td>string</td><td>Date the record was modified in the current database.</td></tr></tbody></table>

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

