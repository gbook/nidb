# ------------------------------------------------------------------------------
# Windows installer (.msi) for squirrel-gui and the squirrel CLI
# ------------------------------------------------------------------------------
# Included from ../CMakeLists.txt on Windows. Needs the WiX Toolset v3.14
# (https://wixtoolset.org, candle.exe/light.exe on PATH or WIX env var set).
#
#   cmake -S src -B build -DCMAKE_PREFIX_PATH=C:/Qt/6.9.3/msvc2022_64
#   cmake --build build --config Release
#   cd build
#   cpack -C Release            ->  squirrel_<version>_Windows-x64.msi
#
# What goes into <install>/bin:
#   squirrel-gui.exe, squirrel.exe
#   Qt DLLs + plugins (platforms, styles, sqldrivers/qsqlite, ...) via windeployqt
#   DCMTK DLLs (from the imported DCMTK targets)
#   7z.dll (SQUIRREL_7ZIP_DLL, taken from an installed 64-bit 7-Zip)
#   MSVC runtime DLLs (InstallRequiredSystemLibraries)
#
# The installer adds Start Menu and desktop shortcuts, associates .sqrl files
# with squirrel-gui, and appends <install>/bin to the system PATH so the CLI can
# be run from any terminal (see wix-patch.xml).
# ------------------------------------------------------------------------------

include(GNUInstallDirs)

set(SQUIRREL_PACKAGING_DIR "${CMAKE_CURRENT_LIST_DIR}")

# ----- executables -------------------------------------------------------------
if(TARGET squirrel-gui)
    install(TARGETS squirrel-gui RUNTIME DESTINATION ${CMAKE_INSTALL_BINDIR} COMPONENT squirrel)
endif()
if(TARGET squirrel)
    install(TARGETS squirrel RUNTIME DESTINATION ${CMAKE_INSTALL_BINDIR} COMPONENT squirrel)
endif()

# ----- Qt runtime (windeployqt) ------------------------------------------------
# squirrel-gui needs a superset of the CLI's Qt modules and both exes share one
# folder, so deploying for the GUI covers the CLI too.
if(TARGET squirrel-gui)
    qt_generate_deploy_app_script(
        TARGET squirrel-gui
        OUTPUT_SCRIPT SQUIRREL_GUI_DEPLOY_SCRIPT
        NO_UNSUPPORTED_PLATFORM_ERROR
        # The MSVC runtime is installed below; the GUI is English only.
        DEPLOY_TOOL_OPTIONS --no-compiler-runtime --no-translations
    )
    install(SCRIPT "${SQUIRREL_GUI_DEPLOY_SCRIPT}" COMPONENT squirrel)
endif()

# ----- DCMTK DLLs ----------------------------------------------------------------
# Every DLL of the imported shared libraries the exes link (DCMTK, plus Qt
# libraries that windeployqt also copies), for the configuration being
# installed. This works whether SQUIRREL_DCMTK_DIR is a DCMTK install tree
# (bin/*.dll) or a build tree (bin/Release/*.dll).
foreach(_exe squirrel-gui squirrel)
    if(TARGET ${_exe})
        install(FILES $<TARGET_RUNTIME_DLLS:${_exe}> DESTINATION ${CMAKE_INSTALL_BINDIR}
                COMPONENT squirrel)
    endif()
endforeach()

# ----- 7-Zip -----------------------------------------------------------------------
# libsquirrel loads 7z.dll at runtime; it looks next to the executable first, so
# ship it with the app instead of requiring 7-Zip to be installed.
set(SQUIRREL_7ZIP_DLL "C:/Program Files/7-Zip/7z.dll" CACHE FILEPATH
    "64-bit 7z.dll to bundle in the Windows installer")
if(EXISTS "${SQUIRREL_7ZIP_DLL}")
    install(FILES "${SQUIRREL_7ZIP_DLL}" DESTINATION ${CMAKE_INSTALL_BINDIR} COMPONENT squirrel)
    # 7-Zip is LGPL (with unRAR restriction); ship its license alongside.
    get_filename_component(_7z_dir "${SQUIRREL_7ZIP_DLL}" DIRECTORY)
    if(EXISTS "${_7z_dir}/License.txt")
        install(FILES "${_7z_dir}/License.txt" DESTINATION ${CMAKE_INSTALL_BINDIR}
                RENAME 7zip-License.txt COMPONENT squirrel)
    endif()
