<?php

include("includes/bibtex-parser/ListenerInterface.php");
include("includes/bibtex-parser/Listener.php");
include("includes/bibtex-parser/Parser.php");
include("includes/Publication.php");

use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;


class Bibliography {
    private $pub_map = [];

    public function __construct($bibtex_filename) {
        $parser = new Parser();
        $listener = new Listener();
        $parser->addListener($listener);
        $parser->parseFile($bibtex_filename);
        $entries = $listener->export();

        foreach ($entries as $entry) {
            $pub = new Publication($entry);
            $type = $pub->type();
            $this->pub_map[$entry['citation-key']] = $pub;
        }
  }
    
    public function lookup($key) {
        if (isset($this->pub_map[$key])) {
            return $this->pub_map[$key];
        }
        return NULL;
    }
}
