# ------------------------------------------------------------------------------
# squirrel-gui: Qt Widgets viewer for squirrel packages (view only in v1)
# ------------------------------------------------------------------------------
# Configured from ../CMakeLists.txt, which provides the squirrellib target.
# This file is not a project on its own: open src/CMakeLists.txt instead.

if(NOT TARGET squirrellib)
    message(FATAL_ERROR
        "src/squirrel-gui/CMakeLists.txt cannot be opened on its own. "
        "Open src/CMakeLists.txt (the top-level project) instead; it builds "
        "bit7z and libsquirrel and then the squirrel-gui target.")
endif()

find_package(Qt6 REQUIRED COMPONENTS Widgets)

set(CMAKE_AUTOMOC ON)

add_executable(squirrel-gui WIN32
    main.cpp
    mainwindow.cpp      mainwindow.h
    packageworker.cpp   packageworker.h
    packagesnapshot.cpp packagesnapshot.h
    startpage.cpp       startpage.h
    detailspane.cpp     detailspane.h
    propertygrid.cpp    propertygrid.h
    extractdialog.cpp   extractdialog.h
    loadingdialog.cpp   loadingdialog.h
    imageviewer.cpp     imageviewer.h
    imageview.cpp       imageview.h
    dicomslice.cpp      dicomslice.h
    theme.cpp           theme.h
    icons.cpp           icons.h
    settings.cpp        settings.h
)

# Window icon (title bar, taskbar, dialogs), compiled into the exe.
qt_add_resources(squirrel-gui "squirrel_gui_icons"
    PREFIX "/"
    FILES resources/squirrel.png)

# squirrellib carries Qt Core/Sql, DCMTK and bit7z with it.
target_link_libraries(squirrel-gui PRIVATE squirrellib Qt6::Widgets)

if(WIN32)
    # DCMTK headers pull in windows.h, whose min/max macros break std::min/std::max.
    target_compile_definitions(squirrel-gui PRIVATE NOMINMAX)

    # Explorer/shortcut icon and file version info.
    set(SQUIRREL_GUI_ICON "${CMAKE_CURRENT_SOURCE_DIR}/resources/squirrel.ico")
    file(TO_CMAKE_PATH "${SQUIRREL_GUI_ICON}" SQUIRREL_GUI_ICON)
    configure_file(resources/squirrel-gui.rc.in
                   "${CMAKE_CURRENT_BINARY_DIR}/squirrel-gui.rc" @ONLY)
    target_sources(squirrel-gui PRIVATE "${CMAKE_CURRENT_BINARY_DIR}/squirrel-gui.rc")
endif()

if(UNIX)
    # Same runtime search path as the CLI: DCMTK lives in /usr/local.
    set_target_properties(squirrel-gui PROPERTIES
        BUILD_RPATH "/usr/local/lib;/usr/local/lib64"
        INSTALL_RPATH "/usr/local/lib;/usr/local/lib64")
endif()

squirrel_copy_dcmtk_dlls(squirrel-gui)
squirrel_copy_7zip(squirrel-gui)
