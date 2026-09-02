# ------------------------------------------------------------------------------
# squirrel core sources + headers
# ------------------------------------------------------------------------------
# Everything that makes up the squirrel library, EXCEPT main.cpp:
#   - squirrellib.pro includes this file to build libsquirrel (no main.cpp).
#   - squirrel.pro includes this file and then adds main.cpp itself to build the
#     command line executable.
#   - squirrel-gui.pro does NOT include this file. It links the prebuilt
#     libsquirrel instead of recompiling these sources.
#
# Paths are written as $$PWD/... so they resolve relative to this .pri's
# directory (src/squirrel) regardless of which .pro includes it.
# ------------------------------------------------------------------------------

QT += sql

DEFINES += SQUIRREL_BUILD

SOURCES += \
    $$PWD/bids.cpp \
    $$PWD/convert.cpp \
    $$PWD/dicom.cpp \
    $$PWD/extract.cpp \
    $$PWD/info.cpp \
    $$PWD/modify.cpp \
    $$PWD/squirrel.cpp \
    $$PWD/squirrelAnalysis.cpp \
    $$PWD/squirrelDataDictionary.cpp \
    $$PWD/squirrelExperiment.cpp \
    $$PWD/squirrelGroupAnalysis.cpp \
    $$PWD/squirrelImageIO.cpp \
    $$PWD/squirrelIntervention.cpp \
    $$PWD/squirrelObservation.cpp \
    $$PWD/squirrelPipeline.cpp \
    $$PWD/squirrelSeries.cpp \
    $$PWD/squirrelStudy.cpp \
    $$PWD/squirrelSubject.cpp \
    $$PWD/utils.cpp

HEADERS += \
    $$PWD/bids.h \
    $$PWD/convert.h \
    $$PWD/dicom.h \
    $$PWD/extract.h \
    $$PWD/info.h \
    $$PWD/modify.h \
    $$PWD/squirrel.h \
    $$PWD/squirrel.sql.h \
    $$PWD/squirrelAnalysis.h \
    $$PWD/squirrelDataDictionary.h \
    $$PWD/squirrelExperiment.h \
    $$PWD/squirrelGroupAnalysis.h \
    $$PWD/squirrelImageIO.h \
    $$PWD/squirrelIntervention.h \
    $$PWD/squirrelObservation.h \
    $$PWD/squirrelPipeline.h \
    $$PWD/squirrelSeries.h \
    $$PWD/squirrelStudy.h \
    $$PWD/squirrelSubject.h \
    $$PWD/squirrelTypes.h \
    $$PWD/squirrelVersion.h \
    $$PWD/utils.h
