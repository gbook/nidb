---
description: JSON array
---

# pipelines

Pipelines are the methods used to analyze data after it has been collected. In other words, the experiment provides the methods to collect the data and the pipelines provide the methods to analyze the data once it has been collected.

<figure><img src="https://mermaid.ink/img/pako:eNptklFPwyAQx79Kg1nCktYspr7UZE_6Yowm7s305VauK64FAlTXLPvuQjtw1vWh_I_7HX8OOJJKMiQF2WlQTfLyXorEfVpKS583b6-jWmbZmoEF6n_Lh1_EzSuo9rBDeh7nWa6w5QINjWpG4EGh5h0Ka-iFnlHeOGO8slwK0AOdxcsJHmez9U7LXoGAdjDc0DFKQhjWPaOm335i5ayDCPkQe8b2jLsOzuMVQm4N6i_wmzH0MrjCcmFd2rU4wn-iSE9G3todh3ceh__p2OO8u8ViKslu_QVp6EzNW39HXgYo3oin_HkYi8pMRzvKAAbbocUkcolbsS1u6rpOXUrLPWYMTANaw1DcXZZEn1iC96tVOhUVN3men3X2zZltilwdSEo61B1w5l7m0S9WEttghyUpnGRYQ9_akpTi5NBeuV3hE-NWalLU0BpMCfRWbgZRkcLqHgP0yME99C5S7pl8SBni0w9HOA2R?type=png" alt=""><figcaption></figcaption></figure>

### JSON Variables

:blue\_circle: Primary key\
:red\_circle: Required\
:yellow\_circle: Computed (squirrel writer/reader should handle these variables)

{% include "../../../../.gitbook/includes/pipelines.md" %}

### Directory structure

Files associated with this section are stored in the following directory. `PipelineName` is the unique name of the pipeline.

> `/pipelines/<PipelineName>`
