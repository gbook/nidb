# ------------------------------------------------------------------------------
#  nidbcluster.pro
#
#  Builds the standalone `nidbcluster` executable: the cluster module ONLY.
#  This is the small subset of NiDB that runs on compute nodes to check pipeline
#  jobs in and out. It links neither dcmtk nor the squirrel/bit7z libraries, so it
#  builds fast and has minimal dependencies compared to the full `nidb` binary.
#
#  The squirrel-dependent code in analysis.cpp (GetSquirrelObject) is compiled out
#  via the NIDB_CLUSTER_BUILD define below.
# ------------------------------------------------------------------------------

QT += core sql network gui
QMAKE_LIBS_OPENGL =

CONFIG += c++17
CONFIG += cmdline
CONFIG -= app_bundle
CONFIG += silent

TARGET = nidbcluster

DEFINES += QT_DEPRECATED_WARNINGS
DEFINES += NIDB_CLUSTER_BUILD
QMAKE_CXXFLAGS += -Wall

win32-g++ {
   QMAKE_CXXFLAGS_CXX17 = -std=c++17
   QMAKE_CXXFLAGS_GNUCXX17 = -std=c++17
}

# ----- Only the sources the cluster module actually needs -----
#   mainCluster.cpp  - cluster-only entry point
#   nidb.cpp         - core (config, DB, logging)
#   utils.cpp        - utility functions (Print, GetDirSizeAndFileCount, ...)
#   analysis.cpp     - analysis loader (squirrel method #ifdef'd out)
#   moduleCluster.cpp- the cluster module itself
SOURCES += \
    mainCluster.cpp \
    nidb.cpp \
    utils.cpp \
    analysis.cpp \
    moduleCluster.cpp

HEADERS += \
    nidb.h \
    utils.h \
    analysis.h \
    moduleCluster.h \
    version.h

# Default rules for deployment.
qnx: target.path = /tmp/$${TARGET}/bin
else: unix:!android: target.path = /opt/$${TARGET}/bin
!isEmpty(target.path): INSTALLS += target

# NOTE: intentionally NO dcmtk libraries and NO squirrel/bit7z libraries here.
