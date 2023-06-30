---
description: How to build a Python wrapper for the squirrel library
---

# Building Python Wrapper

## Prerequisites

On RHEL8 Linux

```bash
sudo yum install swig python3-devel
```

Create the wrapper

```bash
swig -python gfg.i
gcc -c -fpic squirrel_wrap.c squirrel.cpp -I/usr/include/python3.6m
```
