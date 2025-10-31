<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// robot detection
$honeypot = trim($_POST["email_real"]);     

if(!empty($honeypot)) {
    header('Location: index.html');
    exit();
}

// Set maximum file upload size and execution time
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '6M');
ini_set('max_execution_time', 300);

header('Content-Type: application/json');

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {    
    try {
        $position = sanitizeInput($_POST['position'] ?? '');
        $firstName = sanitizeInput($_POST['firstName'] ?? '');
        $lastName = sanitizeInput($_POST['lastName'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $university = sanitizeInput($_POST['university'] ?? '');
        $major = sanitizeInput($_POST['major'] ?? '');
        $graduationYear = sanitizeInput($_POST['graduationYear'] ?? '');
        $experience = sanitizeInput($_POST['experience'] ?? '');
        $motivation = sanitizeInput($_POST['motivation'] ?? '');
        $portfolio = filter_var($_POST['portfolio'] ?? '', FILTER_SANITIZE_URL);
        $availability = sanitizeInput($_POST['availability'] ?? '');
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email) || empty($motivation)) {
            $response['success'] = false;
            $response['message'] = 'Please fill in all required fields (First Name, Last Name, Email, and Motivation).';
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
        
        // Validate and handle resume upload
        $resume_filename = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $resume_filename = handleResumeUpload();
            if ($resume_filename === false) {
                $response['success'] = false;
                $response['message'] = 'Invalid resume file. Please upload a PDF, DOC, or DOCX file under 5MB.';
                echo json_encode($response);
                return;
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Please upload your resume.';
            echo json_encode($response);
            return;
        }
        
        // Create email content
        $email_subject = "New Job Application: $position - $firstName $lastName";
        $email_message = createCareerApplicationEmail(
            $firstName, $lastName, $email, $phone, $university, $major, 
            $graduationYear, $experience, $motivation, $portfolio, 
            $availability, $position, $resume_filename
        );
        
        // Send email
        $to = 'support@smnway.com'; 
        $headers = createEmailHeaders($email, "$firstName $lastName");
        
        if (mail($to, $email_subject, $email_message, $headers)) {            
            $response['success'] = true;
            $response['message'] = 'Thank you for your application! We have received your resume and will review it shortly. We will contact you if your profile matches our requirements.';
        } else {
            $response['success'] = false;
            $response['message'] = 'Failed to submit application. Please try again or contact us directly.';
            
            // If email failed but file was uploaded, clean up the uploaded file
            if ($resume_filename) {
                $uploaded_file = "uploads/resumes/" . $resume_filename;
                if (file_exists($uploaded_file)) {
                    unlink($uploaded_file);
                }
            }
        }
        
        echo json_encode($response);
    } catch (Exception $e) {        
        $response['success'] = false;
        $response['message'] = 'An error occurred: ' . $e->getMessage();
        echo json_encode($response);
    }
} else {
    header('Location: index.html');
    exit();
}

function handleResumeUpload() {
    $target_dir = "uploads/resumes/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            throw new Exception('Failed to create upload directory.');
        }
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        return false;
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
    
    // Additional security: Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (!in_array($mime_type, $allowed_mimes)) {
        return false;
    }
    
    // Generate unique filename to prevent conflicts
    $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original_name);
    $new_filename = $safe_name . '_' . time() . '_' . uniqid() . '.' . $file_extension;
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

function createEmailHeaders($from_email, $from_name) {
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    return $headers;
}

function sanitizeInput($input) {
    if (is_string($input)) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    return '';
}
?>