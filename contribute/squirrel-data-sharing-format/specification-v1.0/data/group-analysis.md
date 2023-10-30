---
description: JSON array
---

# group-analysis

This object is an array of group analyses. A group analysis is considered an analysis involving more than one subject.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8Hv8_xsQ_yGZWcAsLoIEh3XHz7WbQL8wjOVZI8daR8JQeIxnH1eJ2Nvr78-O7UyhgpUSSyr1uLTcA6qFkLMgpq5oBTB4I10CoZ3eiZy6amrFRujcQqxlsi-tXgcl-TJ6l__4HSJPLCZxnnD4LrjrSk7iWTkYsSH3qrR206pSkzpY_jO44GiNTCWLx4x0OFPsjIvcPskNAuYfZrV3DD2-lQ67zK5XJAkrVtkiCNrFht-2SlN7212nOwRjnp1XJ5c_DWdg0H8zVeuA8rz4WmujrGYGB8NCP8Rizg9QD4aAKELai-hkUo33pqfFdVVWxOS_BXSCiRRyIE6fF2Ck1W-Qw4O4XPoJOj-Ag4w0NHP8IOzOTfDhjcp2k8gPguy7JRJ38ZVUecdScUowZEQxg1d8DZJiyQOkIDBcJGUqiIrlWBivZirLozLYAvlCkuEK5ILSFGRCv-0rclwkpo8KZnRsyV0gSXqe0X55MY4TM6IZzGqEd4m-7Wuzx7yHf55mGb77P8EqN_jkjX--HJ7_ebzW6b55f_m-mWow?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th width="221.33333333333331" align="right"></th><th width="132"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><code>GroupAnalysisName</code></td><td>string</td><td>Name of this group analysis.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td>Description.</td></tr><tr><td align="right"><code>Notes</code></td><td>string</td><td>Notes about the group analysis.</td></tr><tr><td align="right"><code>Datetime</code></td><td>datetime</td><td>Datetime of the group analysis.</td></tr><tr><td align="right"><code>NumFiles</code></td><td>number</td><td>Number of files in the group analysis.</td></tr><tr><td align="right"><code>Size</code></td><td>number</td><td>Size in bytes of the analysis.</td></tr><tr><td align="right"><code>VirtualPath</code></td><td>string</td><td>Path to the group analysis data within the squirrel package.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory, where \<GroupAnalysisName> is the name of the analysis.

> `/group-analysis/<GroupAnalysisName>/`
