<?php

include("includes/Bibliography.php");


class ExtMarkdownParser {
    private $command_args = ['theorem' => 2,
                     'definition' => 2,
                 'lemma' => 2,
                 'proof' => 1,
                 'ref' => 1,
                 'cite' => 1
                 ];
    private $symbols = [];
    private $counters = ['theorem' => 0, 'definition' => 0, 'lemma' => 0];
    private $biblio;
    private $pub_list = [];
    private $citations = [];

    public function __construct($biblio=NULL) {
        $this->biblio = $biblio;
    }

    public function parse($source, $in_env=false) {
        $lines = explode("\n", $source);

        $have_cmd = false;
        $have_brace = false;
        $inner = '';
        #cb_delim = '';
        $in_code_block = false;
        $stack = array();
        $escape = false;
        $alt_buf = '';
        $part = '';
        $out = '';
        $cmd = 0;
        $args = [];
        foreach ($lines as $line) {
            $escape = false;
            $offset = 0;
            $len = strlen($line);
            while ($offset < $len) {
                if (!$escape && $line[$offset] == '\\') {
                    $escape = true;
                    $offset++;
                    continue;
                }

                if ($escape) {
                    if ($have_cmd && !$have_brace) {
                        $have_cmd = false;
                        $part .= $alt_buf;
                    }
                }
                elseif (!$in_code_block && !$have_cmd && $line[$offset] == '!') {
                    if (preg_match('/!(\w+)\s*\{?/', substr($line, $offset), $matches)) {
                        if (isset($this->command_args[$matches[1]])) {
                            $cmd = $matches[1];
                            $have_cmd = true;
					        $have_brace = false;
                            $offset += 1 + strlen($cmd);
                            $alt_buf = '!' . $cmd;
                            $out .= $part;
                            $args = [];
                            continue;
                        }
                    }
                   }
                   elseif ($have_cmd && !$have_brace) {
                       if (count($args) < $this->command_args[$cmd]) {
                           if ($line[$offset] == '{') {
                               $have_brace = true;
                               $alt_buf .= '{';
                               $part = '';
                               $offset++;
                               continue;
                            }
                            else {
                               $out .= $alt_buf;
                               $have_cmd = false;
                            }
                           $part = '';
                        }
                        else {
                            $out .= $this->apply_command($cmd, $args, $in_env);
                            $have_cmd = false;
                        }
                   }
                   elseif ($have_brace) {
                       if ($line[$offset] == '{') {
                           array_push($stack, '{');
                       }
                       elseif ($line[$offset] == '}') {
                           if (count($stack) == 0) {
                               $args[] = $part;
                               $part = '';
                               $alt_buf .= '}';
                               $offset++;
                               $have_brace = false;
                               continue;
                           }
                           else {
                               array_pop($stack);
                           }
                       }
                       $alt_buf .= $line[$offset];
                   }
                   if (!$escape && $line[$offset] == '`') {
                       preg_match("/(`+)/", substr($line, $offset), $matches);
                       if ($in_code_block && $cb_delim == $matches[1]) {
                           $in_code_block = false;
                       }
                       elseif (!$in_code_block) {
                           $cb_delim = $matches[1];
                           $in_code_block = true;
                       }
                       $part .= $matches[1];
                       $offset += strlen($matches[1]);
                       continue;
                   }
                  
                       
                   if ($escape) {
                       if ($in_code_block) {
                           $part .= '\\';
                   }
                   $escape = false;
                   }
                   $part .= $line[$offset];
                   $offset++;
            }
            if ($have_cmd && !$have_brace) {
                if (count($args) == $this->command_args[$cmd]) {
                    $out .= $this->apply_command($cmd, $args, $in_env);
                }
                else {
                    $out .= $alt_buf;
                }
                $out .= "\n";
                $part = '';
                $have_cmd = false;
            }
            else {
                $part .= "\n";
            }
        }
        if ($have_cmd) {
            if (count($args) == $this->command_args[$cmd]) {
                $out .= $this->apply_command($cmd, $args, $in_env);
            }
            else {
                $out .= $alt_buf;
            }
            $have_cmd = false;
        }
        else {
            $out .= $part;
        }

        $Parsedown = new Parsedown();
        $content = $Parsedown->text($out);
        
        return $content;
    }

    public function references() {
        return $this->pub_list;
    }

    public function reset_parser() {
	   $this->symbols = [];
	   $this->pub_list = [];
	   $this->citations = [];
	   $this->counters['theorem'] = 0;
	   $this->counters['lemma'] = 0;
	   $this->counters['definition'] = 0;
    }

    private function citation($key) {
        $id = '?';
        if (isset($this->citations[$key])) {
            $id = $this->citations[$key];
        }
        elseif ($this->biblio != NULL) {
            $pub = $this->biblio->lookup($key);
        if ($pub != NULL) {
            $id = count($this->pub_list) + 1;
            $this->citations[$key] = $id;
            $this->pub_list[] = $pub;
        }
        }
        return $id;
    }

    private function apply_command($cmd, $args, $in_env) {
        $out = '';
        if (!$in_env && ($cmd == 'theorem' || $cmd == 'lemma' || $cmd == 'definition')) {
            $id = $args[0];
            $no = $this->counters[$cmd] + 1;
            $this->counters[$cmd] += 1;
            $this->symbols[$id] = $no;
            $Parsedown = new Parsedown();
            $env_content = $this->parse($args[1], true);
            if (preg_match('/^\s*<p>(.*)<\/p>\s*(.*)$/s', $env_content, $matches)) {           
                $env_content = $matches[1] . $matches[2];
            }
            $out = '<div class="' . $cmd . '" style="font-style: normal">' . "\n";
            $out .= '<b>' . $no . '. </b>';
            if ($cmd == 'theorem' || $cmd == 'lemma') {
                $out .= '<i>' . $env_content . "</i>\n";
            }
            else {
                $out .= $env_content . "\n";
            }
            $out .= "</div>\n";
        }
        elseif (!$in_env && $cmd == 'proof') {
            $Parsedown = new Parsedown();
            $env_content = $this->parse($args[0], true);
            if (preg_match('/^\s*<p>(.*)<\/p>\s*$/', $env_content, $matches)) {
                $env_content = $matches[1];
            }
            $out = '<div class="' . $cmd . '">' . "\n";
            $out .= $env_content . "\n";
            $out .= "</div>\n";
        }
        elseif ($cmd == 'ref') {
            if (!isset($this->symbols[$args[0]])) {
                $out = '?';
            }
            else {
                $out = $this->symbols[$args[0]];
            }
        }
        elseif ($cmd == 'cite') {
            $id = $this->citation($args[0]);
            $out .= '[' . $id . ']';
        }

        return $out;
    }
}