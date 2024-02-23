---
description: JSON object
---

# Package root

The package root contains all data and files for the package. The JSON root contains all JSON objects for the package.

<div data-full-width="true">

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8bHMGZWcAsLoIEh3XHz7WbQL8wjOVZI8daR8JQeIxnH1eJ2Nvr78-O7UyoCUKBLZ1y1iE7AOataCjIKaEXDqQLAGWiWjGz2jbGrKSuXWSKxivCWiXw2U-5o8Sf37D5QmkRc-yzh_EFx3pCV1L5mMXJT40KPeatMpTZkpfRzfIRogUguDePEOQ4U-yMi9w-yQ0C5h9mtXcMPb6VDrvMrlcrAka3tIgjSyYrU9Jys99Ba1fbCgnJzVcnnTeItdwwG-xgv3YeV94VBdHWMweHw0c_iNWIPXg8FHE0PYguprWITyLVPju6qqYtMtwV8hoUQeiRCkx9upabLKZ4yzLnzGOmnFR4wzezjRj3gHj70ogYb7NI0HHt9lWTbq5C-j6oiz7oRi1IBoCKPm6p9tngKpIzRQIGwkhYroWhWoaC8G1Z3pPHyhTHGBcEVqCTEiWvGXvi0RVkKDh54ZMX-SJlDmuv3ifBIjfEYnhNMY9Qhv0916l2cP-S7fPGzzfZZfYvTPOdL1fnjy-_1ms9vm-eU_g66S1g?type=png" alt=""><figcaption></figcaption></figure>

</div>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden data-full-width="false"><thead><tr><th align="right">Variable</th><th width="149"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><a href="_package.md">package</a></td><td>JSON object</td><td>Package information.</td></tr><tr><td align="right"><a href="data/">data</a></td><td>JSON object</td><td>Raw and analyzed data.</td></tr><tr><td align="right"><a href="pipelines/">pipelines</a></td><td>JSON object</td><td>Methods used to analyze the data.</td></tr><tr><td align="right"><a href="experiments.md">experiments</a></td><td>JSON object</td><td>Experimental methods used to collect the data.</td></tr><tr><td align="right"><a href="data-dictionary.md">data-dictionary</a></td><td>JSON object</td><td>Data dictionary containing descriptions, mappings, and key/value information for any variables in the package.</td></tr><tr><td align="right"><code>NumPipelines</code></td><td>number</td><td>Number of pipelines.</td></tr><tr><td align="right"><code>NumExperiments</code></td><td>number</td><td>Number of experiments.</td></tr><tr><td align="right"><code>TotalFileCount</code></td><td>number</td><td>Total number of data files in the package, excluding .json files.</td></tr><tr><td align="right"><code>TotalSize</code></td><td>number</td><td>Total size, in bytes, of the data files.</td></tr></tbody></table>

### Directory structure

Files associated with this object are stored in the following directory.

> `/`
