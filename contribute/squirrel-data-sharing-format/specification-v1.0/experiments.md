---
description: JSON array
---

# experiments

Experiments describe how data was collected from the participant. In other words, the methods used to get the data. This does not describe how to analyze the data once it’s collected.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhZ7aS1l2YXtbDGU2Gidq_YUks_GG_PeV7EiJsz20OkjvSM-rkUboAJuaEzDYSmx2k28_s2pimqxrHTy__Pjeq1kUPXDUGNhudn9GzPxrg5t33FLgxNV6IxoqREUq8OqKoH1DUpRUaRVcaEfZnIZS7e832hjECbfuYsvolguT6TR-QJSEqpUGceIDhst2q4K-96vDhjaFOZ7N0A__L2OFRaeECpzwSG-I5qYgKLFUuSgoGKRDptMzZC9tETUq-XR6UR-LncMBPseTfmLmfL72_QlOweBx0ZXDXcAanB4MLhoZ_BV0V9DEH98yBbvJ8zw0VZL1O0Uc1Q6lxI4tx6ZRlq8Yr6rwFeuoFJ8xnmz-DT_juXRePKC30m0ch4OZ3SRJctLRH8H1jiXNHkIoSZYouPmoB7tdBnpHJWXAjOSUY1voDLLqaNC2MeWnJy50LYHlWCgKAVtdv3TVBpiWLTnoUaD596WnGqx-1fUoBnaAPbA4hA7YMl7NV2lyl67Sxd0yXSfpMYS_vSOer4eW3q4Xi9UyTY__AKwJeB0?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|     _**Variable**_ | **Type** | **Description**                             |
| -----------------: | -------- | ------------------------------------------- |
| _\*experimentName_ | string   | Unique name of the experiment               |
|       _\*numFiles_ | number   | Number of files contained in the experiment |
|           _\*size_ | number   | Size in bytes of the experiment files       |

### Directory structure

Files associated with this section are stored in the following directory. Where `experimentName` is the unique name of the experiment.

> `/experiments/experimentName`
