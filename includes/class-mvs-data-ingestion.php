<?php

require_once MVS_PLUGIN_DIR . 'includes/class-mvs-database-handler.php';

class MVS_Data_Ingestion {
    
    // Step 1: Fetch URLs from the sitemap
    public static function fetch_sitemap_urls($sitemap_url) {
        $response = wp_remote_get($sitemap_url);
        if (is_wp_error($response)) {
            return [];
        }

        $xml = wp_remote_retrieve_body($response);
        $urls = [];

        if ($xml) {
            $sitemap = simplexml_load_string($xml);
            foreach ($sitemap->url as $url_entry) {
                $urls[] = (string) $url_entry->loc;
            }
        }

        return $urls;
    }
    public static function ingest_data() {
        // Mapping of sitemaps to their respective parsing functions
        $sitemaps = [
            "products" => "https://miraclevet.com/sitemap_products_1.xml?from=8867547205&to=7469339476042",
            "blogs"    => "https://miraclevet.com/sitemap_blogs_1.xml",
            "pages"    => "https://miraclevet.com/sitemap_pages_1.xml?from=176180485&to=85826306122",
        ];
    
        foreach ($sitemaps as $type => $sitemap_url) {
            $urls = self::fetch_sitemap_urls($sitemap_url);
    
            // Loop through each URL and retrieve content using the appropriate parser
            foreach ($urls as $url) {
                switch ($type) {
                    case "products":
                        $content = self::fetch_product_page($url);
                        $content_type = 'product';
                        break;
                    case "blogs":
                        $content = self::fetch_blog_page($url);
                        $content_type = 'blog';
                        break;
                    case "pages":
                        $content = self::fetch_static_page($url);
                        $content_type = 'general';
                        break;
                    default:
                        $content = null;
                        $content_type = 'general';
                        break;
                }
    
                // Only insert if we have actual data
                if (!empty($content)) {
                    MVS_Database_Handler::insert_metadata('content_' . md5($url), $content, $content_type);
                }
            }
        }
    }
    public static function fetch_product_page($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return null;
        }
    
        $html = wp_remote_retrieve_body($response);
    
        // Load HTML into DOMDocument for parsing
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress warnings for malformed HTML
        $dom->loadHTML($html);
        libxml_clear_errors();
    
        $xpath = new DOMXPath($dom);
    
        // Extract Product Title
        $product_name = $xpath->query("//h1[contains(@class, 'product-title')]");
        $name_text = ($product_name->length > 0) ? trim($product_name->item(0)->nodeValue) : null;
    
        // Extract Product Description
        $description = $xpath->query("//*[contains(@class, 'product__text')]");
        $description_text = ($description->length > 0) ? trim($description->item(0)->nodeValue) : null;
    
        // Extract Nutrition Facts
        $nutrition = $xpath->query("//*[contains(@class, 'vet_info_nutrition')]");
        $nutrition_text = ($nutrition->length > 0) ? trim($nutrition->item(0)->nodeValue) : null;
    
        // Extract Metadata from Accordion
        $accordion_data = [];
        $accordion_cards = $xpath->query("//*[contains(@class, 'accordion')]//*[contains(@class, 'card')]");
        foreach ($accordion_cards as $card) {
            $accordion_data[] = trim($card->nodeValue);
        }
    
        // Extract Product Images
        $images = [];
        $image_elements = $xpath->query("//*[contains(@class, 'MagicToolboxSelectorsContainer')]//*[contains(@class, 'mcs-item')]/a");
        foreach ($image_elements as $img) {
            $images[] = $img->getAttribute('href'); // Get image URLs
        }
    
        // Extract Reviews
        $reviews = [];
        $review_elements = $xpath->query("//*[contains(@class, 'yotpo-reviews-carousel')]//*[contains(@class, 'yotpo-carousel')]");
        foreach ($review_elements as $review) {
            $reviews[] = trim($review->nodeValue); // Get review text
        }
    
        // Return structured data
        return [
            'name' => $name_text,
            'description' => $description_text,
            'nutrition' => $nutrition_text,
            'accordion' => $accordion_data,
            'images' => $images,
            'reviews' => $reviews,
        ];
    }


    public static function fetch_blog_page($url) {
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return null;
    }

    $html = wp_remote_retrieve_body($response);

    // Load HTML into DOMDocument
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress warnings for malformed HTML
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Extract Blog Title
    $title_element = $xpath->query("//h1[contains(@class, 'article-template__title')]");
    $title = ($title_element->length > 0) ? trim($title_element->item(0)->nodeValue) : null;

    // Extract Featured Image
    $featured_image_element = $xpath->query("//*[contains(@class, 'article-template__image')]/img");
    $featured_image = ($featured_image_element->length > 0) ? $featured_image_element->item(0)->getAttribute('src') : null;

    // Extract Blog Content
    $content_element = $xpath->query("//*[contains(@class, 'blog-article-content')]");
    $content_text = ($content_element->length > 0) ? trim($content_element->item(0)->nodeValue) : null;

    // Extract Product Upsell Section
    $upsell_products = [];
    $upsell_containers = $xpath->query("//*[contains(@class, 'product-upsell__container')]");
    foreach ($upsell_containers as $container) {
        // Product image
        $product_image_element = $xpath->query(".//*[contains(@class, 'product-upsell__image')]/img", $container);
        $product_image = ($product_image_element->length > 0) ? $product_image_element->item(0)->getAttribute('src') : null;

        // Product meta data
        $product_meta = [];

        // Yotpo Ratings
        $rating_element = $xpath->query(".//*[contains(@class, 'yotpo-reviews-star-ratings-widget')]", $container);
        $rating = ($rating_element->length > 0) ? trim($rating_element->item(0)->nodeValue) : null;

        // Price
        $price_element = $xpath->query(".//*[contains(@class, 'price-list')]", $container);
        $price = ($price_element->length > 0) ? trim($price_element->item(0)->nodeValue) : null;

        // Buttons (e.g., "Buy Now" or "Add to Cart")
        $buttons = [];
        $button_elements = $xpath->query(".//*[contains(@class, 'buttons')]//a", $container);
        foreach ($button_elements as $button) {
            $buttons[] = [
                'text' => trim($button->nodeValue),
                'link' => $button->getAttribute('href'),
            ];
        }

        // Add product data to upsell products array
        $upsell_products[] = [
            'image' => $product_image,
            'rating' => $rating,
            'price' => $price,
            'buttons' => $buttons,
        ];
    }

    // Return structured data
    return [
        'title' => $title,
        'featured_image' => $featured_image,
        'content' => $content_text,
        'upsell_products' => $upsell_products,
    ];
}

    public static function fetch_static_page($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return null;
        }
    
        $html = wp_remote_retrieve_body($response);
    
        // Load HTML into DOMDocument
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
    
        $xpath = new DOMXPath($dom);
    
        // Extract Page Title
        $title_element = $xpath->query("//h1");
        $title = ($title_element->length > 0) ? trim($title_element->item(0)->nodeValue) : null;
    
        // Extract Page Content
        $content_element = $xpath->query("//*[contains(@class, 'page-content')]");
        $content_text = ($content_element->length > 0) ? trim($content_element->item(0)->nodeValue) : null;
    
        return [
            'title'   => $title,
            'content' => $content_text,
        ];
    }
}