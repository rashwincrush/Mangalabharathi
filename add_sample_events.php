#!/usr/bin/php
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/EventManager.php';

// Ensure GD library is loaded
if (!function_exists('imagecreate')) {
    die("GD library is not installed. Please install php-gd.\n");
}

$conn = Database::getInstance()->getConnection();
$eventManager = new EventManager($conn);

$sample_events = [
    [
        'title' => 'Education Empowerment Workshop',
        'description' => 'A comprehensive workshop focusing on providing educational resources and scholarships to underprivileged students.',
        'event_date' => '2023-07-15 10:00:00',
        'location' => 'Community Center, Bangalore',
        'category' => 'education',
        'status' => 'past',
        'people_helped' => 150,
        'volunteers' => 25,
        'impact_description' => 'Provided scholarships and learning materials to 150 students from low-income backgrounds.'
    ],
    [
        'title' => 'Health Awareness Camp',
        'description' => 'Free medical check-ups and health awareness program for rural communities.',
        'event_date' => '2023-09-22 09:00:00',
        'location' => 'Rural Health Center, Karnataka',
        'category' => 'health',
        'status' => 'past',
        'people_helped' => 200,
        'volunteers' => 30,
        'impact_description' => 'Conducted health screenings for 200 individuals and provided basic medical consultations.'
    ],
    [
        'title' => 'Environmental Conservation Drive',
        'description' => 'Community tree plantation and waste management awareness program.',
        'event_date' => '2024-01-10 08:00:00',
        'location' => 'City Park, Bangalore',
        'category' => 'environment',
        'status' => 'past',
        'people_helped' => 100,
        'volunteers' => 40,
        'impact_description' => 'Planted 500 trees and educated community about waste segregation and recycling.'
    ],
    [
        'title' => 'Community Skills Development Program',
        'description' => 'Training program to enhance vocational skills for unemployed youth.',
        'event_date' => '2024-03-05 14:00:00',
        'location' => 'Skill Development Center, Bangalore',
        'category' => 'community',
        'status' => 'past',
        'people_helped' => 75,
        'volunteers' => 15,
        'impact_description' => 'Trained 75 youth in various vocational skills, improving their employability.'
    ]
];

$media_files = [
    [
        'name' => ['education_workshop.jpg'],
        'type' => ['image/jpeg'],
        'tmp_name' => [__DIR__ . '/sample_images/education_workshop.jpg'],
        'error' => [0],
        'size' => [100000]
    ],
    [
        'name' => ['health_camp.jpg'],
        'type' => ['image/jpeg'],
        'tmp_name' => [__DIR__ . '/sample_images/health_camp.jpg'],
        'error' => [0],
        'size' => [100000]
    ],
    [
        'name' => ['environment_drive.jpg'],
        'type' => ['image/jpeg'],
        'tmp_name' => [__DIR__ . '/sample_images/environment_drive.jpg'],
        'error' => [0],
        'size' => [100000]
    ],
    [
        'name' => ['skills_program.jpg'],
        'type' => ['image/jpeg'],
        'tmp_name' => [__DIR__ . '/sample_images/skills_program.jpg'],
        'error' => [0],
        'size' => [100000]
    ]
];

$sample_images_dir = __DIR__ . '/sample_images';
if (!file_exists($sample_images_dir)) {
    mkdir($sample_images_dir, 0755, true);
}

$placeholder_image = imagecreate(800, 600);
$background = imagecolorallocate($placeholder_image, 240, 240, 240);
$text_color = imagecolorallocate($placeholder_image, 0, 0, 0);

$sample_image_names = [
    'education_workshop.jpg',
    'health_camp.jpg', 
    'environment_drive.jpg', 
    'skills_program.jpg'
];

foreach ($sample_image_names as $image_name) {
    $image_path = $sample_images_dir . '/' . $image_name;
    if (!file_exists($image_path)) {
        imagejpeg($placeholder_image, $image_path);
    }
}

foreach ($sample_events as $key => $event_data) {
    // Simulate file upload
    $_FILES['media'] = $media_files[$key];
    
    $result = $eventManager->createEvent($event_data);
    echo "Created event: " . $event_data['title'] . " (ID: $result)\n";
}

echo "Sample events added successfully!\n";
?>
