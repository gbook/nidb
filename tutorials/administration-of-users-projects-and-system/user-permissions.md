# User Permissions

Each user can have many different permissions. Below are the description of each permission.

| Permission                   | Description                                                                                                                        | How to grant                                                                                        |
| ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Site admin                   | <ul><li>Edit NiDB settings file</li><li>Manage any aspect of the system.</li><li>All permissions available to NiDB admin</li></ul> | Editing the `users` table in Mariadb and changing the `user_issiteamin` column to `1` for that user |
| NiDB admin                   | <ul><li>Manage (add, edit, remove) projects and users</li><li>Can view the Admin page</li></ul>                                    | Admin --> Users                                                                                     |
| Project admin                | <ul><li>Add subjects to a project</li><li>Enroll subject in project</li></ul>                                                      | Admin --> Users --> Project permissions                                                             |
| Imaging data (project level) | <ul><li>View, upload, delete, modify imaging data, including dates of service</li></ul>                                            | Admin --> Users --> Project permissions                                                             |
| PHI/PII (project level)      | <ul><li>View, modify PHI, PII</li></ul>                                                                                            | Admin --> Users --> Project permissions                                                             |
| Read only (project level)    | <ul><li>View or download imaging and PHI/PII</li></ul>                                                                             | Doesn't exist yet                                                                                   |

