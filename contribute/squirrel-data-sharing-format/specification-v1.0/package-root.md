---
description: JSON object
---

# Package root

The package root contains all data and files for the package. The JSON root contains all JSON objects for the package.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFrgzAUx7-KpBQUdJTRXRz0tF3G2GC9DS-v5lmzqpEkbpXS777EGFttD60H8_7J75-XvIceSMopkphsBdS59_6VVJ5-BOfKf1t_fnRREEUrCgp88wqeT4ieryHdwRb9fpyushoLVqH0h2hC4L5GwUqslPTP4gllEkeUpYrxCkTrT3Rg4W42Wm0Fb2qooGilTtwpz0m3b4_KZvODqU7tArfutGFUQ5neqB-vEHwjUfyCOYz0z8UVllVKL-srdvBIDbRNZFLrcpjM3XC5bC_FpO8Ch8zn1hI9mAYJKGXGCtMjEzroEjVFMaAcNXo-P-uLwU7SwiftdROB8w09787RC-txauJwFzEGF1uDUyPDcAXVFugNxzdMEc-yLAt1tQTfYURB5iAEtPHj2DTKco9xUoV7rKNS3GKc2IeO3uK1HvMdDTQ-LRah5ePZcrns4-iPUZXHy3pPQlKiKIFR_Wc4mH0SonIsMSGxDilm0BQqIUl11GhT68rjK2WKCxJnUEgMCTSKr9sqJbESDTrohYH-0ZQDpT_Tb86dPv4DnT6QuA?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:yellow\_circle: Computed (squirrel writer/reader should handles these variables)

<table data-full-width="true"><thead><tr><th width="192">Variable</th><th width="149">Type</th><th width="109">Default</th><th>Description</th></tr></thead><tbody><tr><td><a href="_package.md">package</a></td><td>JSON object</td><td></td><td>Package information.</td></tr><tr><td><a href="data/">data</a></td><td>JSON object</td><td></td><td>Raw and analyzed data.</td></tr><tr><td><a href="pipelines/">pipelines</a></td><td>JSON object</td><td></td><td>Methods used to analyze the data.</td></tr><tr><td><a href="experiments.md">experiments</a></td><td>JSON object</td><td></td><td>Experimental methods used to collect the data.</td></tr><tr><td><a href="data-dictionary.md">data-dictionary</a></td><td>JSON object</td><td></td><td>Data dictionary containing descriptions, mappings, and key/value information for any variables in the package.</td></tr><tr><td><code>NumPipelines</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of pipelines.</td></tr><tr><td><code>NumExperiments</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Number of experiments.</td></tr><tr><td><code>TotalFileCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Total number of data files in the package, excluding .json files.</td></tr><tr><td><code>TotalSize</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Total size, in bytes, of the data files.</td></tr></tbody></table>

### Directory structure

Files associated with this object are stored in the following directory.

> `/`
