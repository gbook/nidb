/**
*  util.h
*  String and misc utility functions for the XAG and XPP projects.
*  Copyright 2004-2010 Michael Foster (Cross-Browser.com)
*  Distributed under the terms of the GNU LGPL
*/

// Includes

#include <string.h>
#include <ctype.h>

// Constants

#define STRTOK_MAX_LEN 100

// Types

#ifndef bool
#define bool int
#define true 1
#define false 0
#endif

// Global Variables

extern char *g_delimiters; // Token delimiters

// Function Prototypes

bool is_word_end(char c);
bool is_line_end(char c);
void skip_white_space(char **s);
void rtrim(char *s);
int stricmp(const char *s1, const char *s2);
char *uitoa(unsigned int value, char *string, int radix);
char *strrepl(char *str, size_t bufsiz, char *oldstr, char *newstr, bool line_start, bool whole_word);
int strreplg(char *str, size_t bufsiz, char *oldstr, char *newstr, bool whole_word);
char *strstrw(char *str, char *word, bool line_start);
char *str_tok(char *str, char *del, char *tok_type);


// end util.h
