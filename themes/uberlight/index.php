<?php
// This check is to make sure the user is actually running on hyperlight and not trying to access the file purposely
if (!defined("HYPERLIGHT_INIT")) die();
// First, get these variables purely for ease later on.
$root = Config::Root;
$title = Config::Title;
$footer = Config::Footer;

// Then set up the HTML document
?>
<!DOCTYPE html>
<html lang="en">
<title><?php echo $Blog->get_title(); ?></title>
<link href="/blog/themes/uberlight/prism.css" rel="stylesheet"/>
<link href="/blog/themes/uberlight/theorems.css" rel="stylesheet"/>
<style>
blockquote {
    font-style: italic;
    color: #555;
    padding-left: 32px;
    border-left: 3px solid <?php echo $colour; ?>;
}
</style>
<script src="/blog/themes/uberlight/js/prism.js" defer></script>

<?php
// Print the website title at the top of every page
echo "
<h1> " .  $Blog->get_title() . " <a href='{$root}'>posts</a></h1>
<hr/>";

// Check what page we're on and display the relevant content.
if ($Blog->url === Url::Error404) {
    echo "<h2>Error 404: Post Not Found</h2>";
} else if (count($Blog->posts) === 0) {
    // Only display this if there are no posts in the posts directory
    echo "<h2>No Posts Found</h2>";
} else {
    // If there wasn't an error, loop through the posts we got.
    // If we're viewing a single post, there will be one element in the array.
    foreach ($Blog->posts as $entry) {

        // Include a link to the single post if we're viewing the archive
        if ($Blog->url === Url::Archive) {
            if (!in_array('invisible', $entry->tags)) {
                echo "<article style='margin-top: 32px;'>";
                echo "<h2><a href='" . get_post_link($entry->slug) . "'>{$entry->title}</a></h2>";
                    echo "<time datetime={$entry->date_datetime()}>{$entry->date_pretty()}</time>";
                echo "</article>";
            }
        } else {
            // Otherwise just include the post's featured image, title and content.
            echo "<article style='margin-top: 32px;'>";
            if ($entry->has_image()) {
                echo "<img src='{$entry->image}' />";
            }
            echo "<h2>{$entry->title}</h2>";
            echo "<time datetime={$entry->date_datetime()}>{$entry->date_pretty()}</time>";
            echo $entry->content;
            echo "</article>";
            echo "<a href=\"{$root}?post={$entry->slug}&raw=markdown\">Raw form (ExtMarkdown)</a><br/>";
            if (count($entry->updates) > 0) {
                echo "<hr/><h3>Updates</h3>";
                $index = 1;
                foreach ($entry->updates as $update) {
                    echo "<article style='margin-top: 32px;'>";
                    echo "<h4>Update {$index}</h4>";
                   
                    echo "<time datetime={$update->date_datetime()}>{$update->date_pretty()}</time>";
                    echo $update->content;
                    echo "</article>";
                    $index++;
               }
            }

        }
    }
    // Include pagination if we have too many posts to display at once.
    if ($Blog->has_pagination()) {
        echo "<div style='margin-top:32px;'>";
        if ($Blog->has_page_prev()) {
            echo "<a href='{$Blog->get_page_prev()}'>< Newer</a>";

            // Don't forget to include some hard-coded
            // spacing for good measure! /s
            echo "&nbsp;&nbsp;";
        }

        if ($Blog->has_page_next()) {
            echo "<a href='{$Blog->get_page_next()}'>Older ></a>";
        }
        echo "</div>";
    }

}

// Display the footer text at the bottom of each page.
echo "<hr/><footer style='margin-top:32px;'>{$footer}
RSS: <a href='/blog?rss=json'>json</a> <a href='/blog?rss=xml'>xml</a>
</footer>";

?>
<!DOCTYPE html>
<script src="/blog/themes/uberlight/js/math_code.js"></script>
<script async
  src="//mathjax.rstudio.com/latest/MathJax.js?config=TeX-MML-AM_CHTML">
</script>
<?php
echo "</html>";
