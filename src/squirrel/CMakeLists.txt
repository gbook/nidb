# ------------------------------------------------------------------------------
# squirrel - top-level CMake project
# ------------------------------------------------------------------------------
# Builds, in one configure/build:
#   bit7z      (src/bit7z)    archive library used by libsquirrel (LZMA/7z)
#   ftxui      (src/ftxui)    terminal UI for 'squirrel explore' (CLI only)
#   squirrellib               libsquirrel static library   -> <build>/squirrel/libsquirrel.a
#   squirrel                  command line utility         -> <build>/squirrel/squirrel
#   squirrel-gui              Qt Widgets GUI (optional)    -> <build>/squirrel-gui/squirrel-gui
#   Windows: 'cpack -C Release' in the build folder makes an .msi (packaging/windows.cmake)
#
# Typical use (Linux):
#   cmake -S src -B bin/ubuntu24 -DCMAKE_PREFIX_PATH=~/Qt/6.9.3/gcc_64
#   cmake --build bin/ubuntu24 -j 16
#
# Qt Creator: File > Open File or Project > src/CMakeLists.txt
#
# Options:
#   -DSQUIRREL_BUILD_CLI=OFF            build only libsquirrel (no FTXUI, no CLI)
#   -DSQUIRREL_BUILD_GUI=OFF            do not build the GUI
#   -DBIT7Z_USE_LEGACY_IUNKNOWN=OFF     must match the 7-Zip library loaded at runtime.
#                                       OFF (default) = modern 7-Zip (23.01+), which is
#                                       what squirrel ships; ON = legacy p7zip 16.02 ABI
#                                       (no longer supported on Linux)
#   -DSQUIRREL_BUILD_7ZIP=OFF           Linux: do not build 7z.so (see below)
#   DCMTK is located with find_package(DCMTK CONFIG); on Linux it is looked for
#   in /usr/local by default, otherwise add its prefix to CMAKE_PREFIX_PATH.
# ------------------------------------------------------------------------------

cmake_minimum_required(VERSION 3.16)

# Version comes from squirrel/squirrelVersion.h (maintained by update_version.sh).
file(STRINGS "${CMAKE_CURRENT_SOURCE_DIR}/squirrel/squirrelVersion.h" _squirrel_ver_lines
     REGEX "#define UTIL_(VERSION_MAJ|VERSION_MIN|BUILD_NUM) ")
foreach(_line IN LISTS _squirrel_ver_lines)
    if(_line MATCHES "UTIL_VERSION_MAJ \"([0-9]+)\"")
        set(SQUIRREL_VERSION_YEAR "${CMAKE_MATCH_1}")
    elseif(_line MATCHES "UTIL_VERSION_MIN \"([0-9]+)\"")
        set(SQUIRREL_VERSION_MONTH "${CMAKE_MATCH_1}")
    elseif(_line MATCHES "UTIL_BUILD_NUM \"([0-9]+)\"")
        set(SQUIRREL_VERSION_BUILD "${CMAKE_MATCH_1}")
    endif()
endforeach()
set(SQUIRREL_VERSION "${SQUIRREL_VERSION_YEAR}.${SQUIRREL_VERSION_MONTH}.${SQUIRREL_VERSION_BUILD}")

project(squirrel VERSION ${SQUIRREL_VERSION} LANGUAGES C CXX)

if(NOT CMAKE_BUILD_TYPE AND NOT CMAKE_CONFIGURATION_TYPES)
    set(CMAKE_BUILD_TYPE Release CACHE STRING "Build type" FORCE)
endif()

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
# libsquirrel.a is also linked into the Python extension module (a shared
# object), so everything must be position independent.
set(CMAKE_POSITION_INDEPENDENT_CODE ON)

option(SQUIRREL_BUILD_CLI "Build the squirrel command line utility (and FTXUI)" ON)

