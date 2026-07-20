# Python Bindings Options For `squirrellib`

There are a few realistic ways to make Python bindings for `squirrellib`, and they are not all equally good for this project.

The short version: `pybind11` is probably the best fit if you want maintainable, hand-written bindings around a curated Python API. A C wrapper plus `ctypes` or `cffi` is the safest ABI-wise if you want long-term stability. Qt-specific generators like SIP or Shiboken are possible, but they are usually a better fit when the library is strongly `QObject`-based, which this codebase does not appear to be.

## Best options

### 1. `pybind11`

- Best choice if you want a real Python module like `import squirrel`.
- Lets you wrap selected C++ classes and functions directly.
- Good if you want Python users to call high-level operations like `read_package()`, `validate()`, `info()`, and `modify()`.
- Works well with modern C++ and can hide a lot of internal complexity.

Why it fits here:

- Your library is C++ already.
- The code looks class-based, not plain C.
- You probably do not want to expose every internal type directly anyway.
- You can design a cleaner Python-facing API than the raw C++ one.

Main caveats:

- Qt types like `QString`, `QList`, and similar types need manual conversion.
- Exception handling and ownership need to be defined carefully.
- You will probably want a separate binding target, not bind directly to the current `squirrellib.pro` as-is.

### 2. Thin C API wrapper plus `ctypes` or `cffi`

- Create a small C-compatible layer on top of the C++ library.
- Python talks only to exported C functions.
- Very stable and portable.
- Easier to keep binary compatibility cleaner over time.

Why it is attractive:

- You avoid exposing C++ ABI details directly to Python.
- Packaging can be simpler in the long run.
- Good if you want a narrow interface like:
  - `sqrl_open_package(...)`
  - `sqrl_validate(...)`
  - `sqrl_get_subjects_json(...)`

Main caveats:

- More boilerplate.
- You will likely end up serializing results as JSON or flat structs.
- Less Pythonic unless you build a Python wrapper layer on top.

### 3. Cython

- Middle ground between raw C bindings and handwritten Python wrappers.
- Can call into C and C++ with `.pxd` and `.pyx` definitions.
- Useful if you want more Python-side implementation logic.

Caveats:

- More build-system overhead.
- Usually less pleasant than `pybind11` for modern C++ class wrapping.
- I would only prefer it if the team is already comfortable with Cython.

## Less likely to be the right fit

### 4. SIP or Shiboken

- These are strong when wrapping Qt-heavy APIs, especially `QObject`-style class hierarchies.
- If `squirrellib` were built like a normal Qt library exposing signals, slots, and `QObject` subclasses, this would be more compelling.

Why I would probably skip them here:

- Your code does not look strongly meta-object driven.
- You would take on Qt binding tool complexity without getting much benefit.

### 5. SWIG

- Can generate Python bindings from C++ headers.
- Fast to prototype sometimes.

Why I would not recommend it first:

- Generated interfaces can get messy.
- C++ plus Qt-like types plus custom ownership tends to become awkward.
- Harder to shape a clean Python API.

## What I would recommend for `squirrellib`

I would choose one of these two paths:

### 1. `pybind11` for a high-level Python API

- Expose a small set of useful operations, not every class.
- Return Python `dict`, `list`, and `str` where possible.
- Keep Qt internal to the C++ side.

### 2. C wrapper plus Python wrapper

- Best if you care most about ABI stability and packaging.
- Especially good if you want to expose results as JSON.

For this project, I would lean `pybind11` first, because the likely user goal is “call squirrel functionality from Python,” not “mirror every C++ implementation detail.”

## What I would change first in the library

- Split the true library from the CLI entrypoint.
- `squirrellib.pro` currently includes `main.cpp`, which should not be part of the Python-bindable core library.
- Build a clean core library target that contains the reusable classes only.
- Then build:
  - the CLI executable against that core library
  - the Python extension against that same core library

That structure will make everything easier.

## A practical binding shape

Instead of exposing all internal classes immediately, start with a narrow module like:

```python
import squirrel

squirrel.validate("my_package.sqrl")
squirrel.info("my_package.sqrl", object="subject", dataset="full", format="csv")
squirrel.modify(...)
```

Then later, if useful, add richer object wrappers:

```python
pkg = squirrel.Package("my_package.sqrl")
subjects = pkg.subjects()
```

## Recommended implementation plan

1. Create a clean core library project without `main.cpp`.
2. Decide whether the Python API should be function-oriented or object-oriented.
3. Build a small `pybind11` proof of concept exposing one function, like `validate()`.
4. Add string, list, and dict conversions for the most common outputs.
5. Package it as a Python wheel later.

If useful, the next step would be to sketch a concrete `pybind11` layout for this repo, including which files and projects to add and what the first binding should expose.
