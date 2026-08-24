# Use this file to build libsquirrel

QT -= gui

CONFIG += c++17 console
CONFIG -= app_bundle
CONFIG += silent

TARGET = squirrel
TEMPLATE = lib

#win32:CONFIG += dll
#win32:CONFIG += lib
*msvc* {
    #LIBS += Advapi32.lib Setupapi.lib
    CONFIG -= dll
    CONFIG += shared static
    DESTDIR = ../../bin/squirrel
}

linux: CONFIG += staticlib

# You can make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

# squirrel core sources (everything except main.cpp)
include($$PWD/squirrel-sources.pri)

# bit7z (LZMA) + DCMTK, shared with squirrel.pro and squirrel-gui.pro
include($$PWD/squirrel-deps.pri)

# dcm2niix in-process DICOM->Nifti conversion (Linux only; see dcm2niix.pri)
include($$PWD/dcm2niix.pri)

# Default rules for deployment.
#qnx: target.path = /tmp/$${TARGET}/bin
#else: unix:!android: target.path = /opt/$${TARGET}/bin
#!isEmpty(target.path): INSTALLS += target
