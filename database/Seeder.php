<?php
/**
 * VelocityPhp Database Seeder
 * Generates test data for development and testing
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Database;

use App\Models\UserModel;

class Seeder
{
    protected static $faker;
    
    /**
     * First names for fake data
     */
    protected static $firstNames = [
        'James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda',
        'William', 'Elizabeth', 'David', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica',
        'Thomas', 'Sarah', 'Charles', 'Karen', 'Christopher', 'Nancy', 'Daniel', 'Lisa',
        'Matthew', 'Betty', 'Anthony', 'Margaret', 'Mark', 'Sandra', 'Donald', 'Ashley',
        'Steven', 'Kimberly', 'Paul', 'Emily', 'Andrew', 'Donna', 'Joshua', 'Michelle',
        'Kenneth', 'Dorothy', 'Kevin', 'Carol', 'Brian', 'Amanda', 'George', 'Melissa'
    ];
    
    /**
     * Last names for fake data
     */
    protected static $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
        'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson',
        'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker',
        'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores',
        'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell'
    ];
    
    /**
     * Email domains for fake data
     */
    protected static $emailDomains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'protonmail.com',
        'icloud.com', 'mail.com', 'example.com', 'test.com', 'demo.com'
    ];
    
    /**
     * Words for generating content
     */
    protected static $words = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
        'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
        'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
        'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
        'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
        'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
        'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
        'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum', 'the', 'quick', 'brown',
        'fox', 'jumps', 'over', 'lazy', 'dog', 'pack', 'my', 'box', 'with', 'five',
        'dozen', 'liquor', 'jugs', 'how', 'vexingly', 'daft', 'zebras', 'jump'
    ];
    
    /**
     * Post titles templates
     */
    protected static $titleTemplates = [
        'How to %s Your %s',
        'The Ultimate Guide to %s',
        '%s Best Practices for %s',
        'Understanding %s in %s',
        'Why %s Matters for %s',
        'Getting Started with %s',
        'Advanced %s Techniques',
        '%s Tips and Tricks',
        'The Future of %s',
        'Mastering %s: A Complete Guide'
    ];
    
    /**
     * Topics for title generation
     */
    protected static $topics = [
        'PHP', 'JavaScript', 'Development', 'Web Design', 'Security', 'Performance',
        'Database', 'API', 'Testing', 'Deployment', 'DevOps', 'Cloud', 'Mobile',
        'Frontend', 'Backend', 'Full Stack', 'Architecture', 'Scaling', 'Caching'
    ];
    
    /**
     * Run all seeders
     */
    public static function run()
    {
        echo "\n=== VelocityPhp Database Seeder ===\n\n";
        
        self::seedUsers(10);
        self::seedCategories();
        self::seedPosts(20);
        
        echo "\n=== Seeding Complete ===\n";
    }
    
    /**
     * Seed users table
     */
    public static function seedUsers($count = 10)
    {
        echo "Seeding users... ";
        
        $model = new UserModel();
        $created = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $firstName = self::randomItem(self::$firstNames);
            $lastName = self::randomItem(self::$lastNames);
            $name = $firstName . ' ' . $lastName;
            $email = strtolower($firstName . '.' . $lastName . rand(1, 999) . '@' . self::randomItem(self::$emailDomains));
            
            // Check if email already exists
            $existing = $model->findByEmail($email);
            if ($existing) {
                continue;
            }
            
            try {
                $model->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'role' => self::randomItem(['user', 'user', 'user', 'moderator', 'admin']),
                    'status' => self::randomItem(['active', 'active', 'active', 'inactive'])
                ]);
                $created++;
            } catch (\Exception $e) {
                // Skip duplicates
            }
        }
        
        echo "{$created} users created\n";
        return $created;
    }
    
    /**
     * Seed categories table
     */
    public static function seedCategories()
    {
        echo "Seeding categories... ";
        
        $categories = [
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Technology and innovation articles'],
            ['name' => 'Programming', 'slug' => 'programming', 'description' => 'Programming tutorials and guides'],
            ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Web development best practices'],
            ['name' => 'Design', 'slug' => 'design', 'description' => 'UI/UX and graphic design'],
            ['name' => 'DevOps', 'slug' => 'devops', 'description' => 'DevOps and deployment strategies'],
            ['name' => 'Security', 'slug' => 'security', 'description' => 'Security and best practices'],
            ['name' => 'Database', 'slug' => 'database', 'description' => 'Database management and optimization'],
            ['name' => 'Mobile', 'slug' => 'mobile', 'description' => 'Mobile app development'],
            ['name' => 'Cloud', 'slug' => 'cloud', 'description' => 'Cloud computing and services'],
            ['name' => 'AI & ML', 'slug' => 'ai-ml', 'description' => 'Artificial Intelligence and Machine Learning']
        ];
        
        $created = 0;
        
        foreach ($categories as $category) {
            try {
                $result = self::executeQuery(
                    "INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)",
                    [$category['name'], $category['slug'], $category['description']]
                );
                if ($result) $created++;
            } catch (\Exception $e) {
                // Skip duplicates
            }
        }
        
        echo "{$created} categories created\n";
        return $created;
    }
    
    /**
     * Seed posts table
     */
    public static function seedPosts($count = 20)
    {
        echo "Seeding posts... ";
        
        // Get user IDs
        $users = self::executeQuery("SELECT id FROM users LIMIT 100");
        if (empty($users)) {
            echo "No users found, skipping posts\n";
            return 0;
        }
        
        $userIds = array_column($users, 'id');
        
        // Get category IDs
        $categories = self::executeQuery("SELECT id FROM categories");
        $categoryIds = !empty($categories) ? array_column($categories, 'id') : [];
        
        $created = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $title = self::generateTitle();
            $slug = self::slugify($title) . '-' . rand(1000, 9999);
            
            try {
                $postId = self::executeInsert(
                    "INSERT INTO posts (user_id, title, slug, content, excerpt, status, views, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [
                        self::randomItem($userIds),
                        $title,
                        $slug,
                        self::generateParagraphs(rand(3, 6)),
                        self::generateSentence(rand(10, 20)),
                        self::randomItem(['draft', 'published', 'published', 'published', 'archived']),
                        rand(0, 1000)
                    ]
                );
                
                // Assign random categories
                if ($postId && !empty($categoryIds)) {
                    $numCategories = rand(1, 3);
                    $selectedCategories = array_rand(array_flip($categoryIds), min($numCategories, count($categoryIds)));
                    
                    if (!is_array($selectedCategories)) {
                        $selectedCategories = [$selectedCategories];
                    }
                    
                    foreach ($selectedCategories as $catId) {
                        try {
                            self::executeQuery(
                                "INSERT IGNORE INTO post_categories (post_id, category_id) VALUES (?, ?)",
                                [$postId, $catId]
                            );
                        } catch (\Exception $e) {
                            // Skip
                        }
                    }
                }
                
                $created++;
            } catch (\Exception $e) {
                // Skip errors
            }
        }
        
        echo "{$created} posts created\n";
        return $created;
    }
    
    /**
     * Seed comments for existing posts
     */
    public static function seedComments($count = 50)
    {
        echo "Seeding comments... ";
        
        $posts = self::executeQuery("SELECT id FROM posts WHERE status = 'published' LIMIT 100");
        if (empty($posts)) {
            echo "No published posts found, skipping comments\n";
            return 0;
        }
        
        $postIds = array_column($posts, 'id');
        $users = self::executeQuery("SELECT id, name, email FROM users LIMIT 100");
        
        $created = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $user = !empty($users) && rand(0, 1) ? self::randomItem($users) : null;
            
            $authorName = $user ? $user['name'] : self::randomItem(self::$firstNames) . ' ' . self::randomItem(self::$lastNames);
            $authorEmail = $user ? $user['email'] : strtolower(str_replace(' ', '.', $authorName)) . '@' . self::randomItem(self::$emailDomains);
            
            try {
                self::executeQuery(
                    "INSERT INTO comments (post_id, user_id, author_name, author_email, content, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())",
                    [
                        self::randomItem($postIds),
                        $user ? $user['id'] : null,
                        $authorName,
                        $authorEmail,
                        self::generateSentence(rand(10, 30)),
                        self::randomItem(['pending', 'approved', 'approved', 'approved', 'spam'])
                    ]
                );
                $created++;
            } catch (\Exception $e) {
                // Skip errors
            }
        }
        
        echo "{$created} comments created\n";
        return $created;
    }
    
    /**
     * Clear all seeded data (dangerous!)
     */
    public static function truncate($tables = ['comments', 'post_categories', 'posts', 'categories'])
    {
        echo "Truncating tables... ";
        
        self::executeQuery("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tables as $table) {
            try {
                self::executeQuery("TRUNCATE TABLE {$table}");
                echo "{$table} ";
            } catch (\Exception $e) {
                // Table might not exist
            }
        }
        
        self::executeQuery("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "done\n";
    }
    
    /**
     * Generate a random title
     */
    protected static function generateTitle()
    {
        $template = self::randomItem(self::$titleTemplates);
        $topic1 = self::randomItem(self::$topics);
        $topic2 = self::randomItem(self::$topics);
        
        return sprintf($template, $topic1, $topic2);
    }
    
    /**
     * Generate a sentence with random words
     */
    protected static function generateSentence($wordCount = 10)
    {
        $words = [];
        for ($i = 0; $i < $wordCount; $i++) {
            $words[] = self::randomItem(self::$words);
        }
        
        $sentence = implode(' ', $words);
        return ucfirst($sentence) . '.';
    }
    
    /**
     * Generate multiple paragraphs
     */
    protected static function generateParagraphs($count = 3)
    {
        $paragraphs = [];
        
        for ($i = 0; $i < $count; $i++) {
            $sentences = [];
            $sentenceCount = rand(3, 6);
            
            for ($j = 0; $j < $sentenceCount; $j++) {
                $sentences[] = self::generateSentence(rand(8, 15));
            }
            
            $paragraphs[] = implode(' ', $sentences);
        }
        
        return implode("\n\n", $paragraphs);
    }
    
    /**
     * Convert string to URL slug
     */
    protected static function slugify($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
    
    /**
     * Get random item from array
     */
    protected static function randomItem($array)
    {
        return $array[array_rand($array)];
    }
    
    /**
     * Execute a database query
     */
    protected static function executeQuery($sql, $params = [])
    {
        $dbConfig = require CONFIG_PATH . '/database.php';
        $config = $dbConfig['connections'][$dbConfig['default']];
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );
        
        $pdo = new \PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if (stripos($sql, 'SELECT') === 0) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        return true;
    }
    
    /**
     * Execute an INSERT and return the last insert ID
     */
    protected static function executeInsert($sql, $params = [])
    {
        $dbConfig = require CONFIG_PATH . '/database.php';
        $config = $dbConfig['connections'][$dbConfig['default']];
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );
        
        $pdo = new \PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $pdo->lastInsertId();
    }
}
