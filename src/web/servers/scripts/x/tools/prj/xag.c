/**
*  xag.c
*  A file aggregator for the X Library.
*  Copyright 2004-2010 Michael Foster (Cross-Browser.com)
*  Distributed under the terms of the GNU LGPL
*
*  v1.0, 11Feb10
*  It's about time we called this thing 1.0, eh? Added more constants. Changed
*  these to use dynamic mem allocation: m_app_file and m_cfg. Modified debug
*  msg formatting. Added option to create xpp obfuscation file. Grouped some
*  globals under m_cfg.
*  v0.04, 10Feb10
*  Added constants for array sizes. prev_token and a local var "tok" were too
*  small. Large tokens would crash xag. Changed these to use dynamic mem
*  allocation: m_symbol, m_prev_token, and m_tmp_token.
*  v0.03 beta, 27Jan10
*  Added time and timezone to the date printed in the XLIB_BANNER.
*  v0.02 beta, 27Nov09
*  Added the "output" option.
*  v0.01 beta, 9Mar09
*  Split XC into two projects: XAG and XPP. Removed the compression, obfuscation,
*  and objectification features so this is now just an aggregator. Now compiling
*  with MinGW. For complete revision history see 'xc_reference.php'.
*
*  NOTE: The major limitation of this C version of XAG is that it does not
*  fully support non-static symbols of type M or P (symbols that are accessed
*  via an instance of their containing object). It does support static symbols
*  of type M or P. I have an experimental version of XAG written in Java which
*  provides full support for both.
*/

// Includes -------------------------------------------------------------------

#include "xag.h"
#include "util.h"

// Module Variables -----------------------------------------------------------

typedef struct tag_symbol
{
  char id[XAG_MAX_NAME_LEN];  // Symbol identifier.
  char src[XAG_MAX_NAME_LEN]; // Source file name.
  int dep[XAG_MAX_SYMBOLS];   /* An array of dependencies for this symbol,
                                 each array element is an index into the symbols array. */
  int dep_len;                // Length of the dep array.
  int inc;                    // Number of times this symbol was found in the app files.
  bool out;                   // Indicates this src file has been written to the output file.
  char type;                  // Symbol type, 'V', 'O', 'P', 'M' or 'F'.
} t_symbol;

static t_symbol* m_symbol = NULL;
static int m_symbol_len = 0;

typedef struct tag_cfg
{
  bool lib; // true = Generate output file. Default = true.
  bool app; // true = Includes application js files into output file. Default = false.
  bool dep; // true = Symbol dependents included in output. Default = true.
  bool xpp; // true = Write XPP obfuscation file to stdout. Overrides lib. Default = false.
  bool dbg; // true = Debug info to stdout. Overrides xpp. Default = false.
  char x_ver[XAG_MAX_NAME_LEN];    // Current X version string read from XLIB_SYM_FILE.
  char prj_name[XAG_MAX_PATH_LEN]; // The project name (filename without extension).
  char prj_ext[XAG_MAX_EXT_LEN];   // The project file extension.
  char libpath[XAG_MAX_PATH_LEN];  // Path to the X lib files.
  char output[XAG_MAX_PATH_LEN];   // The output filename.
  char obfprefix[XAG_MAX_EXT_LEN]; // The xpp file obfuscation prefix.
} t_cfg;

static t_cfg* m_cfg = NULL;

static char** m_app_file = NULL; // Array of app file pathnames.
static int m_app_file_len = 0;   // Length of the m_app_file array.

static char* m_prv_token = NULL; // The previous token.
static char* m_tmp_token = NULL; // A temporary token buffer.

// Function Definitions -------------------------------------------------------

