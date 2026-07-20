# NiDB Tutorial: Logging In and Viewing a Subject

**NiDB (Neuroinformatics Database)** is a web-based platform for storing and managing neuroimaging and research data. This tutorial walks through logging in and viewing a subject record.

---

## Step 1: Navigate to NiDB

Open your browser and go to your NiDB instance (e.g., `http://ado2dev`). You will be presented with the login page.

![NiDB Login Page](screenshots/01-login.png)

The login page displays the NiDB logo and two fields:
- **Username** — your account username
- **Password** — your account password

---

## Step 2: Log In

1. Enter your **Username** in the first field.
2. Enter your **Password** in the second field.
3. Click the **Login** button.

> If your credentials are correct, you will be redirected to the NiDB home page.

---

## Step 3: The Home Page

After a successful login, you will land on the NiDB home dashboard.

![NiDB Home Page](screenshots/02-home.png)

The home page shows:
- **Summary statistics** — total number of subjects, studies, and series in the system
- **New Studies** — imaging studies collected in the past 5 days
- **Favorite Projects** — projects you have starred for quick access
- **Recently accessed subjects and studies** — a quick way to jump back to recent work

The top navigation bar provides access to all major sections:
| Menu Item | Description |
|-----------|-------------|
| **Home** | Returns to this dashboard |
| **Search** | Search across subjects, studies, and data |
| **Subjects** | Browse and manage research subjects |
| **Projects** | View and manage research projects |
| **Pipelines** | Configure data processing pipelines |
| **Data** | Import data |
| **Calendar** | View scheduled events |

---

## Step 4: Navigate to Subjects

Click **Subjects** in the top navigation bar to open the Subjects search page.

![Subjects Search Page](screenshots/03-subjects-search.png)

The Subjects page displays a search form with the following filters:
- **UID** — the unique subject identifier (e.g., `S1234ABC`)
- **Alternate UID** — any alternate identifier on file
- **Name** — subject name
- **Sex** — M, F, O, or U
- **DOB** — date of birth in YYYY-MM-DD format
- **Active?** — filter to show only active subjects (checked by default)

---

## Step 5: Search for a Subject

1. Enter search criteria in one or more fields. For example, type a known UID into the **UID** field.
2. Click the **Search** button on the right.

The results table will appear below the search form, listing matching subjects with their UID, Alternate UID, Name, Sex, DOB, enrolled Projects, active status, and last activity date.

![Subject Search Results](screenshots/03-subjects-search.png)

> **Tip:** You can also search by UID directly from any page using the **Search by UID** field in the top-right corner of the screen.

---

## Step 6: View a Subject

Click on a subject's **UID link** (e.g., `S0622AKK`) in the search results to open their detail page.

![Subject Detail Page](screenshots/04-subject-detail.png)

The subject detail page is divided into two panels:

### Left Panel — Subject Information
- **Subject UID** displayed prominently at the top, with arrow buttons to navigate to the previous/next subject
- **Demographics** — date of birth, gender, alternate UIDs, ethnicity, handedness, education, GUID, and contact status
- **Family** — family linkage information
- **Admin Operations** — administrative actions for the subject
- **Edit subject** button — opens the subject record for editing

### Right Panel — Enrollments
Shows all projects this subject is enrolled in. For each enrollment you can see:
- **Project name** — click to open the project
- **ID(s)** — project-specific identifiers
- **Group** — the subject's group within the project (e.g., patient, control)
- **Enroll date** — when the subject was enrolled
- **Tags** — any tags applied to this enrollment

Each enrollment also lists associated data:
- **Imaging Studies** — MRI, fMRI, and other imaging data
- **Observations** — recorded measurements and data points
- **Interventions** — any interventions recorded
- **Diagnosis** — diagnosis information

Additional actions available from the enrollment panel:
- **View Enrollment** — detailed enrollment view
- **Add to Package** — bundle this subject's data for export
- **View Timeline** — chronological view of subject activity
- **View Imaging Summary** — summary of all imaging data

---

## Logging Out

When finished, click **Logout** in the top-right corner of the navigation bar. You will be returned to the login page with a confirmation message.

---

*NiDB v2025.12.1330*
