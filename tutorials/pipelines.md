---
description: Various pipeline tutorials
---

# Pipelines

The pipeline system is an automated system to analyze imaging data stored within NiDB. Pipelines can be chained together in parent/child configurations with multiple parents and multiple children. Organizing the pipelines can take some planning, but complex pipeline systems can be created using NiDB.

{% hint style="info" %}
Pipelines are run on the **study** level. Every analysis is based on a single imaging study (S1234ABC1)

Your pipeline may pull data from multiple studies, but each analysis will only be associated with one imaging study. Think of it as the "IRB of record"; data may come from many studies, but only one study is the study of record. Therefor all results, statuses, and pipeline logs are associated with just one imaging study.
{% endhint %}

## Common pipeline configurations

### Single study, single pipeline

This configuration starts off with a single imaging study, and a single pipeline. An example is a single T1 image which is passed through a freesurfer pipeline.

<figure><img src="../.gitbook/assets/image (12) (1).png" alt=""><figcaption><p>Simple pipeline example</p></figcaption></figure>

Here's a sample pipeline specification for the above scenario

**Pipeline: Data & Scripts - Options**\
****Pipeline dependency --> Criteria: study

**Pipeline: Data & Scripts - Data:**\
****T1 --> Output --> Data Source: Study

### Single study, multiple pipeline

This configuration gets data from a single imaging study, but passed it through one or more pipelines. An example is an fMRI task that requires structural processing as in the HCP pipeline: the fMRI stats require output from a freesurfer pipeline.

<figure><img src="../.gitbook/assets/image (13).png" alt=""><figcaption></figcaption></figure>

**Pipeline A: Data & Scripts - Options**\
****Pipeline dependency --> Criteria: study

**Pipeline A: Data & Scripts - Data**\
****Output --> Data Source: Study

**Pipeline B: Data & Scripts - Options**\
****Pipeline dependency --> dependency: pipeline A\
Pipeline dependency --> Criteria: study

### Multiple study, single pipeline

This configuration takes data from multiple studies and passes it through a single pipeline. An example is an fMRI task analysis that requires a T1 from a different study. The T1 comes from study A, and the fMRI task from study B.

<figure><img src="../.gitbook/assets/image (11) (1).png" alt=""><figcaption><p>In this example, Study1 is the study of record.</p></figcaption></figure>

In this example, Study1 is the 'study of record'. All analyses, statuses, and results are associated with Study1. Here's the pipeline settings to use in this example.

**Pipeline A - "Preprocessing1"**\
**Data & Scripts** tab:\
****Options --> Pipeline dependency --> Criteria: study\
Data (fMRI) --> Output --> Data Source: Study\
Data (T1) --> Output --> Data Source: Subject\
Data (T1) --> Output --> Subject linkage: Nearest in time

**Pipeline B - "Stats1"**\
**Data & Scripts** tab:\
****Options --> Pipeline dependency --> dependency: pipeline A\
Options --> Pipeline dependency --> Criteria: study

### Multiple study, multiple pipeline

This configuration takes data from multiple studies and uses multiple pipelines to analyze the data. This can come in multiple ways. Below are some examples of complex pipelines.

<figure><img src="../.gitbook/assets/image (6) (3).png" alt=""><figcaption><p>An HCP example</p></figcaption></figure>

In this example, the pipeline settings are the same as above. The only difference is that each analysis (each study) will pull fMRI from the study, and the T1 from 'somewhere'. For the studies that have a T1, it will come from there. For studies that don't have a T1, the T1 will come from the study nearest in time.

Here's the pipeline settings to use in this example.

**Pipeline A - "Preprocessing1"**\
**Data & Scripts** tab:\
****Options --> Pipeline dependency --> Criteria: study\
Data (fMRI) --> Output --> Data Source: Study\
Data (T1) --> Output --> Data Source: Subject\
Data (T1) --> Output --> Subject linkage: Nearest in time

**Pipeline B - "Stats1"**\
**Data & Scripts** tab:\
****Options --> Pipeline dependency --> dependency: pipeline A\
Options --> Pipeline dependency --> Criteria: study