---
description: JSON array
---

# group-analysis

This object is an array of group analyses. A group analysis is considered an analysis involving more than one subject.

<figure><img src="https://mermaid.ink/img/pako:eNqVlFFvgjAQx78KqTGBBBazuBeW-LS9LMuWzLeFl5Me0gmUtGWTGL_7WqBV0Af1wd6__f253l3gQFJOkcRkK6DOvfevpPL0T3Cu_Lf150cXBVG0oqDAN3_B8wnR-zWkO9iiP6zTU1ZjwSqUvosmBO5rFKzESkn_LJ5QJnFEWaoYr0C0_kQHPdztRqut4E0NFRStZNLvlGelfe6Aymbzg6lObQN7brVhVEOZrmBYrxB8I1H8grmM9M_FFZZVSh_rEjt4pBzdJzKpdTtM5m65PHY1Tqubz3tL9GAGJKCUGSvMjExooUvUNMWAcjTo-fxsLgY7yR4-aa_bCKzPzby7xyB6j1UThy3EGGzcG6waGVwJqi3Qc9c3TBHPsiwLdbcE32FEQeYgBLTx49g0ynKPcdKFe6yjVtxinNjdRG_x9p7RO-Fs-LRYhL0xni2XyyGO_hhVebys9yQkJYoSGNWfiIN5YEJUjiUmJNYhxQyaQiUkqY4abWo9AnylTHFB4gwKiSGBRvF1W6UkVqJBC70w0F-c0lH6bt-cW338B1iulI0?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required

<table data-full-width="true"><thead><tr><th width="221.33333333333331" align="right">Variable</th><th width="132">Type</th><th width="98">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>Datetime</code></td><td>datetime</td><td></td><td>Datetime of the group analysis.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td></td><td>Description.</td></tr><tr><td align="right"><code>FileCount</code></td><td>number</td><td></td><td>Number of files in the group analysis.</td></tr><tr><td align="right"><code>GroupAnalysisName</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Name of this group analysis.</td></tr><tr><td align="right"><code>Notes</code></td><td>string</td><td></td><td>Notes about the group analysis.</td></tr><tr><td align="right"><code>Size</code></td><td>number</td><td></td><td>Size in bytes of the analysis.</td></tr><tr><td align="right"><code>VirtualPath</code></td><td>string</td><td></td><td>Path to the group analysis data within the squirrel package.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory, where \<GroupAnalysisName> is the name of the analysis.

> `/group-analysis/<GroupAnalysisName>/`