int main(int argc, char *argv[])
{
  int i;

  /* Expects argv[1] to be project name with optional extension, and expects it
     in the current directory. */
  if (argc <= 1) {
    printf(XAG_HELP);
    return ERR_NO_ARG;
  }

  // Allocate memory.
  if (!init()) {
    return ERR_MALLOC;
  }

  // Read project file.
  m_cfg->output[0] = 0;
  if (!read_project_file(argv[1])) {
    printf(XAG_HELP);
    cleanup();
    return ERR_PRJ_IO;
  }

  if (m_cfg->dbg) {
    fprintf(stderr, MSG_DBG_MODE);
    printf(XAG_BANNER);
  }

  // Parse X Library version string.
  if (!read_version_file()) {
    cleanup();
    return ERR_VER_IO;
  }

  // Create symbol table and sort it.
  if (!read_symbol_library()) {
    cleanup();
    return ERR_SYM_IO;
  }
  qsort(m_symbol, m_symbol_len, sizeof(t_symbol), compare_fn);

  // Get symbols used in library files.
  if (m_cfg->dbg) {
    printf(MSG_LIB_DEPS);
  }
  for (i = 0; i < m_symbol_len; ++i) {
    m_prv_token[0] = 0;
    if (!get_symbol_dependents(i)) {
      cleanup();
      return ERR_SYM_DEP_IO;
    }
  }

  // Get symbols used in application files.
  if (m_cfg->dbg) {
    printf(MSG_APP_DEPS);
  }
  for (i = 0; i < m_app_file_len; ++i) {
    m_prv_token[0] = 0;
    if (!get_app_file_symbols(m_app_file[i])) {
      cleanup();
      return ERR_APP_DEP_IO;
    }
  }

  // Force the inclusion of the xLibrary object.
  include_symbol(get_symbol_index(XLIB_SYM_ID, 'O'));

  // Create output library file.
  if (m_cfg->lib) {
    if (m_cfg->dbg) {
      printf(MSG_OUTPUT);
    }
    if (!create_output_file()) {
      cleanup();
      return ERR_OUTPUT_IO;
    }
  }

  // Report results.
  if (m_cfg->lib) {
    if (m_cfg->dbg) {
      printf("\n");
    }
    printf("XAG created %s\n", m_cfg->output);
  }

  if (m_cfg->dbg) {
    show_debug_info();
  }

  if (m_cfg->xpp) {
    create_xpp_file();
  }

  cleanup();
  return 0; // success
}

/**
  Reads options, libpath and appfiles from project file.
  See the xc_reference for project file details.
*/
bool read_project_file(char *name)
{
  FILE *fp;
  bool opt;
  char *p, line[XAG_MAX_LINE_LEN], *t, token[XAG_MAX_PATH_LEN];

  // cfg defaults
  strcpy(m_cfg->obfprefix, XAG_OBF_PFX);
  // XC does not require a specific extension for the project file,
  // but supplies XAG_PRJ_EXT if an ext is not found.
  strcpy(m_cfg->prj_name, name);
  p = strchr(m_cfg->prj_name, '.');
  if (p) {
    strcpy(m_cfg->prj_ext, p);
    *p = 0;
  }
  else strcpy(m_cfg->prj_ext, XAG_PRJ_EXT);

  // Open the project file.
  strcpy(line, m_cfg->prj_name); // use 'line' temporarily
  strcat(line, m_cfg->prj_ext);
  if ((fp = fopen(line, "r")) == NULL) {
    printf("\nError: Could not open project file: %s%s\n", m_cfg->prj_name, m_cfg->prj_ext);
    return false;
  }

  // option defaults
  m_cfg->lib = true;
  m_cfg->app = false;
  m_cfg->dep = true;
  m_cfg->xpp = false;
  m_cfg->dbg = false;

  while (fgets(line, XAG_MAX_LINE_LEN, fp) != NULL ) {
    p = line;
    skip_white_space(&p);
    // skip newlines and comment lines
    if (*p == ';' || *p == '\n') {
      continue;
    }
    // expect directive as first token on line
    t = token;
    while (*p && *p != ' ' && *p != '\t' && *p != '\n' && *p != ';') {
      *t++ = *p++;
    }
    *t = 0;
    skip_white_space(&p);
    // process directives
    if (!stricmp(token, "output")) {
      t = token;
      while (*p && *p != '\n' && *p != ';') {
        *t++ = *p++;
      }
      *t = 0;
      rtrim(token);
      strcpy(m_cfg->output, token);
    }
    else if (!stricmp(token, "libpath")) {
      t = token;
      while (*p && *p != '\n' && *p != ';') {
        *t++ = *p++;
      }
      *t = 0;
      rtrim(token);
      strcpy(m_cfg->libpath, token);
    }
    else if (!stricmp(token, "obfprefix")) {
      t = token;
      while (*p && *p != '\n' && *p != ';') {
        *t++ = *p++;
      }
      *t = 0;
      rtrim(token);
      strcpy(m_cfg->obfprefix, token);
    }
    else if (!stricmp(token, "appfiles")) {
      // get app file pathnames (expects one per line)
      m_app_file_len = 0;
      while (fgets(line, XAG_MAX_LINE_LEN, fp) != NULL ) {
        p = line;
        skip_white_space(&p);
        if (*p != ';' && *p != '\n') {
          rtrim(p);
          add_app_file(p);
          //strcpy(m_app_file[m_app_file_len++], p);
        }
      }
    }
    else if (!stricmp(token, "options")) {
      // parse space-separated options on this line
      while (*p && *p != '\n' && *p != ';') {
        t = token;
        while (*p && *p != ' ' && *p != '\t' && *p != '\n' && *p != ';') {
          *t++ = tolower(*p++);
        }
        *t = 0;
        skip_white_space(&p);
        opt = *token == '-' ? false : true;
        if (strstr(token, "lib")) { m_cfg->lib = opt; }
        else if (strstr(token, "app")) { m_cfg->app = opt; }
        else if (strstr(token, "dep")) { m_cfg->dep = opt; }
        else if (strstr(token, "xpp")) { m_cfg->xpp = opt; }
        else if (strstr(token, "dbg")) { m_cfg->dbg = opt; }
      } // end while
    }
  }

  // dbg overrides xpp, xpp overrides lib
  if (m_cfg->dbg) {
    m_cfg->xpp = false;
  }
  else if (m_cfg->xpp) {
    m_cfg->lib = false;
  }

  fclose(fp);
  return true;
}