else()
    message(WARNING "7z.dll not found at '${SQUIRREL_7ZIP_DLL}'. The installer will "
                    "not include it and squirrel will need 7-Zip installed on the "
                    "target machine. Set -DSQUIRREL_7ZIP_DLL=<path to 64-bit 7z.dll>.")
endif()

# ----- MSVC runtime ----------------------------------------------------------------
set(CMAKE_INSTALL_SYSTEM_RUNTIME_DESTINATION ${CMAKE_INSTALL_BINDIR})
set(CMAKE_INSTALL_SYSTEM_RUNTIME_COMPONENT squirrel)
set(CMAKE_INSTALL_UCRT_LIBRARIES TRUE)
include(InstallRequiredSystemLibraries)

# ----- license (WiX wants .txt or .rtf) ---------------------------------------------
configure_file("${PROJECT_SOURCE_DIR}/../LICENSE" "${CMAKE_BINARY_DIR}/LICENSE.txt" COPYONLY)
install(FILES "${CMAKE_BINARY_DIR}/LICENSE.txt" DESTINATION . COMPONENT squirrel)

# ----- CPack / WiX --------------------------------------------------------------------
set(CPACK_GENERATOR "WIX")
# Package only the 'squirrel' component above. bit7z's own install rules (its
# headers and static library) are not wanted in the installer.
set(CPACK_INSTALL_CMAKE_PROJECTS "${CMAKE_BINARY_DIR};squirrel;squirrel;/")
set(CPACK_PACKAGE_NAME "Squirrel")
set(CPACK_PACKAGE_VENDOR "Olin Neuropsychiatry Research Center")
set(CPACK_PACKAGE_DESCRIPTION_SUMMARY "Viewer and command line tools for squirrel data packages")
set(CPACK_PACKAGE_HOMEPAGE_URL "https://github.com/gbook/squirrel")
set(CPACK_PACKAGE_INSTALL_DIRECTORY "Squirrel")
set(CPACK_RESOURCE_FILE_LICENSE "${CMAKE_BINARY_DIR}/LICENSE.txt")

# Windows Installer limits the major and minor version to 255, so 2026.11.519
# becomes 26.11.519 inside the MSI (it still sorts correctly for upgrades). The
# full version is in the file name and in the exe's file properties.
math(EXPR _msi_major "${SQUIRREL_VERSION_YEAR} - 2000")
set(CPACK_PACKAGE_VERSION "${_msi_major}.${SQUIRREL_VERSION_MONTH}.${SQUIRREL_VERSION_BUILD}")
set(CPACK_PACKAGE_FILE_NAME "squirrel_${SQUIRREL_VERSION}_Windows-x64")

# Start Menu shortcut "Squirrel" -> squirrel-gui.exe, plus a desktop shortcut.
set(CPACK_PACKAGE_EXECUTABLES "squirrel-gui" "Squirrel")
set(CPACK_CREATE_DESKTOP_LINKS "squirrel-gui")
set(CPACK_WIX_PROGRAM_MENU_FOLDER "Squirrel")

# Never change this GUID: it is how a newer .msi finds and replaces an older one.
set(CPACK_WIX_UPGRADE_GUID "DC4313A4-38A6-40C7-B83C-988B1A631CB3")
set(CPACK_WIX_INSTALL_SCOPE "perMachine")
set(CPACK_WIX_PRODUCT_ICON "${PROJECT_SOURCE_DIR}/squirrel-gui/resources/squirrel.ico")
set(CPACK_WIX_PROPERTY_ARPURLINFOABOUT "https://github.com/gbook/squirrel")
set(CPACK_WIX_PROPERTY_ARPHELPLINK "https://docs.neuroinfodb.org/nidb/contribute/squirrel-data-sharing-format")
# .sqrl file association and PATH entry.
set(CPACK_WIX_PATCH_FILE "${SQUIRREL_PACKAGING_DIR}/wix-patch.xml")
# Let the user choose the install folder.
set(CPACK_WIX_UI_REF "WixUI_InstallDir")

include(CPack)
