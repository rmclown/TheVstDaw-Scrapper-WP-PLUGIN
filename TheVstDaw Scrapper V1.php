<?php
/*
Plugin Name: TheVstDaw Scrapper BY [ WP-SQUAD ]
Plugin URI: https://torrentsquadd.blogspot.com/
Description: This plugin allows you to scrape content from a specific website (https://thevstdaw.com/) and create a post in WordPress. It extracts the title, content, featured image, and magnet link (if available) from the website and generates a post with customizable categories.
Version: 1.0
Author: Ramflow
Author URI: https://www.facebook.com/ramfloww/
License: GPLv2 or later  
*/

function TheVstDaw_Scrapper() {
    // Check if the form is submitted
    if (isset($_POST['submit'])) {
        // Get the URL from the form
        $url = sanitize_text_field($_POST['url']);
        // Get the categories from the form
        $categories = isset($_POST['categories']) ? $_POST['categories'] : '';

        // Validate the URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // Send a GET request to the URL
            $response = wp_remote_get($url);

            // Check if the request was successful
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                // Get the HTML content
                $html = wp_remote_retrieve_body($response);

                // Create a DOMDocument object
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);

                // Load the HTML content into the DOMDocument
                $dom->loadHTML($html);

                // Create a DOMXPath object
                $xpath = new DOMXPath($dom);

                // Find the title element and extract the text
                $titleElement = $xpath->query('//title')->item(0);
                $title = $titleElement ? $titleElement->textContent : '';

                // Find the main content section and extract the HTML
                $contentElement = $xpath->query('//div[contains(@class, "mb-5")]')->item(0);
                $contentHTML = $contentElement ? $dom->saveHTML($contentElement) : '';

               // RMV ANW
                $contentHTML = preg_replace('/<img[^>]+>(?![\s\S]*<img[^>]+>)/', '', $contentHTML);
                $contentHTML = str_replace('<span style="color: red; font-family: courier;"><b>Click On Torrent U To DOWNLOAD For Free 👇👇</b></span>', '', $contentHTML);
                $contentHTML = str_replace('https://www.highcpmrevenuenetwork.com/x1s5j1zzpp?key=c621c3f5f8fbeb16cfaa8beb2cf64dde', 'https://www.highrevenuegate.com/vif59qem?key=8c3861e0a746dccfa0a2bf6bde438e22', $contentHTML);



                // Find the image URL
                $imageElement = $xpath->query('//div[contains(@class, "mb-5")]//img')->item(0);
                $imageURL = $imageElement ? $imageElement->getAttribute('src') : '';

                // Find the magnet link
                $magnetElement = $xpath->query('//div[contains(@class, "separator")]//a[contains(@href, "magnet:")]')->item(0);
                $magnet = $magnetElement ? $magnetElement->getAttribute('href') : '';

                // Create the WordPress post
                $post = array(
                    'post_title'   => $title,
                    'post_content' => '<div class="post-wrapper" style="text-align: center;">' .
                        '<div class="post-content">' .
                        '<h2>' . $title . '</h2>' .
                        '<div class="post-description">' .
                        $contentHTML .
                        '</div>' .
                        '<p style="color: red; font-weight: bold;">Click On Torrent U To DOWNLOAD This Game For Free 👇👇</p>' .
                        '<div class="download-button"><a class="button-89" href="' . $magnet . '"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEg1a-jr5sFtwcPYZtj80qJXXLYSFXEVxgr5PxEQOK8da9zCLwm2hemdAxhRn1GDMogoGWVn8Vfjzvxb1-1UusN_6l7VmYbRhWYJ7zQfOKHV1ix94NOSQqexsHgb2sLc5oeJF7_RMjsR35uM_AnxGsPUW4VHfoffif1hONhFo8p-7IeC_ap8BNYaUs54/s320/torent-magnet.webp" alt="Download" width="300" height="300"></a></div>' .
                        '</div>' .
                        '</div>',
                    'post_status'  => 'publish',
                    'post_author'  => 1,
                );


                // Insert the post into the database
                $postID = wp_insert_post($post);

                if ($postID) {
                    // Download the image and add it to the media library
                    $imageResponse = wp_remote_get($imageURL);
                    $imageData = wp_remote_retrieve_body($imageResponse);

                    // Create a unique file name for the image
                    $imageName = sanitize_file_name($title) . '.jpg';

                    // Save the image file to the temporary directory
                    $uploadDir = wp_upload_dir();
                    $imagePath = $uploadDir['path'] . '/' . $imageName;
                    file_put_contents($imagePath, $imageData);

                    // Create the attachment array
                    $attachment = array(
                        'post_mime_type' => 'image/jpeg',
                        'post_title'     => $title,
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    );

                    // Insert the attachment into the media library
                    $attachmentID = wp_insert_attachment($attachment, $imagePath, $postID);

                    if (!is_wp_error($attachmentID)) {
                        // Generate the attachment metadata
                        $attachmentData = wp_generate_attachment_metadata($attachmentID, $imagePath);

                        // Update the attachment metadata
                        wp_update_attachment_metadata($attachmentID, $attachmentData);

                        // Set the post thumbnail
                        set_post_thumbnail($postID, $attachmentID);
                    }

                    // Assign categories to the post
                    $categoryIDs = array();
                    if (!empty($categories)) {
                        $categorySlugs = explode(',', $categories);
                        foreach ($categorySlugs as $slug) {
                            $category = get_category_by_slug($slug);
                            if ($category) {
                                $categoryIDs[] = $category->term_id;
                            } else {
                                // If the category doesn't exist, create it and get the new category ID
                                $newCategory = wp_insert_category(array('cat_name' => $slug));
                                if ($newCategory && !is_wp_error($newCategory)) {
                                    $categoryIDs[] = $newCategory;
                                }
                            }
                        }
                    }

                    // Assign categories to the post
                    wp_set_post_categories($postID, $categoryIDs);

                    echo '<div class="notice notice-success"><p>Post created successfully.</p><p><strong>Post Title:</strong> ' . $title . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Failed to create post.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Invalid URL or failed to fetch website content.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Invalid URL.</p></div>';
        }
    }
    ?>
    <style>
        .wrap {
            max-width: 600px;
            margin: 50px auto;
        }

        .wrap h1 {
            margin-bottom: 30px;
            text-align: center;
        }

        .wrap label {
            display: block;
            margin-bottom: 10px;
        }

        .wrap input[type="text"] {
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }

        .wrap input[type="submit"] {
            display: block;
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 18px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .notice {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .notice p {
            margin: 0;
        }

        .notice-success {
            background-color: #DFF2BF;
            border: 1px solid #4F8A10;
            color: #4F8A10;
        }

        .notice-error {
            background-color: #FFBABA;
            border: 1px solid #D8000C;
            color: #D8000C;
        }
    </style>
    <div class="wrap">
        <h3 style="font-size: 24px; font-weight: bold; color: green;">TheVstDaw Scrapper BY [ WP-SQUAD ]</h3>
        <p style="color: green;">Support Us | USDT TRC20 : <span style="color: red;">TB4MYsGMSJnTqJzjZYz9HzS4183bPqCGCe</span></p>


        <p>This plugin allows you to scrape content only from: <a href="https://thevstdaw.com/">https://thevstdaw.com/</a></p>
        <p>Get in touch for a captivating custom plugin experience : codeecrafters@gmail.com </p>
        <p>More Plugins In Our Blog : <a href="https://torrentsquadd.blogspot.com/">WP SQUAD BLOG</a></p>

        <form method="post">
            <label for="url">Website URL:</label>
            <input type="text" id="url" name="url" required>
            <br>
            <label for="categories">Categories (comma-separated):</label>
            <input type="text" id="categories" name="categories">
            <br>
            <input type="submit" name="submit" value="Scrape and Create Post">
        </form>
    </div>
    <?php
}

function TheVstDaw_Scrapper_menu() {
    add_menu_page('TheVstDaw Scrapper BY [ WP-SQUAD ]', 'TheVstDaw Scrapper', 'manage_options', 'custom-web-scraper', 'TheVstDaw_Scrapper', 'dashicons-games');
}

add_action('admin_menu', 'TheVstDaw_Scrapper_menu');
?>