/**
  Parse X version string from XLIB_SYM_FILE
*/
bool read_version_file()
{
  int i = 0;
  bool ret;
  FILE *fp;
  char line[XAG_MAX_LINE_LEN], *p;

  *m_cfg->x_ver = 0;
  strcpy(line, m_cfg->libpath);
  strcat(line, XLIB_SYM_FILE);
  if ((fp = fopen(line, "r")) == NULL)
  {
    printf("\nWarning: Could not find X Library version file: %s%s\n", m_cfg->libpath, XLIB_SYM_FILE);
    return false;
  }

  while (fgets(line, XAG_MAX_LINE_LEN, fp) != NULL)
  {
    p = strstr(line, "version");
    if (p)
    {
      p += 7;
      while (*p && *p != '\"' && *p != '\'')
      {
        ++p;
      }
      ++p;
      while (*p && *p != '\"' && *p != '\'')
      {
        m_cfg->x_ver[i++] = *p++;
      }
      m_cfg->x_ver[i] = 0;
      break; // out of while
    }
  } // end while

  ret = true;
  if (!*m_cfg->x_ver)
  {
    printf("\nWarning: Could not read X Library version from file: %s%s\n", m_cfg->libpath, XLIB_SYM_FILE);
    fclose(fp);
    ret = false;
  }
  else if (m_cfg->dbg) {
    printf("\nBuilding from X Library %s\n", m_cfg->x_ver);
  }
  fclose(fp);
  return ret;
}

