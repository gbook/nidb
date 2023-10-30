---
description: JSON array
---

# measures

Measures are observations collected from a participant in response to an experiment.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8TFwRiWngDA6CNIdF99-Fu3CXIJzlSRPHSlfyQGicVw9Xmejry8_vju1MiAlikT2dovYBKyDmrUgo6BmBJw6EKyBVsnoRs8om5qyUrk1EqsYb4noVwPlniZPUv_-A6VJ5IXPMs4fBNcdaUndSyYjFyU-9Ki32nRKU2ZKH8d3iAaI1MIgXrzDUKEPMnL3MDsktEuY_doV3PB2OtQ6r3K5HCzJ2h6SII2sWG3PyUoPvUVtHywoJ2e1XN403mLXcICv8cI9WHlfOFRXxxgMHh_NHH4j1uD1YPDRxBC2oPoaFqF8y9T4rqqq2HRL8FdIKJFHIgTp8XZqmqzyGeOsC5-xTlrxEePMHk70I97B49_A4ID7NI0HD77LsmzUyV9G1RFn3QnFqAHREEbN53-2uQqkjtBAgbCRFCqia1Wgor0YVHem-_CFMsUFwhWpJcSIaMVf-rZEWAkNHnpmxPxNmkCZT-4X55MY4TM6IZzGqEd4m-7Wuzx7yHf55mGb77P8EqN_zpGu98OV3-83m902zy__ASb4lHc?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th width="192.35296740841875" align="right"></th><th width="126"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><code>MeasureName</code></td><td>string</td><td>Name of the measure. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>DateStart</code></td><td>datetime</td><td>Start datetime of the measurement. <mark style="color:red;">REQUIRED</mark></td></tr><tr><td align="right"><code>DateEnd</code></td><td>datetime</td><td>End datetime of the measurement.</td></tr><tr><td align="right"><code>InstrumentName</code></td><td>string</td><td>Name of the instrument associated with this measure.</td></tr><tr><td align="right"><code>Rater</code></td><td>string</td><td>Name of the rater.</td></tr><tr><td align="right"><code>Notes</code></td><td>string</td><td>Detailed notes.</td></tr><tr><td align="right"><code>Value</code></td><td>string</td><td>Value (string or number).</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td>Longer description of the measure.</td></tr><tr><td align="right"><code>Duration</code></td><td>number</td><td>Duration of the measure in seconds, if known.</td></tr></tbody></table>

