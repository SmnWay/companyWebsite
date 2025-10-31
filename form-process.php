<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set response header to JSON
    header('Content-Type: application/json');
    
    // Initialize response array
    $response = array();
    
    try {
        // Check if it's a career application or regular contact form
        if (isset($_POST['position']) && !empty($_POST['position'])) {
            // Handle career application
            handleCareerApplication();
        } else {
            // Handle regular contact form
            handleContactForm();
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'An error occurred: ' . $e->getMessage();
        echo json_encode($response);
    }
} else {
    // Redirect if not POST request
    header('Location: index.html');
    exit();
}

function handleCareerApplication() {
   
}

function handleContactForm() {
    global $response;
    
    // Handle regular contact form (from index.html)
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = filter_var($_POST['psta_adrss'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = sanitizeInput($_POST['msg_subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $response['success'] = false;
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        return;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['success'] = false;
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        return;
    }
    
    // Create email content
    $email_subject = "Contact Form: $subject";
    $email_message = createContactEmail($name, $email, $subject, $message);
    
    // Send email
    $to = 'support@smnway.com'; // Change this to your contact email
    $headers = createEmailHeaders($email, $name);
    
    if (mail($to, $email_subject, $email_message, $headers)) {
        $response['success'] = true;
        $response['message'] = 'Thank you for your message! We will get back to you soon.';
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to send message. Please try again.';
    }
    
    echo json_encode($response);
}

function handleResumeUpload() {
    $target_dir = "uploads/resumes/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file = $_FILES['resume'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_types = ['pdf', 'doc', 'docx'];
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Generate unique filename
    $new_filename = 'resume_' . time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $new_filename;
    }
    
    return false;
}

function createCareerApplicationEmail($firstName, $lastName, $email, $phone, $university, $major, $graduationYear, $experience, $motivation, $portfolio, $availability, $position, $resume_filename) {
    $message = "New Job Application Received\n\n";
    $message .= "Position: $position\n\n";
    $message .= "APPLICANT INFORMATION:\n";
    $message .= "Name: $firstName $lastName\n";
    $message .= "Email: $email\n";
    $message .= "Phone: " . ($phone ?: 'Not provided') . "\n\n";
    
    $message .= "EDUCATION:\n";
    $message .= "University/School: " . ($university ?: 'Not provided') . "\n";
    $message .= "Major/Field of Study: " . ($major ?: 'Not provided') . "\n";
    $message .= "Expected Graduation: " . ($graduationYear ?: 'Not provided') . "\n\n";
    
    $message .= "EXPERIENCE:\n";
    $message .= ($experience ?: 'Not provided') . "\n\n";
    
    $message .= "MOTIVATION:\n";
    $message .= "$motivation\n\n";
    
    $message .= "PORTFOLIO/GITHUB:\n";
    $message .= ($portfolio ?: 'Not provided') . "\n\n";
    
    $message .= "AVAILABILITY:\n";
    $message .= ($availability ?: 'Not specified') . "\n\n";
    
    if ($resume_filename) {
        $message .= "RESUME:\n";
        $message .= "Resume file uploaded: $resume_filename\n";
        $message .= "File location: uploads/resumes/$resume_filename\n\n";
    }
    
    $message .= "Application submitted on: " . date('Y-m-d H:i:s') . "\n";
    
    return $message;
}

function createContactEmail($name, $email, $subject, $message) {
    $email_message = "New Contact Form Submission\n\n";
    $email_message .= "Name: $name\n";
    $email_message .= "Email: $email\n";
    $email_message .= "Subject: $subject\n\n";
    $email_message .= "Message:\n";
    $email_message .= "$message\n\n";
    $email_message .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
    
    return $email_message;
}

function createEmailHeaders($from_email, $from_name) {
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return $headers;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>