<?php

class Publication {
    const JOURNAL = 1;
    const CONFERENCE = 2;
    const PREPRINT = 3;

    private $fields;
    private $type;
    private $venue;
    private $authors;

    public function __construct($fields) {
        $this->fields = $fields;

        if ($fields['type'] == 'inproceedings') {
            $this->type = self::CONFERENCE;
            $this->venue = $fields['booktitle'];
        }
        elseif ($fields['type'] == 'article') {
            $this->type = self::JOURNAL;
            $this->venue = $fields['journal'];
        }
        else {
            $this->type = self::PREPRINT;
            $this->venue = $fields['howpublished'];
        }

        $author_str = $fields['author'];
        $authors = split('[^a-zA-Z]+and[^a-zA-Z]+', $author_str);
        $this->authors = [];
        foreach ($authors as $author) {
            $parts = split(',', $author);
            if (count($parts) == 2) {
               array_push($this->authors, $parts[1] . ' ' . $parts[0]);
            }
            else {
                array_push($this->authors, $author);
            }
        }
    }

    public function type() {
        return $this->type;
    }

    public function venue() {
        return $this->venue;
    }

    public function title() {
        return $this->fields['title'];
    }

    public function year() {
        return $this->fields['year'];
    }

    public function authors() {
        return $this->authors;
    }

    public function fields() {
        return $this->fields;
    }
}