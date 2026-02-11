# Use this file to build libsquirrel

QT -= gui
QT += sql

CONFIG += c++17 console
CONFIG -= app_bundle
CONFIG += silent

win32: LIBS += -loleaut32 -lole32

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
	bids.cpp \
	extract.cpp \
	info.cpp \
	squirrelDataDictionary.cpp \
	squirrelImageIO.cpp \
	squirrelGroupAnalysis.cpp \
	utils.cpp \
	convert.cpp \
	dicom.cpp \
	modify.cpp \
	main.cpp \
	squirrel.cpp \
	squirrelAnalysis.cpp \
	squirrelIntervention.cpp \
	squirrelExperiment.cpp \
	squirrelObservation.cpp \
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
	squirrelDataDictionary.h \
	squirrelImageIO.h \
	squirrelVersion.h \
	squirrelGroupAnalysis.h \
	squirrelTypes.h \
	utils.h \
	convert.h \
	dicom.h \
	modify.h \
	squirrel.h \
	squirrelAnalysis.h \
	squirrelIntervention.h \
	squirrelExperiment.h \
	squirrelObservation.h \
	squirrelPipeline.h \
	squirrelSeries.h \
	squirrelStudy.h \
	squirrelSubject.h

# bit7z library (provides LZMA)
#LZMABIN = ../../bit7z/lib/x64
#LZMAINCLUDE = ../../bit7z/include/bit7z
#*msvc*:CONFIG(release, debug|release): LIBS += -L$$LZMABIN/Release
#else:*msvc*:CONFIG(debug, debug|release): LIBS += -L$$LZMABIN/Debug
#linux: LIBS += -L$$LZMABIN -lbit7z64 -ldl
#INCLUDEPATH += $$LZMAINCLUDE
#HEADERS += $$LZMAINCLUDE/bit7z.hpp
#win32:LIBS += -lbit7z

# bit7z library (provides LZMA)
win32: {
    LZMABIN = ../../bit7z/lib/x64
	LZMAINCLUDE = ../../bit7z/include/bit7z
	*msvc*:CONFIG(release, debug|release): LIBS += -L$$LZMABIN/Release
	else:*msvc*:CONFIG(debug, debug|release): LIBS += -L$$LZMABIN/Debug
	INCLUDEPATH += $$LZMAINCLUDE
	HEADERS += $$LZMAINCLUDE/bit7z.hpp
	LIBS += -lbit7z
}
linux: {
    LZMABIN = ../../bin/bit7z
	LZMAINCLUDE = ../bit7z/include/bit7z
	LIBS += -L$$LZMABIN -lbit7z64 -ldl
	INCLUDEPATH += $$LZMAINCLUDE
	HEADERS += $$LZMAINCLUDE/bit7z.hpp

    # dcmtk
    LIBS += -L/usr/local/lib64/
    INCLUDEPATH += /usr/local/include/

    LIBS += -ldcmdata \
        -lcmr \
		-ldcmdata \
		-ldcmdsig \
		-ldcmect \
		-ldcmfg \
		-ldcmimage \
		-ldcmimgle \
		-ldcmiod \
		-ldcmjpeg \
		-ldcmjpls \
		-ldcmnet \
		-ldcmpmap \
		-ldcmpstat \
		-ldcmqrdb \
		-ldcmrt \
		-ldcmseg \
		-ldcmsr \
		-ldcmtkcharls \
		-ldcmtls \
		-ldcmtract \
		-ldcmwlm \
		-ldcmxml \
		-li2d \
		-lijg8 \
		-lijg12 \
		-lijg16 \
		-loficonv \
		-loflog \
		-lofstd


    message($$LIBS)
	message($$QMAKE_LIBDIR)
	
	
}
