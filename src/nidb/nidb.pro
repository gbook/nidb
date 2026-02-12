QT -= gui
QT += sql
QT += network
QT += core

#CONFIG -= c++98
#CONFIG += c++11
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
#QMAKE_CXXFLAGS += -Wextra
#QMAKE_CXXFLAGS += -Wno-c++98-compat
win32: { # ignore warnings that show up in gdcm and Qt libs
    QMAKE_CXXFLAGS += /wd4711
    QMAKE_CXXFLAGS += /wd4866
    QMAKE_CXXFLAGS += /wd5045
    QMAKE_CXXFLAGS += /wd4996
    #QMAKE_CXXFLAGS += /wno-c++98-compat
}
win32-g++ {
   QMAKE_CXXFLAGS_CXX17 = -std=c++17
   QMAKE_CXXFLAGS_GNUCXX17 = -std=c++17
}

# You can also make your code fail to compile if it uses deprecated APIs.
# In order to do so, uncomment the following line.
# You can also select to disable deprecated APIs only up to a certain version of Qt.
#DEFINES += QT_DISABLE_DEPRECATED_BEFORE=0x060000    # disables all the APIs deprecated before Qt 6.0.0

SOURCES += \
    analysis.cpp \
    archiveio.cpp \
    enrollment.cpp \
    experiment.cpp \
    imageio.cpp \
    intervention.cpp \
    main.cpp \
    minipipeline.cpp \
    moduleBackup.cpp \
    moduleCluster.cpp \
    moduleExport.cpp \
    moduleExportNonImaging.cpp \
    moduleFileIO.cpp \
    moduleImport.cpp \
    moduleMRIQA.cpp \
    moduleManager.cpp \
    moduleMiniPipeline.cpp \
    modulePipeline.cpp \
    moduleQC.cpp \
    moduleUpload.cpp \
    nidb.cpp \
    observation.cpp \
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
    enrollment.h \
    experiment.h \
    imageio.h \
    intervention.h \
    minipipeline.h \
    moduleBackup.h \
    moduleCluster.h \
    moduleExport.h \
    moduleExportNonImaging.h \
    moduleFileIO.h \
    moduleImport.h \
    moduleMRIQA.h \
    moduleManager.h \
    moduleMiniPipeline.h \
    modulePipeline.h \
    moduleQC.h \
    moduleUpload.h \
    nidb.h \
    observation.h \
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
    # dcmtk library
    DCMTK = "C:/Program Files (x86)/DCMTK"

    LIBS += -L$$DCMTK/lib
    #*msvc*:CONFIG(release, debug|release): LIBS += -L$$DCMTK/lib
    #else:*msvc*:CONFIG(debug, debug|release): LIBS += -L$$GDCMBIN/bin/Debug
    INCLUDEPATH += $$DCMTK/include/

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

    # Location of squirrel Library
    SQUIRRELBIN = ../../bin/squirrel-win
    LIBS += -L$$SQUIRRELBIN/release -lsquirrel
    INCLUDEPATH += ../squirrel
    INCLUDEPATH += ../../squirrel
    DEPENDPATH += $$SQUIRRELBIN
    *msvc* { # visual studio spec filter
	QMAKE_CXXFLAGS += -MP
    }
}
unix: {

    # Location of squirrel Library and header
    INCLUDEPATH += ../squirrel
    SQUIRRELBIN = ../../bin/squirrel
    LIBS += -L$$SQUIRRELBIN/ -lsquirrel
    INCLUDEPATH += $$SQUIRRELBIN
    DEPENDPATH += $$SQUIRRELBIN

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
}

DISTFILES += \
    build.sh
