# ------------------------------------------------------------------------------
# bit7z (LZMA) + DCMTK dependencies
# ------------------------------------------------------------------------------
# Shared by squirrellib.pro, squirrel.pro and squirrel-gui.pro so all three link
# against the same archive (bit7z) and DICOM (DCMTK) libraries.
#
# The bit7z paths (LZMABIN, LZMAINCLUDE) are relative to the BUILD directory, not
# the source directory: they match the layout the build-<distro>.sh scripts
# create, where $BUILDDIR/bit7z sits alongside $BUILDDIR/squirrel and
# $BUILDDIR/squirrel-gui (hence ../bit7z from any of them).
#
# In squirrel-gui.pro this file MUST be included AFTER -lsquirrel, because GNU ld
# resolves static archives in command line order and libsquirrel depends on these
# libraries.
# ------------------------------------------------------------------------------

win32: {
    LIBS += -loleaut32 -lole32

    # bit7z library (provides LZMA)
    LZMABIN = C:/squirrel/bit7z/lib/x64
    LZMAINCLUDE = ../../bit7z/include/bit7z
    *msvc*:CONFIG(release, debug|release): LIBS += -L$$LZMABIN/Release
    else:*msvc*:CONFIG(debug, debug|release): LIBS += -L$$LZMABIN/Debug
    INCLUDEPATH += $$LZMAINCLUDE
    HEADERS += $$LZMAINCLUDE/bit7z.hpp
    LIBS += -lbit7z

    # dcmtk library
    DCMTK = "C:/Program Files (x86)/DCMTK"
    LIBS += -L$$DCMTK/lib
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
}

linux: {
    # bit7z library (provides LZMA)
    LZMABIN = ../bit7z
    LZMAINCLUDE = ../bit7z/include/bit7z
    LIBS += -L$$LZMABIN -lbit7z64 -ldl
    INCLUDEPATH += $$LZMAINCLUDE
    HEADERS += $$LZMAINCLUDE/bit7z.hpp

    # dcmtk
    LIBS += -L/usr/local/lib/ -L/usr/local/lib64/
    INCLUDEPATH += /usr/local/include/
    QMAKE_RPATHDIR += /usr/local/lib /usr/local/lib64

    LIBS += -Wl,--start-group \
        -ldcmdata \
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
        -lofstd \
        -Wl,--end-group \
        -lz
}