/**
* Open every XML file in the lib folder and parse out the symbol's id, src and type.
*/
bool read_symbol_library()
{
  FILE *fp;
  int i, root_state, src_state, type_state;
  char *p, dir[XAG_MAX_PATH_LEN], pth[XAG_MAX_PATH_LEN], line[XAG_MAX_LINE_LEN];

#ifdef __GNUC__

  DIR *dirptr;
  struct dirent *ent;

  strcpy(dir, m_cfg->libpath);
  dirptr = opendir(dir);
  if (!dirptr) {
    printf("\nError: get_valid_symbols could not find %s%s\n", m_cfg->libpath, XAG_XML_FILE_MASK);
    return false;
  }
  while ((ent = readdir(dirptr)) != NULL) {
    if (!strstr(ent->d_name, ".xml")) {
      continue;
    }
    strcpy(pth, m_cfg->libpath);
    strcat(pth, ent->d_name);
    if ((fp = fopen(pth, "r")) == NULL) {
      printf("\nError: get_valid_symbols could not find xml file: %s\n", pth);
      closedir(dirptr);
      return false;
    }

#else // WIN32

  long hFile;
  struct _finddata_t fd;

  strcpy(dir, m_cfg->libpath);
  strcat(dir, XAG_XML_FILE_MASK);
  if ((hFile = _findfirst(dir, &fd)) == -1L) {
    printf("\nError: get_valid_symbols could not find %s%s\n", m_cfg->libpath, XAG_XML_FILE_MASK);
    return false;
  }
  do {
    strcpy(pth, m_cfg->libpath);
    strcat(pth, fd.name);
    if ((fp = fopen(pth, "r")) == NULL) {
      printf("\nError: get_valid_symbols could not find xml file: %s\n", pth);
      _findclose(hFile);
      return false;
    }

#endif // end else WIN32

    root_state = 0;
    src_state = 0;
    type_state = 0;
    while (fgets(line, XAG_MAX_LINE_LEN, fp) != NULL )
    {
      if (root_state == 0)
      {
        if (strstr(line, XAG_XML_ROOT))
        {
          p = strstr(line, "id");
          if (p)
          {
            p += 2;
            while (*p && (*p == ' ' || *p == '\t' || *p == '=' || *p == '\'' || *p == '"'))
            {
              ++p;
            }
            for (i = 0; *p && *p != '\'' && *p != '"'; ++i, ++p)
            {
              m_symbol[m_symbol_len].id[i] = *p;
            }
            m_symbol[m_symbol_len].id[i] = 0;
            ++root_state;
          }
          else
          {
            printf("\nWarning: invalid xml file: %s\n", pth); // invalid xml file!
          }
        }
      } // end if (root_state == 0)
      else
      {
        if (src_state == 0)
        {
          if (strstr(line, "<sources")) { ++src_state; }
        }
        if (src_state == 1)
        {
          if (strstr(line, "<src")) { ++src_state; }
        }
        if (src_state == 2)
        {
          p = strstr(line, "<file>");
          if (p)
          {
            p += 6;
            skip_white_space(&p);
            for (i = 0; *p && *p != '<' && *p != ' ' && *p != '\t'; ++i, ++p)
            {
              m_symbol[m_symbol_len].src[i] = *p;
            }
            m_symbol[m_symbol_len].src[i] = 0;
            ++src_state;
          }
        } // end if (src_state == 2)
        if (type_state == 0)
        {
          p = strstr(line, "<type>");
          if (p)
          {
            p += 6;
            skip_white_space(&p);
            m_symbol[m_symbol_len].type = toupper(*p);
            ++type_state;
          }
        } // end if (type_state == 0)
      }
    } // end while (fgets

    fclose(fp);

    m_symbol[m_symbol_len].out = false;
    m_symbol[m_symbol_len].dep_len = 0;
    for (i = 0; i < XAG_MAX_SYMBOLS; ++i)
    {
      m_symbol[m_symbol_len].dep[i] = XAG_INVALID;
    }
    ++m_symbol_len;

#ifdef __GNUC__

  }
  closedir(dirptr);

#else // WIN32

  } while (_findnext(hFile, &fd) == 0);
  _findclose(hFile);

#endif // end else WIN32

  return true;
} // end read_symbol_library

/**
 Update symbol table with dependency info from m_symbol[sym_idx].src.
*/
bool get_symbol_dependents(int sym_idx)
{
  int ln = 0;
  FILE *fp;
  char line[XAG_MAX_LINE_LEN], *p, tok_type;
  int dep;

  strcpy(line, m_cfg->libpath);
  strcat(line, m_symbol[sym_idx].src);
  if ((fp = fopen(line, "r")) == NULL)
  {
    printf("\nError: get_symbol_dependents could not find symbol %i file: %s%s\n", sym_idx, m_cfg->libpath, m_symbol[sym_idx].src);
    return false;
  }

  if (m_cfg->dbg) {
    printf("\nX Symbols found in lib file %s:\n", m_symbol[sym_idx].src);
  }

  while (fgets(line, XAG_MAX_LINE_LEN, fp) != NULL )
  {
    ++ln; // line number
    p = line;
    if (*p && *p != '\n')
    {
      p = str_tok(line, g_delimiters, &tok_type);
      while (p != NULL)
      {
        dep = get_symbol_index(p, tok_type);
        if (m_cfg->dep && dep != XAG_INVALID && dep != sym_idx)
        {
          set_dependent(sym_idx, dep);
          if (m_cfg->dbg) {
            printf("%s(%i), ", p, dep);
          }
        }
        strcpy(m_prv_token, p);
        p = str_tok(NULL, g_delimiters, &tok_type);
      }
    }
  }
  if (m_cfg->dbg) {
    printf("\n");
  }
  fclose(fp);
  return true;
}

