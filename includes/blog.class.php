<?php
if (!defined("HYPERLIGHT_INIT")) die();

class NotFoundException extends Exception {}

abstract class Url {
    const Archive = 0;
    const Post = 1;
    const Page = 2;
    const Update = 3;
    const Error404 = 4;
}

class Blog {
    public $posts;
    public $url;

    private $_page_num;
    private $_page_num_total;

    function __construct($post_slug, $page_slug, $pagination, $tag_slug) {
        $this->_page_num = 0;
        $this->_page_num_total = 1;

        if ($post_slug !== "") {
            try {
                $this->posts = [new Post($post_slug, "")];
                $this->url = Url::Post;
            } catch (NotFoundException $e) {
                Header("HTTP/1.1 404 Not Found");
                $this->url = Url::Error404;
            }
        } else {
            $this->posts = Blog::loadPosts($tag_slug);

            // Pagination configuration
            $this->_page_num = $pagination;
            $offset = Config::PostsPerPage * $this->_page_num;
            $length = Config::PostsPerPage;
            $this->_page_num_total = ceil(count($this->posts) / $length);

            // Only return the posts that appear on that page.
            $this->posts = array_slice($this->posts, $offset, $length);
            $this->url = Url::Archive;
        }
    }

    private function loadPosts($tag) {
        $files = scandir(Config::PostsDirectory);
        $files = array_splice($files, 2);

        $posts = [];

        foreach ($files as $file) {
            try {
                $stem = rtrim($file, '.md');
                if (!preg_match('/[^#]+#.*/', $stem)) {
                   $posts[] = new Post($stem, $tag);
                }
            } catch (NotFoundException $e) {
            
            }
        }

        // Sort the posts before manipulating and displaying them
        usort($posts, function ($a, $b) {
            return ($a->timestamp > $b->timestamp) ? -1 : 1;
        });

        return $posts;
    }


    // Returns the title, depending on whether you're on a single post or not.
    public function get_title() {
        $str = "";
        if ($this->url === Url::Post) {
            $str .= $this->posts[0]->title . Config::TitleSeparator;
        }
        $str .= Config::Title;

        return $str;
    }

    /*
        PAGINATION FUNCTIONS
    */
    public function get_page_num() {
        return $this->_page_num + 1;
    }

    public function get_page_prev() {
        return get_page_url() . "p/" . $this->_page_num;
    }

    public function get_page_next() {
        return get_page_url() . "p/" . ($this->_page_num + 2);
    }

    public function has_page_prev() {
        return ($this->_page_num === 0) ? false : true;
    }

    public function has_page_next() {
        return ($this->_page_num >= $this->_page_num_total - 1) ? false : true;
    }

    public function has_pagination() {
        return ($this->url === Url::Archive && ($this->has_page_next() || $this->has_page_prev())) ? true : false;
    }

    public function get_page_total() {
        return $this->_page_num_total;
    }
}
