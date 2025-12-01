---
description: JSON array
---

# observations

Observations are **collected from** a participant in response to an experiment.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFvgjAUx78KqSHBRBazsAtLPG2XZdmSeVu4POlDOoGStmwS43dfSykKelAO9P3b37-vfU85kJRTJDHZCqhz7_0rqTz9CM5V8Lb-_OiieRiuKCgIzGv-fEL0fA3pDrYY9ON0ldVYsAplMEQTAvc1ClZipWRwFk8okzikLFWMVyDaYKLnFu5mw9VW8KaGCopW6sSd8px0-_aobDY_mOrULnDrThtGNZTpjfrxCsE3EsUvmMPI4FxcYVml9LK-YgeP1EDbRCa1LofJ3A2Xy_ZSTAYucIjvW0v4YBokoJQZK0yPTOigS9QUxYBy1GjfP-uLwU7SwiftdRNz5xt63p2jF9bj1MThLmIMLrYGp0aG4QqqLdAbjm-YIp5lWbbQ1RJ8hyEFmYMQ0MaPY9Moyz3GSRXusY5KcYtxYh86eovXes5_lYMLn5bLhfXFsyiK-jj8Y1TlcVTvyYKUKEpgVH8hDma_hKgcS0xIrEOKGTSFSkhSHTXa1LoD-EqZ4oLEGRQSFwQaxddtlZJYiQYd9MJAf3DKgdJ_12_OnT7-A5W5lBM?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required

<table data-full-width="true"><thead><tr><th width="217.35296740841875" align="right">Variable</th><th width="126">Type</th><th width="106">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>DateEnd</code></td><td>datetime</td><td></td><td>End datetime of the observation.</td></tr><tr><td align="right"><code>DateRecordCreate</code></td><td>datetime</td><td></td><td>Date the record was created in the current database. The original record may have been imported from another database.</td></tr><tr><td align="right"><code>DateRecordEntry</code></td><td>datetime</td><td></td><td>Date the record was first entered into a database.</td></tr><tr><td align="right"><code>DateRecordModify</code></td><td>datetime</td><td></td><td>Date the record was modified in the current database.</td></tr><tr><td align="right"><code>DateStart</code></td><td>datetime</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Start datetime of the observation.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td></td><td>Longer description of the measure.</td></tr><tr><td align="right"><code>Duration</code></td><td>number</td><td></td><td>Duration of the measure in seconds, if known.</td></tr><tr><td align="right"><code>InstrumentName</code></td><td>string</td><td></td><td>Name of the instrument associated with this measure.</td></tr><tr><td align="right"><code>ObservationName</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Name of the observation.</td></tr><tr><td align="right"><code>Notes</code></td><td>string</td><td></td><td>Detailed notes.</td></tr><tr><td align="right"><code>Rater</code></td><td>string</td><td></td><td>Name of the rater.</td></tr><tr><td align="right"><code>Value</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Value (string or number).</td></tr></tbody></table>

