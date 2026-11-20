# ------------------------------------------------------------------------------
# libsquirrel (target: squirrellib, file: libsquirrel.a / squirrel.lib)
# squirrel command line utility (target: squirrel)
# ------------------------------------------------------------------------------
# Configured from ../CMakeLists.txt, which provides the bit7z and ftxui targets.

find_package(Qt6 REQUIRED COMPONENTS Core Sql)

# DCMTK: built from source and installed to /usr/local on the Linux build hosts.
# On Windows ../CMakeLists.txt has already put SQUIRREL_DCMTK_DIR
# (default <repo>/bin/dcmtk-bin) first in CMAKE_PREFIX_PATH.
if(UNIX)
    list(APPEND CMAKE_PREFIX_PATH /usr/local)
elseif(WIN32)
    list(APPEND CMAKE_PREFIX_PATH "C:/Program Files (x86)/DCMTK" "C:/Program Files/DCMTK")
endif()
find_package(DCMTK REQUIRED CONFIG)

# ----- libsquirrel ------------------------------------------------------------
add_library(squirrellib STATIC
    bids.cpp
    convert.cpp
    dicom.cpp
    extract.cpp
    info.cpp
    modify.cpp
    parrec.cpp
    squirrel.cpp
    squirrelAnalysis.cpp
    squirrelDataDictionary.cpp
    squirrelExperiment.cpp
    squirrelGroupAnalysis.cpp
    squirrelImageIO.cpp
    squirrelIntervention.cpp
    squirrelObservation.cpp
    squirrelPipeline.cpp
    squirrelSeries.cpp
    squirrelStudy.cpp
    squirrelSubject.cpp
    utils.cpp

    bids.h
    convert.h
    dicom.h
    extract.h
    info.h
    modify.h
    parrec.h
    squirrel.h
    squirrel.sql.h
    squirrelAnalysis.h
    squirrelDataDictionary.h
    squirrelExperiment.h
    squirrelGroupAnalysis.h
    squirrelImageIO.h
    squirrelIntervention.h
    squirrelObservation.h
    squirrelPipeline.h
    squirrelSeries.h
    squirrelStudy.h
    squirrelSubject.h
    squirrelTypes.h
    squirrelVersion.h
    utils.h
)
set_target_properties(squirrellib PROPERTIES OUTPUT_NAME squirrel)

target_compile_definitions(squirrellib PRIVATE SQUIRREL_BUILD)
# Consumers (CLI, GUI, Python) include "squirrel.h" etc. from this directory.
target_include_directories(squirrellib PUBLIC "${CMAKE_CURRENT_SOURCE_DIR}")
# squirrel.cpp uses #include "bit7z.hpp" (no bit7z/ prefix).
target_include_directories(squirrellib PRIVATE "${PROJECT_SOURCE_DIR}/bit7z/include/bit7z")

target_link_libraries(squirrellib
    PUBLIC  Qt6::Core Qt6::Sql DCMTK::DCMTK
    PRIVATE bit7z)

if(WIN32)
    target_link_libraries(squirrellib PUBLIC oleaut32 ole32)
endif()

# ----- dcm2niix: in-process DICOM -> NIfTI (Linux only) -----------------------
# On Windows the library shells out to dcm2niix.exe instead.
if(UNIX)
    set(DCM2NIIX_DIR "${PROJECT_SOURCE_DIR}/dcm2niix")
    set(DCM2NIIX_SOURCES
        ${DCM2NIIX_DIR}/nii_dicom.cpp
        ${DCM2NIIX_DIR}/nii_dicom_batch.cpp
        ${DCM2NIIX_DIR}/nifti1_io_core.cpp
        ${DCM2NIIX_DIR}/nii_foreign.cpp
        ${DCM2NIIX_DIR}/nii_ortho.cpp
        ${DCM2NIIX_DIR}/jpg_0XC3.cpp
        ${DCM2NIIX_DIR}/ujpeg.cpp
        ${DCM2NIIX_DIR}/cJSON.cpp
        ${DCM2NIIX_DIR}/base64.cpp)
    target_sources(squirrellib PRIVATE ${DCM2NIIX_SOURCES})
    # Bundled, unmodified third-party code with many upstream warnings: silence
    # warnings for these files only, so squirrel's own warnings stay visible.
    set_source_files_properties(${DCM2NIIX_SOURCES} PROPERTIES COMPILE_OPTIONS "-w")
    target_compile_definitions(squirrellib PRIVATE
        USE_DCM2NIIX_LIB myDisableOpenJPEG myEnableJNIFTI)
    target_include_directories(squirrellib PRIVATE "${DCM2NIIX_DIR}")
    # SSE speedup for the converter. All current targets are x86_64; guard this
    # before building for another architecture.
    if(CMAKE_SYSTEM_PROCESSOR MATCHES "x86_64|AMD64")
        target_compile_options(squirrellib PRIVATE -msse2 -mfpmath=sse)
    endif()
endif()

# ----- squirrel command line utility ------------------------------------------
if(SQUIRREL_BUILD_CLI)
    add_executable(squirrel main.cpp explore.cpp explore.h)
    target_link_libraries(squirrel PRIVATE
        squirrellib
        ftxui::component ftxui::dom ftxui::screen)
    # The binary is copied to /usr/local/bin and packaged, so keep DCMTK's
    # location in its runtime search path (as the qmake build did).
    if(UNIX)
        set_target_properties(squirrel PROPERTIES
            BUILD_RPATH "/usr/local/lib;/usr/local/lib64"
            INSTALL_RPATH "/usr/local/lib;/usr/local/lib64")
    endif()
    squirrel_copy_dcmtk_dlls(squirrel)
    squirrel_copy_7zip(squirrel)
endif()
