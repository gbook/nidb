# Creating new projects

{% hint style="info" %}
You must be an an NiDB administrator to create projects.
{% endhint %}

Navigate to the project administration section of NiDB. **Admin** --> **Front-end** --> **Projects**. Click the **Create Project** button. This will show the new project form.

<figure><img src="../../.gitbook/assets/image.png" alt=""><figcaption></figcaption></figure>

Fill out the information about the project. There isn't a lot of information required to create a project. Details such as templates, users, etc are created later. Descriptions of the fields:

| Field                  | Description                                                                                                                                                                                                                                                                                                       |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Name                   | Name displayed throughout NiDB.                                                                                                                                                                                                                                                                                   |
| Project number         | This can be any string of letters or numbers. This is used to uniquely identify the project and is used to automatically archive DICOM series into the correct project. If you don't have an IRB approval or cost-center number, enter a string of the format P1234ABC, where 1234 and ABC are random characters. |
| Use custom IDs         | By default, NiDB IDs (S1234ABC format) are used. If you want to use your own IDs (for example 401, 402, 403, etc) check this box. The NiDB UIDs will still be assigned, but your custom ID will be displayed in place of the UID in most places in the system.                                                    |
| Instance               | NiDB can contain multiple _instances_, or "project groups"                                                                                                                                                                                                                                                        |
| Principle Investigator | The PI for the project. This selection is only used for display purposes and does not create any special permissions,                                                                                                                                                                                             |
| Administrator          | The admin for the project. This selection is also only used for display purposes and does not create any special permissions.                                                                                                                                                                                     |
| Start date             | IRB start-date of the project                                                                                                                                                                                                                                                                                     |
| End date               | IRB end-date of the project                                                                                                                                                                                                                                                                                       |
| Copy Settings          | **This option can be used after a project is created.** This would copy settings (templates, data dictionary, connections, mappings) from another project.                                                                                                                                                        |

Once you've fill out the information, click **Add** and the project will be created. No users will have permissions to access this project. Follow the [Adding Users to Projects](../adding-users-to-projects.md) to add user permissions.

### Related articles

* [Front-end administration](../../using-nidb/administration/front-end-user-facing.md)
* [Adding users to projects](../adding-users-to-projects.md)
