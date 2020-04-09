/**
*  xpp.c
*  A general purpose text preprocessor.
*  Copyright 2004-2010 Michael Foster (Cross-Browser.com)
*  Distributed under the terms of the GNU LGPL
*
*  v1.0, 11Feb10, Minor changes.
*  v0.01 beta, 9Mar09, See xag.c.
*/

// Includes -------------------------------------------------------------------

#include "xpp.h"
#include "util.h"

// Module Variables -----------------------------------------------------------

// Symbol Table
struct tag_symbol
{
  char id[XPP_MAX_ID_LEN];   // identifier
  char val[XPP_MAX_VAL_LEN]; // value
  bool ww;                   // indicates whole-word comparison
};
static struct tag_symbol m_symbol[XPP_MAX_SYMBOLS];
static int m_symbol_len = 0;

// Options
static struct
{
  bool output;
  bool verbose;
  bool debug;
} m_option = { true, false, false }; // Defaults

// Output file and input files pathnames
static char m_output_file[XPP_MAX_PATH_LEN] = {0};
static FILE *m_output_fp;
static char m_input_file[XPP_MAX_INPUT_FILES][XPP_MAX_PATH_LEN];
static int m_input_file_len = 0;

// Buffers
static char m_line_buf[XPP_MAX_LINE_LEN];
static char m_id_buf[XPP_MAX_ID_LEN];
static char m_path_buf[XPP_MAX_PATH_LEN];

// Function Definitions -------------------------------------------------------

int main(int argc, char *argv[])
{
  // Read cmd line arguments
  if (!read_cmd_args(argc, argv)) {
    printf(XPP_HELP);
    return 1;
  }

  if (m_option.verbose) {
    printf(XPP_BANNER);
  }

  // Create output file
  if (m_option.output) {
    if (!create_output_file()) {
      return 2;
    }
  }

  // Report results.
  if (m_option.output) {
    if (m_option.verbose) {
      printf("done.\n");
    }
    else {
      printf("XPP created %s\n", m_output_file);
    }
  }
  if (m_option.debug) {
    debug_report();
  }

  return 0; // success
}

/**
 Read cmd line arguments
*/
bool read_cmd_args(int argc, char *argv[])
{
  int i, v;
  char *p;

  // must be at least two args: an output file and an input file
  if (argc <= 2) {
    return false;
  }

  // read args
  for (i = 1; i < argc; ++i) {
    v = -1;
    p = argv[i];
    if (*p == '-') {
      v = false;
    }
    else if (*p == '+') {
      v = true;
    }
    if (v >= 0) {
      ++p;
      if (!strcmp(p, "o")) {
        m_option.output = v;
      }
      else if (!strcmp(p, "v")) {
        m_option.verbose = v;
      }
      else if (!strcmp(p, "d")) {
        m_option.debug = v;
        if (v) { // +d forces +v
          m_option.verbose = v;
        }
      }
    }
    else if (!m_output_file[0]) { // output file name not yet set so set it (first non-option argument)
      strcpy(m_output_file, p);
    }
    else { // must be an input file name so append it to the list
      strcpy(m_input_file[m_input_file_len], p);
      ++m_input_file_len;
    }
  }
  return true;
}

/**
 Create the output file.
*/
bool create_output_file()
{
  int i;

  if (m_option.verbose) {
    printf("creating %s\n", m_output_file);
  }

  // Create empty output file
  if ((m_output_fp = fopen(m_output_file, "w")) == NULL) {
    printf("Error: Could not create output file: %s\n", m_output_file);
    return false;
  }

  // Loop thru all input files and append to output file
  if (m_option.verbose) {
    printf("from:\n");
  }
  for (i = 0; i < m_input_file_len; ++i) {
    if (append_to_output(m_input_file[i])) {
      if (m_option.verbose) {
        printf("%s\n", m_input_file[i]);
      }
    }
    else if (m_option.verbose) {
      printf("%s (output failed)\n", m_input_file[i]);
    }
  }

  fclose(m_output_fp);
  return true;
}

/**
 Apply preprocessing and append in_name to output file.
*/
bool append_to_output(char *in_name)
{
  FILE *input_fp;
  int ln = 0, mode = XPP_OUTP;

  if ((input_fp = fopen(in_name, "r")) == NULL) {
    printf("Error: could not open \"%s\"\n", in_name);
    return false;
  }

  while (fgets(m_line_buf, XPP_MAX_LINE_LEN, input_fp) != NULL) {

    ++ln; // line number
    if (preprocess(ln, m_line_buf, &mode) == XPP_OUTP) {
      if (m_option.output) {
        if (fputs(m_line_buf, m_output_fp) == EOF) {
          fclose(input_fp);
          printf("Error: could not write line %i to \"%s\"\n", ln, m_output_file);
          return false;
        }
      }
    }
  } // end while (fgets

  fclose(input_fp);
  return true;
}

/**
 @returns:
 XPP_OUTP = output this line,
 XPP_SKIP = skip this line
*/

