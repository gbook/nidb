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

### Repetitive Measure Report

This is a report that involve the variables based on their repetitive nature. Also and we want the variables to display with respect to the dose administrator. Analysis builder will automatically creates a variable that holds the time since dose information. For this type of report, usually time repeated measure are the one that needed to be displayed.

![](<../.gitbook/assets/image (4).png>)

### Steps - Repetitive Measure Report

1. Select a project from the dropdown list of projects on the top of **Analysis Builder** interface.
2. Choose the variables to display in this report. As mentioned above this is a repetitive measures report, I chose  the variables that are repetitive in nature, collected multiple times in a day and on multiple days. Also these variables are collected before or after administration of drug.
3. To include the **drug / dose** information on this report, provide the drug related variable as shown in the above figure in green rectangle.  Dose in this case was administrated on three different days, and all three days were selected.
4. &#x20;The time since dose variables can be calculated and be displayed if the option Include **Time Since Dose** is selected as shown above. All three dose day variables are also selected as shown in the figure above. Also the time will be displayed in minutes as per above selection
5. To group the data based on drug days, check the **Group by Event Date** checkbox from **Grouping Option**.&#x20;
6. After choosing the output parameters, hit the **Update Summery** button that generates a report as shown in the figure below.&#x20;

![](../.gitbook/assets/image.png)
