---
description: JSON array
---

# drugs

‘Drugs’ represents any substances administered to a participant; through a clinical trial or the participant’s use of prescription or recreational drugs. Detailed variables are available to record exactly how much and when a drug is administered. This allows searching by dose amount, or other variable.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8TFwRiWngDA6CNIdF99-Fu3CXIJzlSRPHSlfyQGicVw9Xmejry8_vju1MiAlikT2dovYBKyDmrUgo6BmBJw6EKyBVsnoRs8om5qyUrk1EqsYb4noVwPlniZPUv_-A6VJ5IXPMs4fBNcdaUndSyYjFyU-9Ki32nRKU2ZKH8d3iAaI1MIgXrzDUKEPMnL3MDsktEuY_doV3PB2OtQ6r3K5HCzJ2h6SII2sWG3PyUoPvUVtHywoJ2e1XN403mLXcICv8cI9WHlfOFRXxxgMHh_NHH4j1uD1YPDRxBC2oPoaFqF8y9T4rqqq2HRL8FdIKJFHIgTp8XZqmqzyGeOsC5-xTlrxEePMHk70I96xN_YFCzjcp2k8GPBdlmWjTv4yqo44604oRg2IhjBqvv2zTVQgdYQGCoSNpFARXasCFe3FoLozrYcvlCkuEK5ILSFGRCv-0rclwkpo8NAzI-ZX0gTKfG-_OJ_ECJ_RCeE0Rj3C23S33uXZQ77LNw_bfJ_llxj9c450vR-u_H6_2ey2eX75DyVukzc?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th align="right"></th><th width="150"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><em>*<strong>drugName</strong></em></td><td>string</td><td>Name of the drug</td></tr><tr><td align="right"><em>*<strong>dateStart</strong></em></td><td>datetime</td><td>Date the drug was started</td></tr><tr><td align="right"><em>dateEnd</em></td><td>datetime</td><td>Date the drug was stopped</td></tr><tr><td align="right"><em>*<strong>doseAmount</strong></em></td><td>number</td><td>In combination with other dose variables, the quantity of the drug</td></tr><tr><td align="right"><em>*<strong>doseFrequency</strong></em></td><td>string</td><td>Description of the frequency of administration</td></tr><tr><td align="right"><em>administrationRoute</em></td><td>string</td><td>Drug entry route (oral, IV, unknown, etc)</td></tr><tr><td align="right"><em>drugClass</em></td><td>string</td><td>Drug class </td></tr><tr><td align="right"><em>doseKey</em></td><td>string</td><td>For clinical trials, the dose key</td></tr><tr><td align="right"><em>doseUnit</em></td><td>string</td><td>mg, g, ml, tablets, capsules, etc</td></tr><tr><td align="right"><em>frequencyModifier</em></td><td>string</td><td>(every, times)</td></tr><tr><td align="right"><em>frequencyValue</em></td><td>number</td><td>Number</td></tr><tr><td align="right"><em>frequencyUnit</em></td><td>string</td><td>(bolus, dose, second, minute, hour, day, week, month, year)</td></tr><tr><td align="right"><em>description</em></td><td>string</td><td>Longer description</td></tr><tr><td align="right"><em>rater</em></td><td>string</td><td>Rater/experimenter</td></tr><tr><td align="right"><em>notes</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>dateEntry</em></td><td>string</td><td> date for the data-entry</td></tr></tbody></table>

### Recording drug administration

The following examples convert between common language and the squirrel storage format

> esomeprazole 20mg capsule by mouth daily

| Variable          | Value        |
| ----------------- | ------------ |
| drugClass         | PPI          |
| drugName          | esomeprazole |
| doseAmount        | 20mg         |
| doseFrequency     | daily        |
| route             | oral         |
| doseUnit          | mg           |
| frequencyModifier | every        |
| frequencyValue    | 1            |
| frequencyUnit     | day          |

> 2 puffs atrovent inhaler every 6 hours

| Variable            | Value          |
| ------------------- | -------------- |
| drugName            | ipratropium    |
| drugClass           | bronchodilator |
| doseAmount          | 2              |
| doseFrequency       | every 6 hours  |
| administrationRoute | inhaled        |
| doseUnit            | puffs          |
| frequencyModifier   | every          |
| frequencyValue      | 6              |
| frequencyUnit       | hours          |

