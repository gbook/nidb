---
description: JSON array
---

# subjects

This object is an **array** of subjects, with information about each subject.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8TFwRiWngDA6CNIdF99-Fu3CXIJzlSRPHSlfyQGicVw9Xmejry8_vju1MiAlikT2dovYBKyDmrUgo6BmBJw6EKyBVsnoRs8om5qyUrk1EqsYb4noVwPlniZPUv_-A6VJ5IXPMs4fBNcdaUndSyYjFyU-9Ki32nRKU2ZKH8d3iAaI1MIgXrzDUKEPMnL3MDsktEuY_doV3PB2OtQ6r3K5HCzJ2h6SII2sWG3PyUoPvUVtHywoJ2e1XN403mLXcICv8cI9WHlfOFRXxxgMHh_NHH4j1uD1YPDRxBC2oPoaFqF8y9T4rqqq2HRL8FdIKJFHIgTp8XZqmqzyGeOsC5-xTlrxEePMHk70I97B49-44ID7NI0HD77LsmzUyV9G1RFn3QnFqAHREEbN53-2uQqkjtBAgbCRFCqia1Wgor0YVHem-_CFMsUFwhWpJcSIaMVf-rZEWAkNHnpmxPxNmkCZT-4X55MY4TM6IZzGqEd4m-7Wuzx7yHf55mGb77P8EqN_zpGu98OV3-83m902zy__ASVglHU?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th width="179.0144927536232" align="right"></th><th width="152.00000000000003"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description (acceptable values)</strong></td></tr><tr><td align="right"><code>SubjectID</code></td><td>string</td><td>Unique ID of this subject. Each subject ID must be unique within the package. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>AlternateIDs</code></td><td>JSON array</td><td>List of alternate IDs. Comma separated.</td></tr><tr><td align="right"><code>GUID</code></td><td>string</td><td>Globally unique identifier, from NDA.</td></tr><tr><td align="right"><code>DateOfBirth</code></td><td>date</td><td>Subject’s date of birth. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>Sex</code></td><td>char</td><td>Sex at birth (F,M,O,U).</td></tr><tr><td align="right"><code>Gender</code></td><td>char</td><td>Self-identified gender.</td></tr><tr><td align="right"><code>Ethnicity1</code></td><td>string</td><td>NIH defined ethnicity: Usually <code>hispanic</code>, <code>non-hispanic</code></td></tr><tr><td align="right"><code>Ethnicity2</code></td><td>string</td><td>NIH defined race: <code>americanindian</code>, <code>asian</code>, <code>black</code>, <code>hispanic</code>, <code>islander</code>, <code>white</code></td></tr><tr><td align="right"><code>VirtualPath</code></td><td>string</td><td>Relative path to the data within the package.</td></tr><tr><td align="right"><code>StudyCount</code></td><td>number</td><td>Number of studies.</td></tr><tr><td align="right"><code>MeasureCount</code></td><td>number</td><td>Number of measures.</td></tr><tr><td align="right"><code>DrugCount</code></td><td>number</td><td>Number of drugs.</td></tr><tr><td align="right"><a href="studies/"><em>studies</em></a></td><td>JSON array</td><td>Array of imaging studies/sessions.</td></tr><tr><td align="right"><a href="measures.md">measures</a></td><td>JSON array</td><td>Array of measures.</td></tr><tr><td align="right"><a href="drugs.md">drugs</a></td><td>JSON array</td><td>Array of drugs.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory

> `/data/<SubjectID>`
