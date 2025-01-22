<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/css/variables.css">
    <style>
        :root {
            --primary-color: #f59824;
        }
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.5rem 1rem;
            margin: 0.2rem 0;
        }
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #e48920;
            border-color: #e48920;
        }
        .event-card {
            transition: transform var(--transition-speed) ease;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-box-shadow);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(245, 152, 36, 0.25);
        }
        .media-preview {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
    <?php echo '<meta name="csrf-token" content="' . generate_csrf_token() . '">'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="text-center mb-4">Admin Panel</h3>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/events/">
                            <i class="fas fa-calendar-alt me-2"></i>Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/donations/">
                            <i class="fas fa-hand-holding-heart me-2"></i>Donations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/team/">
                            <i class="fas fa-users me-2"></i>Team
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/partners/">
                            <i class="fas fa-handshake me-2"></i>Partners
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Edit Event</h2>
                    <div>
                        <div class="btn-group me-2">
                            <a href="/admin/events/Upcoming_events.php" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Upcoming Events
                            </a>
                            <a href="/admin/events/Past_events.php" class="btn btn-secondary">
                                <i class="fas fa-history me-2"></i>Past Events
                            </a>
                        </div>
                        <a href="/admin/events/create.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </a>
                    </div>
                </div>

                <div class="card event-card">
                    <div class="card-body">
                        <form id="editEventForm" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token ?? ''; ?>">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Event Title *</label>
                                    <input type="text" 
                                           name="title" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>" 
                                           required>
                                    <div class="invalid-feedback">Please provide an event title.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="event_date" class="form-label">Event Date *</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="event_date" 
                                           name="event_date" 
                                           value="<?php echo htmlspecialchars($event['event_date'] ?? ''); ?>"
                                           required>
                                    <div class="invalid-feedback">
                                        Please select an event date.
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" 
                                           name="location" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">Select Category (Optional)</option>
                                        <?php 
                                        // Fetch categories
                                        $category_query = "SELECT id, category FROM categories ORDER BY category";
                                        $category_result = $conn->query($category_query);
                                        
                                        // Check if query was successful
                                        if ($category_result) {
                                            while ($category = $category_result->fetch_assoc()): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo ($category['id'] == ($event['category_id'] ?? '') ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($category['category']); ?>
                                                </option>
                                            <?php endwhile; 
                                        } else {
                                            // Log category query error
                                            error_log("Category Query Error: " . $conn->error);
                                        }
                                        ?> 
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" 
                                       name="description" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($event['description'] ?? ''); ?>">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="event_date" class="form-label">Event Date</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="event_date" 
                                           name="event_date" 
                                           value="<?php echo htmlspecialchars($event['event_date'] ?? ''); ?>"
                                           required>
                                    <div class="invalid-feedback">
                                        Please select an event date.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Event Status</label>
                                    <div class="form-control-plaintext">
                                        <?php
                                        $event_date = new DateTime($event['event_date'] ?? 'now');
                                        $current_date = new DateTime();
                                        $status = ($event_date > $current_date) ? 'Upcoming' : 'Past';
                                        $badge_class = ($status === 'Upcoming') ? 'bg-success' : 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                        <small class="text-muted d-block mt-1">
                                            Status is automatically determined based on the event date
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="people_helped" class="form-label">People Helped</label>
                                    <input type="number" 
                                           name="people_helped" 
                                           class="form-control" 
                                           value="<?php echo (int)($event['people_helped'] ?? 0); ?>" 
                                           min="0">
                                </div>

                                <div class="col-md-4">
                                    <label for="volunteers" class="form-label">Volunteers</label>
                                    <input type="number" 
                                           name="volunteers" 
                                           class="form-control" 
                                           value="<?php echo (int)($event['volunteers'] ?? 0); ?>" 
                                           min="0">
                                </div>
                            </div>

                            <!-- Existing Media Section -->
                            <?php if (!empty($existing_media)): ?>
                                <div class="mb-4">
                                    <label class="form-label">Current Media</label>
                                    <div class="media-preview">
                                        <?php foreach ($existing_media as $media): ?>
                                            <div class="existing-media-item">
                                                <?php if ($media['media_type'] === 'image'): ?>
                                                    <img src="/<?php echo htmlspecialchars($media['media_url'] ?? ''); ?>" alt="Event media">
                                                <?php elseif ($media['media_type'] === 'video'): ?>
                                                    <?php
                                                    // Convert YouTube URL to embed format if it's a YouTube video
                                                    $video_url = $media['media_url'] ?? '';
                                                    if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                                        // Extract video ID from various YouTube URL formats
                                                        $video_id = '';
                                                        if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([\w-]{11})/', $video_url, $matches)) {
                                                            $video_id = $matches[1];
                                                        } elseif (preg_match('/(?:https?:\/\/)?(?:www\.)?youtu\.be\/([\w-]{11})/', $video_url, $matches)) {
                                                            $video_id = $matches[1];
                                                        }
                                                        
                                                        if (!empty($video_id)) {
                                                            $video_url = "https://www.youtube.com/embed/{$video_id}";
                                                        }
                                                    }
                                                    ?>
                                                    <div class="ratio ratio-16x9">
                                                        <iframe src="<?php echo htmlspecialchars($video_url); ?>" 
                                                                frameborder="0" 
                                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                                allowfullscreen></iframe>
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" class="delete-media" onclick="toggleMediaDeletion(<?php echo $media['id'] ?? 0; ?>, event)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <input type="checkbox" name="delete_media[]" value="<?php echo $media['id'] ?? 0; ?>" style="display: none;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- New Media Upload -->
                            <div class="mb-3">
                                <label for="media" class="form-label">Add New Media</label>
                                <input type="file" class="form-control" id="media" name="media[]" multiple accept="image/*,video/mp4" onchange="previewImages(this)">
                                <div id="mediaPreview" class="mt-2"></div>
                                <small class="text-muted">Select up to 10 files. Supported formats: Images (JPG, PNG, GIF), Videos (MP4)</small>
                            </div>

                            <!-- YouTube Links -->
                            <div class="mb-3">
                                <label class="form-label">YouTube Video Links</label>
                                <div id="youtubeVideoLinks">
                                    <?php 
                                    $youtube_links = array_filter($existing_media ?? [], function($media) {
                                        return ($media['media_type'] ?? '') === 'video' && 
                                               strpos(($media['media_url'] ?? ''), 'youtube') !== false;
                                    });
                                    if (!empty($youtube_links)) {
                                        foreach ($youtube_links as $link) {
                                            echo '<div class="input-group mb-2">';
                                            echo '<input 
                                                type="url" 
                                                name="youtube_links[]" 
                                                class="form-control youtube-link" 
                                                placeholder="Enter YouTube Video URL"
                                                pattern="https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]{11}|https?:\/\/youtu\.be\/[\w-]{11}"
                                                title="Please enter a valid YouTube URL"
                                                value="' . htmlspecialchars($link['media_url']) . '"
                                            >';
                                            echo '<button type="button" class="btn btn-danger remove-youtube-link">
                                                    <i class="fas fa-trash"></i>
                                                  </button>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                                <button type="button" id="addYouTubeLink" class="btn btn-secondary mt-2">
                                    <i class="fas fa-plus me-2"></i>Add YouTube Link
                                </button>
                            </div>

                            <div class="text-end mt-4">
                                <a href="index.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Validate YouTube URL
        function validateYouTubeUrl(url) {
            if (!url) return false;
            
            const patterns = [
                /^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/i
            ];
            
            return patterns.some(pattern => pattern.test(url.trim()));
        }

        // Form validation
        function validateForm() {
            let isValid = true;
            const youtubeInputs = document.querySelectorAll('.youtube-url');
            
            youtubeInputs.forEach(input => {
                const url = input.value.trim();
                if (url && !validateYouTubeUrl(url)) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    
                    // Add error message if not exists
                    let errorDiv = input.nextElementSibling;
                    if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.textContent = 'Please enter a valid YouTube URL';
                        input.parentNode.insertBefore(errorDiv, input.nextSibling);
                    }
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        }

        // Add new YouTube link input
        function addYouTubeLink() {
            const container = document.getElementById('youtubeVideoLinks');
            if (!container) return;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'input-group mb-2';
            
            const input = document.createElement('input');
            input.type = 'url';
            input.name = 'youtube_links[]';
            input.className = 'form-control youtube-url';
            input.placeholder = 'Enter YouTube URL';
            
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-danger';
            button.innerHTML = '<i class="fas fa-trash"></i>';
            button.onclick = () => wrapper.remove();
            
            wrapper.appendChild(input);
            wrapper.appendChild(button);
            container.appendChild(wrapper);
        }

        // Create YouTube preview
        function createYouTubePreview(input) {
            const videoId = extractYouTubeVideoId(input.value);
            if (!videoId) return;
            
            const previewContainer = document.createElement('div');
            previewContainer.className = 'mt-2 youtube-preview';
            
            const iframe = document.createElement('iframe');
            iframe.width = '280';
            iframe.height = '157';
            iframe.src = `https://www.youtube-nocookie.com/embed/${videoId}`;
            iframe.allowFullscreen = true;
            
            previewContainer.appendChild(iframe);
            input.parentNode.appendChild(previewContainer);
        }

        // Extract YouTube video ID
        function extractYouTubeVideoId(url) {
            if (!url) return null;
            
            const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i);
            return match ? match[1] : null;
        }

        // Handle media preview
        function previewImages(input) {
            const preview = document.getElementById('mediaPreview');
            if (!preview || !input.files) return;
            
            preview.innerHTML = '';
            
            Array.from(input.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }

        // Media deletion handling
        function toggleMediaDeletion(mediaId) {
            const checkbox = document.querySelector(`input[name="delete_media[]"][value="${mediaId}"]`);
            const button = document.querySelector(`.delete-media[data-media-id="${mediaId}"]`);
            
            if (!checkbox || !button) return;
            
            checkbox.checked = !checkbox.checked;
            button.classList.toggle('active', checkbox.checked);
        }

        // Initialize existing YouTube links
        function initializeYouTubeLinks() {
            const container = document.getElementById('youtubeVideoLinks');
            if (!container) return;
            
            const links = container.querySelectorAll('.youtube-url');
            links.forEach(link => {
                if (validateYouTubeUrl(link.value)) {
                    createYouTubePreview(link);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editEventForm');
            const youtubeContainer = document.getElementById('youtubeVideoLinks');
            const addYoutubeLinkBtn = document.getElementById('addYouTubeLink');

            // Form validation
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!validateForm()) {
                        event.preventDefault();
                    }
                });
            }

            // Add YouTube link functionality
            if (addYoutubeLinkBtn) {
                addYoutubeLinkBtn.addEventListener('click', function() {
                    addYouTubeLink();
                });
            }

            // Initialize existing YouTube links
            initializeYouTubeLinks();
        });
    </script>
</body>
</html>