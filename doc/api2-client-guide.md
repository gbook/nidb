# NiDB API (v2) — Client Guide

This guide describes how to talk to the NiDB secure API (`api2.php`) from a
client, using either the **curl** command line or **C++ with libcurl**.

---

## 1. Overview

- **Endpoint:** a single script, `api2.php`, at the root of your NiDB web server.
- **Transport:** every request is an HTTP **POST**. `GET` and other methods are rejected.
- **Format:** parameters are sent as normal form fields; every response is **JSON**.
- **Auth:** token based. You authenticate once with an API key to receive a
  short‑lived **session ID**, then send that session ID with every other request.

### Base URL

| Environment | Base URL |
| --- | --- |
| Production | `https://YOUR-NIDB-HOST/api2.php` |
| Development | `http://YOUR-NIDB-HOST:8080/api2.php` |

> **HTTPS is required in production.** Plain `http://` requests are rejected with
> `403`. The development server (reached on port `8080`) is exempt so it can be
> tested over `http`.

### Response envelope

Every response is a JSON object with this shape:

```json
{
  "success": true,
  "message": "Human-readable status.",
  "data": { "...": "action-specific payload (present on most successful calls)" }
}
```

- `success` — boolean.
- `message` — a short, generic status string (never leaks internal detail).
- `data` — present when the action returns a payload; omitted otherwise.

Always branch on the **HTTP status code** and/or the `success` field, not on the
`message` text.

### HTTP status codes

| Code | Meaning |
| --- | --- |
| `200` | Success. |
| `204` | Returned for a CORS `OPTIONS` pre-flight (no body). |
| `400` | Missing or invalid parameter. |
| `401` | Authentication failed, or session missing/invalid/expired. |
| `403` | HTTPS required. |
| `405` | Method was not POST. |
| `415` | Uploaded file type not accepted. |
| `429` | Too many failed logins, or the per-user session limit was reached. |
| `500` | Server error, or an operation could not be completed. |

---

## 2. Getting an API key

1. Log in to NiDB and open **My Account**.
2. Click **Regenerate API Key**.
3. Copy the key that is shown — **it is displayed only once** and is stored
   only as a hash. If you lose it, regenerate a new one (which invalidates the old).

Your **API username** is your NiDB account username (the account must have API
access configured). You will pass the username as `user` and the key as `token`
to the `Authenticate` action.

---

## 3. Authentication flow

```
Authenticate(user, token)  ─────────────▶  { sessionID }
        │
        ▼
GetProjectList(sessionID, instanceID)  ──▶  { projects }
StartTransaction(sessionID)            ──▶  { transactionID }
...                                        (use sessionID for every call)
```

- A session ID is **128 characters**. Store it in memory for the life of the client run.
- A session **expires after 10 minutes of inactivity**. Each successful
  authenticated request slides that window forward.
- Each API user may hold at most **2 concurrent sessions**. A 3rd `Authenticate`
  returns `429` until an existing session expires.
- After too many failed logins from one IP (10 within 15 minutes), further
  attempts are throttled with `429`.

---

## 4. Action reference

Unless noted, every action requires `sessionID` and returns its payload under `data`.

### Authenticate
Open a session. **Does not** require `sessionID`.

| Param | Notes |
| --- | --- |
| `user` | API username |
| `token` | API key |

Returns `data.sessionID` (128-char string).

### GetNidbInfo
Returns `data.NiDBVersion`.

### GetInstanceList
Returns `data.instances` — array of `{ instanceID, instanceUID, instanceName }`.

### GetProjectList
| Param | Notes |
| --- | --- |
| `instanceID` | integer |

Returns `data.projects` — array of `{ projectID, projectName }`.

### GetSubjectList
| Param | Notes |
| --- | --- |
| `projectID` | integer |

Returns `data.subjects` — array of `{ subjectID, subjectUID }`.

### GetUID
| Param | Notes |
| --- | --- |
| `altUID` | an alternate/site UID |

Returns `data.uids` — array of NiDB UIDs mapped to that alternate UID.

### GetSiteList
Returns `data.sites` — array of `{ siteID, siteName }`.

### GetEquipmentList
Returns `data.equipment` — array of equipment/study-site strings.

### StartTransaction
Returns `data.transactionID` (integer). Use it to group an upload/import.

### EndTransaction
| Param | Notes |
| --- | --- |
| `transactionID` | integer, must be one you started |

Marks the transaction complete.

### GetTransactionStatus
| Param | Notes |
| --- | --- |
| `transactionID` | integer |

Returns `data.requests` — the import-request rows for that transaction.

### GetArchiveStatus
| Param | Notes |
| --- | --- |
| `transactionID` | integer |

Returns `data.series` — per-series archive status from the import logs.

### ImportObject
Uploads a squirrel object file (`multipart/form-data`).

| Param | Notes |
| --- | --- |
| `squirrelObject` | the file; must have a `.sqrl` extension |

