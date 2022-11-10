---
description: NiDB's HIPAA compliance
---

# HIPAA Compliance

NiDB attempts to ensure HIPAA compliance, but is not completely compliant with all aspects of data privacy.

## HIPAA Identifiers

There are 18 types of personally identifiable information (from Health and Human Services [website](https://www.hhs.gov/hipaa/for-professionals/privacy/special-topics/de-identification/index.html#standard)). Data that _can be stored_ in NiDB is <mark style="color:orange;">**highlighted**</mark>.

* <mark style="color:orange;">**Names**</mark>
* All geographic subdivisions smaller than a state, including <mark style="color:orange;">**street address**</mark>, city, county, precinct, ZIP code, and their equivalent geocodes, except for the initial three digits of the ZIP code if, according to the current publicly available data from the Bureau of the Census:
  * The geographic unit formed by combining all ZIP codes with the same three initial digits contains more than 20,000 people; and
  * The initial three digits of a ZIP code for all such geographic units containing 20,000 or fewer people is changed to 000
* All elements of <mark style="color:orange;">**dates**</mark> (except year) for dates that are directly related to an individual, including birth date, admission date, discharge date, death date, and all ages over 89 and all elements of dates (including year) indicative of such age, except that such ages and elements may be aggregated into a single category of age 90 or older
* <mark style="color:orange;">**Telephone numbers**</mark>
* Vehicle identifiers and serial numbers, including license plate numbers
* Fax numbers
* Device identifiers and serial numbers
* <mark style="color:orange;">**Email addresses**</mark>
* Web Universal Resource Locators (URLs)
* Social security numbers
* Internet Protocol (IP) addresses
* <mark style="color:orange;">**Medical record numbers**</mark>
* Biometric identifiers, including finger and voice prints
* Health plan beneficiary numbers
* Full-face photographs and any comparable images
* Account numbers
* Any other unique identifying number, characteristic, or code, except as permitted by paragraph (c) of this section \[Paragraph (c) is presented below in the section “Re-identification”]; and
* Certificate/license numbers

## PHI on NiDB

The following pieces of information are stored on NiDB. Not all are required.

| Field                                   | Required?                                                                                   |
| --------------------------------------- | ------------------------------------------------------------------------------------------- |
| Name (First and Last)                   | **Required**. Field cannot be blank, but does not need to be the actual participant's name. |
| Address (street, city, state, zip)      | Not required                                                                                |
| Phone number                            | Not required                                                                                |
| Email address                           | Not required                                                                                |
| ID (unique ID)                          | **Required**. But this is not a medical record number                                       |
| Dates (dates of service, date of birth) | **Required**. Age-at-study is calculated from date of birth and date of service.            |

## Ways to reduce PHI exposure