/**
 Determine which X lib files get included in the output library
 by searching fname for X symbols.
*/
bool get_app_file_symbols(char *fname)
{
  FILE *fp;
  int ln = 0, sym_idx;
  bool in_scr = false, out_scr = false, is_html = false;
  char line[XAG_MAX_LINE_LEN], *p, tok_type;

  if ((fp = fopen(fname, "r")) == NULL)
  {
    printf("\nError: get_app_file_symbols could not find application file: %s\n", fname);
    return false;
  }

  if (strstr(fname, ".htm"))
  {
    is_html = true;
  }

  if (m_cfg->dbg) {
    printf("\nX Symbols found in app file %s:\n", fname);
  }

  while (fgets(line, XAG_MAX_LINE_LEN, fp) != NULL )
  {
    ++ln; // app line number
    if (line[0] != '\n')
    {
      if (is_html && !in_scr)
      {
        if (strstr(line, "<script") || strstr(line, "<SCRIPT")) // if at the start of a script element
        {
          in_scr = true;
          if (m_cfg->dbg) {
            printf("(in_scr on line %i) ", ln);
          }
        }
      }
      if (!is_html || in_scr)
      {
        if (is_html && (strstr(line, "</script>") || strstr(line, "</SCRIPT>"))) // if at the end of a script element
        {
          out_scr = true;
        }
        p = str_tok(line, g_delimiters, &tok_type);
        while (p != NULL)
        {
          sym_idx = get_symbol_index(p, tok_type);
          if (sym_idx != XAG_INVALID)
          {
            include_symbol(sym_idx);
            if (m_cfg->dbg) {
              printf("%s(%i), ", p, sym_idx);
            }
          }
          strcpy(m_prv_token, p);
          p = str_tok(NULL, g_delimiters, &tok_type);
        }
        if (out_scr)
        {
          in_scr = out_scr = false;
          if (m_cfg->dbg) {
            printf("(out_scr on line %i) ", ln);
          }
        }
      }
    }
  }
  if (m_cfg->dbg) {
    printf("\n");
  }
  fclose(fp);
  return true;
}

/**
 Create the output library file.
*/
bool create_output_file()
{
  int i, a = 0, sym_idx;
  FILE *out_fp;
  char tm_str[20];
  time_t tm;

  if (!m_cfg->output[0]) {
    strcpy(m_cfg->output, m_cfg->prj_name);
    strcat(m_cfg->output, XAG_LIB_EXT);
  }
  if ((out_fp = fopen(m_cfg->output, "w")) == NULL)
  {
    printf("\nError: Could not create output file: %s\n", m_cfg->output);
    return false;
  }

  tm = time(NULL);
  strftime(tm_str, 19, "%d%b%y %H:%M UT", gmtime(&tm));
  fprintf(out_fp, XLIB_BANNER, m_cfg->x_ver, XAG_VER, tm_str);

  // For every symbol which has 'm_symbol[sym_idx].inc > 0'
  // include m_symbol[sym_idx].src in the output.

  if (m_cfg->dbg) {
    printf("\nAppending Symbol Files\n");
  }
  for (sym_idx = 0; sym_idx < m_symbol_len; ++sym_idx)
  {
    if (m_symbol[sym_idx].inc)
    {
      if (m_cfg->dbg) {
        printf("%s(%i), ", m_symbol[sym_idx].src, sym_idx);
      }
      if (!append_to_output(out_fp, m_symbol[sym_idx].src, sym_idx)) {
        printf("\nWarning: Could not add %s%s to output\n", m_symbol[sym_idx].src, XAG_LIB_EXT);
      }
    }
  }
  if (m_cfg->dbg) {
    printf("\n");
  }
  if (m_cfg->app)
  {
    if (m_cfg->dbg) {
      printf("\nAppending App Files\n");
    }
    fprintf(out_fp, "\n/* Application */\n");
    // Include the app Js files in the output.
    for (i = 0; i < m_app_file_len; ++i)
    {
      if (strstr(m_app_file[i], XAG_LIB_EXT))
      {
        if (m_cfg->dbg) {
          printf("%s(%i), ", m_app_file[i], i);
        }
        if (append_to_output(out_fp, m_app_file[i], XAG_INVALID))
        {
          ++a;
        }
      }
    }
    if (m_cfg->dbg) {
      printf("\n");
    }
    if (a) fprintf(out_fp, "\n");
  } // end if (m_cfg->app)

  fclose(out_fp);
  return true;
}