Returns `data.results`. *(Server-side processing of the object is still under
development; the endpoint currently validates and accepts the upload.)*

### GetAcceptedObjects
Returns `data.objectTypes`. *(Under development.)*

---

## 5. Using the API from curl

Set a couple of shell variables first:

```bash
BASE="https://YOUR-NIDB-HOST/api2.php"     # dev: http://YOUR-NIDB-HOST:8080/api2.php
USER="myapiuser"
TOKEN="the-api-key-from-my-account"
```

### Authenticate and capture the session ID

Use `--data-urlencode` so keys/tokens with special characters are sent safely,
and keep secrets **out of the URL** (POST body only):

```bash
curl -sS -X POST "$BASE" \
  --data-urlencode "action=Authenticate" \
  --data-urlencode "user=$USER" \
  --data-urlencode "token=$TOKEN"
```

Example response:

```json
{"success":true,"message":"Authenticated.","data":{"sessionID":"a1b2c3...(128 chars)"}}
```

Capture the session ID into a variable (with the `jq` tool):

```bash
SID=$(curl -sS -X POST "$BASE" \
  --data-urlencode "action=Authenticate" \
  --data-urlencode "user=$USER" \
  --data-urlencode "token=$TOKEN" | jq -r '.data.sessionID')
```

### Call an authenticated action

```bash
# Server version
curl -sS -X POST "$BASE" \
  --data-urlencode "action=GetNidbInfo" \
  --data-urlencode "sessionID=$SID"

# Projects in instance 1
curl -sS -X POST "$BASE" \
  --data-urlencode "action=GetProjectList" \
  --data-urlencode "sessionID=$SID" \
  --data-urlencode "instanceID=1"

# Subjects in project 42
curl -sS -X POST "$BASE" \
  --data-urlencode "action=GetSubjectList" \
  --data-urlencode "sessionID=$SID" \
  --data-urlencode "projectID=42"
```

### A full transaction + upload workflow

```bash
# 1. Start a transaction
TID=$(curl -sS -X POST "$BASE" \
  --data-urlencode "action=StartTransaction" \
  --data-urlencode "sessionID=$SID" | jq -r '.data.transactionID')

# 2. Upload a squirrel object (multipart form; -F handles the file)
curl -sS -X POST "$BASE" \
  -F "action=ImportObject" \
  -F "sessionID=$SID" \
  -F "squirrelObject=@/path/to/object.sqrl"

# 3. End the transaction
curl -sS -X POST "$BASE" \
  --data-urlencode "action=EndTransaction" \
  --data-urlencode "sessionID=$SID" \
  --data-urlencode "transactionID=$TID"

# 4. Check status
curl -sS -X POST "$BASE" \
  --data-urlencode "action=GetTransactionStatus" \
  --data-urlencode "sessionID=$SID" \
  --data-urlencode "transactionID=$TID"
```

> **Note on `-d`/`--data-urlencode` vs `-F`:** use `--data-urlencode` for normal
> fields, and `-F` when a file is involved (`ImportObject`). `-F` switches the
> request to `multipart/form-data`; either encoding populates the server's POST
> fields.

---

## 6. Using the API from C++ (libcurl)

