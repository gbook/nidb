---
description: JSON array
---

# interventions

Interventions represent any substances or procedures **administered to** a participant; through a clinical trial or the participant’s use of prescription or recreational drugs. Detailed variables are available to record exactly how much and when a drug is administered. This allows searching by dose amount, or other variable.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFvgjAUx78KqTGBBBazuAtLPG2XZdmSeVu4POlDOoGStmwS43dfSykKelAO9P3b37-vfU85kJRTJDHZCqhz7_0rqTz9CM6V_7b-_OiiIIpWFBT45hU8nxA9X0O6gy36_ThdZTUWrELpD9GEwH2NgpVYKemfxRPKJI4oSxXjFYjWn-jAwt1stNoK3tRQQdFKnbhTnpNu3x6VzeYHU53aBW7dacOohjK9UT9eIfhGovgFcxjpn4srLKuUXtZX7OCRGmibyKTW5TCZu-Fy2V6KSd8FDpnPrSV6MA0SUMqMFaZHJnTQJWqKYkA5avR8ftYXg52khU_a6yYC5xt63p2jF9bj1MThLmIMLrYGp0aG4QqqLdAbjm-YIp5lWRbqagm-w4iCzEEIaOPHsWmU5R7jpAr3WEeluMU4sQ8dvcVrPaMf2mDDp8UitMZ4tlwu-zj6Y1Tl8bLek5CUKEpgVH8iDmbDhKgcS0xIrEOKGTSFSkhSHTXa1LoF-EqZ4oLEGRQSQwKN4uu2SkmsRIMOemGgvzjlQOn_6zfnTh__AT_IlIw?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required

{% include "../../../../../.gitbook/includes/interventions.md" %}

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

