# ------------------------------------------------------------------------------
# dcm2niix (in-process DICOM -> Nifti conversion)
# ------------------------------------------------------------------------------
# Compiles the dcm2niix core sources directly into the target so DICOM
# conversion no longer depends on an external dcm2niix executable. Bundled
# miniz (header-included by nii_dicom_batch.cpp) supplies gzip.
#
# This file is included by BOTH squirrellib.pro and squirrel.pro, so every
# Linux distro build (ubuntu24, debian12, rockylinux8, rockylinux9) picks it up
# through the shared .pro files. There is no separate build step (unlike bit7z,
# which is built via CMake in each build-<distro>.sh).
#
# WINDOWS NOTE: this block is intentionally Linux-only. On Windows,
# USE_DCM2NIIX_LIB is left undefined and ConvertDicom() falls back to invoking an
# external dcm2niix.exe. Building dcm2niix into the Windows target is NOT done
# here and would require: a Windows-appropriate replacement for the
# -msse2/-mfpmath flags, MSVC/MinGW compatibility checks for the dcm2niix
# sources, and verifying miniz/gzip behavior on Windows.
# ------------------------------------------------------------------------------

linux: {
    DEFINES += USE_DCM2NIIX_LIB myDisableOpenJPEG myEnableJNIFTI
    INCLUDEPATH += $$PWD/../dcm2niix
    # SSE speedup for the converter (x86_64; all current distro targets are x86_64).
    # Guard or remove these flags before targeting a non-x86_64 Linux architecture.
    QMAKE_CXXFLAGS += -msse2 -mfpmath=sse

    # dcm2niix is bundled, unmodified third-party code and emits a large number of
    # upstream compiler warnings. Rather than list its files in SOURCES (which would
    # force them to share squirrel's warning flags - qmake applies QMAKE_CXXFLAGS to
    # every file in the target), compile them through a dedicated extra-compiler that
    # appends -w. This suppresses warnings for dcm2niix ONLY, so warnings from
    # squirrel's own code remain visible. The resulting objects are linked via
    # variable_out = OBJECTS, exactly as SOURCES would be.
    dcm2niix_src = \
        $$PWD/../dcm2niix/nii_dicom.cpp \
        $$PWD/../dcm2niix/nii_dicom_batch.cpp \
        $$PWD/../dcm2niix/nifti1_io_core.cpp \
        $$PWD/../dcm2niix/nii_foreign.cpp \
        $$PWD/../dcm2niix/nii_ortho.cpp \
        $$PWD/../dcm2niix/jpg_0XC3.cpp \
        $$PWD/../dcm2niix/ujpeg.cpp \
        $$PWD/../dcm2niix/cJSON.cpp \
        $$PWD/../dcm2niix/base64.cpp

    dcm2niix.name = dcm2niix
    dcm2niix.input = dcm2niix_src
    dcm2niix.output = ${QMAKE_VAR_OBJECTS_DIR}dcm2niix_${QMAKE_FILE_BASE}$$QMAKE_EXT_OBJ
    # $(CXXFLAGS) already carries QMAKE_CXXFLAGS (incl. -msse2) and $(DEFINES);
    # the trailing -w disables all warnings for these translation units only.
    dcm2niix.commands = $$QMAKE_CXX $(CXXFLAGS) $(INCPATH) -w -c ${QMAKE_FILE_IN} -o ${QMAKE_FILE_OUT}
    dcm2niix.variable_out = OBJECTS
    dcm2niix.dependency_type = TYPE_C
    QMAKE_EXTRA_COMPILERS += dcm2niix
}
