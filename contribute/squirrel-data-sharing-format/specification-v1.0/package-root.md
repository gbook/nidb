# Package root

The package root contains all data and files for the package. The JSON root contains all JSON objects for the package.

<figure><img src="https://mermaid.ink/img/pako:eNptks1qwzAQhF_FKBcZEsjBvajQU3sppYXmaihba50okSWhH5oQ8u5dOZZpSXzQjphPGjP2mXVWIhNs68HtqrfP1lT07IM13trIXzcf71VW9Wr1JCECz0v9nyLry0F3gC3yIm4RpxxqZTDwWd1CeHTo1YAmBv5HT2DOJiik7z12RBRRP179ss9MTFJR1jTvEANCSJ6QIu4w0qdt4OM6u9cLcwS9XU4Yx60NBvQpqMCLmJHxQG4EPAxUxziKO5czFR4cdmPpGxJ1CTlpnGureqW1WPQ9PqzXyxC9PaBYNE0z6dWPknEnGndkSzagH0BJ-uLnfFfL4g4HbJkgKbGHpGPLWnMhNDmKxRepovVMRJ9wySBFuzmZruyvzLMC-n8GJnrQAS-_GUDNYg?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

| _**Variable**_ | **Type**    | **Description**                               |
| -------------: | ----------- | --------------------------------------------- |
|  _\*\_package_ | JSON object | Package information                           |
|         _data_ | JSON object | Raw and analyzed data                         |
|      pipelines | JSON object | Methods used to analyze the data              |
|    experiments | JSON object | Experimental methods used to collect the data |

### Directory structure

Files associated with this object are stored in the following directory.

> `/`