# ----- bit7z ------------------------------------------------------------------
# Defaults for bit7z's own options. Anything passed with -D on the command line
# takes precedence, because these only set the cache if it is still empty.
set(BIT7Z_AUTO_FORMAT ON CACHE BOOL "bit7z: detect archive format automatically")
set(BIT7Z_USE_LEGACY_IUNKNOWN OFF CACHE BOOL "bit7z: legacy p7zip IUnknown ABI (OFF for modern 7-Zip)")
# bit7z turns verbose makefiles on in the cache; keep normal output.
set(CMAKE_VERBOSE_MAKEFILE OFF CACHE BOOL "")
add_subdirectory(bit7z)
# bit7z (cmake/OutputOptions.cmake) writes its archive into its SOURCE tree
# (src/bit7z/lib/x64). build-all.sh shares the source tree across distros, so
# that would let one distro link another distro's libbit7z64.a. Keep it in this
# build directory instead.
set_target_properties(bit7z PROPERTIES
    ARCHIVE_OUTPUT_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/bit7z")

# ----- 7-Zip library (Linux) -----------------------------------------------------
# libsquirrel loads 7z.so at runtime. Instead of depending on the distro's 7-Zip
# package (p7zip 16.02 on older systems, modern 7zip on newer ones, at different
# paths and with incompatible ABIs), build 7z.so from the same 7-Zip source that
# bit7z downloaded and compiles against, and copy it next to the squirrel and
# squirrel-gui executables (squirrel_copy_7zip below). The packages ship it there
# and libsquirrel looks there first (squirrel::Get7zipLibPath).
option(SQUIRREL_BUILD_7ZIP "Linux: build 7z.so from bit7z's 7-Zip source and copy it next to the executables" ON)
if(UNIX AND NOT APPLE AND SQUIRREL_BUILD_7ZIP)
    if(BIT7Z_USE_LEGACY_IUNKNOWN)
        message(FATAL_ERROR "SQUIRREL_BUILD_7ZIP builds a modern 7-Zip, which needs "
                            "BIT7Z_USE_LEGACY_IUNKNOWN=OFF")
    endif()
    get_directory_property(SQUIRREL_7ZIP_SRC DIRECTORY bit7z DEFINITION 7zip_SOURCE_DIR)
    set(_7z_bundle "${SQUIRREL_7ZIP_SRC}/CPP/7zip/Bundles/Format7zF")
    if(NOT EXISTS "${_7z_bundle}/makefile.gcc")
        message(FATAL_ERROR "7-Zip source not found at '${SQUIRREL_7ZIP_SRC}' (expected from "
                            "bit7z). Set -DSQUIRREL_BUILD_7ZIP=OFF to skip building 7z.so.")
    endif()
    find_program(SQUIRREL_MAKE NAMES make gmake)
    if(NOT SQUIRREL_MAKE)
        message(FATAL_ERROR "'make' is needed to build 7z.so")
    endif()
    include(ProcessorCount)
    ProcessorCount(_ncpu)
    if(_ncpu EQUAL 0)
        set(_ncpu 4)
    endif()
    set(SQUIRREL_7ZIP_DIR "${CMAKE_CURRENT_BINARY_DIR}/7zip")
    set(SQUIRREL_7ZIP_SO  "${SQUIRREL_7ZIP_DIR}/7z.so")
    add_custom_command(
        OUTPUT "${SQUIRREL_7ZIP_SO}"
        COMMAND "${SQUIRREL_MAKE}" -f makefile.gcc -j${_ncpu} "O=${SQUIRREL_7ZIP_DIR}/obj"
        COMMAND ${CMAKE_COMMAND} -E copy "${SQUIRREL_7ZIP_DIR}/obj/7z.so" "${SQUIRREL_7ZIP_SO}"
        # 7-Zip is LGPL, with the unRAR restriction on its RAR code: keep the
        # licenses with every copy of 7z.so.
        COMMAND ${CMAKE_COMMAND} -E copy "${SQUIRREL_7ZIP_SRC}/DOC/License.txt"     "${SQUIRREL_7ZIP_DIR}/7zip-License.txt"
        COMMAND ${CMAKE_COMMAND} -E copy "${SQUIRREL_7ZIP_SRC}/DOC/unRarLicense.txt" "${SQUIRREL_7ZIP_DIR}/7zip-unRarLicense.txt"
        COMMAND ${CMAKE_COMMAND} -E copy "${SQUIRREL_7ZIP_SRC}/DOC/copying.txt"     "${SQUIRREL_7ZIP_DIR}/7zip-LGPL-copying.txt"
        WORKING_DIRECTORY "${_7z_bundle}"
        COMMENT "Building the 7-Zip library (7z.so)"
        VERBATIM)
    add_custom_target(squirrel_7zip ALL DEPENDS "${SQUIRREL_7ZIP_SO}")
endif()

# Copy 7z.so (and its licenses) next to an executable, so it runs from the build
# folder and the installers can pick it up from there.
function(squirrel_copy_7zip target)
    if(TARGET squirrel_7zip)
        add_dependencies(${target} squirrel_7zip)
        add_custom_command(TARGET ${target} POST_BUILD
            COMMAND ${CMAKE_COMMAND} -E copy_if_different
                    "${SQUIRREL_7ZIP_DIR}/7z.so"
                    "${SQUIRREL_7ZIP_DIR}/7zip-License.txt"
                    "${SQUIRREL_7ZIP_DIR}/7zip-unRarLicense.txt"
                    "${SQUIRREL_7ZIP_DIR}/7zip-LGPL-copying.txt"
                    "$<TARGET_FILE_DIR:${target}>"
            COMMENT "Copying 7z.so next to ${target}")
    endif()
endfunction()

# ----- FTXUI (CLI only) -------------------------------------------------------
if(SQUIRREL_BUILD_CLI)
    set(FTXUI_BUILD_EXAMPLES OFF CACHE BOOL "")
    set(FTXUI_BUILD_TESTS    OFF CACHE BOOL "")
    set(FTXUI_BUILD_DOCS     OFF CACHE BOOL "")
    set(FTXUI_ENABLE_INSTALL OFF CACHE BOOL "")
    set(FTXUI_QUIET          ON  CACHE BOOL "")
    add_subdirectory(ftxui)
endif()

# ----- DCMTK location on Windows ----------------------------------------------
# Expected as an installed DCMTK tree (bin/, include/, lib/, cmake/) in
# <repo>/bin/dcmtk-bin, built with the same MSVC version and build type.
# Override with -DSQUIRREL_DCMTK_DIR=... ; the Program Files locations are
# still searched after it.
if(WIN32)
    get_filename_component(_squirrel_repo "${CMAKE_CURRENT_SOURCE_DIR}/.." ABSOLUTE)
    set(SQUIRREL_DCMTK_DIR "${_squirrel_repo}/bin/dcmtk-bin" CACHE PATH
        "Installed DCMTK (contains bin, include, lib, cmake)")
    list(PREPEND CMAKE_PREFIX_PATH "${SQUIRREL_DCMTK_DIR}")
    # A shared DCMTK build puts its DLLs in bin/; copy them next to the
    # executables so they run from the build folder (and from Qt Creator).
    file(GLOB SQUIRREL_DCMTK_DLLS "${SQUIRREL_DCMTK_DIR}/bin/*.dll")
endif()

function(squirrel_copy_dcmtk_dlls target)
    if(WIN32 AND SQUIRREL_DCMTK_DLLS)
        add_custom_command(TARGET ${target} POST_BUILD
            COMMAND ${CMAKE_COMMAND} -E copy_if_different
                    ${SQUIRREL_DCMTK_DLLS} "$<TARGET_FILE_DIR:${target}>"
            COMMENT "Copying DCMTK DLLs next to ${target}")
    endif()
endfunction()

# ----- squirrel library + CLI -------------------------------------------------
add_subdirectory(squirrel)

# ----- squirrel GUI (Qt Widgets) ----------------------------------------------
# Optional: skipped with a warning when Qt Widgets (and, on Linux, its OpenGL
# development files) are not available, so library/CLI builds never fail on it.
option(SQUIRREL_BUILD_GUI "Build the squirrel GUI (needs Qt6 Widgets)" ON)
if(SQUIRREL_BUILD_GUI)
    find_package(Qt6 QUIET COMPONENTS Widgets)
    if(Qt6Widgets_FOUND)
        add_subdirectory(squirrel-gui)
    else()
        message(WARNING "Qt6 Widgets not found: skipping the squirrel GUI (set SQUIRREL_BUILD_GUI=OFF to silence this)")
    endif()
endif()

# ----- Windows installer (.msi, via 'cpack') -----------------------------------
if(WIN32)
    include(packaging/windows.cmake)
endif()
