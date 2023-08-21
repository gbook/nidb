---
description: JSON array
---

# measures

Measures are observations collected from a participant in response to an experiment.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptkj1rwzAQhv-KURYFHMjgLip0apdSWqhXQ7la50SNZAt90JiQ\_96TazkljQfdY99jveLsE2sHiUywnQO7L17em76gyw1D4M\_12-tE683mQUIAnpb1\_UWh5x8W2gPskGe46ltlUasePV\_oysCjRacM9sHzP5ytlEmWj59f2JKSIffzfXJClIqS5nrDMAg-OlIy3HCkizvPp3Xp\_m6YIuh4KWEq\_9vQgx698jzDokwvpHmAA0PDmEruLqOZB11bbHmGS8qoscgHLzqltVh1Hd5tt6UPbjigWFVVNfPmW8mwF5U9spIZdAaUpM98Sns1LOzRYMMEocQOog4Na\_ozqdFSLj5JFQbHRAfaY8kghqEe-5aJ4CJm6VEB\_TVmts4\_TXPHsw)

### JSON variables

<mark style="color:red;">\*required</mark>

|      _**Variable**_ | **Type** | **Description**                                     |
| ------------------: | -------- | --------------------------------------------------- |
| _\***measureName**_ | string   | Name of the measure                                 |
|   _\***dateStart**_ | datetime | Start date/time of the measurement                  |
|           _dateEnd_ | datetime | End date/time of the measurement                    |
|    _instrumentName_ | string   | Name of the instrument associated with this measure |
|             _rater_ | string   | Name of the rater                                   |
|             _notes_ | string   |                                                     |
|       _\***value**_ | string   | Value (string or number)                            |
|       _description_ | string   | Longer description of the measure                   |
