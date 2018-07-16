<?php
/*
  X Library Viewer Copyright 2005-2010 Michael Foster (Cross-Browser.com).
  Distributed under the terms of the GNU LGPL.
  This code implements the viewer. It displays the contents of the
  symbol's xml and js files on one page. This code is written for PHP5.
*/

if (!stristr($_SERVER['SERVER_NAME'], 'localhost')) { // if not local server
  $lib_path = './';
}
else {
  $lib_path = '/Documents and Settings/Mike/My Documents/www/Cross-Browser.com/x/lib/';
}

function txfm($s)
{
  $s = htmlspecialchars($s);
  $s = str_replace('[code]', '<pre>', $s);
  $s = str_replace('[/code]', '</pre>', $s);
  return $s;
}

$i = 0;
// Get array of .xml filenames in x/lib, and sort them:
$xml_files = array();

if ($dir = opendir($lib_path)) { 
  while (($file_name = readdir($dir)) !== false) { 
    if (stristr($file_name, '.xml')) {
      $xml_files[$i++] = substr($file_name, 0, -4);
    }  
  }   
  closedir($dir); 
} 

sort($xml_files);

// Validate URL argument by searching for it in xml_files array.
// Assumes filenames are lowercase.
$url_arg = '';
$valid_url_arg = false;
if (isset($_GET['sym'])) {
  $url_arg = strtolower($_GET['sym']);
}
else if (isset($_GET['s'])) {
  $url_arg = strtolower($_GET['s']);
}
if ($url_arg != '') {
  if (in_array($url_arg, $xml_files)) {
    $valid_url_arg = true;
  }
}

if (!$valid_url_arg) {
  $url_arg = 'xlibrary'; // default
  $valid_url_arg = true;
}

$o = simplexml_load_file($url_arg . '.xml'); // Load the xml file.
$htmlTitle = 'X: ' . $o['id'];

$siteRoot = '../..'; // relative path to root, no trailing slash
$thisPage = 5; // 0 = other, 1 = talk, 2 = toys, 3 = home, 4 = news, 5 = x viewer, 6 = x compiler
$clpsLeft = true; // leftColumn collapsible elements
$clpsRight = false; // rightColumn collapsible elements
$vToolbar = true; // vertical toolbar
include $siteRoot.'/inc_header_1.php';
?>

<style type='text/css'>
.h5i {
  color:#596380;
  background:transparent;
  margin:1.6em 2em .6em 0;
  padding:0;
  font-weight:bold;
  /**/
  font-size:xx-small;
  voice-family: "\"}\"";
  voice-family: inherit;
  font-size:x-small;
}
html>body .h5i {
  font-size:x-small;
}
div {
  margin: .6em 0;
}
</style>

<?php
include $siteRoot.'/inc_header_2.php';
?>

<h2>X Library Viewer</h2>

<div>
<p><a href='../../downloads/'>Download</a> the <b>X</b> Distribution File.</p>
</div>

<h3>X Index</h3>
<div>
<?php
include $siteRoot.'/x/lib/symbol_index.php';
?>
</div>