/**
 Appends name to the output library js file. Applies optional compression,
 optional function name obfuscation and optional function name object prefix.
*/
bool append_to_output(FILE *out_fp, char *name, int sym_idx)
{
  int ln = 0;
  FILE *lib_fp;
  bool is_app_file;
  char lib_name[XAG_MAX_PATH_LEN], line[XAG_MAX_LINE_LEN];

  if (sym_idx == XAG_INVALID) // append an app file
  {
    strcpy(lib_name, name);
    is_app_file = true;
  }
  else // append a sym file
  {
    if (m_symbol[sym_idx].out) return true;
    strcpy(lib_name, m_cfg->libpath);
    strcat(lib_name, name);
    is_app_file = false;
  }

  if ((lib_fp = fopen(lib_name, "r")) == NULL)
  {
    printf("\nError: append_to_output could not find file: %s\n", lib_name);
    return false;
  }

  while (fgets(line, XAG_MAX_LINE_LEN, lib_fp) != NULL )
  {
    ++ln; // line number
    if (fputs(line, out_fp) == EOF)
    {
      fclose(lib_fp);
      printf("\nError: Could not write to output file: %s\n", m_cfg->output);
      return false;
    }
  } // end while (fgets

  fclose(lib_fp);
  if (!is_app_file) // appended a symbol file, not an app file
  {
    set_symbol_complete(name); // indicate that this file has been output
  }
  return true;
}

/**
 Determine if token matches an ID in the symbol table.
 If sym_type is 'P' or 'M' then the token searched for
 is: "m_prv_token.token".
*/
int get_symbol_index(char *token, char sym_type)
{
  int i, idx = XAG_INVALID;

  if (sym_type == 'P' || sym_type == 'M') {
    strcpy(m_tmp_token, m_prv_token);
    strcat(m_tmp_token, ".");
    strcat(m_tmp_token, token);
  }
  else {
    strcpy(m_tmp_token, token);
  }
  for (i = 0; i < m_symbol_len; ++i) {
    if (!strcmp(m_tmp_token, m_symbol[i].id)) { // token == id
      idx = i;
      break; // out of 'for'
    }
  }
  return idx;
}

/**
 if the 'm_symbol[sym_idx].dep' array does not already contain 'dep'
 then assign 'dep' to the next available array element.
*/
void set_dependent(int sym_idx, int dep)
{
  int i;

  for (i = 0; i < m_symbol[sym_idx].dep_len; ++i) {
    if (m_symbol[sym_idx].dep[i] == dep) {
      return;
    }
  }
  m_symbol[sym_idx].dep[m_symbol[sym_idx].dep_len++] = dep;
}

/**
 Indicate the X lib file m_symbol[sym_idx] (and all it's dependents)
 to be included in the output library file.
*/
void include_symbol(int sym_idx)
{
  int i;

  if (sym_idx >= m_symbol_len) {
    return;
  }
  if (!m_symbol[sym_idx].inc++) {
    for (i = 0; i < m_symbol[sym_idx].dep_len; ++i) {
      include_symbol(m_symbol[sym_idx].dep[i]);
    }
  }
}

/**
 For every symbol with src == s, mark that symbol
 as having been included in the output.
*/
void set_symbol_complete(char *s)
{
  int i;

  if (s && *s) {
    for (i = 0; i < m_symbol_len; ++i) {
      if (!strcmp(s, m_symbol[i].src)) {
        m_symbol[i].out = true;
      }
    }
  }
}

/**
 This is the comparison function passed to 'qsort' when sorting the
 symbol table. Symbols are sorted by type and then by id. The type
 precedence is: V, O, P, M, F.
*/
int compare_fn(const void *ele1, const void *ele2)
{
  int ret = 0;
  t_symbol *s1, *s2;

  s1 = (t_symbol *)ele1;
  s2 = (t_symbol *)ele2;
  ret = strcmp(s1->id, s2->id);
  // Variables come before all other types.
  if (s1->type == 'V') {
    if (s2->type != 'V') ret = -1;
  }
  else if (s2->type == 'V') {
    if (s1->type != 'V') ret = 1;
  }
  // Objects come next.
  else if (s1->type == 'O') {
    if (s2->type != 'O') ret = -1;
  }
  else if (s2->type == 'O') {
    if (s1->type != 'O') ret = 1;
  }
  // Properties come next.
  else if (s1->type == 'P') {
    if (s2->type != 'P') ret = -1;
  }
  else if (s2->type == 'P') {
    if (s1->type != 'P') ret = 1;
  }
  // Methods come next.
  else if (s1->type == 'M') {
    if (s2->type != 'M') ret = -1;
  }
  else if (s2->type == 'M') {
    if (s1->type != 'M') ret = 1;
  }
  // Functions come last.
  // Return the comparison result:
  return ret;
}

