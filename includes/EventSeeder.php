<?php
require_once 'config.php'; // Adjust the path to your DB connection

class EventSeeder {
    public static function run($pdo) {
        $events = [
            [
                'title' => 'Tech Conference 2025',
                'description' => 'A conference for tech enthusiasts and professionals.',
                'img' => 'tech-conference.jpg',
                'date' => '2025-03-15',
                'time' => '09:00:00',
                'location' => 'Tech Center, Silicon Valley',
                'capacity' => 200,
                'created_by' => 1, // Assuming user with ID 1 exists
                'category' => 1 // Assuming category with ID 1 exists
            ],
            [
                'title' => 'Web Development Workshop',
                'description' => 'Hands-on workshop on modern web development techniques.',
                'img' => 'web-development-workshop.jpg',
                'date' => '2025-04-10',
                'time' => '10:00:00',
                'location' => 'Community Hall, Downtown',
                'capacity' => 50,
                'created_by' => 1,
                'category' => 2 // Assuming category with ID 2 exists
            ],
            [
                'title' => 'Networking Event 2025',
                'description' => 'A networking event for professionals and entrepreneurs.',
                'img' => 'networking-event.jpg',
                'date' => '2025-05-05',
                'time' => '14:00:00',
                'location' => 'Convention Center, Downtown',
                'capacity' => 300,
                'created_by' => 1,
                'category' => 3 // Assuming category with ID 3 exists
            ],
            [
                'title' => 'Gaming Event 2025',
                'description' => 'A gaming event for gamers and enthusiasts.',
                'img' => 'gaming-event.jpg',
                'date' => '2025-06-20',
                'time' => '17:00:00',
                'location' => 'Gaming Zone, Silicon Valley',
                'capacity' => 100,
                'created_by' => 1,
                'category' => 4 // Assuming category with ID 4 exists
            ],
            [
                'title' => 'Music Concert',
                'description' => 'A concert for music lovers and enthusiasts.',
                'img' => 'music-concert.jpg',
                'date' => '2025-07-15',
                'time' => '19:00:00',
                'location' => 'Music Hall, Downtown',
                'capacity' => 250,
                'created_by' => 1,
                'category' => 5 // Assuming category with ID 5 exists
            ]
            // Add more events as needed
        ];

        foreach ($events as $event) {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, img, date, time, location, capacity, created_by, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $event['title'],
                $event['description'],
                $event['img'],
                $event['date'],
                $event['time'],
                $event['location'],
                $event['capacity'],
                $event['created_by'],
                $event['category']
            ]);
        }

        echo "Events seeded successfully!";
    }
}

// Run the seeder
EventSeeder::run($pdo);