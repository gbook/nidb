/**
*  xag.h
*  A file aggregator for the X Library.
*  Copyright 2004-2010 Michael Foster (Cross-Browser.com)
*  Distributed under the terms of the GNU LGPL
*/

// Includes

#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <time.h>
#include <ctype.h>

#ifdef __GNUC__
#include <dirent.h>
#else // WIN32
#include <dir.h>
#endif

// Constants

#define XAG_VER "1.0"

#define XAG_MAX_LINE_LEN 2000
#define XAG_MAX_TOKEN_LEN XAG_MAX_LINE_LEN
#define XAG_MAX_SYMBOLS 300
#define XAG_MAX_APP_FILES XAG_MAX_SYMBOLS
#define XAG_MAX_PATH_LEN 250
#define XAG_MAX_NAME_LEN 100
#define XAG_MAX_EXT_LEN 10

#define XAG_OBF_PFX "X"
#define XAG_INVALID 0xFFFF
#define XAG_XML_FILE_MASK "*.xml"
#define XAG_LIB_EXT ".js"
#define XAG_PRJ_EXT ".xag"
#define XAG_XML_ROOT "<x_symbol"
#define XLIB_SYM_ID "xLibrary"
#define XLIB_SYM_FILE "xlibrary.js"
#define XLIB_BANNER "/*! Built from X %s by XAG %s. %s */\n"

#define ERR_NO_ARG     1
#define ERR_PRJ_IO     2
#define ERR_VER_IO     3
#define ERR_SYM_IO     4
#define ERR_SYM_DEP_IO 5
#define ERR_APP_DEP_IO 6
#define ERR_OUTPUT_IO  7
#define ERR_MALLOC     8

#define MSG_HR "\n--------------------------------------------------------------------------------\n"
#define MSG_DBG_MODE "\nXAG is writing log file to stdout\n"
#define MSG_LIB_DEPS MSG_HR "Library File Dependencies\n"
#define MSG_APP_DEPS MSG_HR "Application File Dependencies\n"
#define MSG_OUTPUT   MSG_HR "Creating Output File\n"
#define MSG_DBG_DUMP MSG_HR "Debug Dump\n"

#define XAG_BANNER "XAG " XAG_VER ", an X tool from cross-browser.com\n"
#define XAG_HELP "\n" XAG_BANNER \
"usage: xag prj_name[.ext]\n\
  where .ext defaults to \".xag\"\n\
  and prj file format is...\n\
    ; line comments start with ';'\n\
    options [+|-]lib [+|-]app [+|-]dep [+|-]xpp [+|-]dbg  ; optional\n\
    output <pathname>  ; optional. default \"<prj>.js\"\n\
    libpath <path>  ; x/lib directory (with trailing slash). required\n\
    obfprefix <string>  ; xpp file obfuscation prefix. optional. default \"X\" \n\
    appfiles  ; app file pathnames from next line to end of file. required\n\
  and options are...\n\
    lib = create output file. default +\n\
    app = include app js files in output file. default -\n\
    dep = symbol dependents included in output. default +\n\
    xpp = write xpp obfuscation file to stdout. default -\n\
    dbg = debug msgs to stdout. default -\n"

// Types

#ifndef bool
#define bool int
#define true 1
#define false 0
#endif

// Function Prototypes

int main(int argc, char *argv[]);
bool read_project_file(char *name);
bool read_version_file();
bool read_symbol_library();
bool get_symbol_dependents(int sym_idx);
bool get_app_file_symbols(char *fname);
bool create_output_file();
bool append_to_output(FILE *out_fp, char *name, int sym_idx);
int get_symbol_index(char *token, char sym_type);
void set_dependent(int sym_idx, int dep);
void include_symbol(int idx);
void set_symbol_complete(char *s);
int compare_fn(const void *ele1, const void *ele2);
void show_debug_info();
bool init();
void cleanup();
bool add_app_file(char *fname);
void create_xpp_file();

// end xag.h