/**
 Display project info, app file list and symbol table info.
*/
void show_debug_info()
{
  int i, j;
  char n[6];

  printf(MSG_DBG_DUMP);
  printf("\nproject file: %s%s\n", m_cfg->prj_name, m_cfg->prj_ext);
  printf("output lib file: %s%s\n", m_cfg->prj_name, XAG_LIB_EXT);
  printf("library path: %s\n", m_cfg->libpath);
  printf("options: lib=%i, app=%i, dep=%i, xpp=%i, dbg=%i\n",
          m_cfg->lib,
          m_cfg->app,
          m_cfg->dep,
          m_cfg->xpp,
          m_cfg->dbg);

  printf("\nApplication Files:\n");
  for (i = 0; i < m_app_file_len; ++i)
  {
    printf("%i: %s\n", i, m_app_file[i]);
  }

  if (m_cfg->lib) {
    printf("\nOutput File: ");
    printf(" %s%s\n", m_cfg->prj_name, XAG_LIB_EXT);
  }

  printf("\nSymbol Table\n\n");
  for (i = 0; i < m_symbol_len; ++i)
  {
    printf("%i(%s): %s(inc=%i)(src=%s)(type=%c)", i, uitoa(i,n,36), m_symbol[i].id, m_symbol[i].inc, m_symbol[i].src, m_symbol[i].type);
    for (j = 0; j < m_symbol[i].dep_len; ++j)
    {
      // name(number)
      printf(", %s(%i)", m_symbol[m_symbol[i].dep[j]].id, m_symbol[i].dep[j]);
    }
    printf("\n");
  }

  printf("\nSymbols\n\n");
  for (i = 0; i < m_symbol_len; ++i)
  {
    printf("%s\n", m_symbol[i].id);
  }
}

/**
 Write XPP obfuscation file to stdout. Excludes symbols of type M and P.
*/
void create_xpp_file()
{
  int i = 0, j = 0;
  char n[6];

  fprintf(stderr, "\nXAG is writing XPP obfuscation file to stdout\n");
  for ( ; i < m_symbol_len; ++i) {
    if (m_symbol[i].inc && m_symbol[i].type != 'M' && m_symbol[i].type != 'P' && stricmp(XLIB_SYM_ID, m_symbol[i].id)) {
      printf("%s#defword %s %s%s", (j ? "\n" : ""), m_symbol[i].id, m_cfg->obfprefix, uitoa(j, n, 36));
      ++j;
    }
  }
}

/**
 Memory allocation.
*/
bool init()
{
  m_symbol = malloc(XAG_MAX_SYMBOLS * sizeof(t_symbol));
  m_prv_token = malloc(XAG_MAX_TOKEN_LEN + 1);
  m_tmp_token = malloc(XAG_MAX_TOKEN_LEN + 1);
  m_app_file = malloc(XAG_MAX_APP_FILES * sizeof(char*));
  m_cfg = malloc(sizeof(t_cfg));
  if (!m_symbol || !m_prv_token || !m_tmp_token || !m_app_file || !m_cfg) {
    cleanup();
    return false;
  }
  return true;
}

/**
 Append a pathname to the m_app_file array.
*/
bool add_app_file(char *fname)
{
  char *p = malloc(strlen(fname) + 1);
  if (p) {
    strcpy(p, fname);
    *(m_app_file + m_app_file_len) = p;
    ++m_app_file_len;
    return true;
  }
  return false;
}

/**
 Free allocated memory.
*/
void cleanup()
{
  if (m_symbol) free(m_symbol);
  if (m_prv_token) free(m_prv_token);
  if (m_tmp_token) free(m_tmp_token);
  if (m_cfg) free(m_cfg);
  if (m_app_file) {
    while (m_app_file_len--) {
      free(*(m_app_file + m_app_file_len));
    }
    free(m_app_file);
  }
}

// end xag.c
