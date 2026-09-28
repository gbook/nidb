# NiDB Feature Roadmap

Skeleton list of future large-scale features and changes. Each section will be expanded as planning progresses; where a detailed design document exists, it is linked.

## 1. Tiered Storage

Detailed design: [tiered-storage.md](tiered-storage.md)

## 2. Universal Image Import

Detailed design: [universal-import.md](universal-import.md)

## 3. Files on Disk Audit

## 4. AWS S3 Storage

## 5. Project Usage

## 6. PHI Audit

## 7. User Permissions

### User types

| User type | Description                                                                                                                                                                 | Where type is assigned |
| --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------- |
| siteadmin | Has permissions to manage all aspects of the NiDB system; such as managing sites, instances, modules, cleanup, storage tiers, DICOM endpoints, system settings, and backup. | SQL `users` table      |
| admin     | Can create projects and manage all aspects of projects, and users. Can assign admin permissions to other users.                                                             | **Admin** -> **Users** |
| user      | Has general permissions to manage subjects and data within a project                                                                                                        |                        |

### Per-project permissions

| Permission       | Description                                            |
| ---------------- | ------------------------------------------------------ |
| View PHI         | Can view PHI/PII and demographics                      |
| View Imaging     | Can view imaging data                                  |
| View non-imaging | Can view non-imaging data (observations/interventions) |
| Edit PHI         | Can edit PHI/PII and demographics                      |
| Edit imaging     | Can upload/edit/delete imaging data                    |
| Edit non-imaging | Can upload/edit/delete non-imaging data                |

## 8. 21 CFR Part 11 Compliance

Detailed analysis: [21-cfr-part-11-gap-analysis.md](21-cfr-part-11-gap-analysis.md)
