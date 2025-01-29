<?php
require_once 'config.php'; // Adjust the path to your DB connection

class EventCategorySeeder {
    public static function run($pdo) {
        $categories = [
            ['name' => 'Conference', 'value' => 'conference'],
            ['name' => 'Workshop', 'value' => 'workshop'],
            ['name' => 'Webinar', 'value' => 'webinar'],
            ['name' => 'Networking', 'value' => 'networking'],
            ['name' => 'Seminar', 'value' => 'seminar'],
            ['name' => 'Exhibition', 'value' => 'exhibition'],
        ];

        foreach ($categories as $category) {
            $stmt = $pdo->prepare("INSERT INTO event_categories (name, value) VALUES (?, ?)");
            $stmt->execute([$category['name'], $category['value']]);
        }

        echo "Event categories seeded successfully!";
    }
}

// Run the seeder
EventCategorySeeder::run($pdo);