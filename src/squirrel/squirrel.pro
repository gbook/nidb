QT -= gui

CONFIG += c++17 console
CONFIG -= app_bundle
CONFIG += silent

# You can make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

#INCLUDEPATH += $$PWD/../nidb

SOURCES += \
	../nidb/imageio.cpp \
	../nidb/utils.cpp \
	analysis.cpp \
	convert.cpp \
	dicom.cpp \
	drugs.cpp \
	experiment.cpp \
	main.cpp \
	measures.cpp \
	pipeline.cpp \
	series.cpp \
	squirrel.cpp \
	study.cpp \
	subject.cpp \
	validate.cpp

# Default rules for deployment.
qnx: target.path = /tmp/$${TARGET}/bin
else: unix:!android: target.path = /opt/$${TARGET}/bin
!isEmpty(target.path): INSTALLS += target

HEADERS += \
	../nidb/imageio.h \
	../nidb/utils.h \
	../nidb/version.h \
	analysis.h \
	convert.h \
	dicom.h \
	drugs.h \
	experiment.h \
	measures.h \
	pipeline.h \
	series.h \
	squirrel.h \
	study.h \
	subject.h \
	validate.h


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

}
unix: {

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
