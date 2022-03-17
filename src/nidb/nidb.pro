QT -= gui
QT += sql
QT += network

CONFIG += c++17 cmdline
CONFIG -= app_bundle

# The following define makes your compiler emit warnings if you use
# any Qt feature that has been marked deprecated (the exact warnings
# depend on your compiler). Please consult the documentation of the
# deprecated API in order to know how to port your code away from it.
DEFINES += QT_DEPRECATED_WARNINGS

# You can also make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
# You can also select to disable deprecated APIs only up to a certain version of Qt.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

SOURCES += \
    analysis.cpp \
    archiveio.cpp \
    main.cpp \
    minipipeline.cpp \
    moduleBackup.cpp \
    moduleCluster.cpp \
    moduleExport.cpp \
    moduleFileIO.cpp \
    moduleImport.cpp \
    moduleImportUploaded.cpp \
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
    subject.cpp

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
    minipipeline.h \
    moduleBackup.h \
    moduleCluster.h \
    moduleExport.h \
    moduleFileIO.h \
    moduleImport.h \
    moduleImportUploaded.h \
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
    subject.h


# gdcm
win32: {
    GDCMBIN = C:/gdcmbin
    GDCMSRC = C:/gdcm/Source
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
	SMTPBIN = J:/bin/smtp-win
    LIBS += -L$$SMTPBIN/release -lSMTPEmail
	INCLUDEPATH += J:/src/smtp
    DEPENDPATH += $$SMTPBIN
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
