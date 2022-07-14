---
description: JSON array
---

# experiments

Experiments describe how data was collected from the participant. In other words, the methods used to get the data. This does not describe how to analyze the data once it’s collected.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptks1qwzAQhF\_FKBcFHMjBvajQU3sppYXmaihba-0okWyhH5oQ8u5duZYT0vigHXs-aczYJ9YMEplgnQO7Ld4-676gyw1D4K-bj\_dRLVerJwkBeFqWjxeEnn9ZaPbQIc\_ixrfKolY9ej6rGwIPFp0y2AfPr3SmUiZRPn7vsCEki-zn-8SEKBUlTfMOYRB8dIRkcYeRLnaej-vs\_h2YIuj1UsI4\_tvQgz565XkWMzJuSH2AA0NljCO7czVT0RuLDc\_iknLUWFw1VLRKa7FoW3xYr0sf3LBHsaiqatKrHyXDVlT2wEpm0BlQkr70KR1Xs7BFgzUTJCW2EHWoWd2fCY2WovFFqjA4JlrQHksGMQybY98wEVzEDD0roB\_HTNT5F9DryQI)

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
