# Use this file to build squirrel utils

QT -= gui

CONFIG += c++11
CONFIG += cmdline
CONFIG -= app_bundle
CONFIG += silent

# You can make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

# squirrel core sources (everything except main.cpp). The CLI compiles these
# directly rather than linking libsquirrel, so squirrellib.pro is not a
# prerequisite for building the command line utility.
include($$PWD/squirrel-sources.pri)

SOURCES += $$PWD/main.cpp

# bit7z (LZMA) + DCMTK, shared with squirrellib.pro and squirrel-gui.pro
include($$PWD/squirrel-deps.pri)

# dcm2niix in-process DICOM->Nifti conversion (Linux only; see dcm2niix.pri)
include($$PWD/dcm2niix.pri)

# Default rules for deployment.
#qnx: target.path = /tmp/$${TARGET}/bin
#else: unix:!android: target.path = /opt/$${TARGET}/bin
#!isEmpty(target.path): INSTALLS += target
