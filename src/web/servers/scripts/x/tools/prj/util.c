/**
*  util.c
*  String and misc utility functions for the XAG and XPP projects.
*  Copyright 2004-2010 Michael Foster (Cross-Browser.com)
*  Distributed under the terms of the GNU LGPL
*/


#include "util.h"

// Global Variables

char *g_delimiters = " \n\t,.;:{}()[]=<>?!+-*/%~^|&'\""; // Token delimiters

// Function Definitions

/*
 Return true if c is zero, whitespace or newline, else false.
*/
bool is_word_end(char c)
{
  return (bool)(c == 0 || c == ' ' || c == '\t' || c == '\r' || c == '\n');
}

/*
 Return true if c is zero or newline, else false.
*/
bool is_line_end(char c)
{
  return (bool)(c == 0 || c == '\r' || c == '\n');
}

/*
 Increment *s past all white-space.
*/
void skip_white_space(char **s)
{
  while (**s == ' ' || **s == '\t')
  {
    ++*s;
  }
}

/*
 Remove whitespace and newlines from end of s
*/
void rtrim(char *s)
{
  char *p;

  p = s + (strlen(s) - 1);
  while (*p == ' ' || *p == '\t' || *p == '\n' || *p == '\r')
  {
    --p;
  }
  ++p;
  *p = 0;
}

/*
  ANSI-compliant case-insensitive string comparison.
  I modified it from this source: http://c.snippets.org/
*/
int stricmp(const char *s1, const char *s2)
{
  int dif = 0;

  do
  {
    dif = tolower(*s1++) - tolower(*s2++);
  } while (!dif && *s1 && *s2);
  return dif;
}

/*
  ANSI-compliant? unsigned int to string conversion.
  All alpha chars will be uppercase. I modified it from this source:
  http://www.koders.com/c/fid5F9B1CF12E947E5030A132D309A367C5CCB671CE.aspx?s=itoa
*/
char *uitoa(unsigned int value, char *string, int radix)
{
  int i;
  char *sp;
  char tmp[33];
  char *tp = tmp;
  unsigned int v = value;

  if (!string || radix > 36 || radix <= 1)
  {
    return 0;
  }
  while (v || tp == tmp)
  {
    i = v % radix;
    v = v / radix;
    if (i < 10)
    {
      *tp++ = i + '0';
    }
    else
    {
      *tp++ = i + 'A' - 10; // use uppercase
    }
  }
  sp = string;
  while (tp > tmp)
  {
    *sp++ = *--tp;
  }
  *sp = 0;
  return string;
}

/*
 String Replace
 Replace oldstr by newstr in string str
 contained in buffer of size bufsiz.

 str and oldstr/newstr should not overlap.
 The empty string ("") is found at the beginning of every string.

 Returns: pointer to first location after where newstr was inserted.
          str if oldstr was not found.
          NULL if replacement would overflow str's buffer
 I modified it from this source: http://c.snippets.org/
*/
char *strrepl(char *str, size_t bufsiz, char *oldstr, char *newstr, bool line_start, bool whole_word)
{
  int oldlen, newlen;
  char *p = str, *q;

  oldlen = strlen(oldstr);
  newlen = strlen(newstr);
  if (whole_word)
  {
    if (NULL == (p = strstrw(p, oldstr, line_start))) return str;
  }
  else
  {
    if (NULL == (p = strstr(p, oldstr))) return str;
  }

  if ((strlen(str) + newlen - oldlen + 1) > bufsiz) return NULL;

  memmove(q = p+newlen, p+oldlen, strlen(p+oldlen)+1);
  memcpy(p, newstr, newlen);

  return q;
}

/* String Replace Global
   Returns -1 if str is not long enough, else a count of replacements made.
*/
int strreplg(char *str, size_t bufsiz, char *oldstr, char *newstr, bool whole_word)
{
  int rep = 0;
  char *q, *p = str;
  size_t bufrem = bufsiz;
  bool line_start = true;

  while (p)
  {
    q = strrepl(p, bufrem, oldstr, newstr, line_start, whole_word);
    if (!q) return -1; // error, str not long enough
    if (p == q) return rep;
    line_start = false;
    bufrem -= q - p;
    p = q;
    ++rep; // count replacements
  }
  return rep;
}

/* Find word in str by whole word
*/
char *strstrw(char *str, char *word, bool line_start)
{
  char *p = str;
  int len = strlen(word);

  while (1)
  {
    if (NULL == (p = strstr(p, word))) return NULL; // not found
    if (
      ( (line_start && p == str) || strchr(g_delimiters, *(p-1)) )
      &&
      ( strchr(g_delimiters, *(p+len)) || *(p+len)==0 || *(p+len)=='\n' )
    )
    {
      return p;
    }
    p += len;
  }
}

/* Tokenize a string
 Works just like the standard strtok except:
 1. does not modify str
 2. assigns 0, 'P' or 'M' to tok_type
*/
char *str_tok(char *str, char *del, char *tok_type)
{
  char *t, *p;
  static char *s, *so, tok[STRTOK_MAX_LEN];

  *tok_type = 0;
  tok[0] = 0;
  if (str)
  {
    s = so = str;
  }
  while (*s)
  {
    if (!strchr(del, *s)) // first non-del char is start of token
    {
      // is token preceded by '.'?
      p = s;
      if (p != so)
      {
        do {
          --p;
        } while (p != so && (*p == ' ' || *p == '\t')); // assumes ' ' and '\t' are delimeters!
        if (*p == '.')
        {
          *tok_type = 'P'; // assumes '.' is on same line as token! yuck!
        }
      }
      // get chars of token
      t = tok;
      while (*s && !strchr(del, *s))
      {
        *t++ = *s++;
      }
      *t = 0;
      // is token followed by '('?
      p = s;
      skip_white_space(&p);
      if (*tok_type == 'P' && *p == '(')
      {
        *tok_type = 'M'; // assumes '.' and '(' are on same line as token! yuck!
      }
      return tok; // return pointer to token
    }
    ++s;
  } // end while (*s)
  return NULL; // no token found
}

// end util.c
