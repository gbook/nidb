---
description: Tutorial on how to create reports using Analysis Builder
---

# Analysis Builder

### Reports in Analysis Builder

Analysis builder is a report generating tool in NiDB. In Analysis builder, a report can be build using variables extracted from various types of imaging data, pipelines and cognitive measures. This tool is different than the search tool where you can search stored data and download it. In this tool you can search the variables those are generated and stored or imported in the NiDB (Example: You can query the variables generated from a task using MRI / EEG data OR variables imported from Redcap). Analysis builder can be invoked from a project's main page by selecting the option **Analysis Builder** on the right from **Tools** section. Following is the main interface of the **Analysis Builder**

![](<../.gitbook/assets/image (2) (2).png>)

The interface for Analysis Builder is self explanatory. The main sections consists of selecting a project from the dropdown list, selecting the desired variables, drugs / dose information, choosing the various report parameters like grouping, value replacing a blank entry, and finally the output format of the report.&#x20;

In the next section the steps to create two reports are listed showing how various options can be employed to create a desired report.&#x20;

### Building Reports

Analysis builder is designed to create reports based on variables that can be selected from different types of measures, and modalities shown in the Analysis Builder above. This includes modalities like MR, EEG, cognitive and biological measurements.

### **Simple Report**

Following are the steps to create a simple report where data is not grouped and there is no drug / dose variable is used. The following figure shows our selection of variables and settings to generate this report.

![](<../.gitbook/assets/image (6).png>)

### Steps - Simple Report

1. To generate a report, select a project from the drop-down menu from the top of the screen.
2. Select the variables for any one or combination of modalities and measures. We chose four cognitive variables those are imported from Redcap.
3. Choose if you want to group data on the base of date, or measure. We are not choosing this option in this simple report.
4. The output of a report can be control by various option like:
   * [ ] Selection of all subjects data regardless of their data presence
   * [ ] What should be displayed for a blank value, and missing values
   * [ ] inclusion of, event duration, time, date, subject's height, weight and date of birth
5. Select the output report format, showing it on the screen or saving it as csv file.
6. Hit the **Update Summary** button to generate the final report as shown on the right section of the screen below.

![](<../.gitbook/assets/image (6) (2).png>)

### Dose-based Report

This is a report that involve the variables based on dose / drug information and we want the variables to display with the time since dose. For this type of report, usually daily repeated measure are the one that needed to be displayed.

![](<../.gitbook/assets/image (4).png>)

### Steps - Dose-based Report

1. L1
2. L2

![](../.gitbook/assets/image.png)
