# ------------------------------------------------------------------------------
# FTXUI (terminal UI framework for the interactive 'squirrel explore' shell)
# ------------------------------------------------------------------------------
# Unlike dcm2niix/replxx (compiled straight into the target), FTXUI is built as
# static libraries via CMake in each build-<distro>.sh - the same pattern as
# bit7z. This file only wires the headers and the resulting .a files into the
# CLI link. Included by squirrel.pro ONLY (the shell is CLI-only).
#
# The three libraries have a strict dependency order (component -> dom ->
# screen), so they must be listed in that order on the link line. FTXUI's
# component module uses std::thread, hence -lpthread.
#
# FTXUIBIN is resolved relative to the build directory ($BUILDDIR/ftxui), the
# same way squirrel-deps.pri locates bit7z at ../bit7z.
# ------------------------------------------------------------------------------

INCLUDEPATH += $$PWD/../ftxui/include

FTXUIBIN = ../ftxui
LIBS += -L$$FTXUIBIN -lftxui-component -lftxui-dom -lftxui-screen -lpthread