The examples below use the libcurl "easy" interface. They assume you link
against libcurl (`-lcurl`) and, for parsing responses, a JSON library such as
[nlohmann/json](https://github.com/nlohmann/json) (`#include <nlohmann/json.hpp>`).

### 6.1 A small reusable client

```cpp
#include <curl/curl.h>
#include <string>
#include <vector>
#include <utility>
#include <stdexcept>

// Collects the response body.
static size_t writeCb(char* ptr, size_t size, size_t nmemb, void* userdata) {
    auto* out = static_cast<std::string*>(userdata);
    out->append(ptr, size * nmemb);
    return size * nmemb;
}

class NidbApiClient {
public:
    explicit NidbApiClient(std::string baseUrl) : baseUrl_(std::move(baseUrl)) {
        curl_global_init(CURL_GLOBAL_DEFAULT);
    }
    ~NidbApiClient() { curl_global_cleanup(); }

    // POST a set of url-encoded form fields. Returns the raw JSON response body.
    std::string post(const std::vector<std::pair<std::string, std::string>>& fields) {
        CURL* curl = curl_easy_init();
        if (!curl) throw std::runtime_error("curl_easy_init failed");

        // Build an application/x-www-form-urlencoded body, escaping every value.
        std::string body;
        for (const auto& kv : fields) {
            char* ek = curl_easy_escape(curl, kv.first.c_str(),  (int)kv.first.size());
            char* ev = curl_easy_escape(curl, kv.second.c_str(), (int)kv.second.size());
            if (!body.empty()) body += '&';
            body += ek; body += '='; body += ev;
            curl_free(ek); curl_free(ev);
        }

        std::string response;
        long httpCode = 0;

        curl_easy_setopt(curl, CURLOPT_URL, baseUrl_.c_str());
        curl_easy_setopt(curl, CURLOPT_POST, 1L);
        curl_easy_setopt(curl, CURLOPT_COPYPOSTFIELDS, body.c_str());
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, writeCb);
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
        // Production uses HTTPS with a valid certificate — keep verification ON.
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1L);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 2L);

        CURLcode rc = curl_easy_perform(curl);
        curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &httpCode);
        curl_easy_cleanup(curl);

        if (rc != CURLE_OK)
            throw std::runtime_error(std::string("curl error: ") + curl_easy_strerror(rc));
        // httpCode is available if you want to branch on it (401, 429, ...).
        return response;
    }

private:
    std::string baseUrl_;
};
```

### 6.2 Authenticate and store the session ID

```cpp
#include <nlohmann/json.hpp>
using nlohmann::json;

NidbApiClient api("https://YOUR-NIDB-HOST/api2.php");

std::string authenticate(NidbApiClient& api,
                         const std::string& user,
                         const std::string& token) {
    std::string body = api.post({
        {"action", "Authenticate"},
        {"user",   user},
        {"token",  token},
    });

    json j = json::parse(body);
    if (!j.value("success", false))
        throw std::runtime_error("auth failed: " + j.value("message", ""));

    return j["data"]["sessionID"].get<std::string>();
}

// ...
std::string sessionId = authenticate(api, "myapiuser", "the-api-key");
```

### 6.3 Call an authenticated action

```cpp
// List the projects in an instance.
json getProjectList(NidbApiClient& api,
                   const std::string& sessionId,
                   int instanceId) {
    std::string body = api.post({
        {"action",     "GetProjectList"},
        {"sessionID",  sessionId},
        {"instanceID", std::to_string(instanceId)},
    });

    json j = json::parse(body);
    if (!j.value("success", false))
        throw std::runtime_error(j.value("message", "request failed"));

    return j["data"]["projects"];   // array of { projectID, projectName }
}
```

### 6.4 Start a transaction, upload a file, end the transaction

File uploads use `multipart/form-data`, which libcurl builds with the MIME API
(`curl_mime_*`):

```cpp
// Upload a .sqrl object within a transaction.
std::string importObject(const std::string& baseUrl,
                        const std::string& sessionId,
                        const std::string& filePath) {
    CURL* curl = curl_easy_init();
    if (!curl) throw std::runtime_error("curl_easy_init failed");

    curl_mime* mime = curl_mime_init(curl);

    curl_mimepart* part = curl_mime_addpart(mime);
    curl_mime_name(part, "action");
    curl_mime_data(part, "ImportObject", CURL_ZERO_TERMINATED);

    part = curl_mime_addpart(mime);
    curl_mime_name(part, "sessionID");
    curl_mime_data(part, sessionId.c_str(), CURL_ZERO_TERMINATED);

    part = curl_mime_addpart(mime);
    curl_mime_name(part, "squirrelObject");
    curl_mime_filedata(part, filePath.c_str());   // sets filename + streams the file

    std::string response;
    curl_easy_setopt(curl, CURLOPT_URL, baseUrl.c_str());
    curl_easy_setopt(curl, CURLOPT_MIMEPOST, mime);
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, writeCb);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1L);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 2L);

    CURLcode rc = curl_easy_perform(curl);
    curl_mime_free(mime);
    curl_easy_cleanup(curl);

    if (rc != CURLE_OK)
        throw std::runtime_error(std::string("curl error: ") + curl_easy_strerror(rc));
    return response;
}
```

A full round trip:

```cpp
// 1. StartTransaction
json st = json::parse(api.post({
    {"action", "StartTransaction"}, {"sessionID", sessionId}}));
int transactionId = st["data"]["transactionID"].get<int>();

// 2. ImportObject
importObject("https://YOUR-NIDB-HOST/api2.php", sessionId, "/path/to/object.sqrl");

// 3. EndTransaction
api.post({
    {"action", "EndTransaction"},
    {"sessionID", sessionId},
    {"transactionID", std::to_string(transactionId)},
});
```

---

## 7. Security notes and troubleshooting

- **Keep secrets out of URLs.** The API reads parameters from the POST body
  only, so tokens and session IDs never appear in the query string. Do the same
  on your side (never put them in a URL or a log line).
- **Use HTTPS in production** and keep certificate verification enabled
  (`CURLOPT_SSL_VERIFYPEER`/`VERIFYHOST` in libcurl). Disabling verification
  defeats the point of HTTPS.
- **Re-authenticate on `401`.** A session expires after 10 minutes idle; when
  you get `401 Invalid or expired session`, call `Authenticate` again and retry.
- **Respect `429`.** It means either the per-user session cap (2) or the
  failed-login throttle (10 failures / 15 min per IP). Back off and retry later;
  don't hammer `Authenticate` in a loop.
- **Store the session ID in memory only.** There is no need to persist it; it is
  short-lived by design.
- **One session per client run** is the simplest pattern: authenticate once at
  startup, reuse the session ID, and let it expire when the client exits.
