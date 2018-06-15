/**
*  xpp.h
*  A general purpose text preprocessor.
*  Copyright 2004-2010 Michael Foster (Cross-Browser.com)
*  Distributed under the terms of the GNU LGPL
*/

// Includes

#include <stdio.h>

// Constants

#define XPP_VER "1.0"
#define XPP_BANNER "XPP " XPP_VER ", an X tool from cross-browser.com\n"
#define XPP_HELP "\n" XPP_BANNER "usage: xpp [-|+]o [-|+]v [-|+]d output_file input_file1 [input_file2 ...]\n  where: o = output, v = verbose, d = debug\n"
#define XPP_MAX_SYMBOLS 200
#define XPP_MAX_ID_LEN 250
#define XPP_MAX_VAL_LEN 250
#define XPP_MAX_LINE_LEN 2000
#define XPP_MAX_PATH_LEN 250
#define XPP_MAX_INPUT_FILES 30
#define XPP_UNDEFINED "UNDEFINED"
#define XPP_OUTP  0
#define XPP_SKIP  1

// Types

#ifndef bool
#define bool int
#define true 1
#define false 0
#endif

// Function Prototypes

int main(int argc, char *argv[]);
bool read_cmd_args(int argc, char *argv[]);
bool create_output_file();
bool append_to_output(char *in_name);
int preprocess(int ln, char *line, int *mode);
void pp_include(char *p);
void pp_define(char *p, bool ww);
void pp_undef(char *p);
int pp_ifdef(char *p);
int pp_ifndef(char *p);
char *parse_id(char *p, char *buf);
int get_symbol_index(char *id);
void debug_report();

// end xpp.h