<?php
if ($valid_url_arg) {

  echo '<h3>' . $o['id'] . '</h3><div>';

  if (isset($o->description) && strlen($o->description[0]) > 2) {
    echo '<h4>Description</h4><div>' . txfm($o->description[0]) . '</div>';
  }

  if (isset($o->syntax) && strlen($o->syntax[0]) > 2) {
    echo '<h4>Syntax</h4><div><pre>' . txfm($o->syntax[0]) . '</pre></div>';
  }

  if (isset($o->parameters) && isset($o->parameters[0]->par)) {
    $child_count = count($o->parameters[0]->par);
    if ($child_count) {
      echo '<h4>Parameters</h4><div>';
      for ($i = 0; $i < $child_count; $i++) {
        echo "<div><span class='h5i'>" . $o->parameters[0]->par[$i]->name[0] . '</span>' . txfm($o->parameters[0]->par[$i]->note[0]) . '</div>';
      }
      echo '</div>';
    }
  }

  if (isset($o->return) && strlen($o->return[0]) > 2) {
    echo '<h4>Return</h4><div>' . txfm($o->return[0]) . '</div>';
  }

  // get X symbol source from .js file
  if (isset($o->sources) && isset($o->sources[0]->src) && isset($o->sources[0]->src[0]->file)) {
    $file_name = $o->sources[0]->src[0]->file[0];
    $file_handle = fopen($file_name, 'r');
    if ($file_handle) {
      $source = fread($file_handle, filesize($file_name)); 
      fclose($file_handle); 
      echo '<h4>Source</h4><div><p>' . $o->sources[0]->src[0]->note[0] . '</p><pre>' . htmlentities($source) . '</pre></div>';
    }

    $child_count = count($o->sources[0]->src);
    if ($child_count > 1) { // index 0 is the default implementation
      echo '<h4>Alternative Implementations</h4><div>';
      for ($i = 1; $i < $child_count; $i++) {
        echo "<p><a href='view.php?s=" . $o->sources[0]->src[$i]->sym[0] . "'>" . $o->sources[0]->src[$i]->sym[0] . '</a> - ' . $o->sources[0]->src[$i]->note[0] . '</p>';
      }
      echo '</div>';
    }
  }

  if (isset($o->dependencies) && isset($o->dependencies[0]->dep)) {
    $child_count = count($o->dependencies[0]->dep);
    if ($child_count) {
      echo '<h4>Dependencies</h4><div><p>';
      for ($i = 0; $i < $child_count; $i++) {
        echo "<a href='view.php?s=" . $o->dependencies[0]->dep[$i] . "'>" . $o->dependencies[0]->dep[$i] . '</a>';
        if ($i < $child_count - 1) {
          echo ', ';
        } 
      }
      echo '</p></div>';
    }
  }

//  if (isset($o->properties) && isset($o->properties[0]->prop) && isset($o->properties[0]->prop[0]->name) && strlen($o->properties[0]->prop[0]->name[0]) > 2) {
  if (isset($o->properties) && isset($o->properties[0]->prop) && isset($o->properties[0]->prop[0]->name)) {
    $child_count = count($o->properties[0]->prop);
    if ($child_count) {
      echo '<h4>Public Properties</h4><div>';
      for ($i = 0; $i < $child_count; $i++) {
        echo "<div><span class='h5i'>" . $o->properties[0]->prop[$i]->name[0] . '</span>' . txfm($o->properties[0]->prop[$i]->note[0]) . '</div>';
      }
      echo '</div>';
    }
  }

//  if (isset($o->methods) && isset($o->methods[0]->meth) && isset($o->methods[0]->meth[0]->name) && strlen($o->methods[0]->meth[0]->name[0]) > 2) {
  if (isset($o->methods) && isset($o->methods[0]->meth) && isset($o->methods[0]->meth[0]->name)) {
    $child_count = count($o->methods[0]->meth);
    if ($child_count) {
      echo '<h4>Public Methods</h4><div>';
      for ($i = 0; $i < $child_count; $i++) {
        if ($o->methods[0]->meth[$i]->sym) {
          echo "<div><span class='h5i'>" . $o->methods[0]->meth[$i]->name[0] . '</span>' . "<a href='view.php?s=" . $o->methods[0]->meth[$i]->sym[0] . "'>view</a> - " . txfm($o->methods[0]->meth[$i]->note[0]) . '</div>';
        }
        else {
          echo "<div><span class='h5i'>" . $o->methods[0]->meth[$i]->name[0] . '</span>' . txfm($o->methods[0]->meth[$i]->note[0]) . '</div>';
        }
      }
      echo '</div>';
    }
  }

  if (isset($o->demos) && isset($o->demos[0]->demo) && isset($o->demos[0]->demo[0]->url) && strlen($o->demos[0]->demo[0]->url[0]) > 2) {
    $child_count = count($o->demos[0]->demo);
    if ($child_count) {
      echo '<h4>Demos</h4><div>';
      for ($i = 0; $i < $child_count; $i++) {
        if (strlen($o->demos[0]->demo[$i]->url[0]) > 10) {
          echo "<p><a href='" . $o->demos[0]->demo[$i]->url[0] . "'>" . basename($o->demos[0]->demo[$i]->url[0], '.php') . "</a> - " . $o->demos[0]->demo[$i]->note[0] . '</p>';
        }
      }
      echo '</div>';
    }
  }

  if (isset($o->tests) && isset($o->tests[0]->test) && isset($o->tests[0]->test[0]->url) && strlen($o->tests[0]->test[0]->url[0]) > 2) {
    $child_count = count($o->tests[0]->test);
    if ($child_count) {
      echo '<h4>Discussions &amp; Tests</h4><div>';
      for ($i = 0; $i < $child_count; $i++) {
        if (strlen($o->tests[0]->test[$i]->note[0]) > 10) {
          echo "<p><span class='h5i'>" . $o->tests[0]->test[$i]->date[0] . '</span>';
          echo "<a href='" . $o->tests[0]->test[$i]->url[0] . "'>" .  basename($o->tests[0]->test[$i]->url[0], '.html') . '</a>: ';
          echo $o->tests[0]->test[$i]->note[0] . '</p>';
        }
      }
      echo '</div>';
    }
  }

  if (isset($o->comments) && isset($o->comments[0]->comment) && isset($o->comments[0]->comment[0]->note) && strlen($o->comments[0]->comment[0]->note[0]) > 2) {
    $child_count = count($o->comments[0]->comment);
    if ($child_count) {
      echo '<h4>Notes</h4><div>';
      for ($i = 0; $i < $child_count; $i++) {
        if (strlen($o->comments[0]->comment[$i]->note[0]) > 10) {
          echo "<div><span class='h5i'>" . $o->comments[0]->comment[$i]->date[0] . '</span>' . txfm($o->comments[0]->comment[$i]->note[0]) . '</div>';
        }
      }
      echo '</div>';
    }
  }

  if (isset($o->revisions) && isset($o->revisions[0]->rev)) {
    $child_count = count($o->revisions[0]->rev);
    if ($child_count) {
      echo '<h4>Revisions</h4><div>';
      for ($i = 0; $i < $child_count; $i++) {
        if (strlen($o->revisions->rev[$i]->num[0]) > 0) {
          echo "<div><span class='h5i'>" . $o->revisions->rev[$i]->num[0] . ': ' . $o->revisions->rev[$i]->date[0] . '</span>' . ( strtolower($o['id'])=='xlibrary' ? $o->revisions->rev[$i]->note[0] : txfm($o->revisions->rev[$i]->note[0]) ) . '</div>';
        }
      }
      echo '</div>';
    }
  }
?>

</div> <!-- end H3 div -->

<?php
} // end if ($valid_url_arg)
?>

</div> <!-- end leftContent -->

<?php
include $siteRoot.'/inc_footer.php';
?>

</div> <!-- end leftColumn -->

<div id='rightColumn' class='column'>
<div class='rightContent'>

<?php
include $siteRoot.'/inc_license.php';
include $siteRoot.'/inc_sponsors2.php';
include $siteRoot.'/inc_support.php';
include $siteRoot.'/inc_about.php';
include $siteRoot.'/inc_search.php';
include $siteRoot.'/inc_userprj.php';
?>

</div> <!-- end rightContent -->
</div> <!-- end rightColumn -->

</body>
</html>
