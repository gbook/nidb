QT -= gui

CONFIG += c++17 console
CONFIG -= app_bundle
CONFIG += silent

TARGET = squirrel
TEMPLATE = lib
DEFINES += SQUIRREL_BUILD
#win32:CONFIG += dll
#win32:CONFIG += lib
*msvc* {
    #LIBS += Advapi32.lib Setupapi.lib
    CONFIG -= dll
    CONFIG += shared static
    DESTDIR = ../../bin/squirrel
}

# You can make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

#INCLUDEPATH += $$PWD/../nidb

SOURCES += \
	squirrelImageIO.cpp \
	utils.cpp \
	convert.cpp \
	dicom.cpp \
	main.cpp \
	squirrel.cpp \
	squirrelAnalysis.cpp \
	squirrelDrug.cpp \
	squirrelExperiment.cpp \
	squirrelMeasure.cpp \
	squirrelPipeline.cpp \
	squirrelSeries.cpp \
	squirrelStudy.cpp \
	squirrelSubject.cpp \
	validate.cpp

# Default rules for deployment.
#qnx: target.path = /tmp/$${TARGET}/bin
#else: unix:!android: target.path = /opt/$${TARGET}/bin
#!isEmpty(target.path): INSTALLS += target

HEADERS += \
	squirrelImageIO.h \
	squirrelVersion.h \
	utils.h \
	convert.h \
	dicom.h \
	squirrel.h \
	squirrelAnalysis.h \
	squirrelDrug.h \
	squirrelExperiment.h \
	squirrelMeasure.h \
	squirrelPipeline.h \
	squirrelSeries.h \
	squirrelStudy.h \
	squirrelSubject.h \
	validate.h


# gdcm library
GDCMBIN = ../../bin/gdcm
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
