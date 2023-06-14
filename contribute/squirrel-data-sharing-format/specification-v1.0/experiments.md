---
description: JSON array
---

# experiments

Experiments describe how data was collected from the participant. In other words, the methods used to get the data. This does not describe how to analyze the data once it’s collected.

<figure><img src="https://mermaid.ink/img/pako:eNqVVFFvmzAQ_iuRq0hEgohENCWu1KfuZZo2aX2beLnhI_EKGNlGC4vy32cbTALtQ-sH-zvu--7Od8JnkguGhJKDhOa4-PYzqxdmSSF0FD01kL_CAYPhXD1evcHXlx_fHVoZIgMNgd1uKTYAb7DkNapgRDMGnhqUvMJaq-AGz1g2NOO5djkii7ioQXarnuW-Rk-q_f0HcxPIAx9l8B-kaBuooewUV4GzIm96qpfacLpl3JQ-nO8wKgTVSkPx4B0Ok-1BBW4fvX1Am8Lc12Zwx1v3WOu8yuWyl0RrOyQJlSp4aedkoSe9pdo-WKKazGq5vGm8pV3Nnny1F-7DyuvGobo6BqPXeGum8BexAo97gbcmgvEKuitxMZZvOSW9K4oiNN2S4hUjBuoIUkJHt1PRJMtnhLMufEY6acVHhDP5ONGPaOfVXhPifRyHvYzeJUky4OgvZ_pIk-ZEQlKhrIAz8wKcbbiM6CNWmBFqIMMC2lJnJKsvhto2ZgD4hXEtJKEFlApDAq0WL12dE6pli570zME8KNXIMn_dLyEmNqFnciI0DklH6DberXdp8pDu0s3DNt0n6SUk_5wiXu_7ld7vN5vdNk0v_wE57JXG?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th align="right"></th><th width="150"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><em>*experimentName</em></td><td>string</td><td>Unique name of the experiment</td></tr><tr><td align="right"><em>*numFiles</em></td><td>number</td><td>Number of files contained in the experiment</td></tr><tr><td align="right"><em>*size</em></td><td>number</td><td>Size in bytes of the experiment files</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. Where `experimentName` is the unique name of the experiment.

> `/experiments/experimentName`
