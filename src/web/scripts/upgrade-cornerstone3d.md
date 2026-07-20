# Upgrading Cornerstone3D

The Cornerstone3D libraries used by `dicom.php` are pre-built into two files in this directory:
- `cs3d.bundle.js` — main library bundle
- `decodeImageFrameWorker.js` — DICOM decode web worker

Both files must be rebuilt and replaced together whenever Cornerstone3D is upgraded.

## Prerequisites

- Node.js installed on Windows (not WSL — the build must run on the Windows filesystem)
- Build source lives at `C:\Users\grego\cs3d-build\`

## Steps

### 1. Update the package version

Edit `C:\Users\grego\cs3d-build\package.json` and change the version constraints
for any packages you want to upgrade, e.g.:

```json
"@cornerstonejs/core": "^2",
"@cornerstonejs/tools": "^2",
"@cornerstonejs/dicom-image-loader": "^2",
"dicom-parser": "^1.8"
```

### 2. Install updated packages

Open a Windows command prompt or PowerShell and run:

```
cd C:\Users\grego\cs3d-build
npm install
```

### 3. Build the bundles

From the same directory:

```
npm run build
```

This produces two files in `C:\Users\grego\cs3d-build\`:
- `cs3d.bundle.js`
- `decodeImageFrameWorker.js`

### 4. Copy to the web directory

```
copy cs3d.bundle.js \\wsl.localhost\Ubuntu\mnt\l\scripts\cs3d.bundle.js
copy decodeImageFrameWorker.js \\wsl.localhost\Ubuntu\mnt\l\scripts\decodeImageFrameWorker.js
```

### 5. Test

Open `dicom.php` for any MR or CT series and confirm images load and scroll correctly.

## Notes

- Both output files must always be in the same directory. The main bundle locates
  the worker via `import.meta.url`, so moving one without the other will break decoding.
- The build must run from the Windows filesystem (`C:\...`), not from a WSL path
  (`/home/...`). Windows npm cannot run post-install scripts from WSL UNC paths.
- `dicom.php` loads the bundle as `<script type="module" src="scripts/cs3d.bundle.js">`.
  If the file is ever moved, update that tag.
- The build uses `--format=esm`. Do not switch to `--format=iife`; it breaks
  `import.meta.url` which the worker factory depends on.
