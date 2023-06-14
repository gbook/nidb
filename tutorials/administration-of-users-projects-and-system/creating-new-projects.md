# Creating new projects

{% hint style="info" %}
You must be an an NiDB administrator to create projects.
{% endhint %}

Navigate to the project administration section of NiDB. **Admin** --> **Front-end** --> **Projects**. Click the **Create Project** button. This will show the new project form.

<figure><img src="../../.gitbook/assets/image (3).png" alt=""><figcaption></figcaption></figure>

Fill out the information about the project. There isn't a lot of information required to create a project. Details such as templates, users, etc are created later. Descriptions of the fields:

<table><thead><tr><th width="170">Field</th><th>Description</th></tr></thead><tbody><tr><td>Name</td><td>Name displayed throughout NiDB.</td></tr><tr><td>Project number</td><td>This can be any string of letters or numbers. This is used to uniquely identify the project and is used to automatically archive DICOM series into the correct project. If you don't have an IRB approval or cost-center number, enter a string of the format P1234ABC, where 1234 and ABC are random characters.</td></tr><tr><td>Use custom IDs</td><td>By default, NiDB IDs (S1234ABC format) are used. If you want to use your own IDs (for example 401, 402, 403, etc) check this box. The NiDB UIDs will still be assigned, but your custom ID will be displayed in place of the UID in most places in the system.</td></tr><tr><td>Instance</td><td>NiDB can contain multiple <em>instances</em>, or "project groups"</td></tr><tr><td>Principle Investigator</td><td>The PI for the project. This selection is only used for display purposes and does not create any special permissions,</td></tr><tr><td>Administrator</td><td>The admin for the project. This selection is also only used for display purposes and does not create any special permissions.</td></tr><tr><td>Start date</td><td>IRB start-date of the project</td></tr><tr><td>End date</td><td>IRB end-date of the project</td></tr><tr><td>Copy Settings</td><td><strong>This option can be used after a project is created.</strong> This would copy settings (templates, data dictionary, connections, mappings) from another project.</td></tr></tbody></table>

Once you've fill out the information, click **Add** and the project will be created. No users will have permissions to access this project. Follow the [Adding Users to Projects](../adding-users-to-projects.md) to add user permissions.

### Related articles

* [Front-end administration](../../using-nidb/administration/front-end-user-facing.md)
* [Adding users to projects](../adding-users-to-projects.md)