int preprocess(int ln, char *line, int *mode)
{
  int i;
  char *p;

  p = line;
  skip_white_space(&p);
  if (*p == '#') {
    ++p;
    // else
    if (strncmp(p, "else", 4) == 0) {
      // toggle mode
      if (*mode == XPP_OUTP) {
        *mode = XPP_SKIP;
      }
      else {
        *mode = XPP_OUTP;
      }
      return XPP_SKIP;
    }
    // endif
    if (strncmp(p, "endif", 5) == 0) {
      *mode = XPP_OUTP;
      return XPP_SKIP;
    }
    if (*mode == XPP_OUTP) { // directives only processed in output mode
      // comment
      if (strncmp(p, "//", 2) == 0) {
        return XPP_SKIP;
      }
      // include
      if (strncmp(p, "include ", 8) == 0) {
        pp_include(p + 7);
        return XPP_SKIP;
      }
      // define
      if (strncmp(p, "define ", 7) == 0) {
        pp_define(p + 6, false);
        return XPP_SKIP;
      }
      // defword
      if (strncmp(p, "defword ", 8) == 0) {
        pp_define(p + 7, true);
        return XPP_SKIP;
      }
      // undef
      if (strncmp(p, "undef ", 6) == 0) {
        pp_undef(p + 5);
        return XPP_SKIP;
      }
      // ifdef
      if (strncmp(p, "ifdef ", 6) == 0) {
        *mode = pp_ifdef(p + 5);
        return XPP_SKIP;
      }
      // ifndef
      if (strncmp(p, "ifndef ", 7) == 0) {
        *mode = pp_ifndef(p + 6);
        return XPP_SKIP;
      }
    }
  } // end if (*p == '#')

  // text replacements
  if (*mode == XPP_OUTP) {
    for (i = m_symbol_len - 1; i >= 0 ; --i) {
      if (strreplg(line, XPP_MAX_LINE_LEN, m_symbol[i].id, m_symbol[i].val, m_symbol[i].ww) < 0) {
        printf("Warning: missed a replacement on line %i: \"%s\" -> \"%s\"\n", ln, m_symbol[i].id, m_symbol[i].val);
      }
    }
  }

  return *mode;
}

/**
*/
void pp_include(char *p)
{
  parse_id(p, m_path_buf);
  if (append_to_output(m_path_buf)) {
    if (m_option.verbose) {
      printf("%s (included)\n", m_path_buf);
    }
  }
  else if (m_option.verbose) {
    printf("%s (include failed)\n", m_path_buf);
  }
}

/**
*/
void pp_define(char *p, bool ww)
{
  int i, si;

  p = parse_id(p, m_id_buf);
  si = get_symbol_index(m_id_buf);
  if (si < 0) {
    si = m_symbol_len; // not defined so add it
    strcpy(m_symbol[si].id, m_id_buf);
    m_symbol[si].val[0] = 0; // default val is empty str
    ++m_symbol_len;
  }                   // else if defined change its value

  if (!is_line_end(*p)) {
    skip_white_space(&p);
    i = 0;
    while (!is_line_end(*p)) { // get pp value, to end of line
      m_symbol[si].val[i++] = *p++;
    }
    m_symbol[si].val[i] = 0; // terminate string
  }
  m_symbol[si].ww = ww; // to use whole-word search or not
}

/**
*/
void pp_undef(char *p)
{
  int si;

  p = parse_id(p, m_id_buf);
  si = get_symbol_index(m_id_buf);
  if (si >= 0) {
    strcpy(m_symbol[si].id, XPP_UNDEFINED);
    m_symbol[si].val[0] = 0; // empty str
  }
}

/**
*/
int pp_ifdef(char *p)
{
  parse_id(p, m_id_buf);
  if (get_symbol_index(m_id_buf) >= 0) {
    return XPP_OUTP;
  }
  else {
    return XPP_SKIP;
  }
}

/**
*/
int pp_ifndef(char *p)
{
  parse_id(p, m_id_buf);
  if (get_symbol_index(m_id_buf) < 0) {
    return XPP_OUTP;
  }
  else {
    return XPP_SKIP;
  }
}

/**
*/
char *parse_id(char *p, char *buf)
{
  int i;
  char quote_ch;

  i = 0;
  skip_white_space(&p);
  // Get pp identifier.
  if (*p == '"' || *p == '\'') {
    // If first char is a quote then the id includes everything up to the closing quote.
    quote_ch = *p;
    ++p; // skip the opening quote
    while (*p && *p != quote_ch) {
      buf[i++] = *p++;
    }
    ++p; // point to char after closing quote
  }
  else {
    // If first char is not a quote then id includes everything up to the first
    // whitespace or newline char.
    while (!is_word_end(*p)) {
      buf[i++] = *p++;
    }
  }
  buf[i] = 0; // terminate string
  return p; // points to first char after id
}

/**
 If id matches an id in the symbol table then return the array index,
 else return -1.
*/
int get_symbol_index(char *id)
{
  int i;

  for (i = 0; i < m_symbol_len; ++i) {
    if (strcmp(id, m_symbol[i].id) == 0) {
      return i;
    }
  }

  return -1;
}

/**
*/
void debug_report()
{
  int i;

  printf("Output File:\n  \"%s\"\n", m_output_file);
  printf("Input Files:\n");
  for (i = 0; i < m_input_file_len; ++i) {
    printf("%i: \"%s\"\n", i, m_input_file[i]);
  }
  printf("Options:\n  o=%i, v=%i, dbg=%i\n", m_option.output, m_option.verbose, m_option.debug);
  printf("Symbol Table:\n");
  for (i = 0; i < m_symbol_len; ++i) {
    printf("%i: \"%s\" = \"%s\"\n", i, m_symbol[i].id, m_symbol[i].val);
  }
}

// end xpp.c
