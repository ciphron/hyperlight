<?php
if (!defined("HYPERLIGHT_INIT")) die();

include("includes/ExtMarkdownParser.php");


function build_author_str($authors)
{
    $author_str = '';
    $n_authors = count($authors);
    if ($n_authors >= 2) {
        $n_comma_sep = $n_authors - 2;
        for ($i = 0; $i < $n_comma_sep; $i++) {
            $author_str .= $authors[$i] . ', ';
        }
        $author_str .= $authors[$n_comma_sep] . ' and ' .
                       $authors[$n_comma_sep + 1];
    }
    elseif ($n_authors == 1) {
        $author_str = $authors[0];
    }
    return $author_str;
}


function build_pub_list($pubs, $start) 
{
    $out = '<ol start="' . $start . '">' . "\n";
    foreach ($pubs as $pub) {
        $out .= "<li>\n";

        $out .= "<i>";
        $out .= $pub->title();
        $out .= "</i>.";

        $out .= build_author_str($pub->authors()) . '. ';
        $out .= $pub->venue() . '. ';
        $out .= $pub->year() . '. ';
         
        $out .= "\n</li>\n";
    }
    $out .= "</ol>\n";
    return $out;
}

class Entry {
    public $title;
    public $slug;
    public $summary;
    public $image;
    public $tags;
    public $content;
    public $raw;
    public $timestamp;
    public $edited;
    protected $biblio;
    protected $parser;

    public function __construct($slug, $tag, $url, $parser=NULL) {
        $fullpath = ($url == Url::Post || $url == Url::Update) ? Config::PostsDirectory : Config::PagesDirectory;
        $fullpath .= $slug . ".md";

        if (!preg_match('/^\w+(#\w+)?$/', $slug) || !file_exists($fullpath)) {
            throw new NotFoundException();
        }

        $file_contents = file_get_contents($fullpath);
        $lines = explode("\n", $file_contents);

        $this->slug = $slug;
        $this->title = trim($lines[0]);
        $this->summary = trim($lines[1]);
        $this->image = trim($lines[2]);
        $tags = strtolower(trim($lines[3]));
        if ($tags !== "") {
            $this->tags = explode(", ", $tags);
        } else {
            $this->tags = [];
        }

        if ($tag !== "") {
            if (!in_array($tag, $this->tags)) {
                throw new NotFoundException();
            }
        }

        $this->biblio = NULL;
        if (Config::BibtexFile != '') {
            $this->biblio = new Bibliography(Config::BibtexFile);
        }

        if (using_parsedown() && $parser == NULL) {
            $parser = new ExtMarkdownParser($this->biblio);
        }
        $this->parser = $parser;

        $metadata_length = 5;
        $this->raw = implode("\n", array_slice($lines, $metadata_length));
        $this->content = $this->process_body($this->raw);
        $this->timestamp = filemtime($fullpath);
        $this->edited = filemtime($fullpath);
    }

    public function has_image() {
        if (isset($this->image) && $this->image != "") {
            return true;
        }
        return false;
    }

    public function has_tags() {
        if (isset($this->tags) && count($this->tags) > 0) {
            return true;
        }
        return false;
    }

    public function date_pretty() {
        return gmdate(Config::DatePretty, $this->timestamp);
    }
    public function date_datetime() {
        return gmdate("Y-m-d\TH:i:s", $this->timestamp);
    }


    private function process_body($body) {
        if (!using_parsedown() || $this->parser == NULL) {
            $lines = explode("\n", $body);
            return "<p>" . implode("<br/>", $lines) . "</p>";
        }

        $n_existing_refs = count($this->parser->references());
        $content = $this->parser->parse($body);
        $refs = $this->parser->references();
        $n_total_refs = count($refs);
        $n_new_refs = $n_total_refs - $n_existing_refs;
        $first_new_ref_no = $n_existing_refs + 1;
        $new_refs = array_slice($refs, $n_existing_refs);
        
        if ($n_new_refs > 0) {
            $pub_list_html = build_pub_list($new_refs, $first_new_ref_no);
            $content .= "\n<h2>" . (($first_new_ref_no > 1) ? 'Further ' : '') . "References</h2>\n";
            $content .= $pub_list_html;
        }

        return $content;
    }
}

class Update extends Entry {
    public function __construct($slug, $tag, $parser) {
        parent::__construct($slug, $tag, Url::Update, $parser);
    }
}

class Post extends Entry {
       public $updates;

    public function __construct($slug, $tag) {
        parent::__construct($slug, $tag, Url::Post);
        $this->updates = $this->load_updates($slug, $tag);
    }

    private function load_updates($slug, $tag) {
        $files = scandir(Config::PostsDirectory);
        $files = array_splice($files, 2);

        $updates = [];

        foreach ($files as $file) {
            $stem = rtrim($file, '.md');
            if (preg_match("/^{$slug}#\w+$/", $stem)) {
                $updates[] = new Update($stem, $tag, $this->parser);
            }
        }

        // Sort the posts before manipulating and displaying them
        usort($updates, function ($a, $b) {
            return ($a->timestamp > $b->timestamp) ? -1 : 1;
        });

        return $updates;
    }

}

