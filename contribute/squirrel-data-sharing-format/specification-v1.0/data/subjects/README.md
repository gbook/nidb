---
description: JSON array
---

# subjects

This object is an **array** of subjects, with information about each subject.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8TFwRiWngDA6CNIdF99-Fu3CXIJzlSRPHSlfyQGicVw9Xmejry8_vju1MiAlikT2dovYBKyDmrUgo6BmBJw6EKyBVsnoRs8om5qyUrk1EqsYb4noVwPlniZPUv_-A6VJ5IXPMs4fBNcdaUndSyYjFyU-9Ki32nRKU2ZKH8d3iAaI1MIgXrzDUKEPMnL3MDsktEuY_doV3PB2OtQ6r3K5HCzJ2h6SII2sWG3PyUoPvUVtHywoJ2e1XN403mLXcICv8cI9WHlfOFRXxxgMHh_NHH4j1uD1YPDRxBC2oPoaFqF8y9T4rqqq2HRL8FdIKJFHIgTp8XZqmqzyGeOsC5-xTlrxEePMHk70I97B49-44ID7NI0HD77LsmzUyV9G1RFn3QnFqAHREEbN53-2uQqkjtBAgbCRFCqia1Wgor0YVHem-_CFMsUFwhWpJcSIaMVf-rZEWAkNHnpmxPxNmkCZT-4X55MY4TM6IZzGqEd4m-7Wuzx7yHf55mGb77P8EqN_zpGu98OV3-83m902zy__ASVglHU?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th align="right"></th><th width="152.00000000000003"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description (acceptable values)</strong></td></tr><tr><td align="right"><em><strong>*ID</strong></em></td><td>string</td><td>Unique ID of this subject. It must be unique within the package, ie no other subjects in the package have the same ID</td></tr><tr><td align="right"><em>alternateIDs</em></td><td>JSON array</td><td>List of alternate IDs</td></tr><tr><td align="right"><em>GUID</em></td><td>string</td><td>Globally unique identifier, from NDA</td></tr><tr><td align="right"><em>dateOfBirth</em></td><td>date</td><td>Subject’s date of birth</td></tr><tr><td align="right"><em><strong>*sex</strong></em></td><td>char</td><td>Sex at birth (F,M,O,U)</td></tr><tr><td align="right"><em>gender</em></td><td>char</td><td>Self-identified gender</td></tr><tr><td align="right"><em>ethnicity1</em></td><td>string</td><td>Usually Hispanic/non-hispanic</td></tr><tr><td align="right"><em>ethnicity2</em></td><td>string</td><td>NIH defined race</td></tr><tr><td align="right">virtualPath</td><td>string</td><td>relative path to the data within the package</td></tr><tr><td align="right"><a href="studies/"><em>studies</em></a></td><td>JSON array</td><td></td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory

> `/data/subjectID`
