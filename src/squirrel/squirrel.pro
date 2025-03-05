# Use this file to build squirrel utils

QT -= gui
QT += sql

CONFIG += c++11
CONFIG += cmdline
CONFIG -= app_bundle
CONFIG += silent

win32: LIBS += -loleaut32 -lole32

DEFINES += SQUIRREL_BUILD

# You can make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

#INCLUDEPATH += $$PWD/../nidb

SOURCES += \
	bids.cpp \
	extract.cpp \
	info.cpp \
	modify.cpp \
	squirrelDataDictionary.cpp \
	squirrelImageIO.cpp \
	squirrelGroupAnalysis.cpp \
	squirrelIntervention.cpp \
	squirrelObservation.cpp \
	utils.cpp \
	convert.cpp \
	dicom.cpp \
	main.cpp \
	squirrel.cpp \
	squirrelAnalysis.cpp \
	squirrelExperiment.cpp \
	squirrelPipeline.cpp \
	squirrelSeries.cpp \
	squirrelStudy.cpp \
	squirrelSubject.cpp

# Default rules for deployment.
#qnx: target.path = /tmp/$${TARGET}/bin
#else: unix:!android: target.path = /opt/$${TARGET}/bin
#!isEmpty(target.path): INSTALLS += target

HEADERS += \
	bids.h \
	extract.h \
	info.h \
	modify.h \
	squirrel.sql.h \
	squirrelDataDictionary.h \
	squirrelImageIO.h \
	squirrelIntervention.h \
	squirrelObservation.h \
	squirrelTypes.h \
	squirrelVersion.h \
	squirrelGroupAnalysis.h \
	utils.h \
	convert.h \
	dicom.h \
	squirrel.h \
	squirrelAnalysis.h \
	squirrelExperiment.h \
	squirrelPipeline.h \
	squirrelSeries.h \
	squirrelStudy.h \
	squirrelSubject.h

# bit7z library (provides LZMA)
win32: {
    LZMABIN = C:/squirrel/bit7z/lib/x64
    LZMAINCLUDE = ../../bit7z/include/bit7z
    *msvc*:CONFIG(release, debug|release): LIBS += -L$$LZMABIN/Release
    else:*msvc*:CONFIG(debug, debug|release): LIBS += -L$$LZMABIN/Debug
    INCLUDEPATH += $$LZMAINCLUDE
    HEADERS += $$LZMAINCLUDE/bit7z.hpp
    LIBS += -lbit7z

    # gdcm library
	GDCMBIN = C:/squirrel/bin/gdcm
}
linux: {
    LZMABIN = ../../bin/bit7z
    LZMAINCLUDE = ../bit7z/include/bit7z
    LIBS += -L$$LZMABIN -lbit7z64 -ldl
    INCLUDEPATH += $$LZMAINCLUDE
    HEADERS += $$LZMAINCLUDE/bit7z.hpp

    # gdcm library
	GDCMBIN = ../../bin/gdcm
}

GDCMSRC = ../gdcm/Source
*msvc*:CONFIG(release, debug|release): LIBS += -L$$GDCMBIN/bin/Release/
else:*msvc*:CONFIG(debug, debug|release): LIBS += -L$$GDCMBIN/bin/Debug/
linux: LIBS += -L$$GDCMBIN/bin
INCLUDEPATH += $$GDCMSRC/Attribute
INCLUDEPATH += $$GDCMSRC/Common
INCLUDEPATH += $$GDCMSRC/DataDictionary
INCLUDEPATH += $$GDCMSRC/DataStructureAndEncodingDefinition
INCLUDEPATH += $$GDCMSRC/InformationObjectDefinition
INCLUDEPATH += $$GDCMSRC/MediaStorageAndFileFormat
INCLUDEPATH += $$GDCMSRC/MessageExchangeDefinition
INCLUDEPATH += $$GDCMBIN/Source/Common # for gdcmConfigure.h
HEADERS += $$GDCMBIN/Source/Common/gdcmConfigure.h

LIBS += -lgdcmMSFF \
    -lgdcmCommon \
    -lgdcmDICT \
    -lgdcmDSED \
    -lgdcmIOD \
    -lgdcmMEXD \
    -lgdcmcharls \
    -lgdcmexpat \
    -lgdcmjpeg12 \
    -lgdcmjpeg16 \
    -lgdcmjpeg8 \
    -lgdcmopenjp2 \
    -lgdcmzlib \
    -lsocketxx
