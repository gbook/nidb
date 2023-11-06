# Package root

The package root contains all data and files for the package. The JSON root contain all JSON objects for the package.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptks1qwzAQhF8lKBcFEsjBvajQU3sppYX6aihba52okWShHxoT8u5duZZT0vigHXs-aczYJ9b2EplgOw9uv3h5b-yCLt\_3kT\_Xb6-jWm02DxIi8Lys7i8IPf9w0B5gh7yIK98ph1pZDHxWVwQeHXpl0MbA\_-hC5UyiQvr8wpaQIopf7jMTk1SUNM0bhEEIyRNSxA1G-rQLfFxn9\_fAHEGvlxPG8d8GC3oIKvAiZmTckPsAD4bKGEdx52qmomuHLS\_ikjJoHEtbdEprsew6vNtu1yH6\_oBiWVXVpDffSsa9qNyRrZlBb0BJ-sSnfE7D4h4NNkyQlNhB0rFhjT0Tmhxl4pNUsfdMdKADrhmk2NeDbZmIPmGBHhXQH2Mm6vwDCvzGEg)

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
