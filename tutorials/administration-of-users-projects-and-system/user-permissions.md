# User Permissions

NiDB users can have many different permissions, from complete system administration to read-only access. Most users will fall into the project-based permissions. Below are the description of each permission. Protected health information (PHI) and personally identifiable information (PII) are both referred to as PHI below.

<table><thead><tr><th width="161.33333333333331">Permission</th><th>Description</th><th>How to grant</th></tr></thead><tbody><tr><td><strong>Read-only PHI</strong></td><td><ul><li>View lists of subjects/studies in project</li><li>View subject PHI</li></ul></td><td>Admin --> Users --> Project permissions</td></tr><tr><td><strong>Read-only imaging</strong></td><td><ul><li>All permissions from <strong>Read only PHI</strong></li><li>Search, view, download imaging</li></ul></td><td>Admin --> Users --> Project permissions</td></tr><tr><td><strong>Full PHI</strong></td><td><ul><li>All permissions of <strong>Read only PHI</strong></li><li>Modify PHI</li><li>Create or import assessment (measures, vitals, drugs) data</li></ul></td><td>Admin --> Users --> Project permissions</td></tr><tr><td><strong>Full imaging</strong></td><td><ul><li>All permissions of <strong>Read only imaging</strong></li><li>Download, upload, modify, delete imaging data</li><li>Create new imaging studies</li><li>Add, modify series notes</li><li>Add, modify series ratings </li></ul></td><td>Admin --> Users --> Project permissions</td></tr><tr><td><strong>Project admin</strong></td><td><ul><li>All permissions of <strong>Full imaging</strong> and <strong>Full PHI</strong></li><li>Enroll subject in project</li><li>Move subjects between projects</li><li>Move imaging studies between projects</li><li>Modify series (rename, move to new study, hide/unhide, reset QC)</li></ul></td><td>Admin --> Users --> Project permissions</td></tr><tr><td><strong>NiDB admin</strong></td><td><ul><li>All project-based permissions of <strong>Project admin</strong></li><li>Manage (add, edit, remove) projects and users</li><li>Can view the Admin page</li></ul></td><td>Admin --> Users</td></tr><tr><td><strong>Site admin</strong></td><td><ul><li>All non-project based permissions of <strong>NiDB admin</strong></li><li>Manage system settings</li><li>View system status &#x26; usage</li><li>Manage NiDB modules</li><li>Manage QC modules</li><li>Mass email</li><li>Manage backup</li><li>View error logs</li><li>Set system messages</li><li>View reports</li><li>Manage audits</li><li>Manage sites</li><li>Manage instances</li><li>Manage modalities</li><li>Access to "Powerful tools" on Projects --> Studies page</li><li>Manage all file I/O</li><li>All permissions available to NiDB admin</li></ul></td><td>Editing the <code>users</code> table in Mariadb and changing the <code>user_issiteadmin</code> column to <code>1</code> for that user</td></tr></tbody></table>
