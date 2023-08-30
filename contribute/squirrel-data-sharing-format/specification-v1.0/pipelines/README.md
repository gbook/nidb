---
description: JSON array
---

# pipelines

Pipelines are the methods used to analyze data after it has been collected. In other words, the experiment provides the methods to collect the data and the pipelines provide the methods to analyze the data once it has been collected.

Basic pipeline information is stored in the main `squirrel.json` file, and complete pipeline information is stored in the pipeline subdirectory in the `pipeline.json` file.

<figure><img src="https://mermaid.ink/img/pako:eNqVVFFvmzAQ_iuRq0hEgohENCWu1KfuZZo2aX2beLnhI_EKGNlGC4vy32cbTALtQ-sH-zvu--7OdzJnkguGhJKDhOa4-PYzqxdmSSF0FD01kL_CAYPhXD1evcHXlx_fHVoZIgMNgd1uKTYAb7DkNapgRDMGnhqUvMJaq-AGz1g2NOO5djkii7ioQXarnuW-Rk-q_f0HcxPIAx9l8B-kaBuooewUV4GzIm96qpfacLpl3JQ-nO8wKgTVSkPx4B0Ok-1BBW4fvX1Am8Lc12Zwx1v3WOu8yuWyl0RrOyQJlSp4aedkoSe9pdo-WKKazGq5vGm8pV3Nnny1F-7DyuvGobo6BqPXeGum8BexAo97gbcmgvEKuitxMZZvOSW9K4oiNN2S4hUjBuoIUkJHt1PRJMtnhLMufEY6acVHhDP5ONGPaKcpr-nwPo7DXkTvkiQZcPSXM32kSXMiIalQVsCZef9nGywj-ogVZoQayLCAttQZyeqLobaNaT9-YVwLSWgBpcKQQKvFS1fnhGrZoic9czC_k2pkmTf3S4iJTeiZnAiNQ9IRuo13612aPKS7dPOwTfdJegnJP6eI1_t-pff7zWa3TdPLf80tlNs?type=png" alt=""><figcaption></figcaption></figure>

### JSON Variables

\*required

<table data-header-hidden><thead><tr><th align="right"></th><th width="150"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><em>clusterType</em></td><td>string</td><td>Compute cluster engine (sge or slurm)</td></tr><tr><td align="right"><em>completeFiles</em></td><td>JSON array</td><td>JSON array of complete files, with relative paths to analysisroot</td></tr><tr><td align="right"><em>createDate</em></td><td>datetime</td><td>Date the pipeline was created</td></tr><tr><td align="right"><em>dataCopyMethod</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>depDir</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>depLevel</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>depLinkType</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>description</em></td><td>string</td><td>Longer pipeline description</td></tr><tr><td align="right"><em>dirStructure</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>directory</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>group</em></td><td>string</td><td>ID or name of a group on which this pipeline will run</td></tr><tr><td align="right"><em>groupType</em></td><td>string</td><td>Either subject or study</td></tr><tr><td align="right"><em>*level</em></td><td>number</td><td>subject-level analysis (1) or group-level analysis (2)</td></tr><tr><td align="right"><em>maxWallTime</em></td><td>number</td><td>Maximum allowed clock (wall) time in minutes for the analysis to run</td></tr><tr><td align="right"><em>*name</em></td><td>string</td><td>Pipeline name</td></tr><tr><td align="right"><em>notes</em></td><td>string</td><td>Extended notes about the pipeline</td></tr><tr><td align="right"><em>numConcurrentAnalyses</em></td><td>number</td><td>Number of analyses allowed to run at the same time. This number if managed by NiDB and is different than grid engine queue size.</td></tr><tr><td align="right"><em>resultScript</em></td><td>string</td><td>Executable script to be run at completion of the analysis to find and insert results back into NiDB</td></tr><tr><td align="right"><em>submitDelay</em></td><td>number</td><td> </td></tr><tr><td align="right"><em>submitHost</em></td><td>string</td><td>Hostname of the SGE or slurm submit node</td></tr><tr><td align="right"><em>tmpDir</em></td><td>string</td><td> </td></tr><tr><td align="right"><em>useProfile</em></td><td>bool</td><td> </td></tr><tr><td align="right"><em>useTmpDir</em></td><td>bool</td><td> </td></tr><tr><td align="right"><em>version</em></td><td>number</td><td>Version of the pipeline</td></tr><tr><td align="right"><em>dataSpec</em></td><td>JSON array</td><td>See <a href="dataspec.md">data specifications</a></td></tr><tr><td align="right"><em>primaryScript</em></td><td>string</td><td>See details of <a href="pipeline-scripts.md">pipeline scripts</a></td></tr><tr><td align="right"><em>secondaryScript</em></td><td>string</td><td>See details of <a href="pipeline-scripts.md">pipeline scripts</a></td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. `pipelineName` is the unique name of the pipeline.

> `/pipelines/pipelineName`
