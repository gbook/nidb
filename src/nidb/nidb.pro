QT -= gui
QT += sql
QT += network

CONFIG -= c++98
CONFIG += c++11
CONFIG += c++17
CONFIG += cmdline
CONFIG -= app_bundle
CONFIG += silent

# The following define makes your compiler emit warnings if you use
# any Qt feature that has been marked deprecated (the exact warnings
# depend on your compiler). Please consult the documentation of the
# deprecated API in order to know how to port your code away from it.
DEFINES += QT_DEPRECATED_WARNINGS
QMAKE_CXXFLAGS += -Wall
win32: { # ignore warnings that show up in gdcm and Qt libs
    QMAKE_CXXFLAGS += /wd4711
    QMAKE_CXXFLAGS += /wd4866
    QMAKE_CXXFLAGS += /wd5045
    QMAKE_CXXFLAGS += /wd4996
}

# You can also make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
# You can also select to disable deprecated APIs only up to a certain version of Qt.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

SOURCES += \
    analysis.cpp \
    archiveio.cpp \
    drug.cpp \
    experiment.cpp \
    imageio.cpp \
    main.cpp \
    measure.cpp \
    minipipeline.cpp \
    moduleBackup.cpp \
    moduleCluster.cpp \
    moduleExport.cpp \
    moduleFileIO.cpp \
    moduleImport.cpp \
    moduleMRIQA.cpp \
    moduleManager.cpp \
    moduleMiniPipeline.cpp \
    modulePipeline.cpp \
    moduleQC.cpp \
    moduleUpload.cpp \
    nidb.cpp \
    performanceMetric.cpp \
    pipeline.cpp \
    remotenidbconnection.cpp \
    series.cpp \
    study.cpp \
    subject.cpp \
    utils.cpp

#unix: {
#    BUILDNO = $$system(./build.sh)
#    DEFINES += BUILD_NUM=$${BUILDNO}
#}
#else {
#    DEFINES += BUILD_NUM=0
#}

# Default rules for deployment.
qnx: target.path = /tmp/$${TARGET}/bin
else: unix:!android: target.path = /opt/$${TARGET}/bin
!isEmpty(target.path): INSTALLS += target

HEADERS += \
    analysis.h \
    archiveio.h \
    drug.h \
    experiment.h \
    imageio.h \
    measure.h \
    minipipeline.h \
    moduleBackup.h \
    moduleCluster.h \
    moduleExport.h \
    moduleFileIO.h \
    moduleImport.h \
    moduleMRIQA.h \
    moduleManager.h \
    moduleMiniPipeline.h \
    modulePipeline.h \
    moduleQC.h \
    moduleUpload.h \
    nidb.h \
    performanceMetric.h \
    pipeline.h \
    remotenidbconnection.h \
    series.h \
    study.h \
    subject.h \
    utils.h \
    version.h

# gdcm
win32: {
    GDCMBIN = C:/squirrel/bin/gdcm
    GDCMSRC = C:/squirrel/src/gdcm/Source
    win32:CONFIG(release, debug|release): LIBS += -L$$GDCMBIN/bin/Release/
    else:win32:CONFIG(debug, debug|release): LIBS += -L$$GDCMBIN/bin/Debug/
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

    # Location of SMTP Library
	SMTPBIN = ../../bin/smtp-win
    LIBS += -L$$SMTPBIN/release -lSMTPEmail
	INCLUDEPATH += ../smtp
    DEPENDPATH += $$SMTPBIN
    *msvc* { # visual studio spec filter
	QMAKE_CXXFLAGS += -MP
    }

    # Location of squirrel Library
	SQUIRRELBIN = ../../bin/squirrel-win
	LIBS += -L$$SQUIRRELBIN/release -lsquirrel
	INCLUDEPATH += ../squirrel
	DEPENDPATH += $$SQUIRRELBIN
	*msvc* { # visual studio spec filter
	QMAKE_CXXFLAGS += -MP
	}
}
unix: {
    # Location of SMTP Library and header
	INCLUDEPATH += ../smtp
    SMTPBIN = ../../bin/smtp
	LIBS += -L$$SMTPBIN/ -lSMTPEmail
    INCLUDEPATH += $$SMTPBIN
    DEPENDPATH += $$SMTPBIN

    # Location of squirrel Library and header
	INCLUDEPATH += ../squirrel
	SQUIRRELBIN = ../../bin/squirrel
	LIBS += -L$$SQUIRRELBIN/ -lsquirrel
	INCLUDEPATH += $$SQUIRRELBIN
	DEPENDPATH += $$SQUIRRELBIN

    # GDCM library
    GDCMBIN = ../../bin/gdcm
    GDCMSRC = ../gdcm/Source
    LIBS += -L$$GDCMBIN/bin/
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
	-lgdcmuuid \
	-lgdcmzlib \
	-lsocketxx
}

DISTFILES += \
    build.sh
