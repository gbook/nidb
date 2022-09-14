---
description: JSON array
---

# minipipeline

Mini-pipelines are simple pipelines for tasks such as extracting response times from behavioral files. They are meant to be simple and run on the NiDB server rather than being submitted to a cluster. They have very basic and not computationally intensive.

<figure><img src="../../../.gitbook/assets/image (2).png" alt=""><figcaption><p>JSON object hierarchy</p></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|           _**Variable**_ | **Type** | **Description**                                            |
| -----------------------: | -------- | ---------------------------------------------------------- |
| _**\*minipipelineName**_ | string   | Unique name of the minipipeline                            |
|         _**\*numFiles**_ | number   | Number of files contained in the experiment                |
|         **\*entryPoint** | string   | Script name that is the entry point into the mini-pipeline |

### Directory structure

Files associated with this section are stored in the following directory. Where `minipipelineName` is the unique name of the mini-pipeline.

> `/minipipelines/minipipelineName`
