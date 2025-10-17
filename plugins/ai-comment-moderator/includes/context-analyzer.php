<?php
/**
 * Context Analyzer
 * 
 * Analyzes comment context to provide additional data for AI processing
 * 
 * @package AI_Comment_Moderator
 * @since 2.1.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Context_Analyzer {
    
    /**
     * Analyze comment sentiment
     * 
     * Basic sentiment analysis using keyword matching
     * 
     * @param string $text Comment text
     * @return string Sentiment: positive, negative, neutral, toxic
     */
    public static function analyze_sentiment($text) {
        $text_lower = strtolower($text);
        
        // Toxic/negative indicators
        $toxic_keywords = array(
            'hate', 'stupid', 'idiot', 'moron', 'kill', 'die', 'damn', 'hell', 
            'fuck', 'shit', 'ass', 'bastard', 'bitch', 'crap', 'suck', 'worst',
            'terrible', 'awful', 'garbage', 'trash', 'pathetic', 'loser'
        );
        
        $negative_keywords = array(
            'bad', 'poor', 'disappointing', 'disappointed', 'wrong', 'fail', 
            'failed', 'failure', 'problem', 'issue', 'broken', 'useless'
        );
        
        $positive_keywords = array(
            'great', 'good', 'excellent', 'amazing', 'awesome', 'love', 'best',
            'wonderful', 'fantastic', 'perfect', 'brilliant', 'helpful', 'thanks',
            'thank you', 'appreciate', 'nice', 'beautiful', 'outstanding'
        );
        
        $toxic_count = 0;
        foreach ($toxic_keywords as $keyword) {
            if (strpos($text_lower, $keyword) !== false) {
                $toxic_count++;
            }
        }
        
        if ($toxic_count >= 2) {
            return 'toxic';
        }
        
        $negative_count = 0;
        foreach ($negative_keywords as $keyword) {
            if (strpos($text_lower, $keyword) !== false) {
                $negative_count++;
            }
        }
        
        $positive_count = 0;
        foreach ($positive_keywords as $keyword) {
            if (strpos($text_lower, $keyword) !== false) {
                $positive_count++;
            }
        }
        
        if ($positive_count > $negative_count && $positive_count > 0) {
            return 'positive';
        } elseif ($negative_count > $positive_count && $negative_count > 0) {
            return 'negative';
        }
        
        return 'neutral';
    }
    
    /**
     * Detect comment language
     * 
     * Basic language detection using character sets and common words
     * 
     * @param string $text Comment text
     * @return string Language code (en, es, fr, de, etc.) or 'unknown'
     */
    public static function detect_language($text) {
        // Check for non-Latin scripts first
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            return 'zh'; // Chinese
        }
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text)) {
            return 'ja'; // Japanese
        }
        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $text)) {
            return 'ko'; // Korean
        }
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar'; // Arabic
        }
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            return 'ru'; // Russian
        }
        
        // Common words detection for Latin-script languages
        $text_lower = strtolower($text);
        
        $spanish_words = array('el', 'la', 'de', 'que', 'es', 'en', 'por', 'para', 'con', 'una', 'está', 'como', 'gracias');
        $french_words = array('le', 'de', 'un', 'être', 'et', 'à', 'il', 'avoir', 'ne', 'je', 'son', 'que', 'merci', 'bonjour');
        $german_words = array('der', 'die', 'und', 'in', 'den', 'von', 'zu', 'das', 'mit', 'sich', 'des', 'auf', 'für', 'ist', 'danke');
        $portuguese_words = array('de', 'o', 'a', 'que', 'e', 'do', 'da', 'em', 'um', 'para', 'é', 'com', 'não', 'obrigado');
        $italian_words = array('di', 'il', 'e', 'la', 'che', 'per', 'un', 'in', 'è', 'con', 'non', 'sono', 'grazie', 'ciao');
        
        $es_count = 0;
        foreach ($spanish_words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text_lower)) {
                $es_count++;
            }
        }
        
        $fr_count = 0;
        foreach ($french_words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text_lower)) {
                $fr_count++;
            }
        }
        
        $de_count = 0;
        foreach ($german_words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text_lower)) {
                $de_count++;
            }
        }
        
        $pt_count = 0;
        foreach ($portuguese_words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text_lower)) {
                $pt_count++;
            }
        }
        
        $it_count = 0;
        foreach ($italian_words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text_lower)) {
                $it_count++;
            }
        }
        
        $max_count = max($es_count, $fr_count, $de_count, $pt_count, $it_count);
        
        if ($max_count >= 2) {
            if ($es_count === $max_count) return 'es';
            if ($fr_count === $max_count) return 'fr';
            if ($de_count === $max_count) return 'de';
            if ($pt_count === $max_count) return 'pt';
            if ($it_count === $max_count) return 'it';
        }
        
        // Default to English if no other language detected
        return 'en';
    }
    
    /**
     * Get thread context
     * 
     * Analyze conversation flow and related comments
     * 
     * @param int $comment_id Comment ID
     * @return array Thread information
     */
    public static function get_thread_context($comment_id) {
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return array(
                'parent_exists' => false,
                'thread_depth' => 0,
                'sibling_count' => 0,
                'thread_sentiment' => 'neutral'
            );
        }
        
        $parent_exists = !empty($comment->comment_parent);
        $thread_depth = 0;
        $current_id = $comment_id;
        
        // Calculate thread depth
        while ($current_id) {
            $current = get_comment($current_id);
            if ($current && $current->comment_parent) {
                $thread_depth++;
                $current_id = $current->comment_parent;
            } else {
                break;
            }
        }
        
        // Count siblings (comments with same parent)
        $args = array(
            'post_id' => $comment->comment_post_ID,
            'parent' => $comment->comment_parent,
            'count' => true
        );
        $sibling_count = get_comments($args);
        
        // Analyze thread sentiment
        $thread_comments = get_comments(array(
            'post_id' => $comment->comment_post_ID,
            'parent' => $comment->comment_parent,
            'number' => 10
        ));
        
        $sentiments = array();
        foreach ($thread_comments as $thread_comment) {
            $sentiments[] = self::analyze_sentiment($thread_comment->comment_content);
        }
        
        $sentiment_counts = array_count_values($sentiments);
        arsort($sentiment_counts);
        $thread_sentiment = key($sentiment_counts);
        
        return array(
            'parent_exists' => $parent_exists,
            'thread_depth' => $thread_depth,
            'sibling_count' => $sibling_count - 1, // Exclude current comment
            'thread_sentiment' => $thread_sentiment
        );
    }
    
    /**
     * Get time context
     * 
     * Determine time-based patterns
     * 
     * @param string $comment_date Comment date (MySQL format)
     * @return array Time context information
     */
    public static function get_time_context($comment_date) {
        $timestamp = strtotime($comment_date);
        $hour = date('G', $timestamp); // 24-hour format
        $day_of_week = date('l', $timestamp);
        
        // Determine time of day
        if ($hour >= 6 && $hour < 12) {
            $time_of_day = 'morning';
        } elseif ($hour >= 12 && $hour < 17) {
            $time_of_day = 'afternoon';
        } elseif ($hour >= 17 && $hour < 22) {
            $time_of_day = 'evening';
        } else {
            $time_of_day = 'night';
        }
        
        // Determine day type
        $is_weekend = in_array($day_of_week, array('Saturday', 'Sunday'));
        
        return array(
            'time_of_day' => $time_of_day,
            'day_of_week' => $day_of_week,
            'is_weekend' => $is_weekend,
            'hour' => $hour
        );
    }
    
    /**
     * Get site context
     * 
     * Analyze site characteristics
     * 
     * @return array Site context information
     */
    public static function get_site_context() {
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        
        // Determine site category based on description/name
        $combined_text = strtolower($site_name . ' ' . $site_description);
        
        $categories = array(
            'tech' => array('tech', 'technology', 'software', 'computer', 'programming', 'code', 'developer', 'web'),
            'blog' => array('blog', 'personal', 'diary', 'journal', 'thoughts'),
            'news' => array('news', 'media', 'press', 'journalism', 'reporter'),
            'business' => array('business', 'entrepreneur', 'startup', 'company', 'corporate'),
            'ecommerce' => array('shop', 'store', 'buy', 'sell', 'product', 'ecommerce'),
            'education' => array('education', 'learn', 'course', 'teach', 'school', 'university'),
            'health' => array('health', 'medical', 'doctor', 'fitness', 'wellness'),
            'entertainment' => array('entertainment', 'movie', 'music', 'game', 'fun'),
        );
        
        $detected_category = 'general';
        $max_matches = 0;
        
        foreach ($categories as $category => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                if (strpos($combined_text, $keyword) !== false) {
                    $matches++;
                }
            }
            if ($matches > $max_matches) {
                $max_matches = $matches;
                $detected_category = $category;
            }
        }
        
        return array(
            'category' => $detected_category,
            'name' => $site_name,
            'description' => $site_description
        );
    }
    
    /**
     * Get user history context
     * 
     * Analyze commenter's history on the site
     * 
     * @param string $author_email Comment author email
     * @return array User history information
     */
    public static function get_user_history($author_email) {
        if (empty($author_email)) {
            return array(
                'total_comments' => 0,
                'approved_comments' => 0,
                'spam_comments' => 0,
                'is_new_user' => true,
                'reputation' => 'unknown'
            );
        }
        
        $comments = get_comments(array(
            'author_email' => $author_email,
            'count' => true
        ));
        
        $approved = get_comments(array(
            'author_email' => $author_email,
            'status' => 'approve',
            'count' => true
        ));
        
        $spam = get_comments(array(
            'author_email' => $author_email,
            'status' => 'spam',
            'count' => true
        ));
        
        $is_new_user = $comments == 0;
        
        // Calculate reputation
        if ($comments == 0) {
            $reputation = 'new';
        } elseif ($spam > $approved) {
            $reputation = 'poor';
        } elseif ($approved > 10 && $spam == 0) {
            $reputation = 'excellent';
        } elseif ($approved > 5) {
            $reputation = 'good';
        } else {
            $reputation = 'neutral';
        }
        
        return array(
            'total_comments' => $comments,
            'approved_comments' => $approved,
            'spam_comments' => $spam,
            'is_new_user' => $is_new_user,
            'reputation' => $reputation
        );
    }
    
    /**
     * Get comprehensive context for a comment
     * 
     * Combines all context analysis into one array
     * 
     * @param int $comment_id Comment ID
     * @return array Complete context information
     */
    public static function get_full_context($comment_id) {
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return array();
        }
        
        $sentiment = self::analyze_sentiment($comment->comment_content);
        $language = self::detect_language($comment->comment_content);
        $thread = self::get_thread_context($comment_id);
        $time = self::get_time_context($comment->comment_date);
        $site = self::get_site_context();
        $user_history = self::get_user_history($comment->comment_author_email);
        
        return array(
            'sentiment' => $sentiment,
            'language' => $language,
            'thread' => $thread,
            'time' => $time,
            'site' => $site,
            'user_history' => $user_history
        );
    }
}

